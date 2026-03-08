<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CenterResource\RelationManagers\TeachersRelationManager;
use App\Filament\Resources\CenterResource\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\CenterResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\CenterResource\RelationManagers\BudgetsRelationManager;
use App\Filament\Resources\CenterResource\Pages\ListCenters;
use App\Filament\Resources\CenterResource\Pages\CreateCenter;
use App\Filament\Resources\CenterResource\Pages\EditCenter;
use App\Filament\Resources\CenterResource\Pages;
use App\Filament\Resources\CenterResource\RelationManagers;
use App\Models\Center;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CenterResource extends Resource
{
    protected static ?string $model = Center::class;
    protected static ?string $modelLabel = 'Centro';
    protected static ?string $pluralLabel = 'Centros';
    protected static string | \UnitEnum | null $navigationGroup = 'Centros de interés';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home-modern';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Plan de Área')
                    ->tabs([
                        Tab::make('Identificación')->schema([
    
                            FileUpload::make('image_path')
                                ->label('Portada')
                                ->image()
                                ->imageEditor()
                                ->directory('center-cover')
                                ->visibility('public')
                                ->columnSpanFull(),
    
                                Grid::make(2)->schema([
                                    Select::make('user_id')
                                        ->label('Responsable')
                                        ->options(
                                            User::whereHas('roles', fn ($q) => $q->whereIn('id', [
                                                User::ROLE_CENTRO,
                                            ]))
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                        )
                                        ->searchable()
                                        ->disabled(fn () => !Auth::user()->hasAnyRoleId([
                                            User::ROLE_SOPORTE,
                                            User::ROLE_DIRECTIVO,
                                        ]))
                                        ->required(),
                                    TextInput::make('academic_year')
                                        ->label('Año')
                                        ->required()
                                        ->maxLength(4),
                                ]),
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->placeholder('Escriba el nombre del centro de ineterés')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpanFull(),
                        ]),
    
                        Tab::make('Descripción')->schema([
                            RichEditor::make('description')
                                ->label('')
                                ->disableToolbarButtons([
                                    'attachFiles',
                                ])
                                ->columnSpanFull(),
                        ]),
    
                        Tab::make('Objetivo')->schema([
                            Textarea::make('objective')
                                ->label('')
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
                TextColumn::make('academic_year')
                    ->label('Año')    
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Centro de Interés')    
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Equipo Responsable')    
                    ->numeric()
                    ->sortable()
                    ->wrap(),
                ImageColumn::make('image_path')
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
            TeachersRelationManager::class,
            StudentsRelationManager::class,
            ActivitiesRelationManager::class,
            BudgetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCenters::route('/'),
            'create' => CreateCenter::route('/create'),
            'edit' => EditCenter::route('/{record}/edit'),
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
            User::ROLE_CENTRO
        ]))
        {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if ($user->hasAnyRoleId([
            User::ROLE_DIRECTIVO,
            User::ROLE_SOPORTE,
        ])) {
            return static::getModel()::count();
        }

        if ($user->hasAnyRoleId([
            User::ROLE_CENTRO
        ]))
        {
            return static::getModel()::where('user_id', $user->id)->count();
        }

        return null;
    }
}
