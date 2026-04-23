<?php

namespace App\Filament\Resources\ServiceConnections\Pages;

use App\Filament\Resources\ServiceConnections\ServiceConnectionResource;
use App\Models\ServiceConnection;
use Filament\Resources\Pages\ListRecords;

class ListServiceConnections extends ListRecords
{
    protected static string $resource = ServiceConnectionResource::class;

    public function mount(): void
    {
        $record = ServiceConnection::current();
        $this->redirect(ServiceConnectionResource::getUrl('edit', ['record' => $record]));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
