<?php

namespace App\Filament\Pages;

use App\Enums\ReadingErrorType;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReadingSession extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Sesión de lectura';

    protected static ?string $slug = 'sesion-lectura';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.reading-session';

    public ?array $data = [];

    public ?int $activeAttemptId = null;

    public ?int $errorWordIndex = null;

    public string $errorComment = '';

    public string $attemptNotes = '';

    public ?array $lastResult = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isDocente();
    }

    public function mount(): void
    {
        $this->loadFormDefaults();

        $activeAttempt = ReadingAttempt::query()
            ->where('teacher_id', Auth::id())
            ->where('status', ReadingAttempt::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();

        if (! $activeAttempt) {
            return;
        }

        $this->activeAttemptId = $activeAttempt->id;
        $this->attemptNotes = (string) ($activeAttempt->notes ?? '');
        $this->form->fill([
            'student_id' => $activeAttempt->student_id,
            'passage_id' => $activeAttempt->passage_id,
            'notes' => $activeAttempt->notes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Preparar lectura')
                    ->description('Seleccione el estudiante y la lectura antes de iniciar el cronómetro.')
                    ->schema([
                        Select::make('student_id')
                            ->label('Estudiante')
                            ->options(Student::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->native(false),
                        Select::make('passage_id')
                            ->label('Lectura')
                            ->options(ReadingPassage::query()->where('is_active', true)->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->native(false),
                        Textarea::make('notes')
                            ->label('Observaciones del intento')
                            ->rows(3)
                            ->placeholder('Opcional. Puede registrar contexto antes de iniciar.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function startAttempt(): void
    {
        if ($this->getActiveAttemptRecord()) {
            Notification::make()
                ->title('Ya existe un intento en curso.')
                ->warning()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $passage = ReadingPassage::query()->findOrFail($state['passage_id']);

        $attempt = ReadingAttempt::query()->create([
            'student_id' => $state['student_id'],
            'teacher_id' => Auth::id(),
            'passage_id' => $passage->id,
            'status' => ReadingAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'word_count' => $passage->word_count,
            'notes' => $state['notes'] ?? null,
        ]);

        $this->activeAttemptId = $attempt->id;
        $this->attemptNotes = (string) ($attempt->notes ?? '');
        $this->lastResult = null;

        Notification::make()
            ->title('Lectura iniciada')
            ->success()
            ->send();
    }

    public function registerError(string $type): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        $attempt->registerError(
            ReadingErrorType::from($type),
            max(0, (int) $attempt->started_at?->diffInSeconds(now())),
            $this->errorWordIndex,
            trim($this->errorComment) !== '' ? trim($this->errorComment) : null,
        );

        $this->errorWordIndex = null;
        $this->errorComment = '';

        Notification::make()
            ->title('Error registrado')
            ->success()
            ->send();
    }

    public function finishAttempt(): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        $attempt->complete(notes: trim($this->attemptNotes) !== '' ? trim($this->attemptNotes) : null);
        $attempt->refresh(['student', 'passage']);

        $this->lastResult = $this->buildResultPayload($attempt);
        $this->resetActiveState();

        Notification::make()
            ->title('Lectura finalizada')
            ->success()
            ->send();
    }

    public function cancelAttempt(): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        $attempt->cancel(notes: trim($this->attemptNotes) !== '' ? trim($this->attemptNotes) : null);
        $this->resetActiveState();

        Notification::make()
            ->title('Intento cancelado')
            ->warning()
            ->send();
    }

    public function getActiveAttemptRecord(): ?ReadingAttempt
    {
        if (! $this->activeAttemptId) {
            return null;
        }

        return ReadingAttempt::query()
            ->with(['student', 'passage', 'errors'])
            ->find($this->activeAttemptId);
    }

    public function getErrorCounters(): array
    {
        $attempt = $this->getActiveAttemptRecord();
        $counts = collect(ReadingErrorType::cases())
            ->mapWithKeys(fn (ReadingErrorType $type): array => [$type->value => 0])
            ->all();

        if (! $attempt) {
            return $counts;
        }

        return array_merge(
            $counts,
            $attempt->errors
                ->groupBy(fn ($error) => $error->error_type->value)
                ->map(fn ($errors): int => $errors->count())
                ->all(),
        );
    }

    private function loadFormDefaults(): void
    {
        $this->form->fill([
            'student_id' => Student::query()->where('is_active', true)->value('id'),
            'passage_id' => ReadingPassage::query()->where('is_active', true)->value('id'),
            'notes' => '',
        ]);
    }

    private function resetActiveState(): void
    {
        $this->activeAttemptId = null;
        $this->attemptNotes = '';
        $this->errorWordIndex = null;
        $this->errorComment = '';
    }

    private function buildResultPayload(ReadingAttempt $attempt): array
    {
        return [
            'duration' => $attempt->duration_seconds,
            'word_count' => $attempt->word_count,
            'wpm' => (float) $attempt->words_per_minute,
            'errors' => $attempt->total_errors,
            'attempt_url' => route('filament.app.resources.reading-attempts.view', ['record' => $attempt]),
        ];
    }
}
