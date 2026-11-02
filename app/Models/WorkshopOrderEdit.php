<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopOrderEdit extends Model
{
    protected $guarded = [];

    protected $casts = ['before' => 'array', 'after' => 'array', 'receipts' => 'array', 'undone_at' => 'datetime'];
}
