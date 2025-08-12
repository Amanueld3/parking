<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use App\Filament\Resources\PlaceResource\RelationManagers\SlotsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlace extends ViewRecord
{
    protected static string $resource = PlaceResource::class;
    public static function getRelations(): array
    {
        return [
            SlotsRelationManager::class,
        ];
    }
}
