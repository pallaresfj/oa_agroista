<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->canAny([
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
        ]);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->canAny(['view_any_user', 'view_user']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->canAny(['view_any_user', 'view_user']) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('create_user') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('update_user') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('delete_user') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Usuario')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('identification_number')
                            ->label('Número de Identificación')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1),

                        Select::make('roles')
                            ->label('Rol')
                            ->relationship(
                                'roles',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('name', [
                                    User::ROLE_SUPER_ADMIN,
                                    User::ROLE_SOPORTE,
                                    User::ROLE_DIRECTIVO,
                                    User::ROLE_DOCENTE,
                                ])
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => UserRole::tryFrom((string) $record->name)?->label() ?? (string) $record->name
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->columnSpan(1),

                        Select::make('assignedCourses')
                            ->label('Cursos asignados (Docente)')
                            ->relationship(
                                'assignedCourses',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name')
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn (Get $get, ?User $record): bool => ! static::isDocenteSelection($get('roles'), $record))
                            ->helperText('Solo aplica para usuarios con rol Docente.')
                            ->columnSpan(2),

                        Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('name')
                    ->label('NOMBRE')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('CORREO')
                    ->searchable(),

                TextColumn::make('assignedCourses.name')
                    ->label('CURSOS ASIGNADOS')
                    ->badge()
                    ->listWithLineBreaks()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('role_label')
                    ->label('ROL')
                    ->badge()
                    ->color(fn (User $record) => match (true) {
                        $record->isSuperAdmin() => UserRole::SUPER_ADMIN->color(),
                        $record->isSoporte() => UserRole::SOPORTE->color(),
                        $record->isDirectivo() => UserRole::DIRECTIVO->color(),
                        $record->isDocente() => UserRole::DOCENTE->color(),
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('ACTIVO')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('CREADO')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(UserRole::options())
                    ->query(function ($query, array $data) {
                        $role = $data['value'] ?? null;

                        if (! $role) {
                            return $query;
                        }

                        return $query->whereHas('roles', fn ($shieldQuery) => $shieldQuery->where('name', $role));
                    })
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Editar'),
                DeleteAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Eliminar'),
            ])
            ->defaultSort('name')
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    private static function isDocenteSelection(mixed $rolesState, ?User $record = null): bool
    {
        if ($record?->isDocente()) {
            return true;
        }

        if (blank($rolesState)) {
            return false;
        }

        $values = collect(is_array($rolesState) ? $rolesState : [$rolesState])
            ->filter(static fn (mixed $value): bool => ! blank($value))
            ->map(static fn (mixed $value): string => (string) $value)
            ->values();

        if ($values->contains(User::ROLE_DOCENTE)) {
            return true;
        }

        $roleIds = $values
            ->filter(static fn (string $value): bool => ctype_digit($value))
            ->map(static fn (string $value): int => (int) $value)
            ->all();

        if ($roleIds === []) {
            return false;
        }

        return Role::query()
            ->whereIn('id', $roleIds)
            ->where('name', User::ROLE_DOCENTE)
            ->exists();
    }
}
