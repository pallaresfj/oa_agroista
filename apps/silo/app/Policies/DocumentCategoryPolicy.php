<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;

class DocumentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document_category');
    }

    public function view(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('view_document_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document_category');
    }

    public function update(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('update_document_category');
    }

    public function delete(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('delete_document_category');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_document_category');
    }

    public function restore(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('restore_document_category');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_document_category');
    }

    public function forceDelete(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('force_delete_document_category');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_document_category');
    }

    public function replicate(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('replicate_document_category');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_document_category');
    }
}
