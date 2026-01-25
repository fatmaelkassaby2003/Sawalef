<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'user_one_last_read',
        'user_two_last_read',
    ];

    protected $casts = [
        'user_one_last_read' => 'datetime',
        'user_two_last_read' => 'datetime',
    ];

    /**
     * Get user one
     */
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get user two
     */
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get all messages for this conversation
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get unread messages count for a specific user
     */
    public function unreadMessagesCount($userId)
    {
        $lastRead = $this->user_one_id == $userId 
            ? $this->user_one_last_read 
            : $this->user_two_last_read;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where(function ($query) use ($lastRead) {
                if ($lastRead) {
                    $query->where('created_at', '>', $lastRead);
                }
            })
            ->count();
    }

    /**
     * Get the other user in the conversation
     */
    public function getOtherUser($currentUserId)
    {
        return $this->user_one_id == $currentUserId ? $this->userTwo : $this->userOne;
    }

    /**
     * Find or create conversation between two users
     */
    public static function findOrCreateBetween($userOneId, $userTwoId)
    {
        // Ensure consistent ordering (smaller ID first)
        [$smallerId, $largerId] = $userOneId < $userTwoId 
            ? [$userOneId, $userTwoId] 
            : [$userTwoId, $userOneId];

        return static::firstOrCreate([
            'user_one_id' => $smallerId,
            'user_two_id' => $largerId,
        ]);
    }
}
