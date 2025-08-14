<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait SendsSms
{
    /**
     * Send an SMS using the configured provider.
     * - Formats Ethiopian phone numbers to 251XXXXXXXXX
     * - Respects otp.sms_enabled and provider config
     * - No logging; returns boolean for success/failure
     */
    protected function sendSms(string $rawPhone, string $message, ?string $senderId = null): bool
    {
        // Check feature flag and config
        $enabled = (bool) config('otp.sms_enabled', false);
        $apiKey = (string) config('otp.sms_api_key');
        $apiUrl = (string) config('otp.sms_api_url');
        $sender = $senderId ?: (string) config('otp.sms_sender_id', 'SYSTEM');

        if (! $enabled || $apiUrl === '' || $apiKey === '') {
            return false;
        }

        // Format and validate phone
        $msisdn = $this->formatEthiopiaPhone($rawPhone);
        if ($msisdn === null) {
            return false;
        }

        try {
            $resp = Http::withHeaders([
                'KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'text' => $message,
                'msisdn' => $msisdn,
                'sender_id' => $sender,
            ]);

            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Format a phone number into Ethiopia MSISDN format 251XXXXXXXXX.
     * Returns null if invalid after formatting.
     */
    protected function formatEthiopiaPhone(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '251' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '251')) {
            $digits = '251' . $digits;
        }

        return preg_match('/^251[1-59]\d{8}$/', $digits) ? $digits : null;
    }
}
