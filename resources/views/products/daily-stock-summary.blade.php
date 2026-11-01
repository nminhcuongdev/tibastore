<section class="stock-summary" aria-label="Tổng kết tồn kho theo ngày">
    <h2>Tổng kết theo ngày</h2>
    <p>Số lượng khả dụng theo từng size, tổng hợp riêng cho mỗi mã trong kết quả tìm kiếm. Số âm thể hiện lượng đang vượt tồn.</p>
    @foreach($products->groupBy('code') as $code => $variants)
        <div class="stock-summary-card">
            <h3>{{ $code }} <span>{{ $variants->first()->name }}</span></h3>
            <div class="stock-summary-scroll">
                <table class="stock-summary-table">
                    <thead><tr>@foreach($dates as $date)<th scope="col">Ngày {{ $date->format('d/m') }}<span>{{ $date->format('Y') }}</span></th>@endforeach</tr></thead>
                    <tbody><tr>
                    @foreach($dates as $date)
                        @php
                            $dayTotal = 0;
                        @endphp
                        <td>
                            @foreach($variants->groupBy('size') as $size => $sizeProducts)
                                @php
                                    $sizeQuantity = $sizeProducts->sum(fn ($product) => $availability[$product->id][$date->toDateString()] ?? 0);
                                    $dayTotal += $sizeQuantity;
                                @endphp
                                <div class="stock-summary-size"><span>{{ $size !== '' ? $size : 'Chưa ghi size' }}:</span><strong class="{{ $sizeQuantity < 0 ? 'stock-summary-negative' : '' }}">{{ number_format($sizeQuantity) }}</strong></div>
                            @endforeach
                            <div class="stock-summary-total"><span>Tổng</span><strong>{{ number_format($dayTotal) }}</strong></div>
                        </td>
                    @endforeach
                    </tr></tbody>
                </table>
            </div>
        </div>
    @endforeach
</section>
<style>
.stock-summary{margin-top:26px}.stock-summary>h2{font-size:19px;color:#285a7e;margin:0 0 8px}.stock-summary>p{font-size:12px;color:#7b8fa6;line-height:1.7;margin:0 0 16px}.stock-summary-card{background:white;border:1px solid #d6e5f1;border-radius:12px;overflow:hidden;margin-bottom:16px}.stock-summary-card h3{display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:#f2f8fd;color:#285a7e;font-size:14px;padding:15px 18px;margin:0;border-bottom:1px solid #d6e5f1}.stock-summary-card h3 span{font-size:12px;font-weight:normal;color:#7b8fa6}.stock-summary-scroll{overflow-x:auto}.stock-summary-table{width:100%;border-collapse:collapse;min-width:0}.stock-summary-table th{position:static!important;min-width:145px;background:#dceefa;color:#2d6284;padding:12px;font-size:12px;white-space:nowrap}.stock-summary-table th>span{display:block;font-size:10px;color:#7894aa;font-weight:normal;margin-top:3px}.stock-summary-table td{min-width:145px;padding:14px 16px;border-right:1px solid #e5edf5;vertical-align:top;background:#fff}.stock-summary-size{display:flex;justify-content:space-between;gap:20px;padding:5px 0;font-size:12px;color:#5f7790}.stock-summary-size strong{font-variant-numeric:tabular-nums;color:#285a7e}.stock-summary-size strong.stock-summary-negative{color:#b33f4f}.stock-summary-total{display:flex;justify-content:space-between;gap:16px;margin-top:10px;padding:10px 0 0;border-top:2px solid #e5c67e;font-size:13px;color:#285a7e;font-weight:700}
</style>
