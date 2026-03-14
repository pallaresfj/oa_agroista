<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingAttemptResource\Pages;
use App\Models\ReadingAttempt;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
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
        return Auth::check() && Auth::user()->isDocente();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student', 'teacher', 'passage', 'errors']);

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('teacher_id', Auth::id());
        }

        return $query;
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
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
