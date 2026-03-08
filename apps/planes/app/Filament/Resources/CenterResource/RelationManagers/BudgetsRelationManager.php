<?php

namespace App\Filament\Resources\CenterResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\Summarizers\Sum;

class BudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';
    protected static ?string $modelLabel = 'Recurso';
    protected static ?string $pluralLabel = 'Recursos';
    protected static ?string $title = 'Recursos';

    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            Grid::make([
                'default' => 1,
                'lg' => 10,
            ])
                ->columnSpanFull()
                ->schema([
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make('item')
                    ->label('Item')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ]),
                TextInput::make('unit_value')
                    ->label('Valor Unitario')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 3,
                    ]),
            ]),
            Textarea::make('observations')
                ->label('Observaciones')
                ->placeholder('Dejar en blanco si no hay observaciones')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            ->columns([
                TextInputColumn::make('quantity')
                    ->label('Cantidad')
                    ->rules(['numeric', 'min:1']),
                TextInputColumn::make('item')
                    ->rules(['required', 'max:100'])
                    ->label('Item'),
                TextInputColumn::make('unit_value')
                    ->label('Valor Unitario')
                    ->rules(['numeric', 'min:1']),
                TextColumn::make('total_value')
                    ->label('Total')
                    ->summarize(
                        Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) =>
                                '<span class="font-bold text-lg text-gray-800">$' . number_format($state, 2, ',', '.') . '</span>'
                            )
                            ->html()
                    )
                    ->money('COP', locale: 'es_CO'),
                TextColumn::make('observations')
                    ->label('Observaciones')
                    ->lineClamp(1)
                    ->wrap(),
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
                ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->tooltip('Ver')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->modalHeading(fn ($record) => $record->item),
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
