<?php

namespace App\Filament\Resources\OverlaySettings;

use App\Events\BroadcastOverlayUpdate;
use App\Filament\Resources\OverlaySettings\Pages\CreateOverlaySetting;
use App\Filament\Resources\OverlaySettings\Pages\EditOverlaySetting;
use App\Filament\Resources\OverlaySettings\Pages\ListOverlaySettings;
use App\Models\OverlaySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OverlaySettingResource extends Resource
{
    protected static ?string $model = OverlaySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static ?string $navigationLabel = 'Overlay';

    protected static ?string $modelLabel = 'paramètre overlay';

    protected static ?string $pluralModelLabel = 'Paramètres Overlay';

    protected static string|\UnitEnum|null $navigationGroup = 'Stream';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chaîne')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->schema([
                        TextInput::make('channel_name')
                            ->label('Nom de la chaîne')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('twitch_channel_id')
                            ->label('Twitch Channel ID')
                            ->maxLength(50)
                            ->helperText('ID numérique de la chaîne Twitch (nécessaire pour EventSub)'),
                    ]),

                Section::make('Apparition')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->schema([
                        TextInput::make('starting_title')
                            ->label('Titre de démarrage')
                            ->maxLength(100),
                        TextInput::make('brb_message')
                            ->label('Message BRB')
                            ->maxLength(100),
                        TextInput::make('accent_color')
                            ->label('Couleur d\'accent')
                            ->type('color')
                            ->default('#5B7FFF'),
                    ]),

                Section::make('Stream')
                    ->icon(Heroicon::OutlinedVideoCamera)
                    ->schema([
                        TextInput::make('stream_title')
                            ->label('Titre du stream')
                            ->maxLength(100),
                        TextInput::make('stream_category')
                            ->label('Catégorie')
                            ->maxLength(50),
                        TextInput::make('next_stream')
                            ->label('Prochain live')
                            ->maxLength(100),
                        Toggle::make('next_stream_enabled')
                            ->label('Afficher le prochain live')
                            ->default(true),
                    ]),

                Section::make('Objectifs')
                    ->icon(Heroicon::OutlinedTrophy)
                    ->schema([
                        Toggle::make('goal_sub_enabled')
                            ->label('Afficher objectif subs')
                            ->default(true),
                        Toggle::make('goal_follower_enabled')
                            ->label('Afficher objectif followers')
                            ->default(true),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sub_goal')
                                    ->label('Objectif subs')
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('sub_current')
                                    ->label('Subs actuels')
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('follower_goal')
                                    ->label('Objectif followers')
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('follower_current')
                                    ->label('Followers actuels')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                    ]),

                Section::make('Alertes')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_follow_enabled')
                                    ->label('Follow activé')
                                    ->default(true),
                                TextInput::make('alert_follow_duration')
                                    ->label('Durée Follow (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_sub_enabled')
                                    ->label('Sub activé')
                                    ->default(true),
                                TextInput::make('alert_sub_duration')
                                    ->label('Durée Sub (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_resub_enabled')
                                    ->label('Resub activé')
                                    ->default(true),
                                TextInput::make('alert_resub_duration')
                                    ->label('Durée Resub (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_giftsub_enabled')
                                    ->label('Gift Sub activé')
                                    ->default(true),
                                TextInput::make('alert_giftsub_duration')
                                    ->label('Durée Gift Sub (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_bits_enabled')
                                    ->label('Bits activé')
                                    ->default(true),
                                TextInput::make('alert_bits_duration')
                                    ->label('Durée Bits (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                                TextInput::make('alert_bits_min_amount')
                                    ->label('Bits minimum')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_raid_enabled')
                                    ->label('Raid activé')
                                    ->default(true),
                                TextInput::make('alert_raid_duration')
                                    ->label('Durée Raid (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                                TextInput::make('alert_raid_min_viewers')
                                    ->label('Viewers minimum')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_donation_enabled')
                                    ->label('Don activé')
                                    ->default(true),
                                TextInput::make('alert_donation_duration')
                                    ->label('Durée Don (s)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('alert_hype_train_enabled')
                                    ->label('Hype Train activé')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Chat')
                    ->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->schema([
                        Toggle::make('chat_enabled')
                            ->label('Afficher le chat')
                            ->default(true),
                        TextInput::make('chat_max_messages')
                            ->label('Messages max')
                            ->numeric()
                            ->minValue(1)
                            ->default(50),
                    ]),

                Section::make('Musique')
                    ->icon(Heroicon::OutlinedMusicalNote)
                    ->schema([
                        Toggle::make('now_playing_enabled')
                            ->label('Afficher la musique en cours')
                            ->default(true),
                        TextInput::make('now_playing_track')
                            ->label('Titre en cours')
                            ->maxLength(100),
                        TextInput::make('now_playing_artist')
                            ->label('Artiste')
                            ->maxLength(100),
                    ]),

                Section::make('Compte à rebours & BRB')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('countdown_minutes')
                                    ->label('Minutes (démarrage)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(5),
                                TextInput::make('countdown_seconds')
                                    ->label('Secondes (démarrage)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                TextInput::make('brb_duration_minutes')
                                    ->label('Durée BRB (min)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(5),
                            ]),
                    ]),

                Section::make('Ticker')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->schema([
                        Textarea::make('current_ticker')
                            ->label('Message courant')
                            ->rows(2),
                        KeyValue::make('ticker_messages')
                            ->label('Messages du ticker')
                            ->keyLabel('Index')
                            ->valueLabel('Message'),
                    ]),

                Section::make('Réseaux sociaux')
                    ->icon(Heroicon::OutlinedLink)
                    ->schema([
                        KeyValue::make('socials')
                            ->label('')
                            ->keyLabel('Plateforme')
                            ->valueLabel('URL'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('channel_name')
                    ->label('Chaîne')
                    ->searchable(),
                TextColumn::make('stream_title')
                    ->label('Titre')
                    ->limit(30),
                ColorColumn::make('accent_color')
                    ->label('Accent'),
                TextColumn::make('sub_current')
                    ->label('Subs')
                    ->formatStateUsing(fn ($record) => $record->sub_current.'/'.$record->sub_goal),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                // ── Test Alert Actions ──
                Action::make('testFollow')
                    ->label('Test Follow')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->color('info')
                    ->action(fn () => self::testAlert('follow', ['user' => 'TestUser'])),
                Action::make('testSub')
                    ->label('Test Sub')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->action(fn () => self::testAlert('sub', ['user' => 'TestUser', 'message' => 'Tier 1'])),
                Action::make('testRaid')
                    ->label('Test Raid')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->color('purple')
                    ->action(fn () => self::testAlert('raid', ['user' => 'TestRaider', 'viewers' => 420])),
                Action::make('testBits')
                    ->label('Test Bits')
                    ->icon(Heroicon::OutlinedBolt)
                    ->color('warning')
                    ->action(fn () => self::testAlert('bits', ['user' => 'TestUser', 'amount' => 500])),
                Action::make('testDonation')
                    ->label('Test Don')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->action(fn () => self::testAlert('donation', ['user' => 'TestUser', 'amount' => '$25', 'message' => 'Great stream!'])),
            ]);
    }

    public static function testAlert(string $type, array $extra = []): void
    {
        $payload = array_merge(['type' => $type, 'source' => 'admin-test'], $extra);

        try {
            BroadcastOverlayUpdate::dispatch($payload);
        } catch (\Throwable) {}

        Notification::make()
            ->title("Alerte test envoyée : {$type}")
            ->success()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOverlaySettings::route('/'),
            'create' => CreateOverlaySetting::route('/create'),
            'edit' => EditOverlaySetting::route('/{record}/edit'),
        ];
    }
}
