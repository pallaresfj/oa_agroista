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

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.docente-dashboard';

    public ?int $selectedCourseId = null;

    /**
     * @var array<int, int>
     */
    public array $selectedCourseIds = [];

    public string $sortOption = 'recent';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('view_docente_dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Inicio';
    }

    public function setCourseFilter(int|string|null $courseId = null): void
    {
        $normalizedCourseId = is_numeric($courseId) ? (int) $courseId : null;

        $allowedCourseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if (! $normalizedCourseId || ! in_array($normalizedCourseId, $allowedCourseIds, true)) {
            $this->selectedCourseId = null;

            return;
        }

        $this->selectedCourseId = $normalizedCourseId;
    }

    public function updatedSelectedCourseId(mixed $value): void
    {
        $this->setCourseFilter($value);
    }

    public function updatedSelectedCourseIds(mixed $value): void
    {
        if (! is_array($value)) {
            $this->selectedCourseIds = [];

            return;
        }

        $allowedCourseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $this->selectedCourseIds = $this->sanitizeCourseIds($value, $allowedCourseIds);
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

    public function isDirectivoContext(): bool
    {
        return Auth::user()?->isDirectivo() ?? false;
    }

    public function isStudentPerformanceContext(): bool
    {
        return $this->isDocenteContext() || $this->isDirectivoContext();
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
        if (! $this->isStudentPerformanceContext()) {
            return [];
        }

        $courseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $studentsQuery = (clone $this->studentsQuery())
            ->with('course')
            ->orderBy('name');

        if ($this->isDirectivoContext()) {
            $selectedCourseIds = $this->getSelectedDirectivoCourseIds($courseIds);

            if ($selectedCourseIds !== []) {
                $studentsQuery->whereIn('course_id', $selectedCourseIds);
            }
        } else {
            if ($this->selectedCourseId && ! in_array($this->selectedCourseId, $courseIds, true)) {
                $this->selectedCourseId = null;
            }

            if ($this->selectedCourseId) {
                $studentsQuery->where('course_id', $this->selectedCourseId);
            }
        }

        /** @var EloquentCollection<int, Student> $students */
        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return [];
        }

        $attemptsQuery = ReadingAttempt::query()
            ->where('status', ReadingAttempt::STATUS_COMPLETED)
            ->whereIn('student_id', $students->pluck('id'))
            ->orderByDesc('finished_at')
            ->orderByDesc('id');

        if ($this->isDocenteContext()) {
            $attemptsQuery->where('teacher_id', Auth::id());
        }

        $attemptsByStudent = $attemptsQuery
            ->get(['id', 'student_id', 'teacher_id', 'words_per_minute', 'total_errors', 'finished_at'])
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

    /**
     * @return array{
     *   total_attempts:int,
     *   global_avg_pcpm:float|null,
     *   courses:array<int, array{
     *     course_id:int,
     *     course_name:string,
     *     students_count:int,
     *     attempts_count:int,
     *     avg_pcpm:float|null,
     *     avg_pcpm_bar_percent:int
     *   }>
     * }
     */
    public function getDirectivoCourseMetrics(): array
    {
        if (! $this->isDirectivoContext()) {
            return [
                'total_attempts' => 0,
                'global_avg_pcpm' => null,
                'courses' => [],
            ];
        }

        $courseIds = $this->getCourseOptions()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $selectedCourseIds = $this->getSelectedDirectivoCourseIds($courseIds);

        if ($selectedCourseIds !== []) {
            $courseIds = $selectedCourseIds;
        }

        if ($courseIds === []) {
            return [
                'total_attempts' => 0,
                'global_avg_pcpm' => null,
                'courses' => [],
            ];
        }

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $studentsCountByCourse = (clone $this->studentsQuery())
            ->whereIn('course_id', $courseIds)
            ->selectRaw('course_id, COUNT(*) as aggregate_count')
            ->groupBy('course_id')
            ->pluck('aggregate_count', 'course_id');

        $pcpmExpression = $this->pcpmSqlExpression();

        $attemptsByCourse = ReadingAttempt::query()
            ->join('students', 'students.id', '=', 'reading_attempts.student_id')
            ->whereIn('students.course_id', $courseIds)
            ->where('reading_attempts.status', ReadingAttempt::STATUS_COMPLETED)
            ->selectRaw('students.course_id as course_id')
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw("AVG({$pcpmExpression}) as avg_pcpm")
            ->groupBy('students.course_id')
            ->get()
            ->keyBy('course_id');

        $globalAttempts = (int) $attemptsByCourse
            ->sum(fn (object $row): int => (int) ($row->attempts_count ?? 0));

        $globalAvgPcpm = ReadingAttempt::query()
            ->join('students', 'students.id', '=', 'reading_attempts.student_id')
            ->whereIn('students.course_id', $courseIds)
            ->where('reading_attempts.status', ReadingAttempt::STATUS_COMPLETED)
            ->selectRaw("AVG({$pcpmExpression}) as avg_pcpm")
            ->value('avg_pcpm');

        $maxAvg = $attemptsByCourse
            ->map(fn (object $row): float => (float) ($row->avg_pcpm ?? 0))
            ->max() ?? 0;

        $courseMetrics = $courses->map(function (Course $course) use ($studentsCountByCourse, $attemptsByCourse, $maxAvg): array {
            $attempts = $attemptsByCourse->get($course->id);
            $avgPcpm = $attempts ? round((float) ($attempts->avg_pcpm ?? 0), 1) : null;
            $barPercent = ($avgPcpm !== null && $maxAvg > 0)
                ? max(6, (int) round(($avgPcpm / $maxAvg) * 100))
                : 0;

            return [
                'course_id' => (int) $course->id,
                'course_name' => (string) $course->name,
                'students_count' => (int) ($studentsCountByCourse[$course->id] ?? 0),
                'attempts_count' => (int) ($attempts->attempts_count ?? 0),
                'avg_pcpm' => $avgPcpm,
                'avg_pcpm_bar_percent' => $barPercent,
            ];
        })->all();

        return [
            'total_attempts' => $globalAttempts,
            'global_avg_pcpm' => $globalAvgPcpm !== null ? round((float) $globalAvgPcpm, 1) : null,
            'courses' => $courseMetrics,
        ];
    }

    public function canShowAddStudentCta(): bool
    {
        return Auth::user()?->can('create_student') ?? false;
    }

    public function canStartNewEvaluation(): bool
    {
        $user = Auth::user();

        return (bool) $user
            && $user->can('view_reading_session')
            && $user->can('create_reading_attempt');
    }

    public function getNewEvaluationUrl(): string
    {
        return ReadingSession::getUrl();
    }

    public function exportCsv(): ?StreamedResponse
    {
        if (! $this->isStudentPerformanceContext()) {
            Notification::make()
                ->title('Acción no disponible')
                ->body('La exportación aplica únicamente al panel de seguimiento.')
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

    private function pcpmSqlExpression(): string
    {
        return 'CASE WHEN reading_attempts.words_per_minute - reading_attempts.total_errors < 0 THEN 0 ELSE reading_attempts.words_per_minute - reading_attempts.total_errors END';
    }

    /**
     * @param  array<int, int>  $allowedCourseIds
     * @return array<int, int>
     */
    private function getSelectedDirectivoCourseIds(array $allowedCourseIds): array
    {
        $selectedCourseIds = $this->sanitizeCourseIds($this->selectedCourseIds, $allowedCourseIds);

        if ($selectedCourseIds !== $this->selectedCourseIds) {
            $this->selectedCourseIds = $selectedCourseIds;
        }

        return $selectedCourseIds;
    }

    /**
     * @param  array<int, mixed>  $candidateIds
     * @param  array<int, int>  $allowedCourseIds
     * @return array<int, int>
     */
    private function sanitizeCourseIds(array $candidateIds, array $allowedCourseIds): array
    {
        return collect($candidateIds)
            ->map(fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null)
            ->filter(fn (?int $id): bool => $id !== null && in_array($id, $allowedCourseIds, true))
            ->unique()
            ->values()
            ->all();
    }
}
