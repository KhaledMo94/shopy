<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FawryService
{
    protected string $merchantCode;
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = config('services.fawry.merchant_code');
        $this->secretKey    = config('services.fawry.secure_key');

        $this->baseUrl      = config('services.fawry.mode') == 'sandbox'
            ? 'https://atfawry.fawrystaging.com/fawrypay-api/api/payments/init'
            : 'https://atfawry.com/fawrypay-api/api/payments/init';
    }

    /**
     * Create charge request (Redirect URL / Reference)
     */
    public function createCharge(array $data): array
    {
        $signature = $this->generateSignature(
            $data['merchantRefNum'],
            $data['customerMobile'],
            $data['amount']
        );

        $payload = [
            "merchantCode"      => $this->merchantCode,
            "merchantRefNum"    => $data['merchantRefNum'],
            "customerName"      => $data['customerName'],
            "customerMobile"    => $data['customerMobile'],
            "customerEmail"     => $data['customerEmail'],
            "paymentMethod"     => $data['paymentMethod'],
            "amount"            => $data['amount'],
            "currencyCode"      => "EGP",
            "chargeItems"       => $data['chargeItems'],
            "orderWebHookUrl"   => $data['orderWebHookUrl'],
            "signature"         => $signature,
        ];

        $response = Http::post($this->baseUrl.'charge', $payload);

        return $response->json();
    }


    /**
     * Signature for charge requests
     */
    public function generateSignature(string $merchantRefNum, string $customerMobile, float $amount): string
    {
        return hash('sha256',
            $this->merchantCode .
            $merchantRefNum .
            $customerMobile .
            number_format($amount, 2, '.', '') .
            $this->secretKey
        );
    }

    /**
     * Verify callback/webhook
     */
    public function verifyNotificationSignature(string $merchantRef, string $receivedSignature): bool
    {
        $local = hash('sha256', $merchantRef . $this->secretKey);
        return strtolower($local) === strtolower($receivedSignature);
    }
}
