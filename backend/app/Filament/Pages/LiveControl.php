<?php

namespace App\Filament\Pages;

use App\Events\BroadcastOverlayUpdate;
use App\Models\ServiceConnection;
use App\Models\TickerSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class LiveControl extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Live Control';

    protected static string|\UnitEnum|null $navigationGroup = 'Stream';

    protected static ?int $navigationSort = 0;

    public string $emergencyInput = '';

    public function publishEmergency(): void
    {
        $message = trim($this->emergencyInput);

        if (! $message) {
            Notification::make()->title('Le message est vide')->warning()->send();
            return;
        }

        $ticker = TickerSetting::current();
        $ticker->update([
            'emergency_enabled' => true,
            'emergency_message' => $message,
            'emergency_color'   => $ticker->emergency_color ?? '#FF4444',
        ]);

        Cache::forget('ticker:compiled:all');

        try {
            BroadcastOverlayUpdate::dispatch([
                'type'    => 'ticker_emergency',
                'enabled' => true,
                'message' => $message,
                'color'   => $ticker->emergency_color ?? '#FF4444',
            ]);
        } catch (\Throwable) {}

        $this->emergencyInput = '';

        Notification::make()->title('Urgence publiée sur tous les overlays')->danger()->send();
    }

    public function clearEmergencyInline(): void
    {
        TickerSetting::current()->update(['emergency_enabled' => false]);
        Cache::forget('ticker:compiled:all');

        try {
            BroadcastOverlayUpdate::dispatch(['type' => 'ticker_emergency', 'enabled' => false]);
        } catch (\Throwable) {}

        Notification::make()->title('Mode urgence désactivé')->success()->send();
    }

    public function getView(): string
    {
        return 'filament.pages.live-control';
    }

    public function getTitle(): string
    {
        return 'Live Control';
    }

    public function getStats(): array
    {
        $ticker = TickerSetting::current();
        $svc = ServiceConnection::current();

        return [
            'viewers'              => (int) Cache::get('overlay:viewer_count', 0),
            'ticker_enabled'       => (bool) $ticker->ticker_enabled,
            'emergency_enabled'    => (bool) $ticker->emergency_enabled,
            'emergency_message'    => $ticker->emergency_message,
            'emergency_color'      => $ticker->emergency_color ?? '#FF4444',
            'music_enabled'        => (bool) $ticker->music_enabled,
            'stats_enabled'        => (bool) $ticker->stats_enabled,
            'twitch_events_enabled' => (bool) $ticker->twitch_events_enabled,
            'spotify_connected'    => $svc->isSpotifyConnected(),
            'now_playing'          => Cache::get('ticker:spotify'),
        ];
    }

    // ── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            $this->pushMessageAction(),
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function toggleTickerAction(): Action
    {
        $ticker = TickerSetting::current();

        return Action::make('toggleTicker')
            ->label($ticker->ticker_enabled ? 'Désactiver le ticker' : 'Activer le ticker')
            ->icon($ticker->ticker_enabled ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
            ->color($ticker->ticker_enabled ? 'warning' : 'success')
            ->size('xl')
            ->action(function (): void {
                $ticker = TickerSetting::current();
                $newValue = ! $ticker->ticker_enabled;
                $ticker->update(['ticker_enabled' => $newValue]);
                Cache::forget('ticker:compiled:all');
                try {
                    BroadcastOverlayUpdate::dispatch([
                        'type' => 'ticker_settings_update',
                        'ticker_enabled' => $newValue,
                    ]);
                } catch (\Throwable) {}
                Notification::make()
                    ->title($newValue ? 'Ticker activé' : 'Ticker désactivé')
                    ->success()
                    ->send();
            });
    }

    public function pushMessageAction(): Action
    {
        return Action::make('pushMessage')
            ->label('Push Message')
            ->icon(Heroicon::OutlinedBellAlert)
            ->color('warning')
            ->modalHeading('Envoyer un message prioritaire')
            ->modalDescription('Le message s\'affiche en tête du ticker pendant la durée choisie.')
            ->modalSubmitActionLabel('Envoyer')
            ->form([
                TextInput::make('message')
                    ->label('Message')
                    ->required()
                    ->maxLength(500),
                TextInput::make('expires_minutes')
                    ->label('Durée d\'affichage (minutes)')
                    ->numeric()
                    ->default(30)
                    ->minValue(1)
                    ->maxValue(1440),
            ])
            ->action(function (array $data): void {
                $ticker = TickerSetting::current();
                $priority = $ticker->priority_messages ?? [];
                $priority[] = [
                    'message'    => $data['message'],
                    'expires_at' => now()->addMinutes((int) ($data['expires_minutes'] ?? 30))->toIso8601String(),
                ];
                $ticker->update(['priority_messages' => $priority]);
                Cache::forget('ticker:compiled:all');
                try {
                    BroadcastOverlayUpdate::dispatch([
                        'type'    => 'ticker_push',
                        'message' => $data['message'],
                        'icon'    => 'ph:bell-ringing',
                    ]);
                } catch (\Throwable) {}
                Notification::make()->title('Message envoyé dans le ticker')->warning()->send();
            });
    }

    public function emergencyAction(): Action
    {
        return Action::make('emergency')
            ->label('Mode Urgence')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('danger')
            ->modalHeading('Activer le mode urgence')
            ->modalDescription('Tous les overlays afficheront uniquement ce message.')
            ->modalSubmitActionLabel('Activer')
            ->form([
                Textarea::make('message')
                    ->label('Message d\'urgence')
                    ->required()
                    ->maxLength(500)
                    ->rows(2),
                ColorPicker::make('color')
                    ->label('Couleur')
                    ->default('#FF4444'),
            ])
            ->action(function (array $data): void {
                $ticker = TickerSetting::current();
                $ticker->update([
                    'emergency_enabled' => true,
                    'emergency_message' => $data['message'],
                    'emergency_color'   => $data['color'] ?? '#FF4444',
                ]);
                Cache::forget('ticker:compiled:all');
                try {
                    BroadcastOverlayUpdate::dispatch([
                        'type'    => 'ticker_emergency',
                        'enabled' => true,
                        'message' => $data['message'],
                        'color'   => $data['color'] ?? '#FF4444',
                    ]);
                } catch (\Throwable) {}
                Notification::make()->title('Mode urgence activé sur tous les overlays')->danger()->send();
            });
    }

    public function clearEmergencyAction(): Action
    {
        return Action::make('clearEmergency')
            ->label('Effacer Urgence')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Désactiver le mode urgence')
            ->modalDescription('Le ticker reprendra son fonctionnement normal.')
            ->visible(fn () => (bool) TickerSetting::current()->emergency_enabled)
            ->action(function (): void {
                TickerSetting::current()->update(['emergency_enabled' => false]);
                Cache::forget('ticker:compiled:all');
                try {
                    BroadcastOverlayUpdate::dispatch([
                        'type'    => 'ticker_emergency',
                        'enabled' => false,
                    ]);
                } catch (\Throwable) {}
                Notification::make()->title('Mode urgence désactivé')->success()->send();
            });
    }

    public function toggleMusicAction(): Action
    {
        $ticker = TickerSetting::current();

        return Action::make('toggleMusic')
            ->label($ticker->music_enabled ? 'Désactiver la musique' : 'Activer la musique')
            ->icon(Heroicon::OutlinedMusicalNote)
            ->color($ticker->music_enabled ? 'gray' : 'success')
            ->action(function (): void {
                $ticker = TickerSetting::current();
                $newValue = ! $ticker->music_enabled;
                $ticker->update(['music_enabled' => $newValue]);
                Cache::forget('ticker:compiled:all');
                Notification::make()
                    ->title($newValue ? 'Musique activée dans le ticker' : 'Musique désactivée')
                    ->success()
                    ->send();
            });
    }

    public function toggleStatsAction(): Action
    {
        $ticker = TickerSetting::current();

        return Action::make('toggleStats')
            ->label($ticker->stats_enabled ? 'Masquer les stats viewers' : 'Afficher les stats viewers')
            ->icon(Heroicon::OutlinedEye)
            ->color($ticker->stats_enabled ? 'gray' : 'success')
            ->action(function (): void {
                $ticker = TickerSetting::current();
                $newValue = ! $ticker->stats_enabled;
                $ticker->update(['stats_enabled' => $newValue]);
                Cache::forget('ticker:compiled:all');
                Notification::make()
                    ->title($newValue ? 'Stats viewers affichées' : 'Stats viewers masquées')
                    ->success()
                    ->send();
            });
    }

    public function toggleTwitchEventsAction(): Action
    {
        $ticker = TickerSetting::current();

        return Action::make('toggleTwitchEvents')
            ->label($ticker->twitch_events_enabled ? 'Désactiver les alertes Twitch' : 'Activer les alertes Twitch')
            ->icon(Heroicon::OutlinedUserGroup)
            ->color($ticker->twitch_events_enabled ? 'gray' : 'success')
            ->action(function (): void {
                $ticker = TickerSetting::current();
                $newValue = ! $ticker->twitch_events_enabled;
                $ticker->update(['twitch_events_enabled' => $newValue]);
                Notification::make()
                    ->title($newValue ? 'Alertes Twitch activées' : 'Alertes Twitch désactivées')
                    ->success()
                    ->send();
            });
    }
}
