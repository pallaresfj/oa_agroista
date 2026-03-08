<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class EditProfile extends BaseEditProfile
{
    protected function getRedirectUrl(): ?string
    {
        return route('dashboard');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar')
            ->url(route('dashboard'))
            ->color('gray');
    }

    protected function getAvatarFormComponent(): Component
    {
        return FileUpload::make('avatar_path')
            ->label('Fotografía')
            ->image()
            ->avatar()
            ->disk('public')
            ->directory('avatars')
            ->visibility('public')
            ->maxSize(2048)
            ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file));
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                $this->getAvatarFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
