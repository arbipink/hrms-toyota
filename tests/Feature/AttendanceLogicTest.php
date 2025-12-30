<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Filament\Widgets\AttendanceWidget;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Fine;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttendanceLogicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_user_as_late_and_fines_them_after_9_am()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 09:30:00'));
        
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockIn')
            ->assertNotified('Clocked In Successfully');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'LATE',
            'late_minutes' => 30,
        ]);

        $this->assertDatabaseHas('fines', [
            'user_id' => $user->id,
            'amount' => 50000,
            'reason' => 'Late arrival (30 minutes)',
        ]);
    }

    #[Test]
    public function it_marks_user_present_before_9_am()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:55:00'));

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockIn');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'PRESENT',
            'late_minutes' => 0,
        ]);

        $this->assertDatabaseMissing('fines', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_prevents_early_clock_out_on_weekdays()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id, 
            'date' => now(), 
            'clock_in_time' => now()
        ]);

        Carbon::setTestNow(Carbon::parse('2025-01-01 15:59:00'));

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockOut')
            ->assertNotified('Too Early to Clock Out'); 

        Carbon::setTestNow(Carbon::parse('2025-01-01 16:01:00'));

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockOut')
            ->assertNotified('Clocked Out Successfully');

        $this->assertNotNull($attendance->refresh()->clock_out_time);
    }

    #[Test]
    public function it_handles_saturday_clock_out_rule()
    {
        Carbon::setTestNow(Carbon::parse('2025-01-04 08:00:00'));
        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id, 
            'date' => now(), 
            'clock_in_time' => now()
        ]);

        Carbon::setTestNow(Carbon::parse('2025-01-04 12:59:00'));

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockOut')
            ->assertNotified('Too Early to Clock Out');

        Carbon::setTestNow(Carbon::parse('2025-01-04 13:01:00'));

        Livewire::actingAs($user)
            ->test(AttendanceWidget::class)
            ->call('clockOut')
            ->assertNotified('Clocked Out Successfully');
    }

    #[Test]
    public function command_marks_absent_users_at_4pm()
    {
        $user = User::factory()->create(['role' => 'EMPLOYEE']);

        Carbon::setTestNow(Carbon::parse('2025-01-01 16:05:00'));

        $this->artisan('attendance:mark-absent')
             ->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'ABSENT',
        ]);

        $this->assertDatabaseHas('fines', [
            'user_id' => $user->id,
            'amount' => 50000,
            'reason' => 'Absent without leave',
        ]);
    }
}
