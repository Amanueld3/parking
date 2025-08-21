<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChapaService
{
    public function __construct(private readonly string $secretKey) {}

    public static function make(?string $key = null): self
    {
        $key = $key ?: config('services.chapa.secret');
        return new self((string) $key);
    }

    public function initialize(array $payload): array
    {
        $endpoint = 'https://api.chapa.co/v1/transaction/initialize';
        $response = Http::withToken($this->secretKey)
            ->asJson()
            ->post($endpoint, $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function verify(string $txRef): array
    {
        $endpoint = 'https://api.chapa.co/v1/transaction/verify/' . urlencode($txRef);
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->get($endpoint);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public static function generateTxRef(string $prefix = 'park')
    {
        return $prefix . '-' . Str::uuid()->toString();
    }
}
