<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Weekly Attendance Trend';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::now()->subDays($daysAgo);

            if ($date->isSunday()) {
                return null;
            }

            $counts = Attendance::whereDate('date', $date->format('Y-m-d'))
                ->selectRaw("
                    count(case when status = 'PRESENT' then 1 end) as present_count,
                    count(case when status = 'LATE' then 1 end) as late_count,
                    count(case when status = 'ABSENT' then 1 end) as absent_count
                ")
                ->first();
            
            return [
                'date' => $date->format('M d'),
                'present' => $counts->present_count ?? 0,
                'late' => $counts->late_count ?? 0,
                'absent' => $counts->absent_count ?? 0,
            ];
        })->filter();;

        return [
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $data->pluck('present'),
                    'borderColor' => '#22c55e', // Green
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Late',
                    'data' => $data->pluck('late'),
                    'borderColor' => '#eab308', // Yellow/Amber
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Absent',
                    'data' => $data->pluck('absent'),
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return Auth::user()->isAdmin();
    }
}