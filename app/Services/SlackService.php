<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public function sendToUser(User $user, string $message, string $learnUrl): bool
    {
        if (!$user->slack_webhook_url) {
            return false;
        }

        return $this->post($user->slack_webhook_url, $message, $learnUrl, $user->id);
    }

    public function sendTest(User $user, string $message, string $learnUrl): bool
    {
        if (!$user->slack_webhook_url) {
            return false;
        }

        return $this->post($user->slack_webhook_url, $message, $learnUrl, $user->id);
    }

    private function post(string $webhookUrl, string $message, string $learnUrl, int $userId): bool
    {
        try {
            $response = Http::post($webhookUrl, [
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*Smarter Every Hour*\n{$message}",
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' => [
                            [
                                'type'  => 'button',
                                'text'  => ['type' => 'plain_text', 'text' => 'Chat about this'],
                                'url'   => $learnUrl,
                                'style' => 'primary',
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('Slack webhook failed', ['user_id' => $userId, 'status' => $response->status(), 'body' => $response->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Slack webhook exception', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
