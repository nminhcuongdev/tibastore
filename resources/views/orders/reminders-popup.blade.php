@if (($orderReminders ?? collect())->isNotEmpty())
    <style>
        .reminder-backdrop {
            align-items: center;
            background: rgba(63, 39, 48, .38);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 18px;
            position: fixed;
            z-index: 50;
        }

        .reminder-modal {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 30px 90px rgba(63, 39, 48, .28);
            max-height: min(760px, 92vh);
            max-width: 760px;
            overflow: auto;
            width: 100%;
        }

        .reminder-head {
            background: #fff0f4;
            border-bottom: 1px solid #f7d7e1;
            padding: 18px 20px;
        }

        .reminder-head h2 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 26px;
            margin: 0 0 6px;
        }

        .reminder-head p {
            color: #704252;
            line-height: 1.5;
            margin: 0;
        }

        .reminder-list {
            display: grid;
            gap: 12px;
            padding: 16px;
        }

        .reminder-item {
            border: 1px solid #f2d3dc;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            padding: 14px;
        }

        .reminder-title {
            color: #9d345a;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .reminder-name {
            color: #3f2730;
            font-weight: 900;
        }

        .reminder-meta {
            color: #704252;
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
            background: #be476f;
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
            border: 1px solid #ebc5d2;
            color: #8b2f4d;
        }

        .reminder-check {
            align-items: center;
            color: #704252;
            display: flex;
            font-weight: 700;
            gap: 8px;
        }

        .reminder-close {
            background: transparent;
            border: 0;
            color: #8b2f4d;
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

    <div class="reminder-backdrop" id="order-reminder-popup">
        <section class="reminder-modal" role="dialog" aria-modal="true" aria-labelledby="order-reminder-title">
            <div class="reminder-head">
                <h2 id="order-reminder-title">Thông báo đơn hàng đến ngày</h2>
                <p>Cập nhật từng đơn một. Nếu chưa muốn xử lý, bạn có thể chọn không nhắc lại cho lần nhắc đó.</p>
            </div>

            <div class="reminder-list">
                @foreach ($orderReminders as $reminder)
                    @php($order = $reminder['order'])
                    <article class="reminder-item">
                        <div>
                            <div class="reminder-title">{{ $reminder['title'] }} - {{ $reminder['date']?->format('d/m/Y') }}</div>
                            <div class="reminder-name">{{ $order->order_name }}</div>
                        </div>

                        <div class="reminder-meta">
                            <div>Mã hàng: <strong>{{ $order->product->code }}</strong> - {{ $order->product->name }} | Size: {{ $order->product->size }}</div>
                            <div>Số lượng: <strong>{{ number_format($order->quantity) }}</strong> | Trạng thái hiện tại: {{ $order->statusLabel() }}</div>
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
                        Để sau
                    </button>
                </div>
            </div>
        </section>
    </div>
@endif
