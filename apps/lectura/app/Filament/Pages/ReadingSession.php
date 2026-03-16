<?php

namespace App\Filament\Pages;

use App\Enums\ReadingErrorType;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ReadingSession extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Sesión de lectura';

    protected static ?string $slug = 'sesion-lectura';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.reading-session';

    public ?int $studentId = null;

    public ?int $passageId = null;

    public ?int $activeAttemptId = null;

    public bool $showFinalizeModal = false;

    public int $finalCentiseconds = 0;

    public array $pendingErrorCounts = [];

    public ?array $lastResult = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->canManageReadingOperations();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Sesión de lectura';
    }

    public function mount(): void
    {
        $this->resetErrorCounters();
        $this->loadDefaults();

        $activeAttempt = ReadingAttempt::query()
            ->where('teacher_id', Auth::id())
            ->where('status', ReadingAttempt::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();

        if (! $activeAttempt) {
            return;
        }

        $this->activeAttemptId = $activeAttempt->id;
        $this->studentId = $activeAttempt->student_id;
        $this->passageId = $activeAttempt->passage_id;
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

        if (! $this->studentId || ! $this->passageId) {
            Notification::make()
                ->title('Seleccione estudiante y lectura.')
                ->warning()
                ->send();

            return;
        }

        $student = $this->studentsQuery()->find($this->studentId);

        if (! $student) {
            Notification::make()
                ->title('El estudiante seleccionado no está disponible para su perfil.')
                ->danger()
                ->send();

            return;
        }

        $passage = ReadingPassage::query()
            ->where('is_active', true)
            ->find($this->passageId);

        if (! $passage) {
            Notification::make()
                ->title('La lectura seleccionada no está disponible.')
                ->danger()
                ->send();

            return;
        }

        $attempt = ReadingAttempt::query()->create([
            'student_id' => $student->id,
            'teacher_id' => Auth::id(),
            'passage_id' => $passage->id,
            'status' => ReadingAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'word_count' => $passage->word_count,
        ]);

        $this->activeAttemptId = $attempt->id;
        $this->showFinalizeModal = false;
        $this->finalCentiseconds = 0;
        $this->resetErrorCounters();
        $this->lastResult = null;

        Notification::make()
            ->title('Lectura iniciada')
            ->success()
            ->send();
    }

    public function stopAttempt(): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        $this->finalCentiseconds = $this->calculateElapsedCentiseconds($attempt);
        $this->showFinalizeModal = true;
    }

    public function closeFinalizeModal(): void
    {
        $this->showFinalizeModal = false;
    }

    public function adjustErrorCount(string $type, int $delta): void
    {
        if (! array_key_exists($type, $this->pendingErrorCounts)) {
            return;
        }

        $next = (int) $this->pendingErrorCounts[$type] + $delta;
        $this->pendingErrorCounts[$type] = max(0, $next);
    }

    public function saveEvaluation(): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        if ($this->finalCentiseconds <= 0) {
            $this->finalCentiseconds = $this->calculateElapsedCentiseconds($attempt);
        }

        $attempt->errors()->delete();

        foreach ($this->pendingErrorCounts as $errorType => $count) {
            $count = max(0, (int) $count);

            for ($i = 0; $i < $count; $i++) {
                $attempt->errors()->create([
                    'error_type' => $errorType,
                    'occurred_at_seconds' => intdiv($this->finalCentiseconds, 100),
                ]);
            }
        }

        $finishedAt = $attempt->started_at
            ? $attempt->started_at->copy()->addMilliseconds($this->finalCentiseconds * 10)
            : now();

        $attempt->complete($finishedAt);
        $attempt->refresh(['student', 'passage']);

        $this->lastResult = $this->buildResultPayload($attempt);
        $this->resetSessionState();
        $this->loadDefaults();

        Notification::make()
            ->title('Evaluación guardada')
            ->success()
            ->send();
    }

    public function discardAndReset(): void
    {
        $attempt = $this->getActiveAttemptRecord();

        if (! $attempt) {
            return;
        }

        $attempt->errors()->delete();
        $attempt->cancel();

        $this->resetSessionState();
        $this->loadDefaults();

        Notification::make()
            ->title('Intento descartado y reiniciado')
            ->warning()
            ->send();
    }

    public function getStudentOptions(): array
    {
        return $this->studentsQuery()
            ->with('course')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim($student->name.' · '.($student->course?->name ?? 'Sin curso')),
            ])
            ->all();
    }

    public function getPassageOptions(): array
    {
        return ReadingPassage::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    public function getErrorTypeLabels(): array
    {
        return collect(ReadingErrorType::cases())
            ->mapWithKeys(fn (ReadingErrorType $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function getSelectedPassage(): ?ReadingPassage
    {
        $passageId = $this->passageId;

        if (! $passageId) {
            return null;
        }

        return ReadingPassage::query()->find($passageId);
    }

    public function getActiveAttemptRecord(): ?ReadingAttempt
    {
        if (! $this->activeAttemptId) {
            return null;
        }

        return ReadingAttempt::query()
            ->with(['student.course', 'passage', 'errors'])
            ->find($this->activeAttemptId);
    }

    public function getTotalErrors(): int
    {
        return array_sum(array_map(static fn (mixed $value): int => max(0, (int) $value), $this->pendingErrorCounts));
    }

    public function getApproxWpm(): float
    {
        $passage = $this->getActiveAttemptRecord()?->passage ?? $this->getSelectedPassage();
        $durationSeconds = max(1, intdiv(max(0, $this->finalCentiseconds), 100));

        if (! $passage) {
            return 0;
        }

        return round(($passage->word_count / $durationSeconds) * 60, 0);
    }

    public function formatCentiseconds(int $centiseconds): string
    {
        $centiseconds = max(0, $centiseconds);
        $minutes = intdiv($centiseconds, 6000);
        $seconds = intdiv($centiseconds % 6000, 100);
        $hundredths = $centiseconds % 100;

        return sprintf('%02d:%02d.%02d', $minutes, $seconds, $hundredths);
    }

    private function studentsQuery()
    {
        $query = Student::query();
        $user = Auth::user();

        if (! $user || ! $user->canManageReadingOperations()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdminEquivalent()) {
            return $query;
        }

        $courseIds = $user->assignedCourses()->pluck('courses.id')->all();

        if ($courseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('course_id', $courseIds);
    }

    private function loadDefaults(): void
    {
        $studentOptions = $this->getStudentOptions();
        $passageOptions = $this->getPassageOptions();

        if (! $this->studentId || ! array_key_exists($this->studentId, $studentOptions)) {
            $this->studentId = $studentOptions !== [] ? (int) array_key_first($studentOptions) : null;
        }

        if (! $this->passageId || ! array_key_exists($this->passageId, $passageOptions)) {
            $this->passageId = $passageOptions !== [] ? (int) array_key_first($passageOptions) : null;
        }
    }

    private function calculateElapsedCentiseconds(ReadingAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        $diffMs = now()->valueOf() - $attempt->started_at->valueOf();

        return max(0, intdiv((int) $diffMs, 10));
    }

    private function resetSessionState(): void
    {
        $this->activeAttemptId = null;
        $this->showFinalizeModal = false;
        $this->finalCentiseconds = 0;
        $this->resetErrorCounters();
    }

    private function resetErrorCounters(): void
    {
        $this->pendingErrorCounts = collect(ReadingErrorType::cases())
            ->mapWithKeys(fn (ReadingErrorType $type): array => [$type->value => 0])
            ->all();
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
