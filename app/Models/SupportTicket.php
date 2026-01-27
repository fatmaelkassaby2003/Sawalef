<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'issue_type_id',
        'name',
        'email',
        'message',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issueType()
    {
        return $this->belongsTo(IssueType::class);
    }
}
