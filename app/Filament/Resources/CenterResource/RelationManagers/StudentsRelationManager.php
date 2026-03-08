<?php

namespace App\Filament\Resources\CenterResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';
    protected static ?string $modelLabel = 'Estudiante';
    protected static ?string $pluralLabel = 'Estudiantes';
    protected static ?string $title = 'Estudiantes';

    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            TextInput::make('full_name')
                ->label('Nombre Completo')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),
            TextInput::make('identification')
                ->label('Identificación')
                ->required()
                ->unique(ignorable: fn ($record) => $record)
                ->maxLength(20),
            Select::make('grade')
                ->label('Curso')
                ->searchable()
                ->required()
                ->options([
                    'Agropecuario' => [
                        'CA 0601' => 'CA 0601',
                        'CA 0602' => 'CA 0602',
                        'CA 0701' => 'CA 0701',
                        'CA 0702' => 'CA 0702',
                        'CA 0801' => 'CA 0801',
                        'CA 0802' => 'CA 0802',
                        'CA 0901' => 'CA 0901',
                        'CA 1001' => 'CA 1001',
                        'CA 1101' => 'CA 1101',
                    ],
                    'Divino Niño' => [
                        'DN 0001'=> 'DN 0001',
                        'DN 0101'=> 'DN 0101',
                        'DN 0201'=> 'DN 0201',
                        'DN 0202'=> 'DN 0202',
                        'DN 0301'=> 'DN 0301',
                        'DN 0302'=> 'DN 0302',
                        'DN 0401'=> 'DN 0401',
                        'DN 0501'=> 'DN 0501',
                    ],
                    'Madre Laura' => [
                        'ML 0001'=> 'ML 0001',
                        'ML 0101'=> 'ML 0101',
                        'ML 0201'=> 'ML 0201',
                        'ML 0301'=> 'ML 0301',
                        'ML 0401'=> 'ML 0401',
                        'ML 0501'=> 'ML 0501',
                        'ML 0502'=> 'ML 0502',
                    ],
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->searchable(),
                TextColumn::make('identification')
                    ->label('Identificación')
                    ->searchable(),
                TextColumn::make('grade')
                    ->label('Curso')
                    ->searchable(),
            ])
            ->groups([
                Group::make('grade')
                ->label('Curso')
                ->collapsible()
                ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('grade')
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
