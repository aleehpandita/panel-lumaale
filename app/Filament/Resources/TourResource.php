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

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del tour')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $set('slug', Str::slug($state));
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'inactive' => 'Inactive',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('city'),
                    Forms\Components\TextInput::make('duration_hours')->numeric(),
                    Forms\Components\TextInput::make('min_people')->numeric()->default(1),
                    Forms\Components\TextInput::make('max_people')->numeric(),

                    Forms\Components\Textarea::make('short_description')->columnSpanFull(),
                    Forms\Components\RichEditor::make('long_description')->columnSpanFull(),

                    Forms\Components\TextInput::make('meeting_point')->columnSpanFull(),

                    Forms\Components\Select::make('categories')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),

                    Repeater::make('included')
                        ->label('Included')
                        ->schema([
                            TextInput::make('item')
                                ->required()
                                ->placeholder('Ej: Round-trip transportation'),
                        ])
                        ->addActionLabel('Add item')
                        ->reorderable()
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    Repeater::make('not_included')
                        ->label('Not included')
                        ->schema([
                            TextInput::make('item')
                                ->required()
                                ->placeholder('Ej: Tips / gratuities'),
                        ])
                        ->addActionLabel('Add item')
                        ->reorderable()
                        ->defaultItems(0)
                        ->columnSpanFull(),

                ])
                ->columns(2),

            Forms\Components\Section::make('Imagen principal')
                ->schema([
                    Forms\Components\FileUpload::make('main_image_url')
                        ->disk('s3')
                        ->visibility('public')
                        ->directory('tours/main')
                        ->preserveFilenames(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->image()
                        ->imageEditor()
                        ->maxSize(4096),
                ]),

            Forms\Components\Section::make('Galería')
                ->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->schema([
                            Forms\Components\FileUpload::make('url')
                                ->disk('s3')
                                ->visibility('public')
                                ->directory('tours/gallery')
                                ->preserveFilenames(false)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->image()
                                ->imageEditor()
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
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('included')
                    ->label('Included')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' items' : '0 items'),

                Tables\Columns\TextColumn::make('not_included')
                    ->label('Not included')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' items' : '0 items'),
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
