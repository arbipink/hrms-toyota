<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Fine; //
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [ 'Human Resources', 'IT & Engineering', 'Finance', 'Marketing', 'Sales' ];
        $deptModels = [];
        foreach ($departments as $deptName) {
            $deptModels[] = Department::create(['name' => $deptName]);
        }

        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'password' => bcrypt('password'),
            'department_id' => null,
        ]);

        foreach ($deptModels as $department) {
            $manager = User::factory()->create([
                'name' => $department->name . ' Manager',
                'email' => strtolower(str_replace([' ', '&'], '', $department->name)) . '_manager@example.com',
                'role' => 'MANAGER',
                'department_id' => $department->id,
            ]);

            $this->seedAttendanceForUser($manager, $admin);

            $employees = User::factory(5)->create([
                'role' => 'EMPLOYEE',
                'department_id' => $department->id,
            ]);

            foreach ($employees as $employee) {
                LeaveRequest::factory(rand(1, 3))->create(['user_id' => $employee->id]);
                $this->seedAttendanceForUser($employee, $admin);
            }
        }
    }

    private function seedAttendanceForUser(User $user, User $admin): void
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now()->subDay();
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            if ($date->isWeekend()) continue;

            if (rand(1, 100) <= 5) {
                $attendance = Attendance::factory()->absent()->create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                ]);

            } else {
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                ]);

                if ($attendance->status === 'LATE') {
                    if (rand(1, 100) <= 20) {
                        $attendance->update([
                            'is_forgiven' => true,
                            'forgiven_by' => $admin->id,
                            'forgive_reason' => 'Public Transport Delay (Verified)',
                        ]);
                    }
                }
            }
        }
    }
}