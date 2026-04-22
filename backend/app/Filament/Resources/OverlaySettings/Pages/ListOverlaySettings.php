<?php

namespace App\Filament\Resources\OverlaySettings\Pages;

use App\Filament\Resources\OverlaySettings\OverlaySettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOverlaySettings extends ListRecords
{
    protected static string $resource = OverlaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
