<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailyCheckinsChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Vehicle Check-ins';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Vehicle::select(
            DB::raw('DATE(checkin_time) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->whereNotNull('checkin_time')
            ->where('checkin_time', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Check-ins',
                    'data' => array_values($data->toArray()),
                ],
            ],
            'labels' => array_keys($data->toArray()),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // or 'line'
    }
}
