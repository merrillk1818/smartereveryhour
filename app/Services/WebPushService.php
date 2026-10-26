<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }

    public function sendToUser(User $user, string $title, string $body, string $url): bool
    {
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => '/icons/icon-192.png',
            'badge' => '/icons/badge-96.png',
        ]);

        $sent = false;
        $toDelete = [];

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'contentEncoding' => 'aes128gcm',
                'keys'            => [
                    'p256dh' => $sub->p256dh,
                    'auth'   => $sub->auth,
                ],
            ]);

            try {
                $report = $this->webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sent = true;
                } elseif ($report->isSubscriptionExpired()) {
                    $toDelete[] = $sub->id;
                } else {
                    Log::warning('Push notification failed', [
                        'user_id' => $user->id,
                        'reason'  => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('WebPush exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        if ($toDelete) {
            PushSubscription::whereIn('id', $toDelete)->delete();
        }

        return $sent;
    }
}
