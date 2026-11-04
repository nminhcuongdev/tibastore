        <section data-incoming-schedule style="padding:14px;border:1px solid #cbd9ec;border-radius:8px;background:#f6f9ff" aria-label="Dự kiến hàng về">
            <strong>Dự kiến hàng về</strong>
            @foreach($variants->flatMap(fn ($variant) => $variant->arrival_dates ?? [])->unique()->sort() as $arrivalDate)
                <div style="margin-top:8px">{{ \Illuminate\Support\Carbon::parse($arrivalDate)->format('d/m/Y') }} — Lịch dự kiến của mã sản phẩm</div>
            @endforeach
            @php
                $sizeNames = $variants->pluck('size', 'id');
            @endphp
            @forelse($incomingWorkshopOrders as $incoming)
                @php
                    $incomingLines = $incoming->size_quantities ?? $incoming->purchase_plan['lines'] ?? [];
                    $incomingSizes = collect($incomingLines)->map(fn ($line) => ($sizeNames[$line['product_id']] ?? 'Size chưa xác định').': '.$line['quantity'])->implode(', ');
                @endphp
                <div style="margin-top:10px" data-incoming-workshop="{{ $incoming->id }}">
                    <b>{{ $incoming->returned_date->format('d/m/Y') }}</b> — {{ $incomingSizes ?: ($incoming->size_note ?: 'Chưa ghi size') }}
                    <div class="muted">{{ $incoming->order_name ?: 'Chưa có tên đơn' }} · {{ $incoming->supplySourceLabel() }} · {{ $incoming->statusLabel() }}
                        <a href="{{ route('workshop-orders.index', ['focus' => $incoming->id, 'supply_source' => $incoming->supply_source]) }}#workshop-row-{{ $incoming->id }}" target="_blank" rel="noopener">Xem đặt hàng ↗</a>
                    </div>
                </div>
            @empty
                <div class="muted" style="margin-top:8px">Chưa có lịch hàng về từ Đặt hàng.</div>
            @endforelse
            @foreach($variants as $variant)
                @foreach($variant->expectedReceipts->whereNull('received_at')->whereNull('workshop_order_id') as $receipt)
                    <div class="muted" style="margin-top:8px">{{ $receipt->expected_receive_date?->format('d/m/Y') }} — {{ $variant->size }}: {{ $receipt->expected_receive_quantity }} (dự kiến nhập tại Kho)</div>
                @endforeach
            @endforeach
            <small style="display:block;margin-top:10px">Chỉ là lịch dự kiến. Chưa cộng vào tồn kho hoặc đổi số lượng đơn khách.</small>
        </section>
