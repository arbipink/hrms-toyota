<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Fine;

class AttendanceObserver
{
    /**
     * Handle the Attendance "saved" event.
     * This triggers on both create and update.
     */

    public function saved(Attendance $attendance): void
    {
        $shouldBeFined = false;
        $reason = '';

        if ($attendance->status === 'ABSENT') {
            $shouldBeFined = true;
            $reason = 'Absent without leave';
        } elseif ($attendance->status === 'LATE' && !$attendance->is_forgiven) {
            $shouldBeFined = true;
            $reason = "Late arrival ({$attendance->late_minutes} minutes)";
        }

        if ($shouldBeFined) {
            Fine::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                ],
                [
                    'user_id' => $attendance->user_id,
                    'amount' => 50000,
                    'reason' => $reason,
                ]
            );
        } else {
            Fine::where('attendance_id', $attendance->id)->delete();
        }
    }

    /**
     * Handle the Attendance "created" event.
     */
    public function created(Attendance $attendance): void
    {
        //
    }

    /**
     * Handle the Attendance "updated" event.
     */
    public function updated(Attendance $attendance): void
    {
        //
    }

    /**
     * Handle the Attendance "deleted" event.
     */
    public function deleted(Attendance $attendance): void
    {
        Fine::where('attendance_id', $attendance->id)->delete();
    }

    /**
     * Handle the Attendance "restored" event.
     */
    public function restored(Attendance $attendance): void
    {
        //
    }

    /**
     * Handle the Attendance "force deleted" event.
     */
    public function forceDeleted(Attendance $attendance): void
    {
        //
    }
}
