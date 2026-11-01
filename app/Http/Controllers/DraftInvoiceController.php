<?php

namespace App\Http\Controllers;

use App\Models\DraftInvoice;
use Illuminate\Http\Request;

class DraftInvoiceController extends Controller
{
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
        return view('draft_invoices.form', ['invoice' => new DraftInvoice(['invoice_date' => today()])]);
    }

    public function store(Request $request)
    {
        $invoice = DraftInvoice::create($this->validated($request));

        return redirect()->route('draft-invoices.edit', $invoice)->with('status', 'Đã lưu hóa đơn nháp.');
    }

    public function edit(DraftInvoice $draftInvoice)
    {
        return view('draft_invoices.form', ['invoice' => $draftInvoice]);
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
        return $request->validate([
            'invoice_date' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:name,unit,quantity,unit_price,note'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'items.*.unit_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'invoice_date' => 'ngày hóa đơn', 'customer_name' => 'tên khách hàng',
            'items' => 'dòng sản phẩm', 'items.*.name' => 'tên sản phẩm',
            'items.*.quantity' => 'số lượng', 'items.*.unit_price' => 'đơn giá',
        ]);
    }
}
