<?php

namespace App\Filament\Resources\EcosystemApps;

use App\Filament\Resources\EcosystemApps\Pages\ManageEcosystemApps;
use App\Models\OAuthClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;

class EcosystemAppResource extends Resource
{
    protected static ?string $model = OAuthClient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Apps ecosistema';

    protected static ?string $modelLabel = 'App ecosistema';

    protected static ?string $pluralModelLabel = 'Apps ecosistema';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->helperText('Solo minúsculas, números y guiones.'),
                TextInput::make('base_url')
                    ->label('URL base')
                    ->required()
                    ->url()
                    ->maxLength(2048),
                TagsInput::make('redirect_uris')
                    ->label('Redirect URIs')
                    ->placeholder('https://app.iedagropivijay.edu.co/sso/callback')
                    ->required()
                    ->afterStateHydrated(function (TagsInput $component, mixed $state): void {
                        $component->state(static::normalizeStringArrayState($state));
                    })
                    ->helperText('Cada URI debe ser exacta, sin wildcard y con HTTPS (excepto localhost/127.0.0.1 en HTTP).'),
                TagsInput::make('frontchannel_logout_uris')
                    ->label('Frontchannel logout URIs')
                    ->placeholder('https://app.iedagropivijay.edu.co/sso/frontchannel-logout')
                    ->afterStateHydrated(function (TagsInput $component, mixed $state): void {
                        $component->state(static::normalizeStringArrayState($state));
                    }),
                TagsInput::make('scopes')
                    ->label('Scopes permitidos')
                    ->required()
                    ->default(['openid', 'email', 'profile'])
                    ->afterStateHydrated(function (TagsInput $component, mixed $state): void {
                        $component->state(static::normalizeStringArrayState($state));
                    })
                    ->suggestions(array_keys(config('openid.passport.tokens_can', []))),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
                Toggle::make('revoked')
                    ->label('Revocada')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_url')
                    ->label('URL base')
                    ->wrap(),
                TextColumn::make('id')
                    ->label('Client ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('revoked')
                    ->label('Revocada')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('redirect_uris')
                    ->label('Redirect URIs')
                    ->formatStateUsing(static fn (mixed $state): string => implode("\n", static::normalizeStringArrayState($state)))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('copy_client_id')
                    ->label('Copiar Client ID')
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->iconButton()
                    ->tooltip('Copiar Client ID')
                    ->alpineClickHandler(function (OAuthClient $record): string {
                        $clientIdJs = Js::from((string) $record->getKey());
                        $successMessageJs = Js::from('Client ID copiado');
                        $failureMessageJs = Js::from('No se pudo copiar Client ID');

                        return <<<JS
                            window.navigator.clipboard.writeText({$clientIdJs})
                                .then(() => new window.FilamentNotification().title({$successMessageJs}).success().send())
                                .catch(() => new window.FilamentNotification().title({$failureMessageJs}).danger().send())
                        JS;
                    }),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar app ecosistema')
                    ->using(fn (OAuthClient $record, array $data): OAuthClient => static::updateApp($record, $data)),
                Action::make('regenerate_secret')
                    ->label('Regenerar secret')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->iconButton()
                    ->tooltip('Regenerar secret')
                    ->requiresConfirmation()
                    ->action(function (OAuthClient $record): void {
                        $secret = static::regenerateSecret($record);

                        Notification::make()
                            ->title('Nuevo client secret generado')
                            ->body("Guárdalo ahora. Secret: {$secret}")
                            ->success()
                            ->send();
                    }),
                Action::make('generate_frontchannel_secret')
                    ->label('Generar frontchannel secret')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->iconButton()
                    ->tooltip('Generar SSO_FRONTCHANNEL_LOGOUT_SECRETS')
                    ->schema([
                        TextInput::make('client_key')
                            ->label('Clave del cliente')
                            ->required()
                            ->maxLength(64)
                            ->default(fn (OAuthClient $record): string => static::suggestFrontchannelClientKey($record))
                            ->helperText('Ejemplo: planes (solo minúsculas, números y guiones).'),
                    ])
                    ->modalHeading('Generar frontchannel secret')
                    ->modalDescription('Este valor no se guarda en base de datos. Cópialo y agrégalo manualmente en .env.')
                    ->modalSubmitActionLabel('Generar')
                    ->action(function (OAuthClient $record, array $data): void {
                        $entry = static::generateFrontchannelSecretEntry((string) ($data['client_key'] ?? ''));

                        Notification::make()
                            ->title('Frontchannel secret generado')
                            ->body("Cliente: {$record->name}\nEntrada .env: {$entry}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('toggle_revoked')
                    ->label(fn (OAuthClient $record): string => $record->revoked ? 'Activar' : 'Revocar')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->iconButton()
                    ->tooltip(fn (OAuthClient $record): string => $record->revoked ? 'Activar app' : 'Revocar app')
                    ->color(fn (OAuthClient $record): string => $record->revoked ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(fn (OAuthClient $record): bool => $record->update(['revoked' => ! $record->revoked])),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar app ecosistema')
                    ->modalDescription('Se eliminará la app y sus tokens asociados.')
                    ->successNotificationTitle('App ecosistema eliminada')
                    ->using(fn (OAuthClient $record): bool => static::deleteApp($record)),
            ])
            ->toolbarActions([])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEcosystemApps::route('/'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createApp(array $data): OAuthClient
    {
        $redirectUris = static::sanitizeRedirectUris($data['redirect_uris'] ?? []);
        $slug = static::sanitizeSlug((string) ($data['slug'] ?? ''), '');

        /** @var ClientRepository $clients */
        $clients = app(ClientRepository::class);

        /** @var OAuthClient $client */
        $client = $clients->createAuthorizationCodeGrantClient(
            name: trim((string) $data['name']),
            redirectUris: $redirectUris,
            confidential: true,
        );

        $client->forceFill([
            'slug' => $slug,
            'base_url' => rtrim((string) ($data['base_url'] ?? ''), '/'),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'frontchannel_logout_uris' => static::sanitizeFrontchannelLogoutUris($data['frontchannel_logout_uris'] ?? []),
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => static::sanitizeScopes($data['scopes'] ?? []),
            'revoked' => (bool) ($data['revoked'] ?? false),
        ])->save();

        return $client->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateApp(OAuthClient $record, array $data): OAuthClient
    {
        $record->forceFill([
            'name' => trim((string) ($data['name'] ?? $record->name)),
            'slug' => static::sanitizeSlug((string) ($data['slug'] ?? $record->slug), $record->getKey()),
            'base_url' => rtrim((string) ($data['base_url'] ?? $record->base_url), '/'),
            'is_active' => (bool) ($data['is_active'] ?? $record->is_active),
            'redirect_uris' => static::sanitizeRedirectUris($data['redirect_uris'] ?? []),
            'frontchannel_logout_uris' => static::sanitizeFrontchannelLogoutUris($data['frontchannel_logout_uris'] ?? []),
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => static::sanitizeScopes($data['scopes'] ?? []),
            'revoked' => (bool) ($data['revoked'] ?? false),
        ])->save();

        return $record;
    }

    public static function regenerateSecret(OAuthClient $record): string
    {
        $secret = Str::random(40);
        $record->secret = $secret;
        $record->save();

        return $secret;
    }

    public static function generateFrontchannelSecretEntry(string $clientKey): string
    {
        $normalizedKey = static::sanitizeFrontchannelClientKey($clientKey);
        $secret = bin2hex(random_bytes(32));

        return "{$normalizedKey}|{$secret}";
    }

    /**
     * @return array<int, string>
     */
    public static function sanitizeRedirectUris(mixed $uris): array
    {
        $hosts = config('sso.allowed_redirect_hosts', []);
        $insecureHosts = config('sso.insecure_redirect_hosts', []);

        $normalized = collect(static::normalizeStringArrayState($uris))
            ->map(static fn (string $uri): string => trim($uri))
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'redirect_uris' => 'Debes registrar al menos una redirect URI.',
            ]);
        }

        foreach ($normalized as $uri) {
            if (str_contains($uri, '*')) {
                throw ValidationException::withMessages([
                    'redirect_uris' => "No se permiten wildcards en redirect URI: {$uri}",
                ]);
            }

            $parts = parse_url($uri);

            if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                throw ValidationException::withMessages([
                    'redirect_uris' => "La redirect URI no es válida: {$uri}",
                ]);
            }

            $scheme = mb_strtolower((string) $parts['scheme']);
            $host = mb_strtolower((string) $parts['host']);

            if ($scheme !== 'https' && ! ($scheme === 'http' && in_array($host, $insecureHosts, true))) {
                throw ValidationException::withMessages([
                    'redirect_uris' => "La redirect URI debe usar HTTPS (solo localhost puede usar HTTP): {$uri}",
                ]);
            }

            if (! in_array($host, $hosts, true)) {
                throw ValidationException::withMessages([
                    'redirect_uris' => "Host no permitido en redirect URI: {$uri}",
                ]);
            }
        }

        return $normalized->unique()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function sanitizeFrontchannelLogoutUris(mixed $uris): array
    {
        $hosts = config('sso.post_logout_redirect_hosts', []);
        $insecureHosts = config('sso.insecure_redirect_hosts', []);

        $normalized = collect(static::normalizeStringArrayState($uris))
            ->map(static fn (string $uri): string => trim($uri))
            ->filter()
            ->values();

        foreach ($normalized as $uri) {
            $parts = parse_url($uri);

            if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                throw ValidationException::withMessages([
                    'frontchannel_logout_uris' => "La URI de frontchannel no es válida: {$uri}",
                ]);
            }

            $scheme = mb_strtolower((string) $parts['scheme']);
            $host = mb_strtolower((string) $parts['host']);

            if ($scheme !== 'https' && ! ($scheme === 'http' && in_array($host, $insecureHosts, true))) {
                throw ValidationException::withMessages([
                    'frontchannel_logout_uris' => "La URI de frontchannel debe usar HTTPS (solo localhost puede usar HTTP): {$uri}",
                ]);
            }

            if (! in_array($host, $hosts, true)) {
                throw ValidationException::withMessages([
                    'frontchannel_logout_uris' => "Host no permitido en frontchannel URI: {$uri}",
                ]);
            }
        }

        return $normalized->unique()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function sanitizeScopes(mixed $scopes): array
    {
        $allowedScopes = array_keys(config('openid.passport.tokens_can', []));

        $normalized = collect(static::normalizeStringArrayState($scopes))
            ->map(static fn (string $scope): string => trim($scope))
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            $normalized = collect(['openid', 'email', 'profile']);
        }

        $invalidScopes = $normalized->reject(static fn (string $scope): bool => in_array($scope, $allowedScopes, true));

        if ($invalidScopes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scopes' => 'Scopes inválidos: '.$invalidScopes->implode(', '),
            ]);
        }

        return $normalized->unique()->values()->all();
    }

    public static function deleteApp(OAuthClient $record): bool
    {
        return DB::transaction(function () use ($record): bool {
            $accessTokenIds = DB::table('oauth_access_tokens')
                ->where('client_id', $record->getKey())
                ->pluck('id')
                ->all();

            if ($accessTokenIds !== []) {
                DB::table('oauth_refresh_tokens')
                    ->whereIn('access_token_id', $accessTokenIds)
                    ->delete();
            }

            DB::table('oauth_access_tokens')
                ->where('client_id', $record->getKey())
                ->delete();

            DB::table('oauth_auth_codes')
                ->where('client_id', $record->getKey())
                ->delete();

            return (bool) $record->delete();
        });
    }

    public static function suggestFrontchannelClientKey(OAuthClient $record): string
    {
        $redirectUris = static::normalizeStringArrayState($record->redirect_uris);

        foreach ($redirectUris as $uri) {
            $host = parse_url($uri, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                continue;
            }

            $firstLabel = explode('.', mb_strtolower($host))[0] ?? '';
            $candidate = trim((string) preg_replace('/[^a-z0-9-]+/', '-', $firstLabel), '-');

            if ($candidate !== '') {
                return $candidate;
            }
        }

        $nameCandidate = Str::of((string) $record->name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();

        return $nameCandidate !== '' ? $nameCandidate : 'cliente';
    }

    /**
     * @return array<int, string>
     */
    private static function normalizeStringArrayState(mixed $state): array
    {
        if (is_array($state)) {
            return collect($state)
                ->map(static fn (mixed $item): string => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        if (! is_string($state)) {
            return [];
        }

        $state = trim($state);

        if ($state === '') {
            return [];
        }

        $decoded = json_decode($state, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(static fn (mixed $item): string => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split('/[\r\n,]+/', $state) ?: [])
            ->map(static fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private static function sanitizeFrontchannelClientKey(string $clientKey): string
    {
        $normalized = mb_strtolower(trim($clientKey));

        if ($normalized === '' || preg_match('/^[a-z0-9-]+$/', $normalized) !== 1) {
            throw ValidationException::withMessages([
                'client_key' => 'La clave debe contener solo minúsculas, números y guiones.',
            ]);
        }

        return $normalized;
    }

    private static function sanitizeSlug(string $slug, string $ignoreClientId): string
    {
        $normalized = Str::of($slug)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'slug' => 'El slug es obligatorio.',
            ]);
        }

        $exists = OAuthClient::query()
            ->where('slug', $normalized)
            ->where('id', '!=', $ignoreClientId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'slug' => 'Ya existe otra app con ese slug.',
            ]);
        }

        return $normalized;
    }
}
