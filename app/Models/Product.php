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
        'fabric',
        'size',
        'import_price',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'import_price' => 'decimal:2',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function stockImportHistories(): HasMany
    {
        return $this->hasMany(StockImportHistory::class);
    }
}
