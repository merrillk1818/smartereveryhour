<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VonageService
{
    private ?string $apiKey;
    private ?string $apiSecret;
    private ?string $fromNumber;
    private string $apiUrl = 'https://rest.nexmo.com/sms/json';

    public function __construct()
    {
        $this->apiKey     = config('services.vonage.api_key');
        $this->apiSecret  = config('services.vonage.api_secret');
        $this->fromNumber = config('services.vonage.from');
    }

    public function sendVerificationCode(string $phone, int $code): bool
    {
        $message = "Your Smarter Every Hour verification code is: {$code}. Enter this on the settings page to enable SMS notifications.";
        return $this->send($phone, $message);
    }

    public function sendToUser(string $phone, string $message, string $learnUrl): bool
    {
        $suffix = ' ' . $learnUrl;
        $full   = $message . $suffix;

        if (mb_strlen($full) > 160) {
            $maxBody   = 160 - mb_strlen($suffix) - 1;
            $smsText   = mb_substr($message, 0, $maxBody) . '…' . $suffix;
        } else {
            $smsText = $full;
        }

        return $this->send($phone, $smsText);
    }

    private function send(string $to, string $text): bool
    {
        try {
            if (!$this->apiKey || !$this->apiSecret) {
                Log::error('Vonage credentials not configured');
                return false;
            }

            $response = Http::asForm()->post($this->apiUrl, [
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'from'       => $this->fromNumber,
                'to'         => preg_replace('/\D/', '', $to),
                'text'       => $text,
            ]);

            if ($response->failed()) {
                Log::warning('Vonage SMS delivery failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            $msgStatus = $response->json('messages.0.status');

            if ($msgStatus !== '0') {
                Log::warning('Vonage SMS rejected', [
                    'status' => $msgStatus,
                    'error'  => $response->json('messages.0.error-text'),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Vonage SMS exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
