<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/test-pusher', function () {
    // 1. Get or Create a User (Sender)
    $sender = \App\Models\User::first();
    if (!$sender) {
        return "No users found! Please create a user first.";
    }

    // 2. Create a dummy message object (without saving to DB to keep it clean, or save it if needed)
    // We will save it to ensure ID exists
    $conversation = \App\Models\Conversation::first();
    if (!$conversation) {
        // Create dummy conversation if not exists
        $conversation = \App\Models\Conversation::create([
            'user_one_id' => $sender->id,
            'user_two_id' => $sender->id, // Just for testing
        ]);
    }

    $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'message' => '🔔 تجربة بوشر: الوو هل تسمعني؟ ' . time(),
        'type' => 'text'
    ]);

    // 3. Fire the Event
    // This is the moment of truth!
    broadcast(new \App\Events\MessageSent($message));

    return "✅ تم إرسال الحدث لـ Pusher بنجاح!<br>" .
           "Channel: <b>private-conversation.{$conversation->id}</b><br>" .
           "Event: <b>message.sent</b><br>" .
           "Message: {$message->message}<br><br>" .
           "Please check Pusher Debug Console now.";
});
