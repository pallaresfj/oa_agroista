<?php

namespace App\Filament\Resources\SubjectResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';
    protected static ?string $modelLabel = 'Contenido';
    protected static ?string $pluralLabel = 'Contenidos';
    protected static ?string $title = 'Contenidos';

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
                
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->columnSpanFull()
                ->schema([
                    RichEditor::make('standard')
                    ->label(fn () => ((string) ($this->getOwnerRecord()?->grade) === '0') ? 'Principio' : 'Estándar')
                    ->disableToolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'strike',
                        'codeBlock',
                        'link',
                    ]),
                    RichEditor::make('dba')
                    ->label('DBA')
                    ->disableToolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'strike',
                        'codeBlock',
                        'link',
                    ]),
                RichEditor::make('competencies')
                    ->label('Competencias')
                    ->disableToolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'strike',
                        'codeBlock',
                        'link',
                    ]),
                RichEditor::make('contents')
                    ->label('Contenidos')
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
            ->recordTitleAttribute('period')
            ->columns([
                TextColumn::make('period')
                    ->label('Periodo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('standard')
                    ->label(fn () => ((string) ($this->getOwnerRecord()?->grade) === '0') ? 'Principio' : 'Estándar')
                    ->html()
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('dba')
                    ->label('DBA')
                    ->html()
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('competencies')
                    ->label('Competencias')
                    ->html()
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('contents')
                    ->label('Contenidos')
                    ->html()
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
