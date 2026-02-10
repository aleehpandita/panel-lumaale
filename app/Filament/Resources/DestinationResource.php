<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Models\Destination;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Str;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('slug', Str::slug((string) $state));
                }),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true),

            // ✅ Preview de la imagen ya guardada (S3)
            Placeholder::make('current_image')
            ->label('Imagen actual')
            ->content(function (?Destination $record) {
                if (! $record?->main_image_path) {
                    return new HtmlString('<span style="opacity:.6">Sin imagen</span>');
                }

                // Si tu bucket/objetos son públicos:
                $url = Storage::disk('s3')->url($record->main_image_path);

                // Si son privados, dime y te paso la versión con URL firmada sin usar temporaryUrl()
                // (con AWS SDK presigned request).

                return new HtmlString(
                    '<img src="' . e($url) . '" style="max-height:140px;border-radius:10px;display:block" />'
                );
            })
            ->columnSpanFull(),

            FileUpload::make('main_image_path')
                ->label('Imagen principal')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('local') // ✅ local para evitar Livewire tmp en S3
                ->directory('uploads/destinations') // ✅ se guarda en storage/app/uploads/destinations
                ->visibility('public')  
                ->preserveFilenames(false)
                ->dehydrated(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Como el archivo final queda en S3, generamos URL temporal para verla aunque sea private
            ImageColumn::make('main_image_path')
                ->label('Imagen')
                ->getStateUsing(function ($record) {
                    if (! $record->main_image_path) {
                        return null;
                    }

                    // Si por alguna razón quedó un path local viejo, no intentamos S3
                    if (str_starts_with($record->main_image_path, 'uploads/')) {
                        return null;
                    }

                    // Genera URL temporal (10 min) desde S3
                    return Storage::disk('s3')->temporaryUrl(
                        $record->main_image_path,
                        now()->addMinutes(10)
                    );
                })
                ->height(50),

            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->searchable()
                ->sortable(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit'   => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}