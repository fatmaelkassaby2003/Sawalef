<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat Conversation Channels (Simplified for Testing)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // For testing: allow any authenticated user
    return true; 
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return true;
});
