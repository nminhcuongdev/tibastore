<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_pending',
        'quantity',
        'rental_price',
        'note',
        'returned_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'rental_price' => 'integer',
        'returned_quantity' => 'integer',
        'size_pending' => 'boolean',
    ];

    /**
     * Mã hàng hiển thị (lấy từ sản phẩm đại diện).
     */
    public function displayCode(): string
    {
        return $this->product?->code ?? 'N/A';
    }

    /**
     * Size hiển thị: nếu chưa chốt size thì báo "Chưa chốt".
     */
    public function displaySize(): string
    {
        if ($this->size_pending) {
            return 'Chưa chốt';
        }

        return $this->product?->size ?? 'N/A';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
