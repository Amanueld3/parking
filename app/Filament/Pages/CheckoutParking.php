<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CheckoutParking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static ?string $title = 'Checkout Parking';

    protected static string $view = 'filament.pages.checkout-parking';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user ? $user->can('page_CheckoutParking') : false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('page_CheckoutParking'), 403);
    }
}
