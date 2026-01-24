<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hobby extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'hobby_user', 'hobby_id', 'user_id')->withTimestamps();
    }
}
