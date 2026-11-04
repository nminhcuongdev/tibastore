<?php

namespace App\Http\Controllers;

use App\Models\DraftInvoice;
use App\Models\Product;
use Illuminate\Http\Request;

class DraftInvoiceController extends Controller
{
    public function logo()
    {
        return response()->file(resource_path('images/tiba-invoice-logo.png'), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $invoices = DraftInvoice::query()
            ->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query) {
                $builder->where('customer_name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            }))
            ->latest('id')->paginate(30)->withQueryString();

        return view('draft_invoices.index', compact('invoices', 'query'));
    }

    public function create()
    {
        return view('draft_invoices.form', [
            'invoice' => new DraftInvoice(['invoice_date' => today()]),
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function store(Request $request)
    {
        $invoice = DraftInvoice::create($this->validated($request));

        return redirect()->route('draft-invoices.edit', $invoice)->with('status', 'Đã lưu hóa đơn nháp.');
    }

    public function edit(DraftInvoice $draftInvoice)
    {
        return view('draft_invoices.form', [
            'invoice' => $draftInvoice,
            'productCodes' => $this->productCodes(),
        ]);
    }

    public function update(Request $request, DraftInvoice $draftInvoice)
    {
        $draftInvoice->update($this->validated($request));

        return back()->with('status', 'Đã lưu hóa đơn nháp.');
    }

    public function destroy(DraftInvoice $draftInvoice)
    {
        $draftInvoice->delete();

        return redirect()->route('draft-invoices.index')->with('status', 'Đã xóa hóa đơn nháp.');
    }

    private function validated(Request $request): array
    {
        if (is_array($request->input('items'))) {
            $request->merge(['items' => array_map(function ($item) {
                if (is_array($item)) {
                    $item['days'] = is_string($item['days'] ?? null)
                        ? str_replace(',', '.', trim($item['days'])) : ($item['days'] ?? 1);
                }
                return $item;
            }, $request->input('items'))]);
        }
        $data = $request->validate([
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_value' => ['nullable', 'numeric', 'min:0',
                $request->input('discount_type') === 'percent' ? 'max:100' : 'max:100000000000000',
                $request->input('discount_type') === 'percent' ? 'regex:/^\d+(\.\d{1,2})?$/' : 'integer'],
            'invoice_date' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:code,name,unit,quantity,unit_price,days,note'],
            'items.*.code' => ['nullable', 'string', 'max:255'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'items.*.unit_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'items.*.days' => ['required', 'numeric', 'min:0.01', 'max:10000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'invoice_date' => 'ngày hóa đơn', 'customer_name' => 'tên khách hàng',
            'items' => 'dòng sản phẩm', 'items.*.name' => 'tên sản phẩm',
            'items.*.quantity' => 'số lượng', 'items.*.unit_price' => 'đơn giá',
            'items.*.days' => 'số ngày',
            'discount_value' => 'chiết khấu', 'discount_type' => 'loại chiết khấu',
        ]);
        $data['discount_type'] = $data['discount_type'] ?? 'fixed';
        $data['discount_value'] = $data['discount_value'] ?? 0;
        $preview = new DraftInvoice($data);
        if ($data['discount_type'] === 'fixed' && (float) $data['discount_value'] > $preview->subtotal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Chiết khấu không được lớn hơn tổng tiền hàng.',
            ]);
        }

        return $data;
    }

    private function productCodes(): array
    {
        return Product::query()->orderBy('code')->orderBy('id')
            ->get(['code', 'name', 'image_path', 'category'])->groupBy('code')
            ->map(function ($products) {
                $product = $products->first();
                $image = $products->first(fn ($variant) => filled($variant->image_path));

                return [
                    'code' => $product->code,
                    'name' => $product->name,
                    'category' => $products->pluck('category')->first(fn ($category) => filled($category)) ?: 'Chưa phân loại',
                    'image' => $image ? asset('storage/'.$image->image_path) : null,
                ];
            })->values()->all();
    }
}
