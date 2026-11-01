<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftInvoice extends Model
{
    protected $fillable = ['invoice_date', 'customer_name', 'phone', 'address', 'tax_code', 'items'];

    protected $casts = ['invoice_date' => 'date', 'items' => 'array'];

    public function getTotalAttribute(): float
    {
        return array_reduce($this->items ?? [], fn ($total, $item) =>
            $total + round((float) $item['quantity'] * (int) $item['unit_price']), 0.0);
    }
}
