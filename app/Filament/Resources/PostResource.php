<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Blog Posts';
    protected static ?string $modelLabel = 'Post';
    protected static ?string $pluralModelLabel = 'Posts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Post Information')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('locale')
                        ->label('Language')
                        ->options([
                            'en' => 'English',
                            'es' => 'Español',
                        ])
                        ->default('en')
                        ->required(),

                    Forms\Components\Textarea::make('excerpt')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Short summary for cards and SEO support.'),

                    Forms\Components\FileUpload::make('featured_image')
                        ->label('Featured Image')
                        ->image()
                        ->directory('blog/posts')
                        ->disk('public'),

                    Forms\Components\RichEditor::make('content')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'blockquote',
                            'link',
                            'undo',
                            'redo',
                        ]),
                ])
                ->columns(2),

            Forms\Components\Section::make('Publication')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                        ])
                        ->default('draft')
                        ->required(),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->seconds(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('SEO')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('seo_description')
                        ->rows(3)
                        ->maxLength(160),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'en' => 'English',
                        'es' => 'Español',
                        default => strtoupper($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'en' => 'info',
                        'es' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
