<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PlanResource\RelationManagers\SubjectsRelationManager;
use App\Filament\Resources\PlanResource\Pages\ListPlans;
use App\Filament\Resources\PlanResource\Pages\CreatePlan;
use App\Filament\Resources\PlanResource\Pages\EditPlan;
use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?int $navigationSort = 1;
    protected static string | \UnitEnum | null $navigationGroup = 'Planes de área';
    protected static ?string $modelLabel = 'Área';
    protected static ?string $pluralLabel = 'Áreas';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    public static function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            Tabs::make('Plan de Área')
                ->tabs([
                    Tab::make('Identificación')->schema([
                        Hidden::make('school_profile_id')->default(1),

                        FileUpload::make('cover')
                            ->label('Portada')
                            ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                User::ROLE_SOPORTE,
                                User::ROLE_DIRECTIVO,
                                User::ROLE_AREA,
                            ]))
                            ->image()
                            ->imageEditor()
                            ->directory('plan-cover')
                            ->visibility('public')
                            ->columnSpanFull(),

                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                        User::ROLE_SOPORTE,
                                        User::ROLE_DIRECTIVO,
                                        User::ROLE_AREA,
                                    ]))
                                    ->placeholder('Escriba el nombre del plan de área')
                                    ->required()
                                    ->maxLength(100),
    
                                TextInput::make('year')
                                    ->label('Año')
                                    ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                        User::ROLE_SOPORTE,
                                        User::ROLE_DIRECTIVO,
                                        User::ROLE_AREA,
                                    ]))
                                    ->required()
                                    ->maxLength(4),
                            ]),

                        Select::make('users')
                            ->label('Docentes del área')
                            ->relationship('users', 'name')
                            ->options(function () {
                                $user = Auth::user();

                                if ($user->hasAnyRoleId([
                                    User::ROLE_DIRECTIVO,
                                    User::ROLE_SOPORTE,
                                ])) {
                                    return User::whereHas('roles', fn ($q) =>
                                        $q->whereIn('id', [
                                            User::ROLE_AREA,
                                            User::ROLE_DOCENTE,
                                        ])
                                    )->orderBy('name')->pluck('name', 'id');
                                }

                                if ($user->hasAnyRoleId([User::ROLE_AREA])) {
                                    return User::where('id', $user->id)->pluck('name', 'id');
                                }

                                return [];
                            })
                            ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                User::ROLE_DIRECTIVO,
                                User::ROLE_SOPORTE,
                            ]))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Seleccione docentes')
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Principios')
                        ->schema([
                            Placeholder::make('mission')
                                ->label('Misión institucional')
                                ->content(fn ($record) => new HtmlString($record?->schoolProfile?->mission ?? '<em>No definida</em>')),

                            Placeholder::make('vision')
                                ->label('Visión institucional')
                                ->content(fn ($record) => new HtmlString($record?->schoolProfile?->vision ?? '<em>No definida</em>')),
                        ]),

                    Tab::make('Justificación')->schema([
                        RichEditor::make('justification')
                            ->label('')
                            ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                User::ROLE_SOPORTE,
                                User::ROLE_DIRECTIVO,
                                User::ROLE_AREA,
                            ]))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Objetivos')->schema([
                        RichEditor::make('objectives')
                            ->label('')
                            ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                User::ROLE_SOPORTE,
                                User::ROLE_DIRECTIVO,
                                User::ROLE_AREA,
                            ]))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Metodología')->schema([
                        RichEditor::make('methodology')
                            ->label('')
                            ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                User::ROLE_SOPORTE,
                                User::ROLE_DIRECTIVO,
                                User::ROLE_AREA,
                            ]))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Año')    
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Plan de área')    
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('users.name')
                    ->label('Docentes del área')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->wrap()
                    ->limitList(3),
                ImageColumn::make('cover')
                    ->label('Portada')
                    ->defaultImageUrl(url('/images/portada.jpg'))
                    ->disk('public')
                    ->width(100)
                    ->height(59),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                //
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
                    ->modalHeading(fn ($record) => 'Plan de área: ' . $record->name),
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
            SubjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user->hasAnyRoleId([
            User::ROLE_DIRECTIVO,
            User::ROLE_SOPORTE,
        ])) 
        {
            return $query;
        }

        if ($user->hasAnyRoleId([
            User::ROLE_AREA,
            User::ROLE_DOCENTE,
        ]))
        {
            return $query->whereHas('users', fn ($q) => $q->where('id', $user->id));
        }

        // Para todos los demás roles, no mostrar registros
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
            User::ROLE_AREA,
            User::ROLE_DOCENTE,
        ])) {
            return static::getModel()::whereHas('users', fn ($q) =>
                $q->where('id', $user->id)
            )->count();
        }
 
        return null;
    }
}
