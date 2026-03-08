<?php

namespace App\Filament\Auth\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutResponse implements \Filament\Auth\Http\Responses\Contracts\LogoutResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $idpLogoutUrl = trim((string) config('sso.idp_logout_url', ''));
        $continueUrl = $this->normalizeUrl(Filament::getLoginUrl()) ?? url('/admin/login');

        if ($idpLogoutUrl === '') {
            return redirect()->to($continueUrl);
        }

        $targetUrl = $this->appendQuery($idpLogoutUrl, [
            'continue' => $continueUrl,
            'source' => config('sso.frontchannel_logout_client_key', config('sso.client_key', 'gestionplanes')),
        ]);

        return redirect()->away($targetUrl);
    }

    private function normalizeUrl(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        return url($trimmed);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function appendQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
