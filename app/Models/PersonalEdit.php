<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalEdit extends Model
{
    protected $guarded = [];
    protected $casts = ['changes' => 'array', 'undone_at' => 'datetime'];
}
