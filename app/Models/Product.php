<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'image_path',
        'stock_quantity',
        'expected_receive_date',
        'expected_receive_quantity',
        'expected_received_at',
        'fabric',
        'category',
        'size',
        'import_price',
        'rental_price',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'expected_receive_date' => 'date',
        'expected_receive_quantity' => 'integer',
        'expected_received_at' => 'datetime',
        'import_price' => 'decimal:2',
        'rental_price' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockImportHistories(): HasMany
    {
        return $this->hasMany(StockImportHistory::class);
    }

    public function expectedReceipts(): HasMany
    {
        return $this->hasMany(ProductExpectedReceipt::class);
    }
}
