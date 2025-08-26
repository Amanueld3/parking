<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class VehicleStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // auto refresh every 30 sec
    protected static bool $isLazy = false;
    protected static ?int $sort = 1;
    protected static ?string $maxHeight = '250px';

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $totalVehicles = Vehicle::count();

        $checkedInToday = Vehicle::whereDate('checkin_time', $today)->count();

        $currentlyParked = Vehicle::whereNull('checkout_time')->count();

        $averageDuration = Vehicle::whereNotNull('checkout_time')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, checkin_time, checkout_time)) as avg_minutes'))
            ->value('avg_minutes');

        $avgDurationText = $averageDuration
            ? round($averageDuration / 60, 1) . ' hrs'
            : 'N/A';

        return [
            Stat::make('Total Vehicles', number_format($totalVehicles))
                ->icon('heroicon-o-truck')
                ->color('primary'),

            Stat::make('Checked-in Today', number_format($checkedInToday))
                ->description('Today')
                ->icon('heroicon-o-calendar')
                ->color('success'),

            Stat::make('Currently Parked', number_format($currentlyParked))
                ->description('No checkout yet')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Avg Parking Duration', $avgDurationText)
                ->description('Completed checkouts')
                ->icon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}
