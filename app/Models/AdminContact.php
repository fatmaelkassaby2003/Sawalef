<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'email',
        'links',
    ];

    protected $casts = [
        'links' => 'array',
    ];
}
