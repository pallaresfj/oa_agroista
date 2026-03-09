<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DocumentVisibilityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Users with `view_all_document_states` can see all records.
     * Others only see published documents.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'can') && $user->can('view_all_document_states')) {
            return;
        }

        $builder->where('status', 'Publicado');
    }
}
