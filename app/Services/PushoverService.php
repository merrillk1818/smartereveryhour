<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushoverService
{
    private ?string $appToken;
    private string $apiUrl = 'https://api.pushover.net/1/messages.json';

    public function __construct()
    {
        $this->appToken = config('services.pushover.app_token');
    }

    public function sendToUser(User $user, string $message, string $learnUrl): bool
    {
        if (!$user->pushover_user_key) {
            return false;
        }

        return $this->post($user->pushover_user_key, $message, $learnUrl, 'Chat about this', $user->id);
    }

    public function sendPin(string $userKey, int $pin): bool
    {
        return $this->post(
            $userKey,
            "Your Smarter Every Hour verification PIN is: {$pin}\n\nEnter this code on the settings page to connect your account.",
            null,
            null,
            null
        );
    }

    private function post(string $userKey, string $message, ?string $url, ?string $urlTitle, ?int $userId): bool
    {
        try {
            if (!$this->appToken) {
                Log::error('Pushover app token not configured');
                return false;
            }

            $payload = [
                'token'   => $this->appToken,
                'user'    => $userKey,
                'title'   => 'Smarter Every Hour',
                'message' => $message,
            ];

            if ($url) {
                $payload['url']       = $url;
                $payload['url_title'] = $urlTitle ?? 'Open';
            }

            $response = Http::asForm()->post($this->apiUrl, $payload);

            if ($response->failed()) {
                Log::warning('Pushover delivery failed', [
                    'user_id' => $userId,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Pushover exception', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
