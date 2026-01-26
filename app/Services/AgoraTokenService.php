<?php

namespace App\Services;

use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;
use Illuminate\Support\Facades\Log;

/**
 * AgoraTokenService
 * 
 * Provides dynamic token generation for Agora RTC using the official logic via cyberdeep package.
 */
class AgoraTokenService
{
    /**
     * Generate a token for Agora RTC
     * 
     * @param string $channelName
     * @param int|string $uid
     * @param bool $isAudioOnly
     * @return string|null
     */
    public function createToken(string $channelName, $uid = 0, $isAudioOnly = false)
    {
        try {
            // Using the installed package to generate a valid Agora token
            $token = Agora::make($uid)
                ->channel($channelName)
                ->uId((string)$uid)
                ->audioOnly($isAudioOnly)
                ->token();

            return $token;
            
        } catch (\Exception $e) {
            Log::error('Agora Token generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
