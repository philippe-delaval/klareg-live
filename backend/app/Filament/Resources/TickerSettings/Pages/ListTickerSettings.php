<?php

namespace App\Filament\Resources\TickerSettings\Pages;

use App\Filament\Resources\TickerSettings\TickerSettingResource;
use App\Models\TickerSetting;
use Filament\Resources\Pages\ListRecords;

class ListTickerSettings extends ListRecords
{
    protected static string $resource = TickerSettingResource::class;

    public function mount(): void
    {
        $record = TickerSetting::current();
        $this->redirect(TickerSettingResource::getUrl('edit', ['record' => $record]));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
