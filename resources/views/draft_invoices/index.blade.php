<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hóa đơn nháp — Tiba Store</title>@include('partials.favicon') @include('draft_invoices.styles')</head>
<body><div class="layout">@include('partials.sidebar', ['active' => 'draft-invoices'])<main class="page">
<div class="toolbar"><h1>Hóa đơn nháp</h1><a class="button primary" href="{{ route('draft-invoices.create') }}">+ Tạo hóa đơn nháp</a></div>
<p class="hint">Soạn phiếu cho khách, nhập trực tiếp trong bảng và in hoặc lưu PDF. Hóa đơn nháp không ảnh hưởng đơn hàng, tồn kho hay doanh thu.</p>
@if(session('status'))<div class="status">{{ session('status') }}</div>@endif
<form class="search" method="GET"><input name="q" value="{{ $query }}" placeholder="Tìm khách hàng hoặc số điện thoại" aria-label="Tìm hóa đơn"><button>Tìm kiếm</button>@if($query !== '')<a class="button" href="{{ route('draft-invoices.index') }}">Bỏ lọc</a>@endif</form>
<div class="list-wrap"><table class="list-table"><thead><tr><th>Mã nháp</th><th>Ngày</th><th>Khách hàng</th><th>SĐT</th><th>Tổng tiền</th><th>Thao tác</th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td>#{{ $invoice->id }}</td><td>{{ $invoice->invoice_date->format('d/m/Y') }}</td><td><a href="{{ route('draft-invoices.edit', $invoice) }}">{{ $invoice->customer_name }}</a></td><td>{{ $invoice->phone }}</td><td>{{ number_format($invoice->total, 0, ',', '.') }} đ</td><td><a class="button" href="{{ route('draft-invoices.edit', $invoice) }}">Mở / sửa / in</a> <form method="POST" action="{{ route('draft-invoices.destroy', $invoice) }}" onsubmit="return confirm('Xóa hóa đơn nháp này?')">@csrf @method('DELETE')<button class="danger">Xóa</button></form></td></tr>
@empty<tr><td colspan="6">{{ $query !== '' ? 'Không tìm thấy hóa đơn phù hợp.' : 'Chưa có hóa đơn nháp. Bấm “Tạo hóa đơn nháp” để bắt đầu.' }}</td></tr>@endforelse
</tbody></table></div><div style="margin-top:20px">{{ $invoices->links() }}</div>
</main></div></body></html>
