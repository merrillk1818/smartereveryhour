<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|url',
            'p256dh'   => 'required|string',
            'auth'     => 'required|string',
        ]);

        // Update existing or create new subscription for this endpoint
        Auth::user()->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'p256dh'     => $data['p256dh'],
                'auth'       => $data['auth'],
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request)
    {
        $endpoint = $request->validate(['endpoint' => 'required|string'])['endpoint'];

        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $endpoint)
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    public function vapidPublicKey()
    {
        return response()->json(['key' => config('services.vapid.public_key')]);
    }
}
