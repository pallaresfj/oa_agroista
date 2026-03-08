<?php

namespace App\Filament\Resources\Institutions;

use App\Filament\Resources\Institutions\Pages\ManageInstitutions;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Institucion';

    protected static ?string $modelLabel = 'Institucion';

    protected static ?string $pluralModelLabel = 'Instituciones';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Codigo')
                    ->required()
                    ->maxLength(100),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('logo_url')
                    ->label('Logo URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('primary_color')
                    ->label('Color primario')
                    ->placeholder('#1d6362')
                    ->maxLength(20),
                TextInput::make('secondary_color')
                    ->label('Color secundario')
                    ->placeholder('#6b9a34')
                    ->maxLength(20),
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
                TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_color')
                    ->label('Color primario')
                    ->badge(),
                TextColumn::make('secondary_color')
                    ->label('Color secundario')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar institucion'),
            ])
            ->toolbarActions([])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstitutions::route('/'),
        ];
    }
}
