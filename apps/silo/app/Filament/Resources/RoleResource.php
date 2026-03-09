<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class RoleResource extends ShieldRoleResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Seguridad';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->hiddenLabel()
                    ->tooltip('Editar'),
                DeleteAction::make()
                    ->iconButton()
                    ->hiddenLabel()
                    ->tooltip('Borrar'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
