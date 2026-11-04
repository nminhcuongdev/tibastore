<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftInvoice extends Model
{
    protected $fillable = ['invoice_date', 'customer_name', 'phone', 'address', 'tax_code', 'items', 'discount_type', 'discount_value'];

    protected $casts = ['invoice_date' => 'date', 'items' => 'array', 'discount_value' => 'decimal:2'];

    public function getSubtotalAttribute(): float
    {
        return array_reduce($this->items ?? [], fn ($total, $item) =>
            $total + round((float) $item['quantity'] * (int) $item['unit_price'] * (float) str_replace(',', '.', (string) ($item['days'] ?? 1))), 0.0);
    }

    public function getDiscountAmountAttribute(): float
    {
        $value = (float) ($this->discount_value ?? 0);
        return min($this->subtotal, max(0, round($this->discount_type === 'percent'
            ? $this->subtotal * $value / 100 : $value)));
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }
}
