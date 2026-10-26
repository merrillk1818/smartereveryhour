<?php

namespace App\Http\Controllers;

use App\Models\NotificationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationScheduleController extends Controller
{
    public function index()
    {
        $schedule = Auth::user()->notificationSchedule;
        return view('schedule.index', compact('schedule'));
    }

    public function save(Request $request)
    {
        // Some browsers submit H:i:s — strip seconds before validation
        $request->merge([
            'start_time' => substr($request->input('start_time', ''), 0, 5),
            'end_time'   => substr($request->input('end_time', ''), 0, 5),
        ]);

        $data = $request->validate([
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i|after:start_time',
            'frequency_minutes' => 'required|integer|min:15|max:1440',
            'timezone'          => 'required|timezone',
            'days_of_week'      => 'required|array|min:1',
            'days_of_week.*'    => 'integer|between:0,6',
            'is_active'         => 'boolean',
            'pushover_enabled'  => 'boolean',
            'sms_enabled'       => 'boolean',
        ]);

        $data['is_active']        = $request->boolean('is_active', true);
        $data['pushover_enabled'] = $request->boolean('pushover_enabled', false);
        $data['sms_enabled']      = $request->boolean('sms_enabled', false);

        Auth::user()->notificationSchedule()->updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        return redirect()->route('schedule.index')->with('success', 'Settings saved.');
    }
}
