<?php

namespace App\Filament\Pages\Auth;

use Filament\Support\Enums\Width;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class Login extends \Filament\Auth\Pages\Login
{
    protected string $view = 'filament.auth.login';

    protected Width|string|null $maxWidth = '7xl';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }
}
