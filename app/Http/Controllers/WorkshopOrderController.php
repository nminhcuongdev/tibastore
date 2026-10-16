<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WorkshopOrder;
use Illuminate\Http\RedirectResponse;
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
            ]),
            'statuses' => WorkshopOrder::statuses(),
            'supplySources' => WorkshopOrder::supplySources(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        WorkshopOrder::create($this->validatedData($request));

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

    public function update(Request $request, WorkshopOrder $workshopOrder): RedirectResponse
    {
        $workshopOrder->update($this->validatedData($request));

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

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'product_code' => ['required', 'string', 'max:50', Rule::exists('products', 'code')],
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
            'supply_source.required' => 'Vui lòng chọn nguồn hàng.',
            'supply_source.in' => 'Nguồn hàng không hợp lệ.',
            'quantity.required' => 'Vui lòng nhập số lượng đặt.',
            'quantity.min' => 'Số lượng đặt phải từ 1 trở lên.',
            'unit_price.integer' => 'Đơn giá phải là số.',
            'unit_price.min' => 'Đơn giá không được nhỏ hơn 0.',
            'product_code.exists' => 'Mã hàng này chưa có trong kho.',
            'returned_date.after_or_equal' => 'Ngày trả hàng không được trước ngày đặt hàng.',
        ]);
    }

    /**
     * Danh sách mã để chọn: gộp theo mã vì một mã gồm nhiều size.
     */
    private function productCodes(): array
    {
        return Product::query()
            ->active()
            ->select('code', 'name', 'image_path')
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
