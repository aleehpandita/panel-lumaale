<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Filament\Resources\DestinationResource\RelationManagers;
use App\Models\Destination;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
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

            // FileUpload::make('main_image_path')
            //     ->label('Imagen principal')
            //     ->disk('s3')
            //     ->directory('destinations')
            //     ->image()
            //     ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            //     ->maxSize(4096)
            //     ->columnSpanFull()
            //     ->reactive()
            //     ->required()
            //     ->dehydrated(true)
            //     ->saveUploadedFileUsing(function (UploadedFile $file): string {
            //         // Guarda DIRECTO en S3 en /destinations y devuelve el path
            //         return $file->storePublicly('destinations', 's3');
            //     }),
            FileUpload::make('main_image_path')
                ->label('Imagen principal')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(4096)
                ->columnSpanFull()
                ->dehydrated(true)
                ->saveUploadedFileUsing(function (UploadedFile $file): string {
                    \Log::info('DESTINATION UPLOAD CALLED', [
                        'original' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    // Fuerza un upload directo a S3 para confirmar que el servidor sí puede escribir
                    Storage::disk('s3')->put(
                        'destinations/__FORCED_TEST.webp',
                        file_get_contents($file->getRealPath())
                    );

                    return 'destinations/__FORCED_TEST.webp';
                }),
        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image_path')
                    ->label('Imagen')
                    ->disk('s3')
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
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
