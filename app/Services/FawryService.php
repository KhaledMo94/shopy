<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FawryService
{
    protected string $merchantCode;
    protected string $secureKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = config('services.fawry.merchant_code');
        $this->secureKey    = config('services.fawry.secure_key');

        $this->baseUrl = config('services.fawry.mode') === 'production'
            ? config('services.fawry.live_url')
            : config('services.fawry.sandbox_url');
    }

    public function createCharge(array $data): array
    {
        $signatureString = $this->merchantCode
            . $data['merchantRefNum']
            . 'PAYATFAWRY'
            . number_format($data['amount'], 2, '.', '')
            . $this->secureKey;

        $data['signature']    = hash('sha256', $signatureString);
        $data['merchantCode'] = $this->merchantCode;

        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/charge', $data);

        return $response->json();
    }

    public function verifyNotificationSignature(string $merchantRefNum, string $receivedSignature): bool
    {
        $signatureString = $this->merchantCode . $merchantRefNum . $this->secureKey;
        $calculated      = hash('sha256', $signatureString);

        return hash_equals($calculated, $receivedSignature);
    }
}
