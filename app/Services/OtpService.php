<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected int $otpLength;
    protected int $expiryMinutes;
    protected int $maxAttempts;
    protected string $smsApiKey;
    protected string $smsApiUrl;
    protected string $senderId;
    protected bool $smsEnabled;

    public function __construct()
    {
        $this->otpLength = (int) config('otp.length', 6);
        $this->expiryMinutes = (int) config('otp.expiry', 5);
        $this->maxAttempts = (int) config('otp.max_attempts', 3);
        $this->smsApiKey = config('otp.sms_api_key');
        $this->smsApiUrl = config('otp.sms_api_url');
        $this->senderId = config('otp.sms_sender_id', 'OTP');
        $this->smsEnabled = config('otp.sms_enabled', false);
    }

    public function sendOtp(string $phone): bool
    {
        try {
            $phone = $this->formatPhoneNumber($phone);

            if (!$this->validatePhoneNumber($phone)) {
                throw new \Exception("Invalid phone number format");
            }

            if ($this->hasReachedMaxAttempts($phone)) {
                throw new \Exception("Maximum OTP attempts reached. Please try again later.");
            }

            // $otp = $this->generateOtp();
            $otp = 123456;
            $this->storeOtp($phone, $otp);

            // if ($this->smsEnabled) {
            //     $this->sendViaSmsApi($phone, $otp);
            // }

            Log::info("OTP generated for {$phone}", ['otp' => $otp]);
            return true;
        } catch (\Exception $e) {
            Log::error("OTP send failed for {$phone}: " . $e->getMessage());
            throw $e;
        }
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        $phone = $this->formatPhoneNumber($phone);
        $storedOtp = Cache::get("otp_{$phone}");

        if ($storedOtp === null) {
            Log::warning("OTP expired or not found for {$phone}");
            return false;
        }

        if ($storedOtp === $otp) {
            $this->clearOtp($phone);
            return true;
        }

        return false;
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to Ethiopia format (251XXXXXXXXX)
        if (str_starts_with($phone, '0')) {
            return '251' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '251')) {
            return '251' . $phone;
        }

        return $phone;
    }

    protected function validatePhoneNumber(string $phone): bool
    {
        return preg_match('/^251[1-59]\d{8}$/', $phone);
    }

    protected function hasReachedMaxAttempts(string $phone): bool
    {
        return Cache::get("otp_attempts_{$phone}", 0) >= $this->maxAttempts;
    }

    protected function generateOtp(): string
    {
        return str_pad(
            random_int(0, pow(10, $this->otpLength) - 1),
            $this->otpLength,
            '0',
            STR_PAD_LEFT
        );
    }

    protected function storeOtp(string $phone, string $otp): void
    {
        Cache::put("otp_{$phone}", $otp, now()->addMinutes($this->expiryMinutes));
        Cache::increment("otp_attempts_{$phone}");
    }

    protected function sendViaSmsApi(string $phone, string $otp): void
    {
        $response = Http::withHeaders([
            'KEY' => $this->smsApiKey,
            'Content-Type' => 'application/json',
        ])->post($this->smsApiUrl, [
            'text' => "Your verification code is: {$otp}",
            'msisdn' => $phone,
            'sender_id' => $this->senderId,
        ]);

        if (!$response->successful()) {
            throw new \Exception("SMS API Error: " . $response->body());
        }
    }

    protected function clearOtp(string $phone): void
    {
        Cache::forget("otp_{$phone}");
        Cache::forget("otp_attempts_{$phone}");
    }
}
