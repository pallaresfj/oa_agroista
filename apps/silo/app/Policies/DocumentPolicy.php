<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('view_document');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('update_document');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('delete_document');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_document');
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('restore_document');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_document');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->can('force_delete_document');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_document');
    }

    public function replicate(User $user, Document $document): bool
    {
        return $user->can('replicate_document');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_document');
    }
}
