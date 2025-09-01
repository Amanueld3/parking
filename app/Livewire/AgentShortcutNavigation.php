<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AgentShortcutNavigation extends Widget
{
    protected static string $view = 'livewire.agent-shortcut-navigation';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user ? $user->can('widget_AgentShortcutNavigation') : false;
    }
}
