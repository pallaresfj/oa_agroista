<?php

namespace App\Filament\Resources\InstitutionSettings;

use App\Filament\Resources\InstitutionSettings\Pages\ManageInstitutionSettings;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstitutionSettingResource extends Resource
{
    protected static ?string $model = InstitutionSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Config institucional';

    protected static ?string $modelLabel = 'Config institucional';

    protected static ?string $pluralModelLabel = 'Configuraciones institucionales';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('institution_id')
                ->label('Institucion')
                ->required()
                ->options(Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            TextInput::make('key')
                ->label('Clave')
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label('Tipo')
                ->required()
                ->default('string')
                ->options([
                    'string' => 'string',
                    'json' => 'json',
                    'bool' => 'bool',
                    'number' => 'number',
                ]),
            Textarea::make('value_text')
                ->label('Valor texto')
                ->rows(3)
                ->maxLength(10000),
            Textarea::make('value_json')
                ->label('Valor JSON')
                ->rows(5)
                ->helperText('Opcional. Ingresa JSON valido cuando type=json.')
                ->formatStateUsing(static fn ($state): ?string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                ->dehydrateStateUsing(function ($state): ?array {
                    $value = trim((string) $state);

                    if ($value === '') {
                        return null;
                    }

                    $decoded = json_decode($value, true);

                    return is_array($decoded) ? $decoded : null;
                }),
            Toggle::make('is_public')
                ->label('Publico')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('institution.name')
                    ->label('Institucion')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                IconColumn::make('is_public')
                    ->label('Publico')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Editar'),
                DeleteAction::make()->iconButton()->tooltip('Eliminar'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Nueva configuracion')
                    ->after(function (): void {
                        Notification::make()->title('Configuracion creada')->success()->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstitutionSettings::route('/'),
        ];
    }
}
