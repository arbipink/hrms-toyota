<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Fine;

class MarkAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark employees as ABSENT if they have not clocked in by 4 PM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Carbon::now()->isSunday()) {
            $this->info('Today is Sunday. No absences marked.');
            return;
        }

        $today = Carbon::today();

        $absentUsers = User::where('role', '!=', 'ADMIN')
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->get();

        foreach ($absentUsers as $user) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'status' => 'ABSENT',
                'notes' => 'System generated: No clock-in by 4 PM',
            ]);
            
            $this->info("Marked {$user->name} as ABSENT.");
        }
    }
}
