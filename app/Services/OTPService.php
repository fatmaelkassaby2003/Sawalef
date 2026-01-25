<?php

namespace App\Services;

use Twilio\Rest\Client;
use Carbon\Carbon;

class OTPService
{
    protected $twilio;
    protected $verifySid;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $this->verifySid = env('TWILIO_VERIFY_SID');
        
        $this->twilio = new Client($sid, $token);
    }

    /**
     * Generate random 4-digit OTP code
     */
    public function generateOTP(): string
    {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via Twilio Verify API
     */
    public function sendOTP(string $phone): bool
    {
        try {
            // Ensure phone number has country code (e.g., +20 for Egypt)
            if (!str_starts_with($phone, '+')) {
                // Add Egyptian country code +20
                $phone = '+20' . ltrim($phone, '0');
            }

            $verification = $this->twilio->verify->v2
                ->services($this->verifySid)
                ->verifications
                ->create($phone, "sms");

            return $verification->status === 'pending';
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP code using Twilio Verify API
     */
    public function verifyOTP(string $phone, string $code): bool
    {
        try {
            // Ensure phone number has country code
            if (!str_starts_with($phone, '+')) {
                $phone = '+20' . ltrim($phone, '0');
            }

            $verificationCheck = $this->twilio->verify->v2
                ->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $phone,
                    'code' => $code
                ]);

            return $verificationCheck->status === 'approved';
        } catch (\Exception $e) {
            \Log::error('Failed to verify OTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store OTP in database with expiration
     */
    public function storeOTP($user, string $otp): void
    {
        $user->update([
            'last_otp' => $otp,
            'last_otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);
    }

    /**
     * Check if stored OTP is valid (for fallback verification)
     */
    public function isOTPValid($user, string $otp): bool
    {
        if (!$user->last_otp || !$user->last_otp_expires_at) {
            return false;
        }

        if (Carbon::now()->greaterThan($user->last_otp_expires_at)) {
            return false;
        }

        return $user->last_otp === $otp;
    }
}
