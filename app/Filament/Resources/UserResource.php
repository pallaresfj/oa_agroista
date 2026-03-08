<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?int $navigationSort = 2;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuraciones';
    protected static ?string $label = 'Usuario';
    protected static ?string $pluralLabel = 'Usuarios';

    public static function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            Grid::make(3)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->label('Nombre')
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->label('Correo electrónico')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('password')
                        ->password()
                        ->label('Contraseña')
                        ->hiddenOn('edit')
                        ->required()
                        ->maxLength(255),
                ]),
            FileUpload::make('profile_photo_path')
                ->label('Foto de perfil')
                ->image()
                ->imageEditor()
                ->directory('profile-photos')
                ->preserveFilenames()
                ->visibility('public')
                ->columnSpanFull(),
            CheckboxList::make('roles')
                ->label('Roles')
                ->columns(6)
                ->relationship('roles', 'name')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo_path')
                    ->label('Foto')
                    ->disk('public') // Define el disco desde donde se carga
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles'),
                TextColumn::make('email_verified_at')
                    ->label('Última verificación')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->searchable(),

                Filter::make('email_verified_at')
                    ->label('Verificado')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),

                Filter::make('not_verified')
                    ->label('No verificado')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->tooltip('Editar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large),
                Action::make('verify')
                    ->label('')
                    ->color('warning')
                    ->tooltip('Verificar email')
                    ->icon('heroicon-m-check-circle')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->requiresConfirmation()
                    ->action(function (User $user) {
                        $user->markEmailAsVerified();
                        $user->save();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel'),
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
