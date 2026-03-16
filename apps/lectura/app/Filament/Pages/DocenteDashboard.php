<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ReadingStatsWidget;
use App\Filament\Widgets\RecentAttemptsWidget;
use App\Models\Course;
use App\Models\ReadingAttempt;
use App\Models\Student;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocenteDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.docente-dashboard';

    public ?int $selectedCourseId = null;

    public string $sortOption = 'recent';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('view_docente_dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Panel del Docente';
    }

    public function setCourseFilter(?int $courseId = null): void
    {
        $allowedCourseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if (! $courseId || ! in_array($courseId, $allowedCourseIds, true)) {
            $this->selectedCourseId = null;

            return;
        }

        $this->selectedCourseId = $courseId;
    }

    public function updatedSortOption(string $value): void
    {
        if (! in_array($value, array_keys($this->getSortOptions()), true)) {
            $this->sortOption = 'recent';
        }
    }

    public function isDocenteContext(): bool
    {
        return Auth::user()?->isDocente() ?? false;
    }

    /**
     * @return array<int, string>
     */
    public function getLegacyWidgets(): array
    {
        return [
            ReadingStatsWidget::class,
            RecentAttemptsWidget::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSortOptions(): array
    {
        return [
            'recent' => 'Recientes',
            'pcpm_high' => 'PCPM alto',
            'pcpm_low' => 'PCPM bajo',
            'name' => 'Nombre',
        ];
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCourseOptions(): Collection
    {
        $visibleCourseIds = (clone $this->studentsQuery())
            ->whereNotNull('course_id')
            ->distinct()
            ->pluck('course_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($visibleCourseIds === []) {
            return collect();
        }

        return Course::query()
            ->whereIn('id', $visibleCourseIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStudentCards(): array
    {
        if (! $this->isDocenteContext()) {
            return [];
        }

        $courseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($this->selectedCourseId && ! in_array($this->selectedCourseId, $courseIds, true)) {
            $this->selectedCourseId = null;
        }

        $studentsQuery = (clone $this->studentsQuery())
            ->with('course')
            ->orderBy('name');

        if ($this->selectedCourseId) {
            $studentsQuery->where('course_id', $this->selectedCourseId);
        }

        /** @var EloquentCollection<int, Student> $students */
        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return [];
        }

        $attemptsByStudent = ReadingAttempt::query()
            ->where('teacher_id', Auth::id())
            ->where('status', ReadingAttempt::STATUS_COMPLETED)
            ->whereIn('student_id', $students->pluck('id'))
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'words_per_minute', 'total_errors', 'finished_at'])
            ->groupBy('student_id');

        $cards = $students->map(function (Student $student) use ($attemptsByStudent): array {
            $attempts = $attemptsByStudent->get($student->id, collect())->values();
            /** @var ReadingAttempt|null $latestAttempt */
            $latestAttempt = $attempts->get(0);
            /** @var ReadingAttempt|null $previousAttempt */
            $previousAttempt = $attempts->get(1);

            $latestPcpm = $latestAttempt ? $this->calculatePcpm($latestAttempt) : null;
            $previousPcpm = $previousAttempt ? $this->calculatePcpm($previousAttempt) : null;
            $delta = ($latestPcpm !== null && $previousPcpm !== null) ? $latestPcpm - $previousPcpm : null;

            $trend = $delta === null ? 'stable' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable'));
            [$statusLabel, $statusColor] = $this->resolvePerformanceStatus($latestPcpm);

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'initials' => $this->getInitials($student->name),
                'course_name' => $student->course?->name ?? 'Sin grupo',
                'pcpm' => $latestPcpm,
                'wpm' => $latestAttempt ? round((float) $latestAttempt->words_per_minute) : null,
                'errors' => $latestAttempt ? (int) $latestAttempt->total_errors : null,
                'delta' => $delta,
                'trend' => $trend,
                'finished_at' => $latestAttempt?->finished_at,
                'finished_at_ts' => $latestAttempt?->finished_at?->timestamp,
                'evaluated_human' => $latestAttempt?->finished_at?->diffForHumans(),
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
            ];
        });

        return $this->sortCards($cards)->values()->all();
    }

    public function canShowAddStudentCta(): bool
    {
        return Auth::user()?->can('create_student') ?? false;
    }

    public function getNewEvaluationUrl(): string
    {
        return ReadingSession::getUrl();
    }

    public function exportCsv(): ?StreamedResponse
    {
        if (! $this->isDocenteContext()) {
            Notification::make()
                ->title('Acción no disponible')
                ->body('La exportación aplica únicamente al panel del docente.')
                ->warning()
                ->send();

            return null;
        }

        $rows = collect($this->getStudentCards())
            ->map(function (array $card): array {
                $delta = $card['delta'];

                return [
                    $card['student_name'],
                    $card['course_name'],
                    $card['pcpm'] ?? '-',
                    $card['wpm'] ?? '-',
                    $card['errors'] ?? '-',
                    $card['finished_at']?->format('d/m/Y H:i') ?? '-',
                    $card['status_label'],
                    $delta === null ? '-' : sprintf('%+d', $delta),
                ];
            })
            ->values();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if (! $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Estudiante', 'Grupo', 'PCPM', 'WPM', 'Errores', 'Fecha última evaluación', 'Estado', 'Delta']);

            foreach ($rows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, 'dashboard-docente.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function studentsQuery(): Builder
    {
        $query = Student::query();
        $user = Auth::user();

        if (! $user || ! $user->canAny(['view_any_student', 'view_student'])) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('view_any_student')) {
            return $query;
        }

        $courseIds = $user->assignedCourses()->pluck('courses.id')->all();

        if ($courseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('course_id', $courseIds);
    }

    private function calculatePcpm(ReadingAttempt $attempt): int
    {
        return max(0, (int) round((float) $attempt->words_per_minute - (int) $attempt->total_errors));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvePerformanceStatus(?int $pcpm): array
    {
        if ($pcpm === null) {
            return ['Sin evaluación', 'slate'];
        }

        if ($pcpm >= 100) {
            return ['Nivel avanzado', 'success'];
        }

        if ($pcpm >= 80) {
            return ['Nivel esperado', 'warning'];
        }

        return ['Refuerzo requerido', 'danger'];
    }

    private function getInitials(string $name): string
    {
        $parts = collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1));

        $initials = mb_strtoupper($parts->implode(''));

        return $initials !== '' ? $initials : 'ST';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $cards
     * @return Collection<int, array<string, mixed>>
     */
    private function sortCards(Collection $cards): Collection
    {
        return match ($this->sortOption) {
            'pcpm_high' => $cards->sortByDesc(fn (array $card): int => $card['pcpm'] ?? -1),
            'pcpm_low' => $cards->sortBy(fn (array $card): int => $card['pcpm'] ?? PHP_INT_MAX),
            'name' => $cards->sortBy(fn (array $card): string => mb_strtolower((string) $card['student_name'])),
            default => $cards->sortByDesc(fn (array $card): int => $card['finished_at_ts'] ?? -1),
        };
    }
}
