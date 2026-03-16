<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingPassageResource\Pages;
use App\Models\ReadingPassage;
use App\Services\ReadingPassagePdfExporter;
use Filament\Actions\Action;
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
        $user = Auth::user();

        return (bool) $user?->canAny([
            'view_any_reading_passage',
            'view_reading_passage',
            'create_reading_passage',
            'update_reading_passage',
            'delete_reading_passage',
        ]);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->canAny(['view_any_reading_passage', 'view_reading_passage']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->canAny(['view_any_reading_passage', 'view_reading_passage']) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('create_reading_passage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('update_reading_passage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('delete_reading_passage') ?? false;
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
                Action::make('exportPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->tooltip('Exportar a PDF')
                    ->action(fn (ReadingPassage $record) => app(ReadingPassagePdfExporter::class)->download($record)),
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
