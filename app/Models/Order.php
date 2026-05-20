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

    public static function statuses(): array
    {
        return [
            'len_don' => 'Lên đơn',
            'da_gui' => 'Đã gửi',
            'thanh_cong' => 'Thành công',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
