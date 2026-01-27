<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueType extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}
