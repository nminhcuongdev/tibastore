<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\WorkshopOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkshopOrderController extends Controller
{
    private const DEFAULT_SOURCE_COOKIE = 'workshop_default_supply_source';

    public function saveDefaultSupplySource(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supply_source' => ['required', Rule::in(array_keys(WorkshopOrder::supplySources()))],
        ]);

        return redirect()->route('workshop-orders.index')
            ->withCookie(cookie()->forever(self::DEFAULT_SOURCE_COOKIE, $data['supply_source']));
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $focusedOrder = $request->integer('focus') > 0
            ? WorkshopOrder::find($request->integer('focus')) : null;
        $duplicateRows = app(\App\Services\WorkshopDuplicateService::class)->duplicates();
        $showDuplicates = $focusedOrder && $request->boolean('duplicates');
        $focusedIds = $focusedOrder ? [$focusedOrder->id] : [];
        if ($showDuplicates) $focusedIds = array_merge($focusedIds, $duplicateRows[$focusedOrder->id] ?? []);
        $status = (string) $request->query('status', '');
        $stockReceipt = (string) $request->query('stock_receipt', '');
        $source = trim((string) $request->query('source', ''));
        $defaultSupplySource = $request->cookie(self::DEFAULT_SOURCE_COOKIE);
        if (! array_key_exists($defaultSupplySource ?? '', WorkshopOrder::supplySources())) {
            $defaultSupplySource = WorkshopOrder::DEFAULT_SUPPLY_SOURCE;
        }
        $supplySource = (string) $request->query('supply_source', $defaultSupplySource);
        if ($focusedOrder) {
            $supplySource = $focusedOrder->supply_source;
        }

        if (! array_key_exists($supplySource, WorkshopOrder::supplySources())) {
            $supplySource = WorkshopOrder::DEFAULT_SUPPLY_SOURCE;
        }
        $buyer = trim((string) $request->query('buyer', ''));
        $orderedFrom = $this->filterDate($request->query('ordered_from'));
        $orderedTo = $this->filterDate($request->query('ordered_to'));
        $sort = in_array($request->query('sort'), ['ordered_date', 'returned_date'], true)
            ? $request->query('sort') : 'ordered_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $ordersQuery = WorkshopOrder::query()
            ->with('product')
            ->when($focusedOrder, fn ($builder) => $builder->whereKey($focusedIds))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($sub) use ($query) {
                    $sub->where('product_code', 'like', "%{$query}%")
                        ->orWhere('order_name', 'like', "%{$query}%")
                        ->orWhere('size_note', 'like', "%{$query}%")
                        ->orWhere('buyer_note', 'like', "%{$query}%");
                });
            })
            ->when(array_key_exists($status, WorkshopOrder::statuses()),
                fn ($builder) => $builder->where('status', $status))
            ->when($stockReceipt === 'pending', fn ($builder) => $builder->whereNull('stocked_at'))
            ->when($stockReceipt === 'received', fn ($builder) => $builder->whereNotNull('stocked_at'))
            ->when(in_array($stockReceipt, ['pushed', 'manual'], true),
                fn ($builder) => $builder->whereNotNull('stocked_at')->where('stock_receipt_method', $stockReceipt))
            ->when($stockReceipt === 'unknown',
                fn ($builder) => $builder->whereNotNull('stocked_at')->whereNull('stock_receipt_method'))
            ->when($source !== '', fn ($builder) => $builder->where('source', 'like', "%{$source}%"))
            ->when(!$showDuplicates, fn ($builder) => $builder->where('supply_source', $supplySource))
            ->when($buyer !== '', fn ($builder) => $builder->where('buyer', 'like', "%{$buyer}%"))
            ->when($orderedFrom, fn ($builder) => $builder->whereDate('ordered_date', '>=', $orderedFrom))
            ->when($orderedTo, fn ($builder) => $builder->whereDate('ordered_date', '<=', $orderedTo))
            ->when(!$request->filled('sort'), fn ($builder) => $builder
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [WorkshopOrder::STATUS_NOT_YET]))
            ->when($sort, fn ($builder) => $builder->orderByRaw($sort.' IS NULL')->orderBy($sort, $direction))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // Tổng của TOÀN BỘ kết quả đang lọc, không phải chỉ trang đang xem.
        $totals = (clone $ordersQuery)
            ->reorder()
            ->setEagerLoads([])
            ->select(DB::raw(
                'COALESCE(SUM(quantity), 0) as quantity_total,'
                . ' COALESCE(SUM(quantity * unit_price), 0) as amount_total'
            ))
            ->first();

        $orders = $ordersQuery->paginate(30)->withQueryString();

        return view('workshop_orders.index', [
            'orders' => $orders,
            'duplicateRows' => $duplicateRows,
            'defaultSupplySource' => $defaultSupplySource,
            'focusedOrder' => $focusedOrder,
            'quantityTotal' => (int) ($totals->quantity_total ?? 0),
            'amountTotal' => (int) ($totals->amount_total ?? 0),
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'supplySource' => $supplySource,
            'query' => $query,
            'productCodes' => $this->productCodes(),
            'status' => $status,
            'stockReceipt' => $stockReceipt,
            'source' => $source,
            'buyer' => $buyer,
            'orderedFrom' => $orderedFrom,
            'orderedTo' => $orderedTo,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $defaults = [];
        if ($request->filled('customer_order_id')) {
            $customerOrder = Order::with('items.product')->findOrFail($request->integer('customer_order_id'));
            $source = (string) $request->query('supply_source');
            abort_unless(array_key_exists($source, WorkshopOrder::supplySources()), 422);
            $code = (string) $request->query('product_code');
            $lines = $customerOrder->items->filter(fn ($line) => $line->product?->code === $code
                && $line->fulfillment_source === $source);
            if ($lines->isEmpty()) {
                return redirect()->route('orders.edit', $customerOrder)
                    ->with('error', 'Lựa chọn mã hoặc nguồn cần od chưa được lưu. Hãy lưu đơn khách rồi mở lại Đến đặt hàng od.');
            }
            $existing = WorkshopOrder::where('customer_order_id', $customerOrder->id)
                ->where('product_code', $code)->where('supply_source', $source)->first();
            if ($existing) {
                return redirect()->to(route('workshop-orders.index', ['focus' => $existing->id]).'#workshop-row-'.$existing->id);
            }
            $defaults = ['customer_order_id' => $customerOrder->id, 'product_code' => $code,
                'supply_source' => $source, 'order_name' => $customerOrder->order_name,
                'quantity' => $lines->sum('quantity'),
                'size_note' => $lines->map(fn ($line) => $line->displaySize().': '.$line->quantity)->implode(', ')];
        }
        return view('workshop_orders.form', [
            'mode' => 'create',
            'workshopOrder' => new WorkshopOrder(array_merge([
                'status' => WorkshopOrder::DEFAULT_STATUS,
                'supply_source' => WorkshopOrder::DEFAULT_SUPPLY_SOURCE,
                'quantity' => 1,
                'unit_price' => 0,
                'ordered_date' => now('Asia/Ho_Chi_Minh')->toDateString(),
            ], $defaults)),
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $item = app(\App\Services\WorkshopOrderUndoService::class)->record($request->user()->id, null,
            fn () => WorkshopOrder::create($this->validatedData($request)));
        if ($request->wantsJson()) {
            $item->load('product');
            return response()->json([
                'message' => 'Đã thêm dòng đặt hàng.',
                'id' => $item->id,
                'order_url' => $item->customer_order_url,
                'product_url' => $item->product ? route('products.show', $item->product) : null,
                'product_code' => $item->product_code,
                'duplicate_rows' => app(\App\Services\WorkshopDuplicateService::class)->duplicates(),
                'update_url' => route('workshop-orders.update', $item),
                'delete_url' => route('workshop-orders.destroy', $item),
                'stock_url' => route('workshop-orders.stock', $item),
                'manual_stock_url' => route('workshop-orders.manual-stock', $item),
                'quantity' => $item->quantity, 'total' => $item->total_amount,
                'size_note' => $item->size_note,
                'product_name' => $item->product?->name ?? ($item->product_code === WorkshopOrder::NO_PRODUCT_CODE ? 'Chưa tạo mã' : 'Mã không còn trong kho'),
                'image' => $item->image_url,
            ], 201);
        }

        return redirect()->route('workshop-orders.index')->with('status', 'Đã thêm dòng đặt hàng.');
    }

    public function edit(WorkshopOrder $workshopOrder): View
    {
        return view('workshop_orders.form', [
            'mode' => 'edit',
            'workshopOrder' => $workshopOrder,
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function update(Request $request, WorkshopOrder $workshopOrder): RedirectResponse|JsonResponse
    {
        app(\App\Services\WorkshopOrderUndoService::class)->record($request->user()->id, $workshopOrder, function ($locked) use ($request) {
            $locked->fill($this->validatedData($request, $locked));
            if ($locked->isDirty()) $locked->save();
            if ($locked->purchase_plan && $locked->size_quantities !== null && !$locked->stocked_at) {
                app(\App\Services\WorkshopReceiptService::class)->syncForecast($locked);
            }
            return $locked;
        });
        app(\App\Services\OrderInventoryService::class)->syncDueOrders();
        if ($request->wantsJson()) {
            $workshopOrder->refresh()->load('product');
            return response()->json([
                'message' => 'Đã lưu dòng đặt hàng.',
                'booking_shortages' => app(\App\Services\OrderInventoryService::class)->bookingShortages($workshopOrder->product_code),
                'id' => $workshopOrder->id,
                'order_url' => $workshopOrder->customer_order_url,
                'product_url' => $workshopOrder->product ? route('products.show', $workshopOrder->product) : null,
                'product_code' => $workshopOrder->product_code,
                'duplicate_rows' => app(\App\Services\WorkshopDuplicateService::class)->duplicates(),
                'quantity' => $workshopOrder->quantity,
                'size_note' => $workshopOrder->size_note,
                'total' => $workshopOrder->total_amount,
                'product_name' => $workshopOrder->product?->name ?? ($workshopOrder->product_code === WorkshopOrder::NO_PRODUCT_CODE ? 'Chưa tạo mã' : 'Mã không còn trong kho'),
                'image' => $workshopOrder->image_url,
            ]);
        }

        return redirect()->route('workshop-orders.index')->with('status', 'Đã cập nhật dòng đặt hàng.');
    }

    /**
     * Đổi nhanh trạng thái ngay trên danh sách, không phải mở form sửa.
     */
    public function updateCustomerOrder(Request $request, WorkshopOrder $workshopOrder): JsonResponse
    {
        app(\App\Services\WorkshopReceiptService::class)->updateCustomerOrder($workshopOrder);
        return response()->json(['message' => 'Đã cập nhật phần chênh lệch vào đơn khách, không cộng trùng.']);
    }

    public function updateStatus(Request $request, WorkshopOrder $workshopOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(WorkshopOrder::statuses()))],
        ]);

        app(\App\Services\WorkshopOrderUndoService::class)->record($request->user()->id, $workshopOrder, function ($locked) use ($data) {
            $locked->fill($data);
            if ($locked->isDirty()) $locked->save();
            return $locked;
        });
        $workshopOrder->refresh();
        app(\App\Services\OrderInventoryService::class)->syncDueOrders();

        return back()->with('status', "Đã chuyển {$workshopOrder->product_code} sang \"{$workshopOrder->statusLabel()}\".");
    }

    public function destroy(Request $request, WorkshopOrder $workshopOrder): RedirectResponse
    {
        app(\App\Services\WorkshopOrderUndoService::class)->record($request->user()->id, $workshopOrder, function ($locked) {
            \App\Models\ProductExpectedReceipt::where('workshop_order_id', $locked->id)->whereNull('received_at')->delete();
            $locked->delete();
            return $locked;
        });

        return back()->with('status', 'Đã xoá dòng đặt hàng.');
    }

    public function undoLastEdit(Request $request): JsonResponse
    {
        app(\App\Services\WorkshopOrderUndoService::class)->undo($request->user()->id);
        return response()->json(['message' => 'Đã hoàn tác thao tác gần nhất của bạn.']);
    }

    public function redoLastEdit(Request $request): JsonResponse
    {
        app(\App\Services\WorkshopOrderUndoService::class)->redo($request->user()->id);
        return response()->json(['message' => 'Đã làm lại thao tác của bạn.']);
    }

    public function pushToStock(Request $request, WorkshopOrder $workshopOrder): RedirectResponse|JsonResponse
    {
        return $this->receiveStock($request, $workshopOrder, false);
    }

    public function markManuallyStocked(Request $request, WorkshopOrder $workshopOrder): RedirectResponse|JsonResponse
    {
        return $this->receiveStock($request, $workshopOrder, true);
    }

    private function receiveStock(Request $request, WorkshopOrder $workshopOrder, bool $manual): RedirectResponse|JsonResponse
    {
        $message = DB::transaction(function () use ($workshopOrder, $request, $manual) {
            $item = WorkshopOrder::whereKey($workshopOrder->id)->lockForUpdate()->firstOrFail();
            if (!$manual && $item->image_path && $item->product_code !== WorkshopOrder::NO_PRODUCT_CODE) {
                foreach (Product::where('code', $item->product_code)->orderBy('id')->lockForUpdate()->get() as $variant) {
                    if ($variant->image_path !== $item->image_path) $variant->update(['image_path' => $item->image_path]);
                }
            }
            if ($item->stocked_at) return !$manual && $item->image_path ? 'Đã cập nhật ảnh vào kho. Số lượng không cộng thêm.' : 'Dòng này đã được ghi nhận nhập kho trước đó, không cộng thêm.';
            $lines = $item->plannedSizeLines();
            if (!$lines || $item->product_code === WorkshopOrder::NO_PRODUCT_CODE) {
                throw \Illuminate\Validation\ValidationException::withMessages(['size_quantities' => 'Hãy chọn mã hàng, nhập size và số lượng từng size rồi lưu dòng trước khi đẩy lên kho.']);
            }
            $quantities = collect($lines)->groupBy('product_id')->map(fn ($rows) => $rows->sum('quantity'))->sortKeys();
            $snapshot = [];
            foreach ($quantities as $productId => $quantity) {
                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                if ($product->code !== $item->product_code || $quantity < 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['size_quantities' => 'Size và số lượng không khớp mã hàng.']);
                }
                $receipts = \App\Models\ProductExpectedReceipt::where('workshop_order_id', $item->id)
                    ->where('product_id', $productId)->lockForUpdate()->get();
                // Phiếu đã nhận theo cơ chế cũ không được cộng lần nữa.
                if (!$manual && !$receipts->contains(fn ($receipt) => $receipt->received_at !== null)) {
                    $previous = (int) $product->stock_quantity;
                    $product->update(['stock_quantity' => $previous + $quantity]);
                    \App\Models\StockImportHistory::create(['product_id' => $productId, 'user_id' => $request->user()->id,
                        'quantity' => $quantity, 'previous_quantity' => $previous, 'new_quantity' => $previous + $quantity]);
                    $snapshot[] = ['product_id' => (int) $productId, 'size' => $product->size, 'quantity' => (int) $quantity];
                }
                foreach ($receipts as $receipt) {
                    if (!$receipt->received_at) $receipt->update(['received_at' => now()]);
                }
            }
            $transferredAt = now();
            \App\Models\WorkshopStockTransfer::create(['workshop_order_id' => $item->id, 'product_code' => $item->product_code,
                'supply_source' => $item->supply_source, 'order_name' => $item->order_name, 'supplier' => $item->source,
                'previous_status' => $item->status, 'lines' => $snapshot, 'user_id' => $request->user()->id,
                'transferred_at' => $transferredAt, 'can_undo' => !empty($snapshot)]);
            $item->update(['stocked_at' => $transferredAt, 'status' => WorkshopOrder::STATUS_ARRIVED,
                'stock_receipt_method' => $manual ? 'manual' : 'pushed']);
            if ($manual) return 'Đã ghi nhận bạn tự nhập tay vào kho. Không cộng thêm tồn kho; đã bỏ phần OD này khỏi hàng dự kiến.';
            return $item->image_path ? 'Đã cộng số lượng từng size và cập nhật ảnh vào kho.' : 'Đã cộng số lượng từng size vào kho.';
        });
        $shortages = app(\App\Services\OrderInventoryService::class)->bookingShortages($workshopOrder->product_code);
        return $request->wantsJson()
            ? response()->json(['message' => $message, 'booking_shortages' => $shortages])
            : back()->with('status', $message)->with('booking_shortages', $shortages);
    }

    public function undoStockTransfer(Request $request, \App\Models\WorkshopStockTransfer $transfer): RedirectResponse|JsonResponse
    {
        $message = DB::transaction(function () use ($request, $transfer) {
            $order = WorkshopOrder::whereKey($transfer->workshop_order_id)->lockForUpdate()->first();
            $entry = \App\Models\WorkshopStockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($entry->undone_at) return 'Lần nhập này đã được hoàn tác, không trừ kho thêm.';
            if (!$entry->can_undo || empty($entry->lines)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['undo' => 'Lần nhập cũ này chưa đủ dữ liệu đối chiếu để hoàn tác tự động.']);
            }
            $quantities = collect($entry->lines)->groupBy('product_id')->map(fn ($lines) => $lines->sum('quantity'))->sortKeys();
            foreach ($quantities as $productId => $quantity) {
                $product = Product::whereKey($productId)->lockForUpdate()->first();
                if (!$product || $product->stock_quantity < $quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['undo' => 'Không thể hoàn tác: số lượng còn trong kho không đủ để trừ lại. Hãy kiểm tra hàng đã xuất hoặc đã điều chỉnh.']);
                }
                $product->update(['stock_quantity' => $product->stock_quantity - $quantity]);
            }
            if ($order) {
                \App\Models\ProductExpectedReceipt::where('workshop_order_id', $entry->workshop_order_id)
                    ->whereIn('product_id', $quantities->keys())->update(['received_at' => null]);
                $order->update(['stocked_at' => null, 'stock_receipt_method' => null, 'status' => $entry->previous_status === WorkshopOrder::STATUS_ARRIVED
                    ? WorkshopOrder::STATUS_ORDERED : $entry->previous_status]);
            }
            $entry->update(['undone_at' => now(), 'undone_by' => $request->user()->id]);
            return 'Đã hoàn tác và đảo lại đúng phần tăng hoặc giảm của từng size.';
        });
        return $request->wantsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }

    public function deleteStockTransfer(Request $request, \App\Models\WorkshopStockTransfer $transfer): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($transfer) {
            $entry = \App\Models\WorkshopStockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if (!$entry->undone_at) {
                throw \Illuminate\Validation\ValidationException::withMessages(['undo' => 'Chỉ được xóa dòng lịch sử đã hoàn tác.']);
            }
            $entry->delete();
        });
        $message = 'Đã xóa dòng lịch sử đã hoàn tác.';
        return $request->wantsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }

    private function validatedData(Request $request, ?WorkshopOrder $existing = null): array
    {
        if (is_string($request->input('size_quantities'))) {
            $decoded = json_decode($request->input('size_quantities'), true);
            if (json_last_error() === JSON_ERROR_NONE) $request->merge(['size_quantities' => $decoded]);
        }
        if (! $existing && ! $request->filled('ordered_date')) {
            $request->merge(['ordered_date' => now('Asia/Ho_Chi_Minh')->toDateString()]);
        }

        $data = $request->validate([
            'customer_order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'size_quantities' => ['nullable', 'array', 'max:100'],
            'size_quantities.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'size_quantities.*.quantity' => ['required', 'integer', 'min:1'],
            'product_code' => ['required', 'string', 'max:50',
                Rule::when($request->input('product_code') !== WorkshopOrder::NO_PRODUCT_CODE && (!$existing || $request->input('product_code') !== $existing->product_code), Rule::exists('products', 'code'))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'supply_source' => ['required', Rule::in(array_keys(WorkshopOrder::supplySources()))],
            'size_note' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
            'order_name' => ['nullable', 'string', 'max:255'],
            'buyer_note' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:255'],
            'ordered_date' => ['nullable', 'date'],
            'returned_date' => ['nullable', 'date', 'after_or_equal:ordered_date'],
            'buyer' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(WorkshopOrder::statuses()))],
        ], [
            'product_code.required' => 'Vui lòng chọn mã hàng.',
            'image.image' => 'Vui lòng dán hoặc chọn một tệp ảnh hợp lệ.',
            'image.max' => 'Ảnh không được vượt quá 5MB.',
            'supply_source.required' => 'Vui lòng chọn nguồn hàng.',
            'supply_source.in' => 'Nguồn hàng không hợp lệ.',
            'quantity.required' => 'Vui lòng nhập số lượng đặt.',
            'quantity.min' => 'Số lượng đặt phải từ 1 trở lên.',
            'unit_price.integer' => 'Đơn giá phải là số.',
            'unit_price.min' => 'Đơn giá không được nhỏ hơn 0.',
            'product_code.exists' => 'Mã hàng này chưa có trong kho.',
            'returned_date.after_or_equal' => 'Ngày trả hàng không được trước ngày đặt hàng.',
        ]);

        unset($data['image']);
        if (!empty($data['size_quantities'])) {
            $products = Product::whereIn('id', collect($data['size_quantities'])->pluck('product_id'))->get()->keyBy('id');
            foreach ($data['size_quantities'] as $line) {
                if ($products[$line['product_id']]->code !== $data['product_code']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['size_quantities' => 'Size phải thuộc đúng mã hàng.']);
                }
            }
            $data['quantity'] = collect($data['size_quantities'])->sum('quantity');
            $data['size_note'] = collect($data['size_quantities'])->map(fn ($line) => $products[$line['product_id']]->size.': '.$line['quantity'])->implode(', ');
        }
        if ($existing?->stocked_at) {
            foreach (['product_code', 'quantity', 'size_quantities', 'size_note'] as $field) {
                if (array_key_exists($field, $data) && $data[$field] != $existing->$field) {
                    throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Dòng đã đẩy lên kho không thể đổi mã, size hoặc số lượng. Hãy tạo dòng nhập bổ sung.']);
                }
            }
        }
        if ($existing?->purchase_plan) {
            foreach (['product_code', 'supply_source'] as $field) {
                $saved = $field === 'returned_date' ? $existing->returned_date?->toDateString() : $existing->$field;
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $saved) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        $field => 'Dòng này được đồng bộ từ đơn khách. Hãy sửa kế hoạch mua trên đơn khách để cập nhật đồng thời Kho và od.',
                    ]);
                }
            }
        }
        // Liên kết chỉ được tạo từ các mã đã chọn cần od trong đơn khách.
        $customerOrderId = $data['customer_order_id'] ?? $existing?->customer_order_id;
        if ($customerOrderId) {
            $customerOrder = Order::with('items.product')->findOrFail($customerOrderId);
            $matches = $customerOrder->items->contains(fn ($line) => $line->product?->code === $data['product_code']
                && $line->fulfillment_source === $data['supply_source']);
            if (! $matches) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'product_code' => 'Mã và nguồn od chưa khớp với đơn khách. Hãy lưu lựa chọn trên đơn khách trước.',
                ]);
            }
            $data['customer_order_id'] = $customerOrderId;
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('workshop-orders', 'public');
        }

        return $data;
    }

    public function createProduct(Request $request): JsonResponse
    {
        $request->merge(['code' => trim((string) $request->input('code'))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::notIn([WorkshopOrder::NO_PRODUCT_CODE]), Rule::unique('products', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'rental_price' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'rental_price_text' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ], ['code.unique' => 'Mã hàng đã tồn tại trong kho.']);
        $data = Product::withRentalPriceText($data);
        unset($data['image']);
        $data['rental_price'] = $data['rental_price'] ?? 0;
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('products', 'public');
        try {
            $product = Product::create($data + ['size' => '', 'stock_quantity' => 0, 'fabric' => '']);
        } catch (\Throwable $exception) {
            if (isset($data['image_path'])) \Illuminate\Support\Facades\Storage::disk('public')->delete($data['image_path']);
            throw $exception;
        }

        return response()->json(['code' => $product->code, 'name' => $product->name,
            'category' => $product->category ?: 'Chưa phân loại', 'sizes' => [['id' => $product->id, 'size' => $product->size]],
            'image' => $product->image_path ? asset('storage/'.$product->image_path) : null], 201);
    }

    public function suggestProductCode(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:50']]);
        $code = trim($data['code'] ?? '');
        $prefix = preg_replace('/[0-9]+$/', '', $code);
        if ($prefix === '' || $code === WorkshopOrder::NO_PRODUCT_CODE) $prefix = 'SP';
        $prefix = mb_substr($prefix, 0, 40);
        $used = Product::pluck('code')->mapWithKeys(fn ($value) => [mb_strtolower(trim($value)) => true])->all();
        $number = 1;
        do {
            $suggestion = $prefix.str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
        } while (isset($used[mb_strtolower($suggestion)]));

        return response()->json(['code' => $suggestion, 'exists' => isset($used[mb_strtolower($code)])]);
    }

    public function relatedOrders(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50'],
            'supply_source' => ['required', Rule::in(array_keys(WorkshopOrder::supplySources()))]]);
        $rows = WorkshopOrder::where('product_code', $data['code'])->where('supply_source', $data['supply_source'])
            ->orderByDesc('id')->limit(20)->get();
        return response()->json($rows->map(fn ($item) => [
            'id' => $item->id, 'order_name' => $item->order_name, 'customer_order_id' => $item->customer_order_id,
            'quantity' => $item->quantity, 'size_note' => $item->size_note, 'supplier' => $item->source,
            'lines' => $item->plannedSizeLines(),
            'expected_date' => $item->returned_date?->toDateString(),
            'ordered_date' => $item->ordered_date?->format('d/m/Y'), 'status' => $item->statusLabel(),
            'stocked' => (bool) $item->stocked_at,
            'url' => route('workshop-orders.index', ['focus' => $item->id, 'supply_source' => $item->supply_source]).'#workshop-row-'.$item->id,
        ]));
    }

    /** Danh sách mã để chọn: gộp theo mã vì một mã gồm nhiều size. */
    private function productCodes(): array
    {
        return Product::query()
            ->active()
            ->select('id', 'code', 'name', 'size', 'image_path', 'category')
            ->orderBy('code')
            ->get()
            ->groupBy('code')
            ->map(function ($group) {
                // Ảnh là thuộc tính của mã, nhưng phòng trường hợp chỉ một size
                // có ảnh thì lấy ảnh đầu tiên tìm được trong cả mã.
                $withImage = $group->first(fn ($product) => filled($product->image_path));

                return [
                    'code' => $group->first()->code,
                    'name' => $group->first()->name,
                    'category' => $group->pluck('category')->first(fn ($category) => filled($category)) ?: 'Chưa phân loại',
                    'sizes' => $group->map(fn ($product) => ['id' => $product->id, 'size' => $product->size])->values()->all(),
                    'image' => $withImage ? asset('storage/' . $withImage->image_path) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function filterDate($value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? Carbon::parse($value)->toDateString()
            : null;
    }
}
