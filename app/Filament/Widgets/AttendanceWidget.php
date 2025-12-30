<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class AttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-widget';

    protected int | string | array $columnSpan = 'full';

    public ?Attendance $todayAttendance = null;
    public string $currentTime = '';

    public function mount()
    {
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();
            
        $this->currentTime = Carbon::now()->format('H:i');
    }

    public function clockIn()
    {
        $now = Carbon::now();

        if ($now->isSunday()) {
            Notification::make()
                ->title('Attendance Disabled')
                ->body('Attendance is not recorded on Sundays.')
                ->danger()
                ->send();
            return;
        }

        $shiftStart = Carbon::parse('09:00:00');
        $status = 'PRESENT';
        $lateMinutes = 0;

        if ($now->gt($shiftStart)) {
            $status = 'LATE';
            $lateMinutes = abs($now->diffInMinutes($shiftStart, false));
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'date' => $now->toDateString(),
            'clock_in_time' => $now->toTimeString(),
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'ip_address' => request()->ip(),
        ]);

        Notification::make()
            ->title('Clocked In Successfully')
            ->body($status === 'LATE' ? "You are late by {$lateMinutes} minutes." : "You are on time.")
            ->success()
            ->send();

        $this->refreshData();
    }

    public function clockOut()
    {
        if (!$this->todayAttendance) {
            return;
        }

        $now = Carbon::now();

        if ($now->isSaturday()) {
            $minClockOutTime = $now->copy()->setTime(13, 0, 0);
        } else {
            $minClockOutTime = $now->copy()->setTime(16, 0, 0);
        }

        if ($now->lessThan($minClockOutTime)) {
            $formattedTime = $minClockOutTime->format('H:i');
            
            Notification::make()
                ->title('Too Early to Clock Out')
                ->body("Clock out time for today starts at {$formattedTime}. You cannot leave yet.")
                ->danger()
                ->send();
                
            return;
        }

        $this->todayAttendance->update([
            'clock_out_time' => $now->toTimeString(),
        ]);

        Notification::make()
            ->title('Clocked Out Successfully')
            ->body('Have a good rest!')
            ->success()
            ->send();

        $this->refreshData();
    }
}
