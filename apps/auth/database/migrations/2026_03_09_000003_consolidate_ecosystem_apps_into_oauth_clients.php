<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            if (! Schema::hasColumn('oauth_clients', 'slug')) {
                $table->string('slug', 120)->nullable()->after('name');
            }

            if (! Schema::hasColumn('oauth_clients', 'base_url')) {
                $table->string('base_url')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('oauth_clients', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('revoked');
            }

            if (! Schema::hasColumn('oauth_clients', 'frontchannel_logout_uris')) {
                $table->json('frontchannel_logout_uris')->nullable()->after('redirect_uris');
            }
        });

        if (Schema::hasTable('ecosystem_apps')) {
            $apps = DB::table('ecosystem_apps')->orderBy('id')->get();

            foreach ($apps as $app) {
                $client = null;

                if (! empty($app->oauth_client_id)) {
                    $client = DB::table('oauth_clients')->where('id', (string) $app->oauth_client_id)->first();
                }

                if (! $client) {
                    $client = DB::table('oauth_clients')->where('name', (string) $app->slug)->first();
                }

                if (! $client) {
                    $client = DB::table('oauth_clients')->where('name', (string) $app->name)->first();
                }

                if (! $client) {
                    $newClientId = (string) Str::uuid();

                    DB::table('oauth_clients')->insert([
                        'id' => $newClientId,
                        'name' => (string) $app->name,
                        'slug' => null,
                        'base_url' => null,
                        'secret' => Str::random(40),
                        'provider' => null,
                        'redirect_uris' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'frontchannel_logout_uris' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'grant_types' => json_encode(['authorization_code', 'refresh_token'], JSON_UNESCAPED_UNICODE),
                        'scopes' => json_encode(['openid', 'email', 'profile', 'ecosystem.read'], JSON_UNESCAPED_UNICODE),
                        'revoked' => false,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $client = DB::table('oauth_clients')->where('id', $newClientId)->first();
                }

                $redirects = collect();
                $frontchannels = collect();

                if (Schema::hasTable('ecosystem_app_redirect_uris')) {
                    $rows = DB::table('ecosystem_app_redirect_uris')
                        ->where('ecosystem_app_id', $app->id)
                        ->get();

                    $redirects = $rows
                        ->pluck('redirect_uri')
                        ->map(static fn (mixed $uri): string => trim((string) $uri))
                        ->filter();

                    $frontchannels = $rows
                        ->where('is_frontchannel_logout', true)
                        ->pluck('redirect_uri')
                        ->map(static fn (mixed $uri): string => trim((string) $uri))
                        ->filter();
                }

                $existingRedirects = collect(static::decodeJsonArray($client->redirect_uris ?? null));
                $existingFrontchannels = collect(static::decodeJsonArray($client->frontchannel_logout_uris ?? null));

                $slug = static::resolveUniqueSlug(
                    (string) ($app->slug ?: $app->name),
                    (string) $client->id,
                );

                DB::table('oauth_clients')
                    ->where('id', (string) $client->id)
                    ->update([
                        'name' => (string) $app->name,
                        'slug' => $slug,
                        'base_url' => $app->base_url ? rtrim((string) $app->base_url, '/') : null,
                        'is_active' => (bool) $app->is_active,
                        'redirect_uris' => json_encode(
                            $existingRedirects->merge($redirects)->map(static fn (string $uri): string => trim($uri))->filter()->unique()->values()->all(),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'frontchannel_logout_uris' => json_encode(
                            $existingFrontchannels->merge($frontchannels)->map(static fn (string $uri): string => trim($uri))->filter()->unique()->values()->all(),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'updated_at' => now(),
                    ]);
            }
        }

        $clients = DB::table('oauth_clients')->orderBy('created_at')->orderBy('id')->get();

        foreach ($clients as $client) {
            $currentSlug = trim((string) ($client->slug ?? ''));
            $source = $currentSlug !== '' ? $currentSlug : (string) $client->name;

            $slug = static::resolveUniqueSlug($source, (string) $client->id);

            DB::table('oauth_clients')
                ->where('id', (string) $client->id)
                ->update([
                    'slug' => $slug,
                    'base_url' => $client->base_url ? rtrim((string) $client->base_url, '/') : null,
                    'is_active' => isset($client->is_active) ? (bool) $client->is_active : true,
                    'frontchannel_logout_uris' => json_encode(static::decodeJsonArray($client->frontchannel_logout_uris ?? null), JSON_UNESCAPED_UNICODE),
                    'redirect_uris' => json_encode(static::decodeJsonArray($client->redirect_uris ?? null), JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            try {
                $table->unique('slug');
            } catch (Throwable) {
                // Ignore if index already exists.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            try {
                $table->dropUnique(['slug']);
            } catch (Throwable) {
                // Ignore if missing.
            }

            if (Schema::hasColumn('oauth_clients', 'frontchannel_logout_uris')) {
                $table->dropColumn('frontchannel_logout_uris');
            }

            if (Schema::hasColumn('oauth_clients', 'base_url')) {
                $table->dropColumn('base_url');
            }

            if (Schema::hasColumn('oauth_clients', 'slug')) {
                $table->dropColumn('slug');
            }

            if (Schema::hasColumn('oauth_clients', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private static function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $value)));
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $decoded)));
    }

    private static function resolveUniqueSlug(string $source, string $clientId): string
    {
        $base = Str::of($source)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();

        if ($base === '') {
            $base = 'app';
        }

        $slug = $base;
        $suffix = 1;

        while (
            DB::table('oauth_clients')
                ->where('slug', $slug)
                ->where('id', '!=', $clientId)
                ->exists()
        ) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
};
