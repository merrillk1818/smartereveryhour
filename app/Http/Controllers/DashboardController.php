<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'curricula'  => $user->curricula()->count(),
            'topics'     => \App\Models\Topic::whereHas('curriculum', fn ($q) => $q->where('user_id', $user->id))->count(),
            'sent_today' => MessageLog::where('user_id', $user->id)->whereDate('sent_at', today())->count(),
            'sent_total' => MessageLog::where('user_id', $user->id)->count(),
        ];

        $recentLogs = MessageLog::with('topic')
            ->where('user_id', $user->id)
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get();

        $schedule   = $user->notificationSchedule;
        $curricula  = $user->curricula()->where('is_active', true)->with('topics')->get();

        return view('dashboard', compact('stats', 'recentLogs', 'schedule', 'curricula'));
    }
}
