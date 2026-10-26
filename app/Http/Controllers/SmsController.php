<?php

namespace App\Http\Controllers;

use App\Services\VonageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SmsController extends Controller
{
    public function sendCode(Request $request, VonageService $vonage): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = preg_replace('/\D/', '', $request->input('phone'));

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return response()->json(['error' => 'Please enter a valid phone number including country code (e.g. 14155551234).'], 422);
        }

        $code = random_int(100000, 999999);
        $sent = $vonage->sendVerificationCode($phone, $code);

        if (!$sent) {
            return response()->json(['error' => 'Could not send SMS. Check your phone number and try again.'], 422);
        }

        Cache::put("sms_code:{$request->user()->id}", [
            'code'  => $code,
            'phone' => $phone,
        ], now()->addMinutes(10));

        return response()->json(['sent' => true]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|digits:6']);

        $cached = Cache::get("sms_code:{$request->user()->id}");

        if (!$cached || (int) $request->input('code') !== $cached['code']) {
            return response()->json(['error' => 'Incorrect code. Request a new one and try again.'], 422);
        }

        $request->user()->update(['sms_phone' => $cached['phone']]);
        Cache::forget("sms_code:{$request->user()->id}");

        return response()->json(['verified' => true]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->user()->update(['sms_phone' => null]);
        return response()->json(['disconnected' => true]);
    }
}
