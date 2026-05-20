<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $sortMap = [
            'closer' => 'orders.closer_name',
            'pickup_date' => 'orders.pickup_date',
            'event_date' => 'orders.event_date',
            'return_date' => 'orders.return_date',
            'order_name' => 'orders.order_name',
            'product_code' => 'products.code',
            'quantity' => 'orders.quantity',
            'status' => 'orders.status',
        ];

        $sort = $request->query('sort', 'pickup_date');
        $direction = $request->query('direction', 'desc');
        $query = trim((string) $request->query('q', ''));

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'pickup_date';
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
            ->paginate(8)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $products = Product::orderBy('code')->orderBy('size')->get();

        return view('orders.form', [
            'order' => new Order(['status' => 'len_don']),
            'orderItems' => collect(),
            'products' => $products,
            'productOptions' => $this->productOptions($products),
            'statuses' => Order::statuses(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $items = $data['items'];
        $orderData = $this->orderData($data, $items);

        $order = DB::transaction(function () use ($data, $items, $orderData) {
            if ($data['status'] === 'da_gui') {
                $this->decreaseStocks($items);
            }

            $order = Order::create($orderData);
            $order->items()->createMany($items);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Đã tạo đơn hàng.');
    }

    public function show(Order $order): View
    {
        $order->load(['product', 'items.product']);

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order): View
    {
        $products = Product::orderBy('code')->orderBy('size')->get();
        $order->load('items.product');

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
        $data = $this->validatedData($request);
        $items = $data['items'];
        $orderData = $this->orderData($data, $items);

        DB::transaction(function () use ($data, $items, $orderData, $order) {
            $lockedOrder = Order::whereKey($order->id)->with('items')->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedOrder->status;
            $newStatus = $data['status'];
            $oldItems = $this->itemsForStock($lockedOrder);

            if ($oldStatus !== 'da_gui' && $newStatus === 'da_gui') {
                $this->decreaseStocks($items);
            }

            if ($oldStatus === 'da_gui' && $newStatus === 'da_gui') {
                $this->syncSentOrderStocks($oldItems, $items);
            }

            if ($oldStatus === 'da_gui' && $newStatus !== 'da_gui') {
                $this->increaseStocks($oldItems);
            }

            $lockedOrder->update($orderData);
            $lockedOrder->items()->delete();
            $lockedOrder->items()->createMany($items);
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã cập nhật đơn hàng.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->with('items')->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === 'da_gui') {
                $this->increaseStocks($this->itemsForStock($lockedOrder));
            }

            $lockedOrder->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã xóa đơn hàng.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'closer_name' => ['required', 'string', 'max:255'],
            'pickup_date' => ['required', 'date'],
            'event_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'return_date' => ['required', 'date', 'after_or_equal:event_date'],
            'order_name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_keys(Order::statuses()))],
        ], [
            'closer_name.required' => 'Vui lòng nhập người chốt.',
            'pickup_date.required' => 'Vui lòng nhập ngày lấy.',
            'event_date.required' => 'Vui lòng nhập ngày diễn.',
            'event_date.after_or_equal' => 'Ngày diễn phải bằng hoặc sau ngày lấy.',
            'return_date.required' => 'Vui lòng nhập ngày trả.',
            'return_date.after_or_equal' => 'Ngày trả phải bằng hoặc sau ngày diễn.',
            'order_name.required' => 'Vui lòng nhập tên đơn.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.min' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn mã hàng và size.',
            'items.*.product_id.distinct' => 'Mỗi sản phẩm/size chỉ nên chọn một lần trong đơn.',
            'items.*.product_id.exists' => 'Mã hàng không tồn tại trong kho.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);
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
            'product_id' => $firstItem['product_id'],
            'quantity' => $firstItem['quantity'],
            'status' => $data['status'],
        ];
    }

    private function decreaseStock(int $productId, int $quantity): void
    {
        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

        if ($product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Số lượng đơn hàng vượt quá tồn kho hiện tại.',
            ]);
        }

        $product->decrement('stock_quantity', $quantity);
    }

    private function increaseStock(int $productId, int $quantity): void
    {
        Product::whereKey($productId)->lockForUpdate()->firstOrFail()
            ->increment('stock_quantity', $quantity);
    }

    private function decreaseStocks(array $items): void
    {
        foreach ($this->stockTotals($items) as $productId => $quantity) {
            $this->decreaseStock((int) $productId, (int) $quantity);
        }
    }

    private function increaseStocks(array $items): void
    {
        foreach ($this->stockTotals($items) as $productId => $quantity) {
            $this->increaseStock((int) $productId, (int) $quantity);
        }
    }

    private function syncSentOrderStocks(array $oldItems, array $newItems): void
    {
        $oldTotals = $this->stockTotals($oldItems);
        $newTotals = $this->stockTotals($newItems);
        $productIds = collect(array_keys($oldTotals))
            ->merge(array_keys($newTotals))
            ->unique();

        foreach ($productIds as $productId) {
            $difference = (int) ($newTotals[$productId] ?? 0) - (int) ($oldTotals[$productId] ?? 0);

            if ($difference > 0) {
                $this->decreaseStock((int) $productId, $difference);
            }

            if ($difference < 0) {
                $this->increaseStock((int) $productId, abs($difference));
            }
        }
    }

    private function stockTotals(array $items): array
    {
        return collect($items)
            ->groupBy('product_id')
            ->map(fn ($productItems) => $productItems->sum('quantity'))
            ->all();
    }

    private function itemsForStock(Order $order): array
    {
        if ($order->items->isNotEmpty()) {
            return $order->items
                ->map(fn (OrderItem $item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])
                ->values()
                ->all();
        }

        return [[
            'product_id' => $order->product_id,
            'quantity' => $order->quantity,
        ]];
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
                        'stock_quantity' => $product->stock_quantity,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
