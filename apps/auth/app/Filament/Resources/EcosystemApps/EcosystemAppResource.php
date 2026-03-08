<?php

namespace App\Filament\Resources\EcosystemApps;

use App\Filament\Resources\EcosystemApps\Pages\ManageEcosystemApps;
use App\Models\EcosystemApp;
use App\Models\EcosystemAppRedirectUri;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EcosystemAppResource extends Resource
{
    protected static ?string $model = EcosystemApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Apps ecosistema';

    protected static ?string $modelLabel = 'App ecosistema';

    protected static ?string $pluralModelLabel = 'Apps ecosistema';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('institution_id')
                ->label('Institucion')
                ->required()
                ->options(Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(100),
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('base_url')
                ->label('URL base')
                ->required()
                ->url()
                ->maxLength(2048),
            TextInput::make('oauth_client_id')
                ->label('OAuth client id')
                ->maxLength(191),
            TagsInput::make('redirect_uris')
                ->label('Redirect URIs')
                ->placeholder('https://app.ejemplo.edu.co/sso/callback')
                ->afterStateHydrated(function (TagsInput $component, ?EcosystemApp $record): void {
                    if (! $record) {
                        return;
                    }

                    $component->state(
                        $record->redirectUris()->pluck('redirect_uri')->all()
                    );
                }),
            TagsInput::make('frontchannel_logout_uris')
                ->label('Frontchannel logout URIs')
                ->placeholder('https://app.ejemplo.edu.co/sso/frontchannel-logout')
                ->afterStateHydrated(function (TagsInput $component, ?EcosystemApp $record): void {
                    if (! $record) {
                        return;
                    }

                    $component->state(
                        $record->redirectUris()
                            ->where('is_frontchannel_logout', true)
                            ->pluck('redirect_uri')
                            ->all()
                    );
                }),
            Toggle::make('is_active')
                ->label('Activa')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('institution.name')->label('Institucion')->searchable(),
                TextColumn::make('slug')->label('Slug')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('base_url')->label('URL base')->wrap(),
                TextColumn::make('oauth_client_id')->label('Client ID')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Activa')->boolean(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->using(fn (EcosystemApp $record, array $data): EcosystemApp => static::updateApp($record, $data)),
                DeleteAction::make()->iconButton()->tooltip('Eliminar'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Nueva app ecosistema')
                    ->createAnother(false)
                    ->using(fn (array $data): EcosystemApp => static::createApp($data)),
            ])
            ->defaultSort('name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function createApp(array $data): EcosystemApp
    {
        /** @var EcosystemApp $app */
        $app = EcosystemApp::query()->create([
            'institution_id' => $data['institution_id'],
            'slug' => mb_strtolower(trim((string) $data['slug'])),
            'name' => trim((string) $data['name']),
            'base_url' => rtrim((string) $data['base_url'], '/'),
            'oauth_client_id' => trim((string) ($data['oauth_client_id'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        static::syncRedirectUris($app, $data);

        return $app;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateApp(EcosystemApp $record, array $data): EcosystemApp
    {
        $record->fill([
            'institution_id' => $data['institution_id'],
            'slug' => mb_strtolower(trim((string) $data['slug'])),
            'name' => trim((string) $data['name']),
            'base_url' => rtrim((string) $data['base_url'], '/'),
            'oauth_client_id' => trim((string) ($data['oauth_client_id'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ])->save();

        static::syncRedirectUris($record, $data);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function syncRedirectUris(EcosystemApp $app, array $data): void
    {
        $redirectUris = collect($data['redirect_uris'] ?? [])->map(fn ($uri) => trim((string) $uri))->filter()->unique();
        $frontchannelUris = collect($data['frontchannel_logout_uris'] ?? [])->map(fn ($uri) => trim((string) $uri))->filter()->unique();

        $app->redirectUris()->delete();

        $rows = $redirectUris->map(function (string $uri) use ($frontchannelUris): array {
            return [
                'redirect_uri' => $uri,
                'is_frontchannel_logout' => $frontchannelUris->contains($uri),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        if ($rows !== []) {
            $app->redirectUris()->createMany($rows);
        }

        $missingFrontchannel = $frontchannelUris->diff($redirectUris)->values();

        foreach ($missingFrontchannel as $uri) {
            EcosystemAppRedirectUri::query()->create([
                'ecosystem_app_id' => $app->id,
                'redirect_uri' => $uri,
                'is_frontchannel_logout' => true,
            ]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEcosystemApps::route('/'),
        ];
    }
}
