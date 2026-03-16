<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Course;
use App\Models\Student;
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
        return Auth::check() && Auth::user()->canManageReadingOperations();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('course');
        $user = Auth::user();

        if (! $user || $user->isAdminEquivalent()) {
            return $query;
        }

        if ($user->isDocente()) {
            $courseIds = $user->assignedCourses()->pluck('courses.id')->all();

            if ($courseIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('course_id', $courseIds);
        }

        return $query->whereRaw('1 = 0');
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
                EditAction::make()->iconButton()->tooltip('Editar'),
                DeleteAction::make()->iconButton()->tooltip('Eliminar'),
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
}
