<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $source = trim((string) $request->query('source', ''));
        // Danh sach luon chi hien MOT nguon hang, doi qua lai bang toggle;
        // vao thang khong kem tham so thi mac dinh xem hang dat xuong.
        $supplySource = (string) $request->query('supply_source', WorkshopOrder::DEFAULT_SUPPLY_SOURCE);

        if (! array_key_exists($supplySource, WorkshopOrder::supplySources())) {
            $supplySource = WorkshopOrder::DEFAULT_SUPPLY_SOURCE;
        }
        $buyer = trim((string) $request->query('buyer', ''));
        $orderedFrom = $this->filterDate($request->query('ordered_from'));
        $orderedTo = $this->filterDate($request->query('ordered_to'));

        $ordersQuery = WorkshopOrder::query()
            ->with('product')
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
            ->when($source !== '', fn ($builder) => $builder->where('source', 'like', "%{$source}%"))
            ->where('supply_source', $supplySource)
            ->when($buyer !== '', fn ($builder) => $builder->where('buyer', 'like', "%{$buyer}%"))
            ->when($orderedFrom, fn ($builder) => $builder->whereDate('ordered_date', '>=', $orderedFrom))
            ->when($orderedTo, fn ($builder) => $builder->whereDate('ordered_date', '<=', $orderedTo))
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
            'quantityTotal' => (int) ($totals->quantity_total ?? 0),
            'amountTotal' => (int) ($totals->amount_total ?? 0),
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'supplySource' => $supplySource,
            'query' => $query,
            'productCodes' => $this->productCodes(),
            'status' => $status,
            'source' => $source,
            'buyer' => $buyer,
            'orderedFrom' => $orderedFrom,
            'orderedTo' => $orderedTo,
        ]);
    }

    public function create(): View
    {
        return view('workshop_orders.form', [
            'mode' => 'create',
            'workshopOrder' => new WorkshopOrder([
                'status' => WorkshopOrder::DEFAULT_STATUS,
                'supply_source' => WorkshopOrder::DEFAULT_SUPPLY_SOURCE,
                'quantity' => 1,
                'unit_price' => 0,
                'ordered_date' => now('Asia/Ho_Chi_Minh')->toDateString(),
            ]),
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $item = WorkshopOrder::create($this->validatedData($request));
        if ($request->wantsJson()) {
            $item->load('product');
            return response()->json([
                'message' => 'Đã thêm dòng đặt hàng.',
                'update_url' => route('workshop-orders.update', $item),
                'delete_url' => route('workshop-orders.destroy', $item),
                'quantity' => $item->quantity, 'total' => $item->total_amount,
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
        $workshopOrder->update($this->validatedData($request, $workshopOrder));
        if ($request->wantsJson()) {
            $workshopOrder->refresh()->load('product');
            return response()->json([
                'message' => 'Đã lưu dòng đặt hàng.',
                'quantity' => $workshopOrder->quantity,
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
    public function updateStatus(Request $request, WorkshopOrder $workshopOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(WorkshopOrder::statuses()))],
        ]);

        $workshopOrder->update($data);

        return back()->with('status', "Đã chuyển {$workshopOrder->product_code} sang \"{$workshopOrder->statusLabel()}\".");
    }

    public function destroy(WorkshopOrder $workshopOrder): RedirectResponse
    {
        $workshopOrder->delete();

        return back()->with('status', 'Đã xoá dòng đặt hàng.');
    }

    private function validatedData(Request $request, ?WorkshopOrder $existing = null): array
    {
        if (! $existing && ! $request->filled('ordered_date')) {
            $request->merge(['ordered_date' => now('Asia/Ho_Chi_Minh')->toDateString()]);
        }

        $data = $request->validate([
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
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('workshop-orders', 'public');
        }

        return $data;
    }

    /**
     * Danh sách mã để chọn: gộp theo mã vì một mã gồm nhiều size.
     */
    private function productCodes(): array
    {
        return Product::query()
            ->active()
            ->select('code', 'name', 'image_path', 'category')
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
