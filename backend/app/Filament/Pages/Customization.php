<?php

namespace App\Filament\Pages;

use App\Models\OverlaySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
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

    public function mount(): void
    {
        $setting = OverlaySetting::current();
        $this->form->fill([
            'starting_soon_bg_style' => $setting->starting_soon_bg_style ?? 'aurora',
            'starting_title'         => $setting->starting_title,
            'brb_message'            => $setting->brb_message,
            'accent_color'           => $setting->accent_color ?? '#5B7FFF',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Starting Soon — Fond animé')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->description('Choisis l\'animation affichée en fond de la scène "Starting Soon".')
                    ->schema([
                        Select::make('starting_soon_bg_style')
                            ->label('Style d\'animation')
                            ->options([
                                'aurora'        => 'Aurora — blobs colorés + radar + particules',
                                'warp'          => 'Warp — étoiles en hyperespace',
                                'constellation' => 'Constellation — réseau neuronal',
                                'mission'       => 'Mission Control — télémétrie NASA',
                                'synthwave'     => 'Synthwave — grille 80s rétro',
                            ])
                            ->native(false)
                            ->required()
                            ->default('aurora'),
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

                Section::make('Couleur d\'accent')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->schema([
                        ColorPicker::make('accent_color')
                            ->label('Couleur principale')
                            ->default('#5B7FFF'),
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
