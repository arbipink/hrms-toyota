<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DepartmentStaffChart extends ChartWidget
{
    protected ?string $heading = 'Staff per Department';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $departments = Department::withCount('users')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Staff Count',
                    'data' => $departments->pluck('users_count'),
                    'backgroundColor' => [
                        '#3b82f6', '#ef4444', '#eab308', '#22c55e', '#8b5cf6', '#ec4899'
                    ],
                ],
            ],
            'labels' => $departments->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return Auth::user()->isAdmin();
    }
}