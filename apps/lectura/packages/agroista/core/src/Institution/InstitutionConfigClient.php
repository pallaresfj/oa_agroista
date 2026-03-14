<?php

namespace Agroista\Core\Institution;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class InstitutionConfigClient
{
    /**
     * @return array<string, mixed>
     */
    public function getInstitution(): array
    {
        return $this->getJson('/institution');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getApps(): array
    {
        /** @var array<int, array<string, mixed>> $apps */
        $apps = $this->getJson('/apps');

        return $apps;
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $path): array
    {
        $url = rtrim((string) config('agroista-core.institution.api_base', ''), '/').$path;

        if ($url === $path) {
            throw new RuntimeException('Missing AUTH_API_BASE / agroista-core.institution.api_base configuration.');
        }

        $timeout = max(1, (int) config('agroista-core.institution.http_timeout', 3));
        $request = Http::acceptJson()
            ->connectTimeout(min(2, $timeout))
            ->timeout($timeout)
            ->withOptions(['allow_redirects' => false]);
        $token = trim((string) config('agroista-core.institution.api_token', ''));

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get($url);

        if (! $response->successful()) {
            $message = (string) ($response->json('message') ?? $response->body());
            throw new RuntimeException(sprintf(
                'Institution API error (HTTP %d): %s',
                $response->status(),
                Str::limit($message, 240)
            ));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Institution API returned a non-JSON payload.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
