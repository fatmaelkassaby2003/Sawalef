<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'type',
        'user_id',
        'status',
    ];

    /**
     * Get the user that received this notification (if any)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
