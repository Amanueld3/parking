<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CheckoutParking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static ?string $title = 'Checkout Parking';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.checkout-parking';
}
