<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;

class ParkingStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Parking Status Overview';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $currentlyParked = Vehicle::whereNull('checkout_time')->count();
        $checkedOut = Vehicle::whereNotNull('checkout_time')->count();

        return [
            'datasets' => [
                [
                    'data' => [$currentlyParked, $checkedOut],
                    'backgroundColor' => ['#fbbf24', '#34d399'],
                ],
            ],
            'labels' => ['Currently Parked', 'Checked Out'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // or 'pie'
    }
}
