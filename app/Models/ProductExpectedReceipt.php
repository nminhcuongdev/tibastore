<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductExpectedReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'expected_receive_date',
        'expected_receive_quantity',
        'received_at',
    ];

    protected $casts = [
        'expected_receive_date' => 'date',
        'expected_receive_quantity' => 'integer',
        'received_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
