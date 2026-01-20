<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hobby extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon'];

    /**
     * Users who have this hobby
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_hobbies');
    }
}
