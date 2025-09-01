<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ParkingDesk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-square-2-stack';

    protected static ?string $navigationLabel = 'Parking Desk';

    protected static ?string $title = 'Parking Desk';

    protected static string $view = 'filament.pages.parking-desk';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user ? $user->can('page_ParkingDesk') : false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('page_ParkingDesk'), 403);
    }
}
