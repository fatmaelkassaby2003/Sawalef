<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FCMService
{
    /**
     * Send notification to a specific user
     */
    public function sendToUser($userId, $title, $body, $data = [])
    {
        $user = User::find($userId);
        
        if (!$user || !$user->fcm_token) {
            Log::warning("FCM: User {$userId} does not have a token.");
            return false;
        }

        return $this->sendNotification($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send notification to multiple users (topic or tokens)
     * Note: FCM V1 sends to one token at a time usually, or uses topics.
     * For simplicity, we loop or use multicast if available.
     */
    public function sendToUsers($userIds, $title, $body, $data = [])
    {
        $tokens = User::whereIn('id', $userIds)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
        
        if (empty($tokens)) {
            return false;
        }

        // For large scale, use Topic or Batch. Here we loop for simplicity in demo
        foreach ($tokens as $token) {
            $this->sendNotification($token, $title, $body, $data);
        }
        
        return true;
    }

    /**
     * Send notification to All Users
     */
    public function sendToAll($title, $body, $data = [])
    {
        // Ideally use a Topic like 'all_users' that users subscribe to on mobile app login
        return $this->sendToTopic('all_users', $title, $body, $data);
    }

    /**
     * Send to Topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        return $this->sendRaw('/topics/' . $topic, $title, $body, $data);
    }

    /**
     * Internal method to send via FCM HTTP v1 API
     */
    private function sendNotification($token, $title, $body, $data = [])
    {
        return $this->sendRaw($token, $title, $body, $data);
    }

    private function sendRaw($target, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $projectId = config('services.fcm.project_id'); // We need to add this to config

            if (!$accessToken || !$projectId) {
                Log::error('FCM: Missing configuration or credentials.');
                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => str_starts_with($target, '/topics/') ? null : $target,
                    'topic' => str_starts_with($target, '/topics/') ? substr($target, 8) : null,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data, // Custom data (e.g., click_action, type)
                ]
            ];

            // Remove nulls (token vs topic)
            $payload['message'] = array_filter($payload['message'], function($v) { return !is_null($v); });

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('FCM Send Error: ' . $response->body());
                return false;
            }

            Log::info("FCM Sent to {$target}: " . $response->body());
            return true;

        } catch (\Exception $e) {
            Log::error('FCM Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate OAuth 2.0 Access Token using Google Client
     */
    private function getAccessToken()
    {
        try {
            $credentialsPath = storage_path('app/firebase_credentials.json');
            
            if (!file_exists($credentialsPath)) {
                Log::error('FCM: Firebase credentials file not found at ' . $credentialsPath);
                return null;
            }

            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            $token = $client->fetchAccessTokenWithAssertion();
            return $token['access_token'];

        } catch (\Exception $e) {
            Log::error('FCM Auth Error: ' . $e->getMessage());
            return null;
        }
    }
}
