<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'text_ar',
        'text_en',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
