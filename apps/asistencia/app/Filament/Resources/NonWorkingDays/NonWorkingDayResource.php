<?php

namespace App\Filament\Resources\NonWorkingDays;

use App\Filament\Resources\NonWorkingDays\Pages\CreateNonWorkingDay;
use App\Filament\Resources\NonWorkingDays\Pages\CreateVacationRange;
use App\Filament\Resources\NonWorkingDays\Pages\EditNonWorkingDay;
use App\Filament\Resources\NonWorkingDays\Pages\ListNonWorkingDays;
use App\Filament\Resources\NonWorkingDays\Schemas\NonWorkingDayForm;
use App\Filament\Resources\NonWorkingDays\Tables\NonWorkingDaysTable;
use App\Models\NonWorkingDay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NonWorkingDayResource extends Resource
{
    protected static ?string $model = NonWorkingDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Día no laborable';

    protected static ?string $pluralModelLabel = 'Días no laborables';

    protected static ?string $navigationLabel = 'Días no laborables';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }

    public static function form(Schema $schema): Schema
    {
        return NonWorkingDayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NonWorkingDaysTable::configure($table);
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
            'index' => ListNonWorkingDays::route('/'),
            'create' => CreateNonWorkingDay::route('/create'),
            'create-range' => CreateVacationRange::route('/create-range'),
            'edit' => EditNonWorkingDay::route('/{record}/edit'),
        ];
    }
}
