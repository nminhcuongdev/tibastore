<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockImportHistory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $sortMap = [
            'code' => 'code',
            'name' => 'name',
            'quantity' => 'stock_quantity',
        ];

        $sort = $request->query('sort', 'code');
        $direction = $request->query('direction', 'asc');
        $query = trim((string) $request->query('q', ''));

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'code';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $products = Product::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderBy($sortMap[$sort], $direction)
            ->paginate(8)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(),
            'mode' => 'create',
        ]);
    }

    public function show(Product $product): View
    {
        $orders = $product->orders()
            ->latest('pickup_date')
            ->paginate(10);

        $timeline = $this->buildOrderTimeline($product);

        return view('products.show', [
            'product' => $product,
            'orders' => $orders,
            'timeline' => $timeline,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($data) {
            $product = Product::create($data);

            if ($product->stock_quantity > 0) {
                $this->recordStockImport($product, $product->stock_quantity, 0, $product->stock_quantity);
            }
        });

        return redirect()
            ->route('products.index')
            ->with('status', 'Đã thêm sản phẩm vào kho.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        unset($data['image']);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($data, $product) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $previousQuantity = $lockedProduct->stock_quantity;

            $lockedProduct->update($data);
            $newQuantity = (int) $lockedProduct->stock_quantity;

            if ($newQuantity > $previousQuantity) {
                $this->recordStockImport(
                    $lockedProduct,
                    $newQuantity - $previousQuantity,
                    $previousQuantity,
                    $newQuantity
                );
            }
        });

        return redirect()
            ->route('products.index')
            ->with('status', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $imagePath = $product->image_path;
            $product->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        } catch (QueryException $exception) {
            return redirect()
                ->route('products.index')
                ->with('status', 'Không thể xóa sản phẩm đang được dùng trong đơn hàng.');
        }

        return redirect()
            ->route('products.index')
            ->with('status', 'Đã xóa sản phẩm khỏi kho.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->where(fn ($query) => $query->where('size', $request->input('size')))
                    ->ignore($productId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'fabric' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:50'],
            'import_price' => ['required', 'numeric', 'min:0'],
        ], [
            'code.required' => 'Vui lòng nhập mã sản phẩm.',
            'code.unique' => 'Cặp mã sản phẩm và size đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'image.image' => 'Hình ảnh phải là tệp ảnh hợp lệ.',
            'image.max' => 'Hình ảnh không được vượt quá 2MB.',
            'stock_quantity.required' => 'Vui lòng nhập số lượng tồn.',
            'stock_quantity.integer' => 'Số lượng tồn phải là số nguyên.',
            'stock_quantity.min' => 'Số lượng tồn không được âm.',
            'fabric.required' => 'Vui lòng nhập chất liệu vải.',
            'size.required' => 'Vui lòng nhập size.',
            'import_price.required' => 'Vui lòng nhập giá nhập.',
            'import_price.numeric' => 'Giá nhập phải là số.',
            'import_price.min' => 'Giá nhập không được âm.',
        ]);
    }

    private function recordStockImport(Product $product, int $quantity, int $previousQuantity, int $newQuantity): void
    {
        StockImportHistory::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
        ]);
    }

    private function buildOrderTimeline(Product $product): array
    {
        $events = [];

        $product->orders()
            ->orderBy('created_at')
            ->get()
            ->each(function ($order) use (&$events) {
                $createdDate = $order->created_at
                    ? Carbon::parse($order->created_at)->toDateString()
                    : now()->toDateString();
                $pickupDate = Carbon::parse($order->pickup_date)->toDateString();
                $returnDate = Carbon::parse($order->return_date)->toDateString();

                $events[] = [
                    'date' => $createdDate,
                    'label' => 'Lên đơn',
                    'order_name' => $order->order_name,
                    'quantity_change' => 0,
                    'quantity' => $order->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $pickupDate,
                    'label' => 'Đã gửi',
                    'order_name' => $order->order_name,
                    'quantity_change' => -1 * $order->quantity,
                    'quantity' => $order->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $returnDate,
                    'label' => 'Thành công',
                    'order_name' => $order->order_name,
                    'quantity_change' => $order->quantity,
                    'quantity' => $order->quantity,
                    'status' => $order->statusLabel(),
                ];
            });

        usort($events, fn ($first, $second) => strcmp($first['date'], $second['date']));

        $runningChange = 0;
        $points = [];

        foreach ($events as $event) {
            $runningChange += $event['quantity_change'];
            $points[] = array_merge($event, [
                'running_change' => $runningChange,
                'estimated_quantity' => $product->stock_quantity + $runningChange,
                'display_date' => Carbon::parse($event['date'])->format('d/m/Y'),
            ]);
        }

        return $points;
    }
}
