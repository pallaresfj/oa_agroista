<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\RubricResource\Pages\ListRubrics;
use App\Filament\Resources\RubricResource\Pages\CreateRubric;
use App\Filament\Resources\RubricResource\Pages\EditRubric;
use App\Filament\Resources\RubricResource\Pages;
use App\Filament\Resources\RubricResource\RelationManagers;
use App\Models\Rubric;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use Filament\Forms\Components\Textarea;
use App\Models\User;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RubricResource extends Resource
{
    protected static ?string $model = Rubric::class;
    protected static ?int $navigationSort = 4;
    protected static string | \UnitEnum | null $navigationGroup = 'Planes de área';
    protected static ?string $modelLabel = 'Rúbrica';
    protected static ?string $pluralLabel = 'Rúbricas';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Schema $schema): Schema
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
                    ->required(),
                Select::make('subject_id')
                    ->label('Asignatura')
                    ->native(false)
                    ->placeholder('Seleccione una asignatura')
                    ->options(function () {
                        $user = Auth::user();

                        if ($user->hasAnyRoleId([
                            User::ROLE_DIRECTIVO,
                            User::ROLE_SOPORTE,
                        ])) {
                            return Subject::orderBy('name')->pluck('name', 'id');
                        }

                        if ($user->hasAnyRoleId([
                            User::ROLE_AREA
                        ])) 
                        {
                            $planIds = $user->plans()->pluck('plans.id');
                            return Subject::whereIn('plan_id', $planIds)->orderBy('name')->pluck('name', 'id');
                        }

                        if ($user->hasAnyRoleId([
                            User::ROLE_DOCENTE
                        ])) 
                        {
                            return $user->subjects()->select('subjects.name', 'subjects.id')->orderBy('subjects.name')->pluck('subjects.name', 'subjects.id');
                        }

                        return [];
                    })
                    ->searchable()
                    ->preload()
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

    public static function table(Table $table): Table
    {
        return $table
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
                ->collapsible(),
                Group::make('subject.name')
                ->label('Asignatura')
                ->collapsible()
                ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('period')
            ->groupingDirectionSettingHidden()
            ->filters([
                SelectFilter::make('period')
                    ->label('Periodo')
                    ->options([
                        '1' => 'Primero',
                        '2' => 'Segundo',
                        '3' => 'Tercero',
                    ])
                    ->searchable(),

                SelectFilter::make('subject_id')
                    ->label('Asignatura')
                    ->relationship('subject', 'name')
                    ->searchable(),

                SelectFilter::make('subject.plan_id')
                    ->label('Área')
                    ->relationship('subject.plan', 'name')
                    ->searchable(),
            ])
            ->persistFiltersInSession()
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRubrics::route('/'),
            'create' => CreateRubric::route('/create'),
            'edit' => EditRubric::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRoleId([
            User::ROLE_DIRECTIVO,
            User::ROLE_SOPORTE,
        ])) {
            return $query;
        }

        if ($user->hasAnyRoleId([
            User::ROLE_DOCENTE
        ])) 
        {
            return $query->whereHas('subject.users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($user->hasAnyRoleId([
            User::ROLE_AREA
        ])) 
        {
            return $query->whereHas('subject.plan.users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        return $query->whereRaw('0 = 1');
    }
}