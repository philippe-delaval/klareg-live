<?php

namespace App\Filament\Pages;

use App\Models\OverlaySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Customization extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Personnalisation';

    protected static string|\UnitEnum|null $navigationGroup = 'Personnalisation';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    private const BG_STYLES = [
        'none'          => 'Aucune — fond statique du thème',
        'aurora'        => 'Aurora — blobs + radar + particules',
        'warp'          => 'Warp — étoiles en hyperespace',
        'constellation' => 'Constellation — réseau neuronal',
        'mission'       => 'Mission Control — télémétrie',
        'synthwave'     => 'Synthwave — grille 80s rétro',
    ];

    public function mount(): void
    {
        $setting = OverlaySetting::current();
        $this->form->fill([
            'overlay_theme'          => $setting->overlay_theme ?? 'default',
            'starting_soon_bg_style' => $setting->starting_soon_bg_style ?? 'aurora',
            'brb_bg_style'           => $setting->brb_bg_style ?? 'none',
            'ending_bg_style'        => $setting->ending_bg_style ?? 'none',
            'starting_title'         => $setting->starting_title,
            'brb_message'            => $setting->brb_message,
            'accent_color'           => $setting->accent_color ?? '#5B7FFF',
            'color_presets'          => $setting->color_presets ?? [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Thème général')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->description('S\'applique à toutes les scènes (starting, BRB, gaming, ending, etc.).')
                    ->schema([
                        Select::make('overlay_theme')
                            ->label('Thème')
                            ->options([
                                'default' => 'Default — Dark tech (fond sombre, condensé)',
                                'studio'  => 'Studio — Éditorial (fond blanc, serif)',
                            ])
                            ->native(false)
                            ->required()
                            ->default('default'),
                    ]),

                Section::make('Couleur d\'accent')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->description('La couleur est appliquée à toutes les animations et éléments d\'accent.')
                    ->schema([
                        ColorPicker::make('accent_color')
                            ->label('Couleur principale')
                            ->default('#5B7FFF')
                            ->live(),
                    ]),

                Section::make('Palette de couleurs mémorisées')
                    ->icon(Heroicon::OutlinedBookmark)
                    ->description('Tes couleurs favorites, accessibles rapidement.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('color_presets')
                            ->label('Presets')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(40),
                                ColorPicker::make('hex')
                                    ->label('Couleur')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->addActionLabel('Ajouter une couleur')
                            ->itemLabel(fn (array $state): ?string => ($state['name'] ?? '') . ' — ' . ($state['hex'] ?? ''))
                            ->collapsed(),
                    ]),

                Section::make('Fonds animés par scène')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->description('Choisis un fond animé distinct pour chacune des 3 scènes. Les animations s\'adaptent à la couleur d\'accent.')
                    ->schema([
                        Select::make('starting_soon_bg_style')
                            ->label('Starting Soon')
                            ->options(self::BG_STYLES)
                            ->native(false)
                            ->required()
                            ->default('aurora'),
                        Select::make('brb_bg_style')
                            ->label('BRB (Be Right Back)')
                            ->options(self::BG_STYLES)
                            ->native(false)
                            ->required()
                            ->default('none'),
                        Select::make('ending_bg_style')
                            ->label('Ending')
                            ->options(self::BG_STYLES)
                            ->native(false)
                            ->required()
                            ->default('none'),
                    ]),

                Section::make('Textes de scènes')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        TextInput::make('starting_title')
                            ->label('Titre "Starting Soon"')
                            ->maxLength(100),
                        TextInput::make('brb_message')
                            ->label('Message BRB')
                            ->maxLength(100),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        OverlaySetting::current()->update($data);

        Notification::make()
            ->title('Personnalisation enregistrée')
            ->body('Les overlays se mettent à jour automatiquement.')
            ->success()
            ->send();
    }

    public function getView(): string
    {
        return 'filament.pages.customization';
    }

    public function getTitle(): string
    {
        return 'Personnalisation';
    }
}
