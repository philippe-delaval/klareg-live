<?php

namespace App\Filament\Resources\ServiceConnections\Pages;

use App\Filament\Resources\ServiceConnections\ServiceConnectionResource;
use App\Models\ServiceConnection;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class EditServiceConnection extends EditRecord
{
    protected static string $resource = ServiceConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectSpotify')
                ->label('Connecter Spotify')
                ->icon(Heroicon::OutlinedMusicalNote)
                ->color('success')
                ->url(route('spotify.redirect'))
                ->visible(fn () => (bool) ServiceConnection::current()->spotify_client_id),

            Action::make('disconnectSpotify')
                ->label('Déconnecter Spotify')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Déconnecter Spotify')
                ->modalDescription('Le token Spotify sera supprimé. La musique en cours ne sera plus affichée dans le ticker.')
                ->action(function (): void {
                    $record = ServiceConnection::current();
                    $record->update([
                        'spotify_refresh_token' => null,
                        'spotify_connected_at' => null,
                    ]);
                    Cache::forget('ticker:spotify');
                    Cache::forget('spotify:access_token:'.md5($record->spotify_refresh_token ?? ''));
                    Notification::make()->title('Spotify déconnecté')->success()->send();
                    $this->refreshFormData(['spotify_refresh_token', 'spotify_connected_at']);
                })
                ->visible(fn () => (bool) ServiceConnection::current()->isSpotifyConnected()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Paramètres services sauvegardés';
    }
}
