@if (($showEmptyReminders ?? false) || ($orderReminders ?? collect())->isNotEmpty() || ($sameDayReminders ?? collect())->isNotEmpty())
    <style>
        .reminder-product { display: flex; align-items: center; gap: 12px; margin: 10px 0; }
        .reminder-product-photo { width: 72px; height: 88px; object-fit: cover; border-radius: 8px; border: 1px solid #d8e4f5; flex-shrink: 0; }
        .reminder-product-empty { display: grid; place-items: center; background: #eff6ff; color: #64748b; font-size: 12px; text-align: center; }
    </style>
    <style>
        .reminder-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, .38);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 18px;
            position: fixed;
            z-index: 50;
        }

        .reminder-modal {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 30px 90px rgba(15, 23, 42, .28);
            max-height: min(760px, 92vh);
            max-width: 760px;
            overflow: auto;
            width: 100%;
        }

        .reminder-head {
            background: #eff6ff;
            border-bottom: 1px solid #d8e4f5;
            padding: 18px 20px;
        }

        .reminder-head h2 {
            color: #1e3a5f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 26px;
            margin: 0 0 6px;
        }

        .reminder-head p {
            color: #475569;
            line-height: 1.5;
            margin: 0;
        }

        .reminder-list {
            display: grid;
            gap: 12px;
            padding: 16px;
        }

        .reminder-item {
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            padding: 14px;
        }

        .reminder-title {
            color: #1e40af;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .reminder-name {
            color: #1e293b;
            font-weight: 900;
        }

        .reminder-meta {
            color: #475569;
            display: grid;
            gap: 4px;
            line-height: 1.45;
        }

        .reminder-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .reminder-button {
            background: #1d4ed8;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 900;
            min-height: 42px;
            padding: 10px 14px;
        }

        .reminder-button.secondary {
            background: #fff;
            border: 1px solid #c3d4ed;
            color: #1e40af;
        }

        .reminder-check {
            align-items: center;
            color: #475569;
            display: flex;
            font-weight: 700;
            gap: 8px;
        }

        .reminder-close {
            background: transparent;
            border: 0;
            color: #1e40af;
            cursor: pointer;
            font-weight: 900;
            margin-left: auto;
            min-height: 42px;
            padding: 10px 14px;
        }

        @media (max-width: 640px) {
            .reminder-actions,
            .reminder-button,
            .reminder-close {
                width: 100%;
            }
        }
    </style>

    <div class="reminder-backdrop" id="order-reminder-popup" @if (! ($reminderInitiallyOpen ?? true)) style="display:none" @endif>
        <section class="reminder-modal" role="dialog" aria-modal="true" aria-labelledby="order-reminder-title">
            <div class="reminder-head">
                <h2 id="order-reminder-title">Nhắc đòi đồ về sớm</h2>
                <p>{{ ($showAllUpcomingReminders ?? false) ? 'Toàn bộ đơn sắp tới cần hàng từ đơn trả cùng ngày, không phụ thuộc bộ lọc danh sách. Lời nhắc tự hiện trước ngày lấy 1 ngày.' : 'Đơn lấy hôm nay hoặc ngày mai cần hàng từ đơn trả cùng ngày.' }} Hãy liên hệ khách để nhận đồ sớm trước giờ giao đơn mới.</p>
            </div>

            <div class="reminder-list">
                @if (($sameDayReminders ?? collect())->isEmpty() && ($orderReminders ?? collect())->isEmpty())
                    <p>Hiện không có đơn cần đòi đồ về sớm.</p>
                @endif
                @foreach ($sameDayReminders ?? [] as $reminder)
                    <article class="reminder-item">
                        <div class="reminder-title" style="color:#b4233f">{{ ($reminder['size_pending'] ?? false) ? 'Cần xác nhận size và đòi đồ về sớm' : 'Cảnh báo thiếu hàng nếu đồ chưa về kịp' }}</div>
                        <div class="reminder-name">
                            <a href="{{ route('orders.show', $reminder['order']) }}">{{ $reminder['order']->order_name }}</a>
                            — lấy {{ $reminder['order']->pickup_date->format('d/m/Y') }}
                        </div>
                        <div class="reminder-meta">
                            <div class="reminder-product">
                                @if ($reminder['product']?->image_path)
                                    <a href="{{ asset('storage/' . $reminder['product']->image_path) }}" target="_blank" rel="noopener" aria-label="Xem ảnh mã {{ $reminder['product']->code }}">
                                        <img class="reminder-product-photo" src="{{ asset('storage/' . $reminder['product']->image_path) }}" alt="{{ $reminder['product']->name }} — mã {{ $reminder['product']->code }}" loading="lazy">
                                    </a>
                                @else
                                    <span class="reminder-product-photo reminder-product-empty">Chưa có ảnh</span>
                                @endif
                                <div>
                                    Mã <strong>{{ $reminder['product']?->code }}</strong>
                                    @if ($reminder['size_pending'] ?? false)
                                        — có dòng chưa chốt size. Đơn mới cần {{ number_format($reminder['needed_quantity']) }} cái theo mã; cần xác nhận size và đòi đồ về sớm. Chưa xác định số thiếu theo từng size.
                                    @else
                                        — size {{ $reminder['product']?->size }}: cần đòi về sớm <strong>{{ number_format($reminder['shortage']) }}</strong> cái.
                                    @endif
                                </div>
                            </div>
                            @foreach ($reminder['returns'] as $returnItem)
                                <div>
                                    Đòi đồ từ <a href="{{ route('orders.show', $returnItem->order) }}">{{ $returnItem->order->order_name }}</a>
                                    — {{ number_format($returnItem->quantity) }} cái, size {{ $returnItem->displaySize() }}, trả {{ $returnItem->order->return_date->format('d/m/Y') }}.
                                    @if ($returnItem->order->phone) Điện thoại: {{ $returnItem->order->phone }}. @endif
                                </div>
                            @endforeach
                            <div>Sau khi nhận đủ đồ, chuyển đơn trả sang “Đã trả về” trước khi gửi đơn mới.</div>
                        </div>
                    </article>
                @endforeach
                @foreach ($orderReminders as $reminder)
                    @php($order = $reminder['order'])
                    <article class="reminder-item">
                        <div>
                            <div class="reminder-title">{{ $reminder['title'] }} - {{ $reminder['date']?->format('d/m/Y') }}</div>
                            <div class="reminder-name">{{ $order->order_name }}</div>
                        </div>

                        <div class="reminder-meta">
                            @forelse ($order->items as $item)
                                <div>Mã hàng: <strong>{{ $item->product->code }}</strong> - {{ $item->product->name }} | Size: {{ $item->product->size }} | SL: {{ number_format($item->quantity) }}</div>
                            @empty
                                <div>Mã hàng: <strong>{{ $order->product->code }}</strong> - {{ $order->product->name }} | Size: {{ $order->product->size }}</div>
                            @endforelse
                            <div>Số lượng: <strong>{{ number_format($order->items->sum('quantity') ?: $order->quantity) }}</strong> | Trạng thái hiện tại: {{ $order->statusLabel() }}</div>
                            <div>{{ $reminder['message'] }}</div>
                        </div>

                        <div class="reminder-actions">
                            <form method="POST" action="{{ route('order-reminders.confirm', $order) }}">
                                @csrf
                                <input type="hidden" name="type" value="{{ $reminder['type'] }}">
                                <button class="reminder-button" type="submit">
                                    {{ $reminder['type'] === 'pickup' ? 'Xác nhận đã gửi' : 'Xác nhận thành công' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('order-reminders.dismiss', $order) }}">
                                @csrf
                                <input type="hidden" name="type" value="{{ $reminder['type'] }}">
                                <label class="reminder-check">
                                    <input type="checkbox" required>
                                    Không nhắc lại
                                </label>
                                <button class="reminder-button secondary" type="submit">Lưu lựa chọn</button>
                            </form>
                        </div>
                    </article>
                @endforeach

                <div class="reminder-actions">
                    <button class="reminder-close" type="button" onclick="document.getElementById('order-reminder-popup').style.display='none'">
                        Đóng
                    </button>
                </div>
            </div>
        </section>
    </div>
@endif
