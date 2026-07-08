<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'closer_name',
        'pickup_date',
        'event_date',
        'return_date',
        'order_name',
        'product_id',
        'quantity',
        'status',
        'payment_status',
        'total_amount',
        'shipping_fee',
        'payment_1',
        'payment_2',
        'compensation_amount',
        'check_note',
        'pickup_reminder_dismissed',
        'return_reminder_dismissed',
        'stock_decreased_at',
        'stock_returned_at',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'event_date' => 'date',
        'return_date' => 'date',
        'quantity' => 'integer',
        'total_amount' => 'integer',
        'shipping_fee' => 'integer',
        'payment_1' => 'integer',
        'payment_2' => 'integer',
        'compensation_amount' => 'integer',
        'pickup_reminder_dismissed' => 'boolean',
        'return_reminder_dismissed' => 'boolean',
        'stock_decreased_at' => 'datetime',
        'stock_returned_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Các trạng thái khiến hàng đang ở ngoài kho (đã trừ kho, chưa cộng lại).
     */
    public const STOCK_OUT_STATUSES = ['da_gui', 'da_tra_ve'];

    /**
     * Trạng thái "đã kiểm": khi chuyển sang, người dùng nhập số lượng thực nhận lại
     * vào kho cho từng dòng sản phẩm; phần thiếu coi như mất/hỏng, không hoàn kho.
     */
    public const CHECKED_STATUS = 'thanh_cong';

    public const DEFAULT_STATUS = 'chua_cho_size';

    public const DEFAULT_PAYMENT_STATUS = 'coc';

    public static function statuses(): array
    {
        return [
            'chua_cho_size' => 'Chưa cho size',
            'da_in_file' => 'Đã in đơn lên file',
            'da_in_giay' => 'Đã in đơn ra giấy',
            'dang_soan' => 'Đang soạn đơn',
            'da_soan_xong' => 'Đã soạn xong',
            'da_gui' => 'Đã gửi',
            'da_tra_ve' => 'Đã trả về',
            'thanh_cong' => 'Đã kiểm',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            'coc' => 'Cọc',
            'thanh_toan_1' => 'Thanh toán lần 1',
            'thanh_toan_2' => 'Thanh toán lần 2',
            'con_lai' => 'Còn lại',
        ];
    }

    /**
     * Tổng đơn đã cộng tiền bồi thường (nếu có).
     */
    public function getTotalWithCompensationAttribute(): int
    {
        return (int) $this->total_amount + (int) $this->compensation_amount;
    }

    /**
     * Số tiền còn lại theo công thức:
     * (tổng đơn - thanh toán lần 1 + thanh toán lần 2 + tiền ship).
     * "Tổng đơn" đã bao gồm tiền bồi thường.
     */
    public function getRemainingAttribute(): int
    {
        return $this->total_with_compensation
            - (int) $this->payment_1
            + (int) $this->payment_2
            + (int) $this->shipping_fee;
    }

    /**
     * Đơn đã kiểm nhưng thiếu: có ít nhất một mã hàng số nhận lại < số gửi đi.
     */
    public function hasShortage(): bool
    {
        if ($this->status !== self::CHECKED_STATUS) {
            return false;
        }

        return $this->items->contains(function ($item) {
            $returned = $item->returned_quantity ?? $item->quantity;

            return $returned < $item->quantity;
        });
    }

    /**
     * Khóa màu cho trạng thái: đơn "đã kiểm" nhưng thiếu sẽ bôi đỏ thay vì xanh.
     */
    public function statusColorKey(): string
    {
        if ($this->status === self::CHECKED_STATUS && $this->hasShortage()) {
            return 'thanh_cong_thieu';
        }

        return $this->status;
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::paymentStatuses()[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Hàng của đơn có đang phải nằm ngoài kho theo trạng thái hiện tại không.
     */
    public function requiresStockOut(): bool
    {
        return in_array($this->status, self::STOCK_OUT_STATUSES, true);
    }
}
