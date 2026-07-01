<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $this->inventory->syncDueOrders();

        $sortMap = [
            'closer' => 'orders.closer_name',
            'pickup_date' => 'orders.pickup_date',
            'event_date' => 'orders.event_date',
            'return_date' => 'orders.return_date',
            'order_name' => 'orders.order_name',
            'product_code' => 'products.code',
            'quantity' => 'orders.quantity',
            'status' => 'orders.status',
            'updated' => 'orders.updated_at',
        ];

        $sort = $request->query('sort', 'updated');
        $direction = $request->query('direction', 'desc');
        $query = trim((string) $request->query('q', ''));

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'updated';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $orders = Order::query()
            ->select('orders.*')
            ->with(['product', 'items.product'])
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('orders.closer_name', 'like', "%{$query}%")
                        ->orWhere('orders.order_name', 'like', "%{$query}%")
                        ->orWhere('orders.status', 'like', "%{$query}%")
                        ->orWhere('products.code', 'like', "%{$query}%")
                        ->orWhere('products.name', 'like', "%{$query}%")
                        ->orWhereHas('items.product', function ($productSearch) use ($query) {
                            $productSearch->where('code', 'like', "%{$query}%")
                                ->orWhere('name', 'like', "%{$query}%");
                        });
                });
            })
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('orders.id', 'desc')
            ->paginate(8)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'statuses' => Order::statuses(),
            'paymentStatuses' => Order::paymentStatuses(),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $this->inventory->syncDueOrders();

        $data = $request->validate([
            'pickup_date' => ['nullable', 'date'],
            'event_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'exclude_order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $productIds = collect($data['product_ids'] ?? [])
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            $productIds = Product::query()
                ->pluck('id')
                ->map(fn ($productId) => (int) $productId)
                ->values();
        }

        if (empty($data['pickup_date']) || empty($data['return_date'])) {
            $availability = Product::query()
                ->whereIn('id', $productIds->all())
                ->pluck('stock_quantity', 'id')
                ->map(fn ($quantity) => (int) $quantity)
                ->all();

            return response()->json(['availability' => $availability]);
        }

        $availability = $this->inventory->projectedAvailableQuantities(
            $productIds->all(),
            $data['pickup_date'],
            $data['return_date'],
            isset($data['exclude_order_id']) ? (int) $data['exclude_order_id'] : null
        );

        return response()->json(['availability' => $availability]);
    }

    public function create(): View
    {
        $this->inventory->syncDueOrders();

        $products = Product::with('expectedReceipts')->orderBy('code')->orderBy('size')->get();

        return view('orders.form', [
            'order' => new Order(['status' => Order::DEFAULT_STATUS]),
            'orderItems' => collect(),
            'products' => $products,
            'productOptions' => $this->productOptions($products),
            'statuses' => Order::statuses(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->inventory->syncDueOrders();

        $data = $this->validatedData($request);
        $items = $data['items'];
        $orderData = $this->orderData($data, $items);

        $order = DB::transaction(function () use ($items, $orderData) {
            $order = Order::create($orderData);
            $order->items()->createMany($items);
            $order->load('items');

            $this->inventory->applyStatusAdjustment($order);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Đã tạo đơn hàng.');
    }

    public function show(Order $order): View
    {
        $this->inventory->syncDueOrders();

        $order->refresh()->load(['product', 'items.product']);

        return view('orders.show', [
            'order' => $order,
            'statuses' => Order::statuses(),
            'paymentStatuses' => Order::paymentStatuses(),
        ]);
    }

    public function edit(Order $order): View
    {
        $this->inventory->syncDueOrders();

        $products = Product::with('expectedReceipts')->orderBy('code')->orderBy('size')->get();
        $order->refresh()->load('items.product');

        return view('orders.form', [
            'order' => $order,
            'orderItems' => $order->items,
            'products' => $products,
            'productOptions' => $this->productOptions($products),
            'statuses' => Order::statuses(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->inventory->syncDueOrders();

        $data = $this->validatedData($request, $order);
        $items = $data['items'];
        $orderData = $this->orderData($data, $items);

        DB::transaction(function () use ($items, $orderData, $order) {
            $lockedOrder = Order::whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            $this->inventory->resetAdjustment($lockedOrder);

            $lockedOrder->update($orderData);
            $lockedOrder->items()->delete();
            $lockedOrder->items()->createMany($items);
            $lockedOrder->load('items');

            $this->inventory->applyStatusAdjustment($lockedOrder);
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã cập nhật đơn hàng.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $isChecking = $request->input('status') === Order::CHECKED_STATUS;

        $rules = [
            'status' => ['required', Rule::in(array_keys(Order::statuses()))],
        ];

        if ($isChecking) {
            $rules['check_note'] = ['nullable', 'string', 'max:1000'];
            $rules['returned_quantities'] = ['nullable', 'array'];
            $rules['returned_quantities.*'] = ['nullable', 'integer', 'min:0'];
        }

        $data = $request->validate($rules, [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'returned_quantities.*.integer' => 'Số lượng nhận lại phải là số nguyên.',
            'returned_quantities.*.min' => 'Số lượng nhận lại không được nhỏ hơn 0.',
        ]);

        DB::transaction(function () use ($data, $order, $isChecking) {
            $lockedOrder = Order::whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($isChecking) {
                $returned = $data['returned_quantities'] ?? [];

                foreach ($lockedOrder->items as $item) {
                    // Mặc định nhận lại đủ; giới hạn trong [0, số lượng đơn].
                    $quantity = array_key_exists($item->id, $returned) && $returned[$item->id] !== null
                        ? (int) $returned[$item->id]
                        : $item->quantity;

                    $item->forceFill([
                        'returned_quantity' => max(0, min($quantity, $item->quantity)),
                    ])->save();
                }

                $lockedOrder->forceFill(['check_note' => $data['check_note'] ?? null])->save();
                $lockedOrder->load('items');
            }

            $lockedOrder->update(['status' => $data['status']]);

            $this->inventory->applyStatusAdjustment($lockedOrder);
        });

        return back()->with('status', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function updatePaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(Order::paymentStatuses()))],
        ], [
            'payment_status.required' => 'Vui lòng chọn trạng thái thanh toán.',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ.',
        ]);

        $order->update(['payment_status' => $data['payment_status']]);

        return back()->with('status', 'Đã cập nhật trạng thái thanh toán.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->inventory->syncDueOrders();

        DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            $this->inventory->resetAdjustment($lockedOrder);
            $lockedOrder->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã xóa đơn hàng.');
    }

    private function validatedData(Request $request, ?Order $order = null): array
    {
        $data = $request->validate([
            'closer_name' => ['required', 'string', 'max:255'],
            'pickup_date' => ['required', 'date'],
            'event_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'return_date' => ['required', 'date', 'after_or_equal:event_date'],
            'order_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Order::statuses()))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'closer_name.required' => 'Vui lòng nhập người chốt.',
            'pickup_date.required' => 'Vui lòng nhập ngày lấy.',
            'event_date.required' => 'Vui lòng nhập ngày diễn.',
            'event_date.after_or_equal' => 'Ngày diễn phải bằng hoặc sau ngày lấy.',
            'return_date.required' => 'Vui lòng nhập ngày trả.',
            'return_date.after_or_equal' => 'Ngày trả phải bằng hoặc sau ngày diễn.',
            'order_name.required' => 'Vui lòng nhập tên đơn.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.min' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn mã hàng và size.',
            'items.*.product_id.distinct' => 'Mỗi sản phẩm/size chỉ nên chọn một lần trong đơn.',
            'items.*.product_id.exists' => 'Mã hàng không tồn tại trong kho.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ]);

        $this->inventory->assertItemsAvailable(
            $data['items'],
            $data['pickup_date'],
            $data['return_date'],
            $order?->id
        );

        return $data;
    }

    private function orderData(array $data, array $items): array
    {
        $firstItem = $items[0];

        return [
            'closer_name' => $data['closer_name'],
            'pickup_date' => $data['pickup_date'],
            'event_date' => $data['event_date'],
            'return_date' => $data['return_date'],
            'order_name' => $data['order_name'],
            'status' => $data['status'],
            'product_id' => $firstItem['product_id'],
            'quantity' => $firstItem['quantity'],
        ];
    }

    private function productOptions($products): array
    {
        return $products
            ->groupBy('code')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'code' => $first->code,
                    'name' => $first->name,
                    'items' => $items->map(fn (Product $product) => [
                    'id' => $product->id,
                    'size' => $product->size,
                    'name' => $product->name,
                        'fabric' => $product->fabric,
                        'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                        'stock_quantity' => $product->stock_quantity,
                        'expected_receipts' => $product->expectedReceipts
                            ->whereNull('received_at')
                            ->map(fn ($receipt) => [
                                'expected_receive_date' => $receipt->expected_receive_date?->format('Y-m-d'),
                                'expected_receive_quantity' => $receipt->expected_receive_quantity,
                            ])
                            ->values()
                            ->all(),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
