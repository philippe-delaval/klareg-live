<?php

namespace App\Filament\Resources\TickerSettings;

use App\Events\BroadcastOverlayUpdate;
use App\Filament\Resources\TickerSettings\Pages\EditTickerSetting;
use App\Filament\Resources\TickerSettings\Pages\ListTickerSettings;
use App\Models\TickerSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TickerSettingResource extends Resource
{
    protected static ?string $model = TickerSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Ticker';

    protected static ?string $modelLabel = 'Ticker';

    protected static ?string $pluralModelLabel = 'Ticker';

    protected static string|\UnitEnum|null $navigationGroup = 'Stream';

    protected static ?int $navigationSort = 2;

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => TickerSetting::current()]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Ticker')
                ->tabs([
                    Tab::make('Messages')
                        ->icon(Heroicon::OutlinedChatBubbleBottomCenter)
                        ->schema([
                            Section::make('Messages personnalisés')
                                ->icon(Heroicon::OutlinedPencilSquare)
                                ->description('Messages affichés dans le ticker. Chaque message peut être limité à une scène spécifique.')
                                ->schema([
                                    Repeater::make('messages')
                                        ->label('')
                                        ->schema([
                                            TextInput::make('text')
                                                ->label('Message')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpan(3),
                                            Select::make('scene')
                                                ->label('Scène')
                                                ->options([
                                                    'all' => 'Toutes les scènes',
                                                    'gaming' => 'Gaming',
                                                    'just-chatting' => 'Just Chatting',
                                                    'screen-share' => 'Screen Share',
                                                    'starting-soon' => 'Starting Soon',
                                                    'brb' => 'BRB',
                                                ])
                                                ->default('all')
                                                ->selectablePlaceholder(false),
                                            Toggle::make('enabled')
                                                ->label('Actif')
                                                ->default(true)
                                                ->inline(false),
                                        ])
                                        ->columns(5)
                                        ->defaultItems(0)
                                        ->addActionLabel('+ Ajouter un message')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['text'] ?? null)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Messages planifiés')
                                ->icon(Heroicon::OutlinedCalendar)
                                ->description('Messages affichés uniquement durant une fenêtre de temps donnée.')
                                ->schema([
                                    Repeater::make('scheduled_messages')
                                        ->label('')
                                        ->schema([
                                            TextInput::make('message')
                                                ->label('Message')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpan(2),
                                            DateTimePicker::make('starts_at')
                                                ->label('Début')
                                                ->seconds(false),
                                            DateTimePicker::make('ends_at')
                                                ->label('Fin')
                                                ->seconds(false)
                                                ->after('starts_at'),
                                        ])
                                        ->columns(4)
                                        ->defaultItems(0)
                                        ->addActionLabel('+ Ajouter un message planifié')
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['message'] ?? null)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('File prioritaire')
                                ->icon(Heroicon::OutlinedBellAlert)
                                ->description('Messages urgents insérés en tête de file, affichés en priorité jusqu\'à expiration.')
                                ->schema([
                                    Repeater::make('priority_messages')
                                        ->label('')
                                        ->schema([
                                            TextInput::make('message')
                                                ->label('Message')
                                                ->required()
                                                ->maxLength(500)
                                                ->columnSpan(2),
                                            DateTimePicker::make('expires_at')
                                                ->label('Expiration')
                                                ->seconds(false),
                                        ])
                                        ->columns(3)
                                        ->defaultItems(0)
                                        ->addActionLabel('+ Ajouter message prioritaire')
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['message'] ?? null)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Données Live')
                        ->icon(Heroicon::OutlinedSignal)
                        ->schema([
                            Section::make('Météo')
                                ->icon(Heroicon::OutlinedCloud)
                                ->description('Affiche la météo en temps réel dans le ticker.')
                                ->schema([
                                    Toggle::make('weather_enabled')
                                        ->label('Activer la météo')
                                        ->live()
                                        ->default(false),
                                    Placeholder::make('weather_service_info')
                                        ->label('')
                                        ->content('La clé API et la ville se configurent dans la page Services (menu de gauche).'),
                                ]),

                            Section::make('Musique (Spotify)')
                                ->icon(Heroicon::OutlinedMusicalNote)
                                ->description('Affiche le titre en cours de lecture sur Spotify.')
                                ->schema([
                                    Toggle::make('music_enabled')
                                        ->label('Activer la musique')
                                        ->live()
                                        ->default(false),
                                    Placeholder::make('spotify_service_info')
                                        ->label('')
                                        ->content('La connexion Spotify se gère dans la page Services (menu de gauche) via le bouton "Gérer les services".'),
                                ]),

                            Section::make('Événements Twitch dans le ticker')
                                ->icon(Heroicon::OutlinedUserGroup)
                                ->description('Annonce les nouveaux followers et subs directement dans le ticker.')
                                ->schema([
                                    Toggle::make('twitch_events_enabled')
                                        ->label('Activer les événements Twitch')
                                        ->live()
                                        ->default(false),
                                    Grid::make(2)->schema([
                                        Toggle::make('twitch_events_follow')
                                            ->label('Nouveaux follows')
                                            ->default(true),
                                        Toggle::make('twitch_events_sub')
                                            ->label('Nouveaux subs')
                                            ->default(true),
                                    ]),
                                ]),

                            Section::make('Crypto / Bourse')
                                ->icon(Heroicon::OutlinedChartBarSquare)
                                ->description('Affiche les cours de crypto-monnaies en temps réel (via CoinGecko, gratuit et sans clé).')
                                ->schema([
                                    Toggle::make('crypto_enabled')
                                        ->label('Activer les cryptos')
                                        ->live()
                                        ->default(false),
                                    Grid::make(2)->schema([
                                        TagsInput::make('crypto_symbols')
                                            ->label('Symboles (BTC, ETH, SOL…)')
                                            ->suggestions(['BTC', 'ETH', 'SOL', 'ADA', 'XRP', 'BNB', 'DOGE', 'MATIC', 'DOT', 'AVAX'])
                                            ->placeholder('Ajouter un symbole'),
                                        TextInput::make('crypto_refresh_minutes')
                                            ->label('Rafraîchissement (minutes)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(60)
                                            ->default(5),
                                    ]),
                                ]),

                            Section::make('Stats du stream')
                                ->icon(Heroicon::OutlinedEye)
                                ->description('Affiche le nombre de viewers en direct dans le ticker.')
                                ->schema([
                                    Toggle::make('stats_enabled')
                                        ->label('Afficher les stats viewers')
                                        ->default(false),
                                ]),

                            Section::make('Compte à rebours dans le ticker')
                                ->icon(Heroicon::OutlinedClock)
                                ->description('Affiche un compte à rebours jusqu\'à un événement (tournoi, prochain live…).')
                                ->schema([
                                    Toggle::make('countdown_ticker_enabled')
                                        ->label('Activer le compte à rebours')
                                        ->live()
                                        ->default(false),
                                    Grid::make(2)->schema([
                                        TextInput::make('countdown_ticker_label')
                                            ->label('Label')
                                            ->placeholder('Prochain tournoi')
                                            ->maxLength(100),
                                        DateTimePicker::make('countdown_ticker_target')
                                            ->label('Date / heure cible')
                                            ->seconds(false),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Interactions Chat')
                        ->icon(Heroicon::OutlinedChatBubbleLeft)
                        ->schema([
                            Section::make('Commande chat')
                                ->icon(Heroicon::OutlinedCommandLine)
                                ->description('Permet à toi ou tes modos d\'envoyer un message dans le ticker directement depuis le chat Twitch.')
                                ->schema([
                                    Toggle::make('chat_command_enabled')
                                        ->label('Activer la commande chat')
                                        ->live()
                                        ->default(false),
                                    TextInput::make('chat_command_keyword')
                                        ->label('Commande déclencheur')
                                        ->placeholder('!ticker')
                                        ->default('!ticker')
                                        ->helperText('Usage : !ticker Mon message ici → envoie dans le ticker pendant 30 minutes')
                                        ->maxLength(50),
                                ]),

                            Section::make('Mise en avant de messages chat')
                                ->icon(Heroicon::OutlinedStar)
                                ->description('Fonctionnalité prévue — les modos pourront marquer un message du chat pour l\'envoyer dans le ticker.')
                                ->schema([
                                    Placeholder::make('chat_highlight_info')
                                        ->label('')
                                        ->content('Disponible prochainement : un bouton dans l\'interface de chat permettra aux modérateurs d\'envoyer n\'importe quel message dans le ticker en un clic.'),
                                ]),
                        ]),

                    Tab::make('Contrôle')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->schema([
                            Section::make('Général')
                                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                                ->schema([
                                    Grid::make(2)->schema([
                                        Toggle::make('ticker_enabled')
                                            ->label('Bandeau activé')
                                            ->default(true),
                                        TextInput::make('ticker_speed')
                                            ->label('Vitesse (secondes par cycle)')
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->default(60)
                                            ->helperText('Plus le nombre est faible, plus le défilement est rapide'),
                                    ]),
                                ]),

                            Section::make('Activation par scène')
                                ->icon(Heroicon::OutlinedTv)
                                ->description('Détermine sur quelles scènes le bandeau est visible.')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Toggle::make('scene_gaming_enabled')
                                            ->label('Gaming')
                                            ->default(true),
                                        Toggle::make('scene_chatting_enabled')
                                            ->label('Just Chatting')
                                            ->default(true),
                                        Toggle::make('scene_screenshare_enabled')
                                            ->label('Screen Share')
                                            ->default(true),
                                        Toggle::make('scene_starting_enabled')
                                            ->label('Starting Soon')
                                            ->default(true),
                                        Toggle::make('scene_brb_enabled')
                                            ->label('BRB')
                                            ->default(false),
                                    ]),
                                ]),

                            Section::make('Mode Urgence')
                                ->icon(Heroicon::OutlinedExclamationTriangle)
                                ->description('En mode urgence, le ticker affiche uniquement ce message en rouge clignotant. Toutes les autres sources sont ignorées.')
                                ->schema([
                                    Toggle::make('emergency_enabled')
                                        ->label('Activer le mode urgence')
                                        ->live()
                                        ->default(false),
                                    Grid::make(2)->schema([
                                        Textarea::make('emergency_message')
                                            ->label('Message d\'urgence')
                                            ->maxLength(500)
                                            ->rows(2),
                                        ColorPicker::make('emergency_color')
                                            ->label('Couleur d\'urgence')
                                            ->default('#FF4444'),
                                    ]),
                                    Placeholder::make('emergency_tip')
                                        ->label('')
                                        ->content('Conseil : utilisez le bouton "Mode Urgence" en haut de page pour activer instantanément sans sauvegarder le formulaire.'),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('ticker_enabled')
                    ->label('Actif')
                    ->boolean(),
                IconColumn::make('emergency_enabled')
                    ->label('Urgence')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),
                IconColumn::make('weather_enabled')
                    ->label('Météo')
                    ->boolean(),
                TextColumn::make('crypto_enabled')
                    ->label('Crypto')
                    ->formatStateUsing(fn ($record) => $record->crypto_enabled ? '✓ '.implode(', ', $record->crypto_symbols ?? []) : '—'),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickerSettings::route('/'),
            'edit' => EditTickerSetting::route('/{record}/edit'),
        ];
    }
}
