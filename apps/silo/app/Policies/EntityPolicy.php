<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_entity');
    }

    public function view(User $user, Entity $entity): bool
    {
        return $user->can('view_entity');
    }

    public function create(User $user): bool
    {
        return $user->can('create_entity');
    }

    public function update(User $user, Entity $entity): bool
    {
        return $user->can('update_entity');
    }

    public function delete(User $user, Entity $entity): bool
    {
        return $user->can('delete_entity');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_entity');
    }

    public function restore(User $user, Entity $entity): bool
    {
        return $user->can('restore_entity');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_entity');
    }

    public function forceDelete(User $user, Entity $entity): bool
    {
        return $user->can('force_delete_entity');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_entity');
    }

    public function replicate(User $user, Entity $entity): bool
    {
        return $user->can('replicate_entity');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_entity');
    }
}
