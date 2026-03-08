<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SubjectResource\RelationManagers\TopicsRelationManager;
use App\Filament\Resources\SubjectResource\RelationManagers\RubricsRelationManager;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Filament\Resources\SubjectResource\Pages\CreateSubject;
use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers;
use App\Models\Subject;
use App\Models\User;
use App\Models\Center;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    protected static ?int $navigationSort = 2;
    protected static string | \UnitEnum | null $navigationGroup = 'Planes de área';
    protected static ?string $modelLabel = 'Asignatura';
    protected static ?string $pluralLabel = 'Asignaturas';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('grade')
                    ->label('Grado')
                    ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                        User::ROLE_SOPORTE,
                        User::ROLE_DIRECTIVO,
                    ]))
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->placeholder('Seleccione un grado')
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
                    ]),
                Select::make('plan_id')
                    ->label('Área')
                    ->relationship('plan', 'name')
                    ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                        User::ROLE_SOPORTE,
                        User::ROLE_DIRECTIVO,
                    ]))
                    ->required()
                    ->searchable()
                    ->placeholder('Seleccione un área')
                    ->preload()
                    ->native(false),
                TextInput::make('name')
                    ->label('Asignatura')
                    ->required()
                    ->maxLength(100),
                TextInput::make('weekly_hours')
                    ->label('Horas semanales')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                Select::make('users')
                    ->label('Docentes para esta asignatura')
                    ->relationship('users', 'name')
                ->options(function () {
                        return User::whereHas('roles', fn ($q) => $q->whereIn('id', [User::ROLE_DOCENTE]))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                })
                    ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                        User::ROLE_DIRECTIVO,
                        User::ROLE_SOPORTE,
                    ]))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->placeholder('Seleccione docentes')
                    ->native(false),

                Select::make('interest_centers')
                    ->label('Centros de Interés')
                    ->multiple()
                    ->options(Center::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->preload(),

                RichEditor::make('contributions')
                    ->label('Aportes')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'bulletList', 'orderedList'
                    ]),

                RichEditor::make('strategies')
                    ->label('Estrategias')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'bulletList', 'orderedList'
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grade')
                    ->label('Grado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Área')
                    ->wrap()
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
                    ->wrap()
                    ->bulleted(),
            ])
            ->defaultSort('grade', 'asc')
            ->groups([
                Group::make('grade')
                ->label('Grado')
                ->collapsible(),
                Group::make('plan.name')
                ->label('Área')
                ->collapsible()
                ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('plan.name')
            ->groupingDirectionSettingHidden()
            ->filters([
                SelectFilter::make('grade')
                    ->label('Grado')
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
                    ->searchable(),

                SelectFilter::make('plan_id')
                    ->label('Área')
                    ->relationship('plan', 'name')
                    ->searchable(),

                SelectFilter::make('users')
                    ->label('Docentes')
                    ->multiple()
                    ->relationship('users', 'name')
                    ->searchable(),
            ])
            ->persistFiltersInSession()
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->tooltip('Editar')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->disabled(function ($record) {
                        $user = Auth::user();
                        return ! $user->hasAnyRoleId([User::ROLE_DIRECTIVO, User::ROLE_SOPORTE]) &&
                               ! $record->users->contains('id', $user->id);
                    }),
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

    public static function getRelations(): array
    {
        return [
            TopicsRelationManager::class,
            RubricsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit' => EditSubject::route('/{record}/edit'),
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

        if ($user->hasAnyRoleId([User::ROLE_AREA])) {
            return $query->where(function ($q) use ($user) {
                $q->whereHas('plan.users', fn ($q) => $q->where('users.id', $user->id))
                  ->orWhereHas('users', fn ($q) => $q->where('users.id', $user->id));
            });
        }

        if ($user->hasAnyRoleId([User::ROLE_DOCENTE])) {
            return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query->whereRaw('0 = 1');
    }
    public static function getNavigationBadge(): ?string
{
    $user = Auth::user();

    if ($user->hasAnyRoleId([
        User::ROLE_SOPORTE,
        User::ROLE_DIRECTIVO,
    ])) {
        return static::getModel()::count();
    }

    if ($user->hasAnyRoleId([
        User::ROLE_DOCENTE
    ]))
    {
        return static::getModel()::whereHas('users', fn ($q) =>
            $q->where('users.id', $user->id)
        )->count();
    }

    if ($user->hasAnyRoleId([
        User::ROLE_AREA
    ])) 
    {
        return static::getModel()::whereHas('plan.users', fn ($q) =>
            $q->where('users.id', $user->id)
        )->count();
    }

    return null;
}
}
