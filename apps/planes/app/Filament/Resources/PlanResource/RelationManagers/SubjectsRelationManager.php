<?php

namespace App\Filament\Resources\PlanResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

use App\Filament\Resources\SubjectResource;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Models\User;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';
    protected static ?string $modelLabel = 'Asignatura';
    protected static ?string $pluralLabel = 'Asignaturas';
    protected static ?string $title = 'Asignaturas';

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
                    Select::make('grade')
                        ->label('Grado')
                        ->native(false)
                        ->placeholder('Seleccione un grado')
                        ->required()
                        ->options([
                            '0' => 'Transición',
                            '1' => 'Primero',
                            '2' => 'Segundo',
                            '3' => 'Tercero',
                            '4' => 'Cuarto',
                            '5' => 'Quinto',
                            '6' => 'Sexto',
                            '7' => 'Séptimo',
                            '8' => 'Octavo',
                            '9' => 'Noveno',
                            '10' => 'Décimo',
                            '11' => 'Undécimo',
                        ])
                        ->visible(fn () => !Auth::user()->hasRole('Centro'))
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    TextInput::make('name')
                        ->label('Asignatura')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(fn () => Auth::user()->hasRole('Centro')
                            ? ['default' => 1, 'lg' => 9]
                            : ['default' => 1, 'lg' => 7]),
                    TextInput::make('weekly_hours')
                        ->label('IHS')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->columnSpan(fn () => Auth::user()->hasRole('Centro')
                            ? ['default' => 1, 'lg' => 3]
                            : ['default' => 1, 'lg' => 2]),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if ($user->hasAnyRoleId([
                    User::ROLE_DIRECTIVO,
                    User::ROLE_SOPORTE,
                ])) {
                    return $query;
                }

                if ($user->hasAnyRoleId([
                    User::ROLE_AREA,
                    User::ROLE_DOCENTE,
                ]))
                {
                    return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
                }

                return $query->whereRaw('0 = 1');
            })
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('grade')
                    ->label('Grado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Asignatura')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('weekly_hours')
                    ->label('IHS')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('users.name')
                    ->label('Docentes')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->bulleted(),
            ])
            ->defaultSort('grade', 'asc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('')
                    ->color('success')
                    ->tooltip('Editar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => SubjectResource::getUrl('edit', ['record' => $record])),
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
                    ->modalHeading(fn ($record) => $record->plan->name . ': ' . $record->name),
                ReplicateAction::make()
                    ->label('')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->tooltip('Duplicar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->visible(fn () => Auth::user()->hasAnyRoleId([User::ROLE_SOPORTE, User::ROLE_DIRECTIVO])),
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
