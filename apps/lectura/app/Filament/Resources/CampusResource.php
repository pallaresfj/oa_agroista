<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampusResource\Pages;
use App\Models\Campus;
use App\Services\QRGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CampusResource extends Resource
{
    protected static ?string $model = Campus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Sedes';

    protected static ?string $modelLabel = 'Sede';

    protected static ?string $pluralModelLabel = 'Sedes';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Sede')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(500)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Sede Activa')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),

                        TextInput::make('latitude')
                            ->label('Latitud')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label('Longitud')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->columnSpan(1),

                        TextInput::make('radius_meters')
                            ->label('Radio permitido')
                            ->required()
                            ->numeric()
                            ->default(20)
                            ->minValue(10)
                            ->maxValue(1000)
                            ->suffix('metros')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('name')
                    ->label('NOMBRE')
                    ->searchable(),

                TextColumn::make('address')
                    ->label('DIRECCIÓN')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('latitude')
                    ->label('LATITUD')
                    ->numeric(7)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('longitude')
                    ->label('LONGITUD')
                    ->numeric(7)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('radius_meters')
                    ->label('RADIO')
                    ->suffix(' m'),

                IconColumn::make('is_active')
                    ->label('ACTIVO')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->native(false),
            ])
            ->actions([
                Action::make('viewQr')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Ver QR')
                    ->modalHeading(fn (Campus $record) => "Código QR - {$record->name}")
                    ->modalContent(function (Campus $record) {
                        $qrService = app(QRGeneratorService::class);
                        $disk = \Illuminate\Support\Facades\Storage::disk('public');

                        // Generate QR if not exists or file is missing
                        if (empty($record->qr_code_path) || empty($record->qr_token) || ! $disk->exists($record->qr_code_path)) {
                            $record->qr_token = $record->qr_token ?: Str::random(32);
                            $record->save();
                            $qrService->generateCampusQR($record);
                            $record->refresh();
                        }

                        $qrUrl = route('media.public', ['path' => $record->qr_code_path]);

                        return view('filament.modals.campus-qr', [
                            'campus' => $record,
                            'qrUrl' => $qrUrl,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('downloadQr')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Descargar QR')
                    ->action(function (Campus $record) {
                        $qrService = app(QRGeneratorService::class);
                        $disk = \Illuminate\Support\Facades\Storage::disk('public');

                        // Generate QR if not exists or file is missing
                        if (empty($record->qr_code_path) || empty($record->qr_token) || ! $disk->exists($record->qr_code_path)) {
                            $record->qr_token = $record->qr_token ?: Str::random(32);
                            $record->save();
                            $qrService->generateCampusQR($record);
                            $record->refresh();
                        }

                        $path = $disk->path($record->qr_code_path);

                        if (file_exists($path)) {
                            return response()->download($path, "QR-{$record->name}.svg");
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('No se pudo descargar el archivo QR.')
                            ->danger()
                            ->send();
                    }),
                Action::make('regenerateQr')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Regenerar QR')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerar Código QR')
                    ->modalDescription('¿Está seguro de regenerar el QR? El código anterior dejará de funcionar.')
                    ->action(function (Campus $record) {
                        $record->update(['qr_token' => Str::random(32)]);
                        app(QRGeneratorService::class)->generateCampusQR($record);

                        \Filament\Notifications\Notification::make()
                            ->title('QR Regenerado')
                            ->body("El código QR de {$record->name} ha sido regenerado exitosamente.")
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Editar'),
                DeleteAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Eliminar'),
            ])
            ->defaultSort('name')
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCampuses::route('/'),
            'create' => Pages\CreateCampus::route('/create'),
            'edit' => Pages\EditCampus::route('/{record}/edit'),
        ];
    }
}
