<?php

namespace App\Filament\Resources\SubjectResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RubricsRelationManager extends RelationManager
{
    protected static string $relationship = 'rubrics';
    protected static ?string $modelLabel = 'Rúbrica';
    protected static ?string $pluralLabel = 'Rúbricas';
    protected static ?string $title = 'Rúbricas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('period')
                    ->label('Periodo')
                    ->native(false)
                    ->placeholder('Seleccione un periodo')
                    ->options([
                        '1' => 'Primero',
                        '2' => 'Segundo',
                        '3' => 'Tercero',
                    ])
                    ->columnSpanFull()
                    ->required(),
                Textarea::make('criterion')
                    ->label('Criterio')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('superior_level')
                    ->label('Superior')
                    ->rows(3)
                    ->maxLength(255),
                Textarea::make('high_level')
                    ->label('Alto')
                    ->rows(3)
                    ->maxLength(255),
                Textarea::make('basic_level')
                    ->label('Básico')
                    ->rows(3)
                    ->maxLength(255),
                Textarea::make('low_level')
                    ->label('Bajo')
                    ->rows(3)
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period')
            ->columns([
                TextColumn::make('period')
                    ->label('Periodo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Asignatura')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('criterion')
                    ->label('Criterio')
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('superior_level')
                    ->label('Superior')
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('high_level')
                    ->label('Alto')
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('basic_level')
                    ->label('Básico')
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('low_level')
                    ->label('Bajo')
                    ->wrap()
                    ->lineClamp(2),
            ])
            ->groups([
                Group::make('period')
                ->label('Periodo')
                ->collapsible()
                ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('period')
            ->groupingDirectionSettingHidden()
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
                    ->color('success')
                    ->tooltip('Editar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large),
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Borrar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large),
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->tooltip('Ver')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->modalHeading(fn ($record) => 'Periodo ' . $record->period . ': ' . $record->subject->name),
                ReplicateAction::make()
                    ->label('')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->tooltip('Duplicar')
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
