<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\StockImportHistory;
use App\Services\OrderInventoryService;
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
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $this->inventory->syncDueOrders();

        $sortMap = [
            'code' => 'products.code',
            'name' => 'name',
            'quantity' => 'total_stock_quantity',
            'updated' => 'updated_at',
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

        $products = Product::query()
            ->selectRaw('MIN(id) as id')
            ->selectRaw('code')
            ->selectRaw('MAX(name) as name')
            ->selectRaw('SUM(stock_quantity) as total_stock_quantity')
            ->selectRaw('COUNT(*) as size_count')
            ->selectRaw('MAX(image_path) as image_path')
            ->selectRaw('MAX(fabric) as fabric')
            ->selectRaw('MAX(category) as category')
            ->selectRaw('MAX(rental_price) as rental_price')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('products.code', 'like', "%{$query}%")
                        ->orWhere('products.name', 'like', "%{$query}%");
                });
            })
            ->groupBy('code')
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('id', 'desc')
            ->get();

        $products->each(function ($product) {
            $expectedReceipts = ProductExpectedReceipt::query()
                ->join('products', 'products.id', '=', 'product_expected_receipts.product_id')
                ->where('products.code', $product->code)
                ->whereNull('product_expected_receipts.received_at')
                ->where('product_expected_receipts.expected_receive_quantity', '>', 0)
                ->get([
                    'product_expected_receipts.expected_receive_date',
                    'product_expected_receipts.expected_receive_quantity',
                ]);

            $product->expected_receive_date = $expectedReceipts->min('expected_receive_date');
            $product->total_expected_receive_quantity = $expectedReceipts->sum('expected_receive_quantity');
        });

        $uncategorizedLabel = 'Chưa phân loại';

        // Nhóm các mã hàng theo danh mục; danh mục trống gộp vào "Chưa phân loại".
        $grouped = $products->groupBy(fn ($product) => filled($product->category)
            ? trim($product->category)
            : $uncategorizedLabel);

        // Sắp xếp danh mục theo bảng chữ cái, "Chưa phân loại" xuống cuối.
        $productGroups = $grouped
            ->sortKeys()
            ->sortBy(fn ($items, $category) => $category === $uncategorizedLabel ? 1 : 0);

        return view('products.index', [
            'productGroups' => $productGroups,
            'totalProducts' => $products->count(),
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
            $source = Product::with('expectedReceipts')->find($request->query('copy_from'));

            if ($source) {
                $product->fill([
                    'code' => $source->code,
                    'name' => $source->name,
                    'fabric' => $source->fabric,
                    'category' => $source->category,
                    'rental_price' => $source->rental_price,
                    'image_path' => $source->image_path,
                ]);
                $sourceProductId = $source->id;
            }
        }

        return view('products.form', [
            'product' => $product,
            'sourceProductId' => $sourceProductId,
            'sourceExpectedReceipts' => isset($source)
                ? $source->expectedReceipts->whereNull('received_at')->values()
                : collect(),
            'mode' => 'create',
            'existingCodes' => $this->existingCodeSuggestions(),
        ]);
    }

    /**
     * Danh sách mã hàng đang có kèm các size, dùng để gợi ý mã khi tạo sản phẩm
     * mới và cảnh báo sớm trùng mã + size ngay trên form.
     */
    private function existingCodeSuggestions(): array
    {
        return Product::query()
            ->orderBy('code')
            ->get(['code', 'size'])
            ->groupBy('code')
            ->map(fn ($rows, $code) => [
                'code' => (string) $code,
                'sizes' => $rows->pluck('size')->map(fn ($size) => (string) $size)->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function show(Request $request, Product $product): View
    {
        $this->inventory->syncDueOrders();
        $product->refresh();

        $sortMap = [
            'size' => 'size',
            'quantity' => 'stock_quantity',
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
            ->with('expectedReceipts')
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
        $this->inventory->syncDueOrders();
        $product->refresh();

        $orders = Order::query()
            ->select('orders.*')
            ->selectRaw('order_items.quantity as tracked_quantity')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->latest('orders.pickup_date')
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

    /**
     * Theo dõi tồn kho khả dụng theo từng ngày cho TẤT CẢ sản phẩm (ma trận sản phẩm × ngày).
     */
    public function inventoryCalendar(Request $request): View
    {
        $this->inventory->syncDueOrders();

        [$dateFrom, $dateTo] = $this->inventoryCalendarRange($request);
        $query = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderBy('code')
            ->orderBy('size')
            ->get(['id', 'code', 'name', 'size', 'stock_quantity']);

        $availability = $this->inventory->dailyAvailability(
            $products->pluck('id')->all(),
            $dateFrom,
            $dateTo
        );

        $dates = [];
        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $dates[] = $date->copy();
        }

        return view('products.daily-stock', [
            'products' => $products,
            'availability' => $availability,
            'dates' => $dates,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'query' => $query,
        ]);
    }

    private function inventoryCalendarRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->startOfDay()
            : $from->copy()->addDays(13);

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        // Giới hạn 60 ngày để bảng không quá lớn.
        if ($from->diffInDays($to) > 60) {
            $to = $from->copy()->addDays(60);
        }

        return [$from, $to];
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
                    'category' => $data['category'] ?? null,
                    'size' => $variant['size'],
                    'rental_price' => $data['rental_price'] ?? 0,
                ]);

                $this->replacePendingExpectedReceipts($product, $variant['expected_receipts']);

                if ($product->stock_quantity > 0) {
                    $this->recordStockImport($product, $product->stock_quantity, 0, $product->stock_quantity);
                }
            }
        });

        return redirect()
            ->back()
            ->with('status', 'Đã thêm sản phẩm vào kho.');
    }

    public function edit(Product $product): View
    {
        $product->load('expectedReceipts');

        return view('products.form', [
            'product' => $product,
            'sourceExpectedReceipts' => collect(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $expectedReceipts = $data['expected_receipts'];
        unset($data['expected_receipts']);
        unset($data['image']);

        // Bật: áp dụng thông tin cấp mã hàng cho TẤT CẢ size. Tắt: chỉ sửa riêng size này.
        $applyToAll = $request->boolean('apply_to_all_sizes');

        $oldCode = $product->code;
        $newCode = $data['code'];

        // Đổi mã chỉ lan sang cả nhóm khi đang đồng bộ toàn mã.
        if ($applyToAll && $newCode !== $oldCode) {
            $this->ensureGroupCodeChangeIsValid($product, $newCode, $data['size']);
        }

        // Gom ảnh cũ để dọn: toàn mã gom cả nhóm; riêng size chỉ ảnh của size này.
        $oldImages = collect();

        if ($request->hasFile('image')) {
            $oldImages = $applyToAll
                ? Product::where('code', $oldCode)->pluck('image_path')->filter()->unique()
                : collect([$product->image_path])->filter()->unique();

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        // Thông tin cấp "mã hàng" (đồng bộ khi bật "áp dụng cho cả mã").
        // Riêng size và số lượng tồn luôn là của từng size nên KHÔNG đồng bộ.
        $sharedData = [
            'name' => $data['name'],
            'fabric' => $data['fabric'],
            'category' => $data['category'] ?? null,
            'rental_price' => $data['rental_price'] ?? 0,
        ];

        if (array_key_exists('image_path', $data)) {
            $sharedData['image_path'] = $data['image_path'];
        }

        DB::transaction(function () use ($data, $sharedData, $applyToAll, $expectedReceipts, $product, $oldCode, $newCode) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $previousQuantity = $lockedProduct->stock_quantity;

            $lockedProduct->update($data);
            $this->replacePendingExpectedReceipts($lockedProduct, $expectedReceipts);

            if ($applyToAll) {
                if ($newCode !== $oldCode) {
                    Product::where('code', $oldCode)
                        ->whereKeyNot($lockedProduct->id)
                        ->lockForUpdate()
                        ->update(['code' => $newCode]);
                }

                // Đồng bộ toàn bộ thông tin cấp mã hàng cho mọi size cùng mã.
                Product::where('code', $newCode)->update($sharedData);
            }

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

        // Dọn ảnh cũ không còn ai dùng sau khi đổi ảnh.
        $newImage = $data['image_path'] ?? null;

        foreach ($oldImages as $oldImage) {
            if ($oldImage !== $newImage) {
                $this->deleteProductImageIfUnused($oldImage);
            }
        }

        return redirect()
            ->back()
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
            // Xóa thất bại: sản phẩm vẫn còn nên ở lại trang hiện tại.
            return redirect()
                ->back()
                ->with('error', 'Không thể xóa sản phẩm đang được dùng trong đơn hàng.');
        }

        $status = $deleteScope === 'code'
            ? 'Đã xóa mã hàng và toàn bộ size khỏi kho.'
            : 'Đã xóa sản phẩm khỏi kho.';

        return $this->redirectAfterDestroy($product)->with('status', $status);
    }

    /**
     * Sau khi xóa: ở lại trang trước đó; nếu trang đó là chính sản phẩm vừa xóa
     * (chi tiết/sửa/theo dõi) thì quay về danh sách để tránh 404.
     */
    private function redirectAfterDestroy(Product $product): RedirectResponse
    {
        $previous = url()->previous();
        $previousPath = parse_url($previous, PHP_URL_PATH) ?: '';
        $ownPaths = [
            parse_url(route('products.show', $product), PHP_URL_PATH),
            parse_url(route('products.edit', $product), PHP_URL_PATH),
            parse_url(route('products.track', $product), PHP_URL_PATH),
        ];

        if ($previous === '' || in_array($previousPath, $ownPaths, true)) {
            return redirect()->route('products.index');
        }

        return redirect()->to($previous);
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
            'expected_receipts' => ['nullable', 'array'],
            'expected_receipts.*.expected_receive_date' => ['nullable', 'date'],
            'expected_receipts.*.expected_receive_quantity' => ['nullable', 'integer', 'min:0'],
            'fabric' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:50'],
            'rental_price' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.required' => 'Vui lòng nhập mã sản phẩm.',
            'code.unique' => 'Cặp mã sản phẩm và size đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'image.image' => 'Hình ảnh phải là tệp ảnh hợp lệ.',
            'image.max' => 'Hình ảnh không được vượt quá 2MB.',
            'stock_quantity.required' => 'Vui lòng nhập số lượng tồn.',
            'stock_quantity.integer' => 'Số lượng tồn phải là số nguyên.',
            'stock_quantity.min' => 'Số lượng tồn không được âm.',
            'expected_receipts.*.expected_receive_date.date' => 'Ngày dự kiến nhận hàng không hợp lệ.',
            'expected_receipts.*.expected_receive_quantity.integer' => 'Số lượng nhận dự kiến phải là số nguyên.',
            'expected_receipts.*.expected_receive_quantity.min' => 'Số lượng nhận dự kiến không được âm.',
            'fabric.required' => 'Vui lòng nhập chất liệu vải.',
            'size.required' => 'Vui lòng nhập size.',
            'rental_price.integer' => 'Giá thuê phải là số.',
            'rental_price.min' => 'Giá thuê không được âm.',
        ]);

        $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
        $data['rental_price'] = (int) ($data['rental_price'] ?? 0);
        $data['expected_receipts'] = $this->normalizeExpectedReceipts($data['expected_receipts'] ?? [], 'expected_receipts');

        return $data;
    }

    /**
     * Khi đổi mã hàng, toàn bộ size cùng mã sẽ được đổi theo. Hàm này đảm bảo
     * mã mới không đụng size với một mã hàng đang tồn tại (chống trùng/gộp nhầm).
     */
    private function ensureGroupCodeChangeIsValid(Product $product, string $newCode, string $editedSize): void
    {
        $oldCode = $product->code;

        if ($newCode === $oldCode) {
            return;
        }

        $groupIds = Product::where('code', $oldCode)->pluck('id');

        // Các size sẽ thuộc về $newCode sau khi đổi: mọi dòng cùng mã cũ giữ
        // nguyên size, riêng dòng đang sửa dùng size mới được nhập.
        $groupSizes = Product::where('code', $oldCode)
            ->get(['id', 'size'])
            ->map(fn ($row) => $row->id === $product->id ? $editedSize : $row->size)
            ->map(fn ($size) => trim((string) $size));

        // Trùng size ngay trong nhóm sau khi đổi (vd: size mới trùng một size anh em).
        if ($groupSizes->map(fn ($size) => mb_strtolower($size))->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'code' => 'Sau khi đổi mã, có size bị trùng trong cùng mã hàng.',
            ]);
        }

        // Đụng độ với mã hàng đích đang tồn tại (loại trừ chính nhóm đang đổi).
        $conflictSize = Product::where('code', $newCode)
            ->whereNotIn('id', $groupIds)
            ->whereIn('size', $groupSizes->all())
            ->value('size');

        if ($conflictSize !== null) {
            throw ValidationException::withMessages([
                'code' => "Mã {$newCode} đã tồn tại size {$conflictSize}. Vui lòng chọn mã khác.",
            ]);
        }
    }

    private function normalizeExpectedReceipts(array $receipts, string $fieldPrefix): array
    {
        return collect($receipts)
            ->map(function ($receipt, $index) use ($fieldPrefix) {
                $quantity = (int) ($receipt['expected_receive_quantity'] ?? 0);
                $date = $receipt['expected_receive_date'] ?? null;

                if ($quantity <= 0 && empty($date)) {
                    return null;
                }

                if ($quantity <= 0) {
                    return null;
                }

                if (empty($date)) {
                    throw ValidationException::withMessages([
                        "{$fieldPrefix}.{$index}.expected_receive_date" => 'Vui lòng nhập ngày dự kiến nhận hàng.',
                    ]);
                }

                return [
                    'expected_receive_date' => $date,
                    'expected_receive_quantity' => $quantity,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function replacePendingExpectedReceipts(Product $product, array $receipts): void
    {
        $product->expectedReceipts()
            ->whereNull('received_at')
            ->delete();

        if ($receipts === []) {
            return;
        }

        $product->expectedReceipts()->createMany($receipts);
    }

    private function validatedStoreData(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'fabric' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'rental_price' => ['nullable', 'integer', 'min:0'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.expected_receipts' => ['nullable', 'array'],
            'variants.*.expected_receipts.*.expected_receive_date' => ['nullable', 'date'],
            'variants.*.expected_receipts.*.expected_receive_quantity' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.required' => 'Vui lòng nhập mã sản phẩm.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'image.image' => 'Hình ảnh phải là tệp ảnh hợp lệ.',
            'image.max' => 'Hình ảnh không được vượt quá 2MB.',
            'fabric.required' => 'Vui lòng nhập chất liệu vải.',
            'rental_price.integer' => 'Giá thuê phải là số.',
            'rental_price.min' => 'Giá thuê không được âm.',
            'variants.required' => 'Vui lòng thêm ít nhất một size.',
            'variants.min' => 'Vui lòng thêm ít nhất một size.',
            'variants.*.size.required' => 'Vui lòng nhập size.',
            'variants.*.stock_quantity.required' => 'Vui lòng nhập số lượng tồn.',
            'variants.*.stock_quantity.integer' => 'Số lượng tồn phải là số nguyên.',
            'variants.*.stock_quantity.min' => 'Số lượng tồn không được âm.',
            'variants.*.expected_receipts.*.expected_receive_date.date' => 'Ngày dự kiến nhận hàng không hợp lệ.',
            'variants.*.expected_receipts.*.expected_receive_quantity.integer' => 'Số lượng nhận dự kiến phải là số nguyên.',
            'variants.*.expected_receipts.*.expected_receive_quantity.min' => 'Số lượng nhận dự kiến không được âm.',
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
            ->map(function ($variant, $index) {
                return [
                    'size' => trim((string) $variant['size']),
                    'stock_quantity' => (int) ($variant['stock_quantity'] ?? 0),
                    'expected_receipts' => $this->normalizeExpectedReceipts(
                        $variant['expected_receipts'] ?? [],
                        "variants.{$index}.expected_receipts"
                    ),
                ];
            })
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

        $product->orderItems()
            ->with('order')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.*')
            ->orderBy('orders.created_at')
            ->get()
            ->each(function ($item) use (&$events) {
                $order = $item->order;

                if (! $order) {
                    return;
                }

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
                    'quantity' => $item->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $pickupDate,
                    'label' => 'Đã gửi',
                    'order_name' => $order->order_name,
                    'quantity_change' => -1 * $item->quantity,
                    'quantity' => $item->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $returnDate,
                    'label' => 'Thành công',
                    'order_name' => $order->order_name,
                    'quantity_change' => $item->quantity,
                    'quantity' => $item->quantity,
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

        $product->orderItems()
            ->with('order')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.*')
            ->orderBy('orders.created_at')
            ->get()
            ->each(function ($item) use (&$events) {
                $order = $item->order;

                if (! $order) {
                    return;
                }

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
                    'quantity' => $item->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $pickupDate,
                    'label' => 'Đã gửi',
                    'order_name' => $order->order_name,
                    'quantity_change' => -1 * $item->quantity,
                    'quantity' => $item->quantity,
                    'status' => $order->statusLabel(),
                ];

                $events[] = [
                    'date' => $returnDate,
                    'label' => 'Thành công',
                    'order_name' => $order->order_name,
                    'quantity_change' => $item->quantity,
                    'quantity' => $item->quantity,
                    'status' => $order->statusLabel(),
                ];
            });

        return $events;
    }
}
