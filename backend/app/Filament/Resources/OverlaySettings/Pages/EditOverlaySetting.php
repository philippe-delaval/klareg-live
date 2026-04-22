<?php

namespace App\Filament\Resources\OverlaySettings\Pages;

use App\Filament\Resources\OverlaySettings\OverlaySettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOverlaySetting extends EditRecord
{
    protected static string $resource = OverlaySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
