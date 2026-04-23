<?php

namespace App\Filament\Resources\ServiceConnections;

use App\Filament\Resources\ServiceConnections\Pages\EditServiceConnection;
use App\Filament\Resources\ServiceConnections\Pages\ListServiceConnections;
use App\Models\ServiceConnection;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class ServiceConnectionResource extends Resource
{
    protected static ?string $model = ServiceConnection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Services';

    protected static ?string $pluralModelLabel = 'Services';

    protected static string|\UnitEnum|null $navigationGroup = 'Stream';

    protected static ?int $navigationSort = 3;

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => ServiceConnection::current()]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Services')
                ->tabs([
                    Tab::make('Spotify')
                        ->icon(Heroicon::OutlinedMusicalNote)
                        ->schema([
                            Section::make('Connexion Spotify')
                                ->icon(Heroicon::OutlinedMusicalNote)
                                ->description('Connecte ton compte Spotify pour afficher le titre en cours de lecture dans le ticker.')
                                ->schema([
                                    Toggle::make('spotify_enabled')
                                        ->label('Activer Spotify')
                                        ->live()
                                        ->default(false),

                                    Grid::make(2)->schema([
                                        TextInput::make('spotify_client_id')
                                            ->label('Client ID Spotify')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('xxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                            ->maxLength(255),
                                        TextInput::make('spotify_client_secret')
                                            ->label('Client Secret Spotify')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('xxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                            ->maxLength(255),
                                    ]),

                                    Placeholder::make('spotify_status')
                                        ->label('Statut de connexion')
                                        ->content(fn ($record) => $record?->isSpotifyConnected()
                                            ? '✅ Spotify connecté — token actif depuis le '.($record->spotify_connected_at?->format('d/m/Y à H:i') ?? '?')
                                            : '⚠️ Non connecté — sauvegarde tes clés puis clique "Connecter Spotify"'),

                                    Placeholder::make('spotify_instructions')
                                        ->label('Instructions')
                                        ->content(
                                            '1. Crée une app sur developer.spotify.com → Dashboard'."\n".
                                            '2. Dans les Redirect URIs, ajoute : '.config('app.url').'/spotify/callback'."\n".
                                            '3. Copie le Client ID et Client Secret ci-dessus'."\n".
                                            '4. Clique "Sauvegarder" puis "Connecter Spotify"'
                                        ),
                                ]),
                        ]),

                    Tab::make('Météo')
                        ->icon(Heroicon::OutlinedCloud)
                        ->schema([
                            Section::make('OpenWeatherMap')
                                ->icon(Heroicon::OutlinedCloud)
                                ->description('Affiche la météo en temps réel dans le ticker. Clé API gratuite sur openweathermap.org.')
                                ->schema([
                                    Toggle::make('weather_enabled')
                                        ->label('Activer la météo')
                                        ->live()
                                        ->default(false),

                                    Grid::make(3)->schema([
                                        TextInput::make('weather_city')
                                            ->label('Ville')
                                            ->default('Paris')
                                            ->maxLength(100),
                                        TextInput::make('weather_units')
                                            ->label('Unité')
                                            ->default('metric')
                                            ->helperText('metric = °C, imperial = °F')
                                            ->maxLength(10),
                                        TextInput::make('weather_api_key')
                                            ->label('Clé API OpenWeatherMap')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                            ->maxLength(255),
                                    ]),

                                    Placeholder::make('weather_info')
                                        ->label('')
                                        ->content('Clé gratuite disponible sur openweathermap.org → My API Keys. Rafraîchissement toutes les 15 minutes.'),
                                ]),
                        ]),

                    Tab::make('CoinGecko')
                        ->icon(Heroicon::OutlinedChartBarSquare)
                        ->schema([
                            Section::make('CoinGecko API')
                                ->icon(Heroicon::OutlinedChartBarSquare)
                                ->description('Données de prix des crypto-monnaies. La clé API est optionnelle — sans clé, le tier gratuit est utilisé (limite de requêtes plus stricte).')
                                ->schema([
                                    TextInput::make('coingecko_api_key')
                                        ->label('Clé API CoinGecko (Demo Key)')
                                        ->password()
                                        ->revealable()
                                        ->placeholder('CG-xxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                        ->maxLength(255),

                                    Placeholder::make('coingecko_info')
                                        ->label('')
                                        ->content('Clé Demo gratuite obligatoire — sans clé, les prix ne s\'affichent pas. Disponible sur coingecko.com → Developers → API (gratuit, inscription requise).'),
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
                IconColumn::make('spotify_enabled')
                    ->label('Spotify')
                    ->boolean(),
                TextColumn::make('spotify_client_id')
                    ->label('Client ID')
                    ->formatStateUsing(fn ($state) => $state ? '••••••••' : '—'),
                IconColumn::make('weather_enabled')
                    ->label('Météo')
                    ->boolean(),
                TextColumn::make('weather_city')
                    ->label('Ville météo'),
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
            'index' => ListServiceConnections::route('/'),
            'edit' => EditServiceConnection::route('/{record}/edit'),
        ];
    }
}
