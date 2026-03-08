<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique()
            ->push($this->panelAccessPermissionName())
            ->filter(static fn (mixed $permission): bool => filled($permission))
            ->unique()
            ->values();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterCreate(): void
    {
        $guardName = (string) ($this->data['guard_name'] ?? Utils::getFilamentAuthGuard());
        $permissionModels = collect();

        $this->permissions->each(function ($permission) use ($permissionModels, $guardName) {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                /** @phpstan-ignore-next-line */
                'name' => $permission,
                'guard_name' => $guardName,
            ]));
        });

        $this->record->syncPermissions($permissionModels);
    }

    private function panelAccessPermissionName(): string
    {
        $name = trim((string) config('filament-shield.panel_user.name', 'panel_user'));

        return $name !== '' ? $name : 'panel_user';
    }
}
