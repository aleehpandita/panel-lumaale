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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static function s3PreviewUrl(string $key): ?string
    {
        try {
            $disk = Storage::disk('s3');

            // Si el adapter soporta URLs temporales (S3 privado), úsalo
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl($key, now()->addMinutes(10));
            }

            // Si no, intenta URL normal
            if (method_exists($disk, 'url')) {
                return $disk->url($key);
            }

            // Último fallback: usar el "url" configurado del disk s3 (si existe)
            $base = config('filesystems.disks.s3.url');
            return $base ? rtrim($base, '/') . '/' . ltrim($key, '/') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normaliza lo que viene del estado (puede ser null, array, filename suelto, etc)
     * y regresa una URL lista para <img src="..."> o null.
     */
    protected static function previewUrlFromState($state, string $defaultPrefix = 'tours/gallery/'): ?string
    {
        if (blank($state)) return null;

        // A veces llega como array
        if (is_array($state)) {
            $state = $state[0] ?? null;
            if (blank($state)) return null;
        }

        // URL absoluta
        if (is_string($state) && str_starts_with($state, 'http')) {
            return $state;
        }

        if (! is_string($state)) return null;

        $key = $state;

        // Si guardaste algo tipo "gallery/xxx.webp" o "main/xxx.webp"
        if (str_starts_with($key, 'gallery/')) $key = 'tours/' . $key;
        if (str_starts_with($key, 'main/'))    $key = 'tours/' . $key;

        // Si viene solo el filename "xxx.webp", asumimos que es galería
        if (! str_contains($key, '/') && preg_match('/\.(jpe?g|png|webp)$/i', $key)) {
            $key = rtrim($defaultPrefix, '/') . '/' . $key;
        }

        // Si ya es key completo tipo "tours/..."
        if (str_starts_with($key, 'tours/')) {
            return self::s3PreviewUrl($key);
        }

        // Si por alguna razón quedó local "uploads/..." y tienes storage:link
        if (str_starts_with($key, 'uploads/')) {
            return asset('storage/' . $key); // si no hay symlink, esto dará 404 pero no revienta
        }

        return null;
    }
        protected static function s3Url(string $path): string
    {
        $disk = Storage::disk('s3');

        // Si el bucket es privado, esto genera URL firmada (lo más seguro).
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($path, now()->addMinutes(10));
        }

        return $disk->url($path);
    }

   protected static function uploadedFileUrl($file): ?string
{
    if (blank($file)) {
        return null;
    }

    // A veces llega como array
    if (is_array($file)) {
        $file = $file[0] ?? null;
        if (blank($file)) return null;
    }
    // A veces llega como TemporaryUploadedFile (objeto)
    if ($file instanceof TemporaryUploadedFile) {
        // Esto genera preview del temporal (no toca S3 final)
        return $file->temporaryUrl();
    }
    // Ya debe ser string
    if (! is_string($file)) {
        return null;
    }

    // Ya está en S3 (guardado final)
    if (str_starts_with($file, 'tours/')) {
        return Storage::disk('s3')->url($file);
    }

    // Si todavía es local (uploads/...)
    if (str_starts_with($file, 'uploads/')) {
        // OJO: si NO tienes symlink /storage, esto puede dar 404.
        // Pero por lo menos no revienta.
        return Storage::disk('local')->url($file);
    }

    // Si guardaste una URL absoluta
    if (str_starts_with($file, 'http')) {
        return $file;
    }

    return null;
}

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
                            Forms\Components\Hidden::make('id'),
                            Placeholder::make('current_gallery_image')
                                ->label('Imagen actual')
                                ->reactive()
                                ->content(function (Get $get) {
                                    $imageId = $get('id');

                                    if (! $imageId) {
                                        return '-'; // todavía no guardas ese row (nuevo)
                                    }

                                    $img = \App\Models\TourImage::find($imageId);
                                    if (! $img || blank($img->url)) {
                                        return '-';
                                    }

                                    // aquí $img->url YA debe ser "tours/gallery/xxx.webp"
                                    $path = $img->url;

                                    $disk = Storage::disk('s3');
                                    $url = method_exists($disk, 'temporaryUrl')
                                        ? $disk->temporaryUrl($path, now()->addMinutes(10))
                                        : $disk->url($path);

                                    return new HtmlString(
                                        '<img src="'.$url.'" style="max-width:160px;height:auto;border-radius:8px;border:1px solid #e5e7eb;" />'
                                    );
                                }),
                        
                            Forms\Components\FileUpload::make('url')
                                ->label('Imagen')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->disk('local') // primero local
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