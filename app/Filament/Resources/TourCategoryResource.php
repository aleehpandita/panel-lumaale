<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourCategoryResource\Pages;
use App\Filament\Resources\TourCategoryResource\RelationManagers;
use App\Models\TourCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;


class TourCategoryResource extends Resource
{
    protected static ?string $model = TourCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name.es')
                    ->label('Nombre (ES)')
                    ->required(),

                Forms\Components\TextInput::make('name.en')
                    ->label('Name (EN)')
                    ->required(),

                Forms\Components\TextInput::make('slug')
                    ->required(),
            ]);
    }


   public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->formatStateUsing(fn ($state) =>
                    is_array($state) ? ($state['es'] ?? '') : (string) $state
                ),

                \Filament\Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListTourCategories::route('/'),
            'create' => Pages\CreateTourCategory::route('/create'),
            'edit' => Pages\EditTourCategory::route('/{record}/edit'),
        ];
    }
}
