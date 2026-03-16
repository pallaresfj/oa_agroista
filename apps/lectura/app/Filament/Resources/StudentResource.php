<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Estudiantes';

    protected static ?string $modelLabel = 'Estudiante';

    protected static ?string $pluralModelLabel = 'Estudiantes';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->canAny([
            'view_any_student',
            'view_student',
            'create_student',
            'update_student',
            'delete_student',
        ]);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->canAny(['view_any_student', 'view_student']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (! $user->canAny(['view_any_student', 'view_student'])) {
            return false;
        }

        return static::recordIsVisibleForUser($record, $user);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->can('create_student');
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user || ! $user->can('update_student')) {
            return false;
        }

        return static::recordIsVisibleForUser($record, $user);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user || ! $user->can('delete_student')) {
            return false;
        }

        return static::recordIsVisibleForUser($record, $user);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('course');
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('view_any_student')) {
            return $query;
        }

        if (! $user->can('view_student')) {
            return $query->whereRaw('1 = 0');
        }

        $courseIds = $user->assignedCourses()->pluck('courses.id')->all();

        if ($courseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('course_id', $courseIds);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del estudiante')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),
                        Select::make('course_id')
                            ->label('Curso')
                            ->required()
                            ->options(Course::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.name')
                    ->label('Curso')
                    ->placeholder('-'),
                TextColumn::make('reading_attempts_count')
                    ->label('Intentos')
                    ->counts('readingAttempts')
                    ->badge()
                    ->color('info'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->visible(fn (Student $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    ->visible(fn (Student $record): bool => static::canDelete($record)),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    private static function recordIsVisibleForUser(Model $record, User $user): bool
    {
        if (! $record instanceof Student) {
            return false;
        }

        if ($user->can('view_any_student')) {
            return true;
        }

        $courseIds = $user->assignedCourses()->pluck('courses.id')->all();

        if ($courseIds === []) {
            return false;
        }

        return in_array((int) $record->course_id, array_map('intval', $courseIds), true);
    }
}
