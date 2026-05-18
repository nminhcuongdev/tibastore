<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
            ->with('product')
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('orders.closer_name', 'like', "%{$query}%")
                        ->orWhere('orders.order_name', 'like', "%{$query}%")
                        ->orWhere('orders.status', 'like', "%{$query}%")
                        ->orWhere('products.code', 'like', "%{$query}%")
                        ->orWhere('products.name', 'like', "%{$query}%");
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
        return view('orders.form', [
            'order' => new Order(['status' => 'len_don']),
            'products' => Product::orderBy('code')->get(),
            'statuses' => Order::statuses(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            if ($data['status'] === 'da_gui') {
                $this->decreaseStock((int) $data['product_id'], (int) $data['quantity']);
            }

            Order::create($data);
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã tạo đơn hàng.');
    }

    public function edit(Order $order): View
    {
        return view('orders.form', [
            'order' => $order,
            'products' => Product::orderBy('code')->get(),
            'statuses' => Order::statuses(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data, $order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedOrder->status;
            $newStatus = $data['status'];

            if ($oldStatus !== 'da_gui' && $newStatus === 'da_gui') {
                $this->decreaseStock((int) $data['product_id'], (int) $data['quantity']);
            }

            if ($oldStatus === 'da_gui' && $newStatus === 'da_gui') {
                $this->syncSentOrderStock($lockedOrder, $data);
            }

            if ($oldStatus === 'da_gui' && $newStatus !== 'da_gui') {
                $this->increaseStock((int) $lockedOrder->product_id, (int) $lockedOrder->quantity);
            }

            $lockedOrder->update($data);
        });

        return redirect()
            ->route('orders.index')
            ->with('status', 'Đã cập nhật đơn hàng.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === 'da_gui') {
                $this->increaseStock((int) $lockedOrder->product_id, (int) $lockedOrder->quantity);
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
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_keys(Order::statuses()))],
        ], [
            'closer_name.required' => 'Vui lòng nhập người chốt.',
            'pickup_date.required' => 'Vui lòng nhập ngày lấy.',
            'event_date.required' => 'Vui lòng nhập ngày diễn.',
            'event_date.after_or_equal' => 'Ngày diễn phải bằng hoặc sau ngày lấy.',
            'return_date.required' => 'Vui lòng nhập ngày trả.',
            'return_date.after_or_equal' => 'Ngày trả phải bằng hoặc sau ngày diễn.',
            'order_name.required' => 'Vui lòng nhập tên đơn.',
            'product_id.required' => 'Vui lòng chọn mã hàng.',
            'product_id.exists' => 'Mã hàng không tồn tại trong kho.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);
    }

    private function decreaseStock(int $productId, int $quantity): void
    {
        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

        if ($product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng đơn hàng vượt quá tồn kho hiện tại.',
            ]);
        }

        $product->decrement('stock_quantity', $quantity);
    }

    private function increaseStock(int $productId, int $quantity): void
    {
        Product::whereKey($productId)->lockForUpdate()->firstOrFail()
            ->increment('stock_quantity', $quantity);
    }

    private function syncSentOrderStock(Order $order, array $data): void
    {
        $oldProductId = (int) $order->product_id;
        $newProductId = (int) $data['product_id'];
        $oldQuantity = (int) $order->quantity;
        $newQuantity = (int) $data['quantity'];

        if ($oldProductId === $newProductId) {
            $difference = $newQuantity - $oldQuantity;

            if ($difference > 0) {
                $this->decreaseStock($newProductId, $difference);
            }

            if ($difference < 0) {
                $this->increaseStock($newProductId, abs($difference));
            }

            return;
        }

        $this->increaseStock($oldProductId, $oldQuantity);
        $this->decreaseStock($newProductId, $newQuantity);
    }
}
