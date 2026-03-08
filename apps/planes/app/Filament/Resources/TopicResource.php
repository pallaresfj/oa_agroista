<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TopicResource\Pages\ListTopics;
use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\Pages;
use App\Filament\Resources\TopicResource\RelationManagers;
use App\Models\Topic;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;
    protected static ?int $navigationSort = 3;
    protected static string | \UnitEnum | null $navigationGroup = 'Planes de área';
    protected static ?string $modelLabel = 'Contenido';
    protected static ?string $pluralLabel = 'Contenidos';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';
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
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->columnSpanFull()
                ->schema([
                    RichEditor::make('standard')
                    ->label(fn (Get $get) => ((string) (Subject::find($get('subject_id'))?->grade) === '0') ? 'Principio' : 'Estándar')
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
                TextColumn::make('standard')
                    ->label('Estándar')
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
            'index' => ListTopics::route('/'),
            'create' => CreateTopic::route('/create'),
            'edit' => EditTopic::route('/{record}/edit'),
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
