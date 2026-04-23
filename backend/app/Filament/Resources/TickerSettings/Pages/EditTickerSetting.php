<?php

namespace App\Filament\Resources\TickerSettings\Pages;

use App\Events\BroadcastOverlayUpdate;
use App\Filament\Resources\TickerSettings\TickerSettingResource;
use App\Models\TickerSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use App\Filament\Resources\ServiceConnections\ServiceConnectionResource;

class EditTickerSetting extends EditRecord
{
    protected static string $resource = TickerSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageServices')
                ->label('Gérer les services')
                ->icon(Heroicon::OutlinedPuzzlePiece)
                ->color('gray')
                ->url(fn () => ServiceConnectionResource::getUrl('edit', ['record' => \App\Models\ServiceConnection::current()])),

            Action::make('emergency')
                ->label('Mode Urgence')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->modalHeading('Activer le mode urgence')
                ->modalDescription('Le ticker affichera uniquement ce message en priorité sur tous les overlays.')
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
                    $record = TickerSetting::current();
                    $record->update([
                        'emergency_enabled' => true,
                        'emergency_message' => $data['message'],
                        'emergency_color' => $data['color'] ?? '#FF4444',
                    ]);
                    Cache::forget('ticker:compiled:all');
                    try {
                        BroadcastOverlayUpdate::dispatch([
                            'type' => 'ticker_emergency',
                            'enabled' => true,
                            'message' => $data['message'],
                            'color' => $data['color'] ?? '#FF4444',
                        ]);
                    } catch (\Throwable) {}
                    Notification::make()
                        ->title('Mode urgence activé sur tous les overlays')
                        ->danger()
                        ->send();
                    $this->refreshFormData(['emergency_enabled', 'emergency_message', 'emergency_color']);
                }),

            Action::make('pushMessage')
                ->label('Push Message')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('warning')
                ->modalHeading('Envoyer un message prioritaire')
                ->modalDescription('Le message sera affiché en tête du ticker pendant la durée choisie.')
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
                    $record = TickerSetting::current();
                    $priority = $record->priority_messages ?? [];
                    $priority[] = [
                        'message' => $data['message'],
                        'expires_at' => now()->addMinutes((int) ($data['expires_minutes'] ?? 30))->toIso8601String(),
                    ];
                    $record->update(['priority_messages' => $priority]);
                    Cache::forget('ticker:compiled:all');
                    try {
                        BroadcastOverlayUpdate::dispatch([
                            'type' => 'ticker_push',
                            'message' => $data['message'],
                            'icon' => 'ph:bell-ringing',
                        ]);
                    } catch (\Throwable) {}
                    Notification::make()
                        ->title('Message envoyé dans le ticker')
                        ->warning()
                        ->send();
                    $this->refreshFormData(['priority_messages']);
                }),

            Action::make('clearEmergency')
                ->label('Effacer Urgence')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Désactiver le mode urgence')
                ->modalDescription('Le ticker reprendra son fonctionnement normal.')
                ->action(function (): void {
                    $record = TickerSetting::current();
                    $record->update(['emergency_enabled' => false]);
                    Cache::forget('ticker:compiled:all');
                    try {
                        BroadcastOverlayUpdate::dispatch([
                            'type' => 'ticker_emergency',
                            'enabled' => false,
                        ]);
                    } catch (\Throwable) {}
                    Notification::make()
                        ->title('Mode urgence désactivé')
                        ->success()
                        ->send();
                    $this->refreshFormData(['emergency_enabled']);
                })
                ->visible(fn () => (bool) TickerSetting::current()->emergency_enabled),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Paramètres ticker sauvegardés';
    }

    protected function afterSave(): void
    {
        // Cache invalidation + broadcast handled by TickerSettingObserver
    }
}
