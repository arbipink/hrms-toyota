<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\LeaveRequest;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $departments = [
            'Human Resources',
            'IT & Engineering',
            'Finance',
            'Marketing',
            'Sales'
        ];

        $deptModels = [];

        foreach ($departments as $deptName) {
            $deptModels[] = Department::create(['name' => $deptName]);
        }

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'department_id' => null,
        ]);

        foreach ($deptModels as $department) {
            User::factory()->create([
                'name' => $department->name . ' Manager',
                'email' => strtolower(str_replace([' ', '&'], '', $department->name)) . '_manager@example.com',
                'role' => 'MANAGER',
                'department_id' => $department->id,
            ]);

            $employees = User::factory(5)->create([
                'role' => 'EMPLOYEE',
                'department_id' => $department->id,
            ]);

            foreach ($employees as $employee) {
                LeaveRequest::factory(rand(1, 3))->create([
                    'user_id' => $employee->id,
                ]);
            }
        }
    }
}
