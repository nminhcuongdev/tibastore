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

    public const DEFAULT_STATUS = 'chua_cho_size';

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

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    /**
     * Hàng của đơn có đang phải nằm ngoài kho theo trạng thái hiện tại không.
     */
    public function requiresStockOut(): bool
    {
        return in_array($this->status, self::STOCK_OUT_STATUSES, true);
    }
}
