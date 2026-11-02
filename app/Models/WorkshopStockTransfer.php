<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopStockTransfer extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['lines' => 'array', 'transferred_at' => 'datetime', 'undone_at' => 'datetime', 'can_undo' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
