<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingPassageResource\Pages;
use App\Models\ReadingPassage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReadingPassageResource extends Resource
{
    protected static ?string $model = ReadingPassage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Lecturas';

    protected static ?string $modelLabel = 'Lectura';

    protected static ?string $pluralModelLabel = 'Lecturas';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return Auth::check() && (Auth::user()->canManageReadingOperations() || Auth::user()->isDirectivo());
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->canManageReadingOperations();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Texto de lectura')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('difficulty_level')
                            ->label('Nivel')
                            ->maxLength(50),
                        TextInput::make('word_count')
                            ->label('Palabras')
                            ->disabled()
                            ->dehydrated(false),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                        Textarea::make('content')
                            ->label('Contenido')
                            ->required()
                            ->rows(12)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('difficulty_level')
                    ->label('Nivel')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('word_count')
                    ->label('Palabras')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->since(),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Editar'),
                DeleteAction::make()->iconButton()->tooltip('Eliminar'),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadingPassages::route('/'),
            'create' => Pages\CreateReadingPassage::route('/create'),
            'edit' => Pages\EditReadingPassage::route('/{record}/edit'),
        ];
    }
}
