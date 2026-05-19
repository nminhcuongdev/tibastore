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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $sortMap = [
            'code' => 'products.code',
            'name' => 'name',
            'quantity' => 'total_stock_quantity',
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
            ->selectRaw('MIN(id) as id')
            ->selectRaw('code')
            ->selectRaw('MAX(name) as name')
            ->selectRaw('SUM(stock_quantity) as total_stock_quantity')
            ->selectRaw('COUNT(*) as size_count')
            ->selectRaw('MAX(image_path) as image_path')
            ->selectRaw('MAX(fabric) as fabric')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('products.code', 'like', "%{$query}%")
                        ->orWhere('products.name', 'like', "%{$query}%");
                });
            })
            ->groupBy('code')
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

    public function create(Request $request): View
    {
        $product = new Product();
        $sourceProductId = null;

        if ($request->filled('copy_from')) {
            $source = Product::find($request->query('copy_from'));

            if ($source) {
                $product->fill([
                    'code' => $source->code,
                    'name' => $source->name,
                    'fabric' => $source->fabric,
                    'import_price' => $source->import_price,
                    'image_path' => $source->image_path,
                ]);
                $sourceProductId = $source->id;
            }
        }

        return view('products.form', [
            'product' => $product,
            'sourceProductId' => $sourceProductId,
            'mode' => 'create',
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        $sortMap = [
            'size' => 'size',
            'quantity' => 'stock_quantity',
            'price' => 'import_price',
            'updated' => 'updated_at',
        ];

        $sort = $request->query('sort', 'size');
        $direction = $request->query('direction', 'asc');

        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'size';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $variants = Product::query()
            ->where('code', $product->code)
            ->orderBy($sortMap[$sort], $direction)
            ->paginate(8)
            ->withQueryString();

        $totalQuantity = Product::where('code', $product->code)->sum('stock_quantity');

        return view('products.show', [
            'product' => $product,
            'variants' => $variants,
            'totalQuantity' => $totalQuantity,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function track(Request $request, Product $product): View
    {
        $orders = $product->orders()
            ->latest('pickup_date')
            ->paginate(10)
            ->withQueryString();

        [$dateFrom, $dateTo] = $this->forecastRange($request);

        $timeline = $this->buildOrderTimeline($product);
        $dailyForecast = $this->buildDailyForecast($product, $dateFrom, $dateTo);

        return view('products.tracking', [
            'product' => $product,
            'orders' => $orders,
            'timeline' => $timeline,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dailyForecast' => $dailyForecast,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedStoreData($request);
        $variants = $data['variants'];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('source_product_id')) {
            $source = Product::find($request->input('source_product_id'));

            if ($source) {
                $data['image_path'] = $source->image_path;
            }
        }

        DB::transaction(function () use ($data, $variants) {
            foreach ($variants as $variant) {
                $product = Product::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'image_path' => $data['image_path'] ?? null,
                    'stock_quantity' => $variant['stock_quantity'],
                    'fabric' => $data['fabric'],
                    'size' => $variant['size'],
                    'import_price' => $data['import_price'],
                ]);

                if ($product->stock_quantity > 0) {
                    $this->recordStockImport($product, $product->stock_quantity, 0, $product->stock_quantity);
                }
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
                $this->deleteProductImageIfUnused($product->image_path, $product->id);
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

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $deleteScope = $request->input('delete_scope') === 'code' ? 'code' : 'variant';
        $imagePaths = collect();

        try {
            DB::transaction(function () use ($deleteScope, $product, &$imagePaths) {
                if ($deleteScope === 'code') {
                    $variants = Product::where('code', $product->code)
                        ->lockForUpdate()
                        ->get();

                    $imagePaths = $variants
                        ->pluck('image_path')
                        ->filter()
                        ->unique()
                        ->values();

                    foreach ($variants as $variant) {
                        $variant->delete();
                    }

                    return;
                }

                $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $imagePaths = collect([$lockedProduct->image_path])->filter()->values();
                $lockedProduct->delete();
            });

            foreach ($imagePaths as $imagePath) {
                $this->deleteProductImageIfUnused($imagePath);
            }
        } catch (QueryException $exception) {
            return redirect()
                ->route('products.index')
                ->with('status', 'Không thể xóa sản phẩm đang được dùng trong đơn hàng.');
        }

        $status = $deleteScope === 'code'
            ? 'Đã xóa mã hàng và toàn bộ size khỏi kho.'
            : 'Đã xóa sản phẩm khỏi kho.';

        return redirect()
            ->route('products.index')
            ->with('status', $status);
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        $data = $request->validate([
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
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
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

        $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);

        return $data;
    }

    private function validatedStoreData(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'fabric' => ['required', 'string', 'max:255'],
            'import_price' => ['required', 'numeric', 'min:0'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.required' => 'Vui lòng nhập mã sản phẩm.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'image.image' => 'Hình ảnh phải là tệp ảnh hợp lệ.',
            'image.max' => 'Hình ảnh không được vượt quá 2MB.',
            'fabric.required' => 'Vui lòng nhập chất liệu vải.',
            'import_price.required' => 'Vui lòng nhập giá nhập.',
            'import_price.numeric' => 'Giá nhập phải là số.',
            'import_price.min' => 'Giá nhập không được âm.',
            'variants.required' => 'Vui lòng thêm ít nhất một size.',
            'variants.min' => 'Vui lòng thêm ít nhất một size.',
            'variants.*.size.required' => 'Vui lòng nhập size.',
            'variants.*.stock_quantity.required' => 'Vui lòng nhập số lượng tồn.',
            'variants.*.stock_quantity.integer' => 'Số lượng tồn phải là số nguyên.',
            'variants.*.stock_quantity.min' => 'Số lượng tồn không được âm.',
        ]);

        $sizes = collect($data['variants'])
            ->pluck('size')
            ->map(fn ($size) => trim((string) $size));

        if ($sizes->map(fn ($size) => strtolower($size))->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Các dòng size trong cùng một mã sản phẩm không được trùng nhau.',
            ]);
        }

        $existingSize = Product::query()
            ->where('code', $data['code'])
            ->whereIn('size', $sizes->all())
            ->value('size');

        if ($existingSize !== null) {
            throw ValidationException::withMessages([
                'variants' => "Mã sản phẩm {$data['code']} với size {$existingSize} đã tồn tại.",
            ]);
        }

        $data['variants'] = collect($data['variants'])
            ->map(fn ($variant) => [
                'size' => trim((string) $variant['size']),
                'stock_quantity' => (int) ($variant['stock_quantity'] ?? 0),
            ])
            ->values()
            ->all();

        return $data;
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

    private function deleteProductImageIfUnused(string $imagePath, ?int $exceptProductId = null): void
    {
        $query = Product::where('image_path', $imagePath);

        if ($exceptProductId !== null) {
            $query->whereKeyNot($exceptProductId);
        }

        $isUsedByAnotherProduct = $query->exists();

        if (! $isUsedByAnotherProduct) {
            Storage::disk('public')->delete($imagePath);
        }
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

    private function forecastRange(Request $request): array
    {
        $today = now()->startOfDay();
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : $today->copy();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->query('date_to'))->startOfDay()
            : $dateFrom->copy()->addDays(30);

        if ($dateTo->lt($dateFrom)) {
            $dateTo = $dateFrom->copy();
        }

        if ($dateFrom->diffInDays($dateTo) > 180) {
            $dateTo = $dateFrom->copy()->addDays(180);
        }

        return [$dateFrom, $dateTo];
    }

    private function buildDailyForecast(Product $product, Carbon $dateFrom, Carbon $dateTo): array
    {
        $events = $this->buildOrderEvents($product);
        $eventsByDate = collect($events)->groupBy('date');
        $runningChange = collect($events)
            ->filter(fn ($event) => Carbon::parse($event['date'])->lt($dateFrom))
            ->sum('quantity_change');
        $rows = [];
        $totalDecrease = 0;
        $totalIncrease = 0;

        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $dateKey = $date->toDateString();
            $dayEvents = $eventsByDate->get($dateKey, collect())->values();
            $quantityChange = $dayEvents->sum('quantity_change');
            $runningChange += $quantityChange;

            if ($quantityChange < 0) {
                $totalDecrease += abs($quantityChange);
            }

            if ($quantityChange > 0) {
                $totalIncrease += $quantityChange;
            }

            $rows[] = [
                'date' => $dateKey,
                'display_date' => $date->format('d/m/Y'),
                'quantity_change' => $quantityChange,
                'estimated_quantity' => $product->stock_quantity + $runningChange,
                'events' => $dayEvents->all(),
            ];
        }

        return [
            'rows' => $rows,
            'total_decrease' => $totalDecrease,
            'total_increase' => $totalIncrease,
            'net_change' => $totalIncrease - $totalDecrease,
        ];
    }

    private function buildOrderEvents(Product $product): array
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

        return $events;
    }
}
