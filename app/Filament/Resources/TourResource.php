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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

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

        if (isset($state[0]) && is_string($state[0])) {
            return array_values(array_filter($state));
        }

        return array_values(array_filter(array_map(
            fn ($row) => is_array($row) ? ($row['item'] ?? null) : null,
            $state
        )));
    }

    /**
     * Convierte ["A","B"] -> [{item:"A"},{item:"B"}]
     */
    protected static function stringArrayToRepeater(?array $state): array
    {
        if (!is_array($state)) return [];

        if (isset($state[0]) && is_array($state[0]) && array_key_exists('item', $state[0])) {
            return $state;
        }

        return array_map(fn ($v) => ['item' => $v], array_values(array_filter($state)));
    }
protected static function moveLocalToS3(?string $path, string $prefix): ?string
{
    if (! $path) return null;

    // ya está en S3
    if (str_starts_with($path, 'tours/')) {
        return $path;
    }

    // solo movemos uploads/...
    if (! str_starts_with($path, 'uploads/')) {
        return $path;
    }

    if (! Storage::disk('local')->exists($path)) {
        return $path;
    }

    $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
    $key = rtrim($prefix, '/') . '/' . Str::uuid() . '.' . $ext;

    $stream = Storage::disk('local')->readStream($path);

    Storage::disk('s3')->put($key, $stream, [
        'visibility' => 'public',
        'ContentType' => match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        },
    ]);

    if (is_resource($stream)) fclose($stream);

    Storage::disk('local')->delete($path);

    return $key;
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

                            $disk = Storage::disk('s3');

                            // Si S3 está privado, esto es lo correcto:
                            $url = method_exists($disk, 'temporaryUrl')
                                ? $disk->temporaryUrl($record->main_image_url, now()->addMinutes(10))
                                : $disk->url($record->main_image_url);

                            return new HtmlString('<img src="'.$url.'" style="height:80px;border-radius:8px" />');
                        }),

                    Forms\Components\FileUpload::make('main_image_url')
                        ->label('Imagen principal')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('local') // igual que Destinations
                        ->directory('uploads/tours/main') // primero local
                        ->visibility('public')
                        ->preserveFilenames(false)
                        ->dehydrated(true)
                        ->columnSpanFull(),
                ]),

           Forms\Components\Section::make('Galería')
    ->schema([
        Repeater::make('images')
            ->relationship()
            ->schema([
                Placeholder::make('current_gallery_image')
                    ->label('Imagen actual')
                    ->content(function (Get $get) {
                        $state = $get('url');
                        if (! $state) return '-';

                        if (str_starts_with($state, 'tours/')) {
                            $url = Storage::disk('s3')->url($state);
                            return new HtmlString(
                                '<img src="'.$url.'" style="max-width:160px; height:auto; border-radius:8px; border:1px solid #e5e7eb;" />'
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

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ])
            ->columns(2)
            ->defaultItems(0)

            // CREATE: mover a S3
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                $data['url'] = self::moveLocalToS3($data['url'] ?? null, 'tours/gallery');
                return $data;
            })

            // UPDATE: mover a S3
            ->mutateRelationshipDataBeforeUpdateUsing(function (array $data): array {
                $data['url'] = self::moveLocalToS3($data['url'] ?? null, 'tours/gallery');
                return $data;
            }),
    ]),

            Forms\Components\Section::make('Horarios (si aplica)')
                ->schema([
                    Repeater::make('departures')
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
                    Repeater::make('prices')
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
                Tables\Columns\TextColumn::make('title')
                    ->label('Title (ES)')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['es'] ?? $state['en'] ?? '') : (string) $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),

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