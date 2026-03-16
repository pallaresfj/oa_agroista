<?php

namespace App\Filament\Resources;

use App\Enums\ReadingErrorType;
use App\Filament\Resources\ReadingAttemptResource\Pages;
use App\Models\ReadingAttempt;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReadingAttemptResource extends Resource
{
    protected static ?string $model = ReadingAttempt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Intentos';

    protected static ?string $modelLabel = 'Intento';

    protected static ?string $pluralModelLabel = 'Intentos';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return Auth::check() && (Auth::user()->canManageReadingOperations() || Auth::user()->isDirectivo());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student', 'teacher', 'passage', 'errors']);
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isDocente()) {
            $query->where('teacher_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen del intento')
                    ->schema([
                        TextInput::make('status')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('duration_seconds')
                            ->label('Tiempo (segundos)')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('word_count')
                            ->label('Palabras')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('words_per_minute')
                            ->label('WPM')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Errores')
                    ->description('Ajuste manual de errores registrados en el intento.')
                    ->schema([
                        Repeater::make('errors')
                            ->relationship()
                            ->schema([
                                Select::make('error_type')
                                    ->label('Tipo')
                                    ->options(ReadingErrorType::options())
                                    ->required()
                                    ->native(false),
                                TextInput::make('occurred_at_seconds')
                                    ->label('Segundo')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0),
                                TextInput::make('word_index')
                                    ->label('Palabra')
                                    ->numeric()
                                    ->minValue(1),
                                Textarea::make('comment')
                                    ->label('Comentario')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable(),
                TextColumn::make('passage.title')
                    ->label('Lectura')
                    ->limit(28)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReadingAttempt::STATUS_COMPLETED => 'success',
                        ReadingAttempt::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('duration_seconds')
                    ->label('Tiempo')
                    ->formatStateUsing(fn (int $state): string => gmdate('i:s', $state)),
                TextColumn::make('words_per_minute')
                    ->label('WPM')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('total_errors')
                    ->label('Errores')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'success' : ($state <= 3 ? 'warning' : 'danger')),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        ReadingAttempt::STATUS_IN_PROGRESS => 'En curso',
                        ReadingAttempt::STATUS_COMPLETED => 'Completado',
                        ReadingAttempt::STATUS_CANCELLED => 'Cancelado',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Ver'),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->visible(fn (ReadingAttempt $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    ->visible(fn (ReadingAttempt $record): bool => static::canDelete($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student.name')->label('Estudiante'),
                TextEntry::make('teacher.name')->label('Docente'),
                TextEntry::make('passage.title')->label('Lectura'),
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('duration_seconds')
                    ->label('Tiempo')
                    ->formatStateUsing(fn (int $state): string => gmdate('i:s', $state)),
                TextEntry::make('word_count')->label('Palabras del texto'),
                TextEntry::make('words_per_minute')->label('Palabras por minuto'),
                TextEntry::make('total_errors')->label('Errores'),
                TextEntry::make('notes')
                    ->label('Observaciones')
                    ->placeholder('Sin observaciones'),
                RepeatableEntry::make('errors')
                    ->label('Errores registrados')
                    ->schema([
                        TextEntry::make('error_type')
                            ->label('Tipo')
                            ->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                        TextEntry::make('occurred_at_seconds')->label('Segundo')->numeric(),
                        TextEntry::make('word_index')->label('Palabra')->placeholder('-'),
                        TextEntry::make('comment')->label('Comentario')->placeholder('-'),
                    ])
                    ->contained(false)
                    ->columns(4),
            ])
            ->columns(2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadingAttempts::route('/'),
            'view' => Pages\ViewReadingAttempt::route('/{record}'),
            'edit' => Pages\EditReadingAttempt::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && (Auth::user()->canManageReadingOperations() || Auth::user()->isDirectivo());
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdminEquivalent() || $user->isDirectivo()) {
            return true;
        }

        if ($user->isDocente()) {
            return (int) $record->teacher_id === (int) $user->id;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdminEquivalent()) {
            return true;
        }

        if ($user->isDocente()) {
            return (int) $record->teacher_id === (int) $user->id;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }
}
