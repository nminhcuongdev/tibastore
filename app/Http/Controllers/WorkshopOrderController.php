<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WorkshopOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkshopOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $source = trim((string) $request->query('source', ''));
        $buyer = trim((string) $request->query('buyer', ''));
        $orderedFrom = $this->filterDate($request->query('ordered_from'));
        $orderedTo = $this->filterDate($request->query('ordered_to'));

        $orders = WorkshopOrder::query()
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
            ->when($buyer !== '', fn ($builder) => $builder->where('buyer', 'like', "%{$buyer}%"))
            ->when($orderedFrom, fn ($builder) => $builder->whereDate('ordered_date', '>=', $orderedFrom))
            ->when($orderedTo, fn ($builder) => $builder->whereDate('ordered_date', '<=', $orderedTo))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('workshop_orders.index', [
            'orders' => $orders,
            'statuses' => WorkshopOrder::statuses(),
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
            'workshopOrder' => new WorkshopOrder(['status' => WorkshopOrder::DEFAULT_STATUS]),
            'statuses' => WorkshopOrder::statuses(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        WorkshopOrder::create($this->validatedData($request));

        return redirect()->route('workshop-orders.index')->with('status', 'Đã thêm dòng đặt xưởng.');
    }

    public function edit(WorkshopOrder $workshopOrder): View
    {
        return view('workshop_orders.form', [
            'mode' => 'edit',
            'workshopOrder' => $workshopOrder,
            'statuses' => WorkshopOrder::statuses(),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function update(Request $request, WorkshopOrder $workshopOrder): RedirectResponse
    {
        $workshopOrder->update($this->validatedData($request));

        return redirect()->route('workshop-orders.index')->with('status', 'Đã cập nhật dòng đặt xưởng.');
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

        return back()->with('status', 'Đã xoá dòng đặt xưởng.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'product_code' => ['required', 'string', 'max:50', Rule::exists('products', 'code')],
            'size_note' => ['nullable', 'string', 'max:255'],
            'order_name' => ['nullable', 'string', 'max:255'],
            'buyer_note' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:255'],
            'ordered_date' => ['nullable', 'date'],
            'returned_date' => ['nullable', 'date', 'after_or_equal:ordered_date'],
            'buyer' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(WorkshopOrder::statuses()))],
        ], [
            'product_code.required' => 'Vui lòng chọn mã hàng.',
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
