<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SchedulesRelationManager;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
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

                        Select::make('role')
                            ->label('Rol')
                            ->options(UserRole::options())
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

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

                TextColumn::make('phone')
                    ->label('TELÉFONO')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('role')
                    ->label('ROL')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color()),

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
        return [
            SchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
