<?php

namespace App\Filament\Resources\CenterResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $modelLabel = 'Actividad';
    protected static ?string $pluralLabel = 'Actividades';
    protected static ?string $title = 'Actividades';

    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            DatePicker::make('week')
                ->label('Semana')
                ->required(),
            Textarea::make('activity')
                ->label('Actividad')
                ->required(),
            Textarea::make('objective')
                ->label('Objetivo de la Actividad')
                ->columnSpanFull(),
            Grid::make([
                'default' => 1,
                'lg' => 2,
            ])
                ->columnSpanFull()
                ->schema([
                    RichEditor::make('methodology')
                        ->label('Metodología')
                        ->disableToolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'strike',
                            'codeBlock',
                            'link',
                        ]),
                    RichEditor::make('materials')
                        ->label('Materiales')
                        ->disableToolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'strike',
                            'codeBlock',
                            'link',
                        ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('activity')
            ->columns([
                TextColumn::make('week')
                    ->label('Semana')
                    ->wrap()
                    ->date('F j \d\e Y')
                    ->sortable(),
                TextColumn::make('activity')
                    ->label('Actividad')
                    ->wrap(),
                TextColumn::make('objective')
                    ->label('Objetivo')
                    ->lineClamp(4)
                    ->wrap(),
                TextColumn::make('methodology')
                    ->label('Metodología')
                    ->html()
                    ->lineClamp(4)
                    ->wrap(),
                TextColumn::make('materials')
                    ->label('Materiales')
                    ->html()
                    ->lineClamp(4)
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
                    ->modalHeading(fn ($record) => $record->activity . ' (' . $record->week?->translatedFormat('F j \d\e Y') . ')'),
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
