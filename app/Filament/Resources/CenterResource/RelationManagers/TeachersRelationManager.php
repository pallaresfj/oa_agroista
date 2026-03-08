<?php

namespace App\Filament\Resources\CenterResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TeachersRelationManager extends RelationManager
{
    protected static string $relationship = 'teachers';
    protected static ?string $modelLabel = 'Profesor';
    protected static ?string $pluralLabel = 'Profesores';
    protected static ?string $title = 'Profesores';

    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            Grid::make([
                'default' => 1,
                'lg' => 12,
            ])
                ->columnSpanFull()
                ->schema([
                TextInput::make('full_name')
                    ->label('Nombre Completo')
                    ->required()
                    ->maxLength(150)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 9,
                    ]),
                FileUpload::make('profile_photo_path')
                    ->label('Foto')
                    ->image()
                    ->imageEditor()
                    ->directory('teachers')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 3,
                    ]),
            ]),
            Grid::make([
                'default' => 1,
                'lg' => 3,
            ])
                ->columnSpanFull()
                ->schema([
                TextInput::make('identification')
                    ->label('Identificación')
                    ->unique(ignorable: fn ($record) => $record)
                    ->required()
                    ->maxLength(20),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->maxLength(100),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->required()
                    ->tel()
                    ->maxLength(20),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                ImageColumn::make('profile_photo_path')
                    ->label('Foto')
                    ->circular()
                    ->height(48)
                    ->width(48)
                    ->defaultImageUrl(asset('images/default-avatar.png'))
                    ->extraImgAttributes(['class' => 'object-cover border border-gray-300 shadow-sm']),
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('identification')
                    ->label('Identificación')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Editar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large),
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Borrar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
