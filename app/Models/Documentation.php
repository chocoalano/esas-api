<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'text_docs',
        'status',
    ];
    protected $casts = [
        'status' => 'boolean',
    ];
}
