<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

class CustomAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        if (method_exists($record, 'getFilamentAvatarUrl')) {
            $avatarUrl = $record->getFilamentAvatarUrl();

            if (filled($avatarUrl)) {
                return $avatarUrl;
            }
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode((string) ($record->name ?? 'Usuario'));
    }
}
