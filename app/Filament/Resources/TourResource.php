<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';

    /**
     * Convierte repeater [{item:"A"},{item:"B"}] -> ["A","B"]
     */
    protected static function repeaterToStringArray(?array $state): array
    {
        if (!is_array($state)) return [];

        // Si ya viene como ["A","B"], lo dejamos
        if (isset($state[0]) && is_string($state[0])) {
            return array_values(array_filter($state));
        }

        // Si viene como [{item:"A"}...]
        return array_values(array_filter(array_map(
            fn ($row) => is_array($row) ? ($row['item'] ?? null) : null,
            $state
        )));
    }

    /**
     * Convierte ["A","B"] -> [{item:"A"},{item:"B"}] (para que el repeater lo pinte)
     */
    protected static function stringArrayToRepeater(?array $state): array
    {
        if (!is_array($state)) return [];

        // Si ya es repeater [{item:"A"}], lo dejamos
        if (isset($state[0]) && is_array($state[0]) && array_key_exists('item', $state[0])) {
            return $state;
        }

        return array_map(fn ($v) => ['item' => $v], array_values(array_filter($state)));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del tour')
                ->schema([
                    Forms\Components\Tabs::make('Idiomas')
                        ->columnSpanFull()
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Español')
                                ->schema([
                                    TextInput::make('title.es')
                                        ->label('Título (ES)')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('slug', Str::slug((string) $state));
                                        }),

                                    Forms\Components\Textarea::make('short_description.es')
                                        ->label('Descripción corta (ES)')
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('long_description.es')
                                        ->label('Descripción larga (ES)')
                                        ->columnSpanFull(),

                                    TextInput::make('meeting_point.es')
                                        ->label('Punto de encuentro (ES)')
                                        ->columnSpanFull(),

                                    Repeater::make('included.es')
                                        ->label('Incluye (ES)')
                                        ->schema([
                                            TextInput::make('item')->required()->placeholder('Ej: Transporte redondo'),
                                        ])
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('Agregar item')
                                        ->columnSpanFull()
                                        ->afterStateHydrated(function (Repeater $component, $state) {
                                            $component->state(self::stringArrayToRepeater($state));
                                        })
                                        ->mutateDehydratedStateUsing(fn ($state) => self::repeaterToStringArray($state)),

                                    Repeater::make('not_included.es')
                                        ->label('No incluye (ES)')
                                        ->schema([
                                            TextInput::make('item')->required()->placeholder('Ej: Propinas'),
                                        ])
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('Agregar item')
                                        ->columnSpanFull()
                                        ->afterStateHydrated(function (Repeater $component, $state) {
                                            $component->state(self::stringArrayToRepeater($state));
                                        })
                                        ->mutateDehydratedStateUsing(fn ($state) => self::repeaterToStringArray($state)),
                                ]),

                            Forms\Components\Tabs\Tab::make('English')
                                ->schema([
                                    TextInput::make('title.en')
                                        ->label('Title (EN)')
                                        ->required(),

                                    Forms\Components\Textarea::make('short_description.en')
                                        ->label('Short description (EN)')
                                        ->columnSpanFull(),

                                    Forms\Components\RichEditor::make('long_description.en')
                                        ->label('Long description (EN)')
                                        ->columnSpanFull(),

                                    TextInput::make('meeting_point.en')
                                        ->label('Meeting point (EN)')
                                        ->columnSpanFull(),

                                    Repeater::make('included.en')
                                        ->label('Included (EN)')
                                        ->schema([
                                            TextInput::make('item')->required()->placeholder('e.g. Round-trip transportation'),
                                        ])
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('Add item')
                                        ->columnSpanFull()
                                        ->afterStateHydrated(function (Repeater $component, $state) {
                                            $component->state(self::stringArrayToRepeater($state));
                                        })
                                        ->mutateDehydratedStateUsing(fn ($state) => self::repeaterToStringArray($state)),

                                    Repeater::make('not_included.en')
                                        ->label('Not included (EN)')
                                        ->schema([
                                            TextInput::make('item')->required()->placeholder('e.g. Tips / gratuities'),
                                        ])
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('Add item')
                                        ->columnSpanFull()
                                        ->afterStateHydrated(function (Repeater $component, $state) {
                                            $component->state(self::stringArrayToRepeater($state));
                                        })
                                        ->mutateDehydratedStateUsing(fn ($state) => self::repeaterToStringArray($state)),
                                ]),
                        ]),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'inactive' => 'Inactive',
                        ])
                        ->required(),

                    Select::make('destination_id')
                        ->label('Destino')
                        ->relationship('destination', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('city'),
                    TextInput::make('duration_hours')->numeric(),
                    TextInput::make('min_people')->numeric()->default(1),
                    TextInput::make('max_people')->numeric(),
                    
                    Select::make('categories')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Imagen principal')
    ->schema([
        Placeholder::make('main_image_current')
            ->label('Imagen actual')
            ->content(function (?Tour $record) {
                if (! $record || ! $record->main_image_url) {
                    return '-';
                }

                $url = Storage::disk('s3')->url($record->main_image_url);

                return new HtmlString('<img src="'.$url.'" style="height:80px;border-radius:8px" />');
            }),

        FileUpload::make('main_image_url')
            ->label('Imagen principal')
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->disk('local')
            ->directory('uploads/tours/main')
            ->visibility('public')
            ->preserveFilenames(false)
            ->dehydrated(true)
            ->columnSpanFull(),
    ]),

Forms\Components\Repeater::make('images')
    ->relationship()
    ->schema([
        Placeholder::make('current_gallery_image')
            ->label('Imagen actual')
            ->content(function (Get $get) {
                $state = $get('url');
                if (! $state) return '-';

                if (str_starts_with($state, 'tours/')) {
                    $url = Storage::disk('s3')->url($state);
                    return new \Illuminate\Support\HtmlString(
                        '<img src="'.$url.'" style="max-width:160px;height:auto;border-radius:8px;border:1px solid #e5e7eb;" />'
                    );
                }

                if (str_starts_with($state, 'http')) {
                    return new \Illuminate\Support\HtmlString(
                        '<img src="'.$state.'" style="max-width:160px;height:auto;border-radius:8px;border:1px solid #e5e7eb;" />'
                    );
                }

                return '-';
            }),

        Forms\Components\FileUpload::make('url')
            ->label('Imagen')
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->disk('local')
            ->directory('uploads/tours/gallery')
            ->visibility('public')
            ->preserveFilenames(false)
            ->dehydrated(true)
            ->maxSize(4096)
            ->required(),

        Forms\Components\TextInput::make('sort_order')
            ->numeric()
            ->default(0),
    ])
    ->columns(2)
    ->defaultItems(0),

            Forms\Components\Section::make('Horarios (si aplica)')
                ->schema([
                    Forms\Components\Repeater::make('departures')
                        ->relationship()
                        ->schema([
                            Forms\Components\TimePicker::make('departure_time')->required(),
                            Forms\Components\Toggle::make('is_active')->default(true),
                        ])
                        ->columns(2)
                        ->defaultItems(0),
                ]),

            Forms\Components\Section::make('Precios (flexible)')
                ->schema([
                    Forms\Components\Repeater::make('prices')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('name')->placeholder('Tarifa base / Temporada alta'),
                            Forms\Components\DatePicker::make('start_date')->nullable(),
                            Forms\Components\DatePicker::make('end_date')->nullable(),

                            Forms\Components\TextInput::make('price_adult')->numeric()->required(),

                            Forms\Components\TextInput::make('price_child')->numeric()->nullable()
                                ->helperText('NULL = no niños, 0 = gratis, >0 = paga'),

                            Forms\Components\TextInput::make('price_infant')->numeric()->nullable()
                                ->helperText('NULL = no infantes, 0 = gratis, >0 = paga'),

                            Forms\Components\TextInput::make('currency')->default('USD')->maxLength(3),
                        ])
                        ->columns(3)
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mostramos el título ES como default en el admin
                Tables\Columns\TextColumn::make('title')
                    ->label('Title (ES)')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['es'] ?? $state['en'] ?? '') : (string) $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),

                Tables\Columns\TextColumn::make('included')
                    ->label('Included (ES)')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && (isset($state['es']) || isset($state['en']))) {
                            $list = $state['es'] ?? $state['en'] ?? [];
                            return is_array($list) ? count($list) . ' items' : '0 items';
                        }
                        return is_array($state) ? count($state) . ' items' : '0 items';
                    }),

                Tables\Columns\TextColumn::make('not_included')
                    ->label('Not included (ES)')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && (isset($state['es']) || isset($state['en']))) {
                            $list = $state['es'] ?? $state['en'] ?? [];
                            return is_array($list) ? count($list) . ' items' : '0 items';
                        }
                        return is_array($state) ? count($state) . ' items' : '0 items';
                    }),

                Tables\Columns\TextColumn::make('updated_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
