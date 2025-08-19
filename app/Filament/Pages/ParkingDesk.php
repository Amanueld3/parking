<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ParkingDesk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-square-2-stack';

    protected static ?string $navigationLabel = 'Parking Desk';

    protected static ?string $title = 'Parking Desk';

    protected static string $view = 'filament.pages.parking-desk';
}
