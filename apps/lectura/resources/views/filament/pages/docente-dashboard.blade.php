<x-filament::page>
    @if (! $this->isStudentPerformanceContext())
        <div class="space-y-6">
            @foreach ($this->getLegacyWidgets() as $widgetClass)
                @if (! method_exists($widgetClass, 'canView') || $widgetClass::canView())
                    @livewire($widgetClass, [], key("legacy-docente-dashboard-{$widgetClass}"))
                @endif
            @endforeach
        </div>
    @else
        @php
            $cards = $this->getStudentCards();
            $courses = $this->getCourseOptions();
            $sortOptions = $this->getSortOptions();
            $directivoMetrics = $this->isDirectivoContext() ? $this->getDirectivoCourseMetrics() : null;
        @endphp

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-base text-slate-600 dark:text-slate-400">
                            Seguimiento de Palabras Correctas Por Minuto (PCPM)
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @if ($this->canStartNewEvaluation())
                            <x-filament::button
                                tag="a"
                                :href="$this->getNewEvaluationUrl()"
                                color="primary"
                                icon="heroicon-m-plus">
                                Nueva Evaluación
                            </x-filament::button>
                        @endif

                        <x-filament::button
                            wire:click="exportCsv"
                            color="gray"
                            icon="heroicon-m-arrow-down-tray">
                            Exportar
                        </x-filament::button>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    @if ($this->isDirectivoContext())
                        <div x-data="{ open: false }" class="relative text-sm font-medium text-slate-600 dark:text-slate-300">
                            <button
                                type="button"
                                @click="open = !open"
                                class="inline-flex min-w-[17rem] items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 transition hover:bg-slate-100 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                                <span class="truncate">
                                    Grupo(s):
                                    @if (count($selectedCourseIds ?? []) === 0)
                                        Todos los grupos
                                    @else
                                        {{ count($selectedCourseIds ?? []) }} seleccionado(s)
                                    @endif
                                </span>
                                <x-heroicon-m-chevron-down class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-300" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                @click.outside="open = false"
                                x-transition.opacity
                                class="absolute left-0 z-30 mt-2 w-[20rem] rounded-xl border border-slate-200 bg-white p-3 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Seleccionar grupos</p>
                                    <button
                                        type="button"
                                        wire:click="$set('selectedCourseIds', [])"
                                        class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        Limpiar
                                    </button>
                                </div>

                                <div class="max-h-64 space-y-1 overflow-y-auto pr-1">
                                    @foreach ($courses as $course)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selectedCourseIds"
                                                value="{{ $course->id }}"
                                                class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800" />
                                            <span class="text-sm text-slate-700 dark:text-slate-200">{{ $course->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="setCourseFilter"
                                class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $selectedCourseId === null ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/20 dark:text-primary-300 dark:ring-primary-500/40' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                                Todos los grupos
                            </button>

                            @foreach ($courses as $course)
                                <button
                                    type="button"
                                    wire:click="setCourseFilter({{ $course->id }})"
                                    class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $selectedCourseId === $course->id ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/20 dark:text-primary-300 dark:ring-primary-500/40' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                                    {{ $course->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <label class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300">
                        <span>Ordenar:</span>
                        <select
                            wire:model.live="sortOption"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            @if ($this->isDirectivoContext() && $directivoMetrics !== null)
                @php
                    $chartCourses = collect($directivoMetrics['courses'])->values();

                    $lineData = $chartCourses->map(fn (array $metric): array => [
                        'label' => (string) $metric['course_name'],
                        'value' => $metric['avg_pcpm'] !== null ? (float) $metric['avg_pcpm'] : 0.0,
                    ])->values();

                    $lineCount = $lineData->count();
                    $lineWidth = max(760, $lineCount * 120);
                    $lineHeight = 280;
                    $linePaddingLeft = 56;
                    $linePaddingRight = 24;
                    $linePaddingTop = 18;
                    $linePaddingBottom = 58;
                    $linePlotWidth = $lineWidth - $linePaddingLeft - $linePaddingRight;
                    $linePlotHeight = $lineHeight - $linePaddingTop - $linePaddingBottom;
                    $lineMaxValue = max(100.0, ceil((float) ($lineData->max('value') ?? 0) / 10) * 10);
                    $lineStepX = $lineCount > 1 ? $linePlotWidth / ($lineCount - 1) : 0;

                    $linePoints = $lineData->map(function (array $point, int $index) use ($lineCount, $linePaddingLeft, $linePaddingTop, $linePlotHeight, $linePlotWidth, $lineStepX, $lineMaxValue): array {
                        $x = $linePaddingLeft + ($lineCount > 1 ? $index * $lineStepX : $linePlotWidth / 2);
                        $ratio = $lineMaxValue > 0 ? max(0, min(1, $point['value'] / $lineMaxValue)) : 0;
                        $y = $linePaddingTop + ((1 - $ratio) * $linePlotHeight);

                        return [
                            'x' => round($x, 2),
                            'y' => round($y, 2),
                            'value' => $point['value'],
                            'label' => $point['label'],
                        ];
                    })->values();

                    $linePolyline = $linePoints
                        ->map(fn (array $point): string => $point['x'] . ',' . $point['y'])
                        ->implode(' ');

                    $lineGridTicks = collect(range(0, 4))->map(function (int $index) use ($lineMaxValue, $linePaddingTop, $linePlotHeight): array {
                        $value = ($lineMaxValue / 4) * (4 - $index);
                        $y = $linePaddingTop + (($linePlotHeight / 4) * $index);

                        return [
                            'value' => $value,
                            'y' => round($y, 2),
                        ];
                    });

                    $barData = $chartCourses->map(fn (array $metric): array => [
                        'label' => (string) $metric['course_name'],
                        'value' => (int) $metric['attempts_count'],
                    ])->values();

                    $barCount = $barData->count();
                    $barWidth = max(760, $barCount * 120);
                    $barHeight = 280;
                    $barPaddingTop = 18;
                    $barPaddingBottom = 58;
                    $barPaddingLeft = 56;
                    $barPaddingRight = 24;
                    $barPlotWidth = $barWidth - $barPaddingLeft - $barPaddingRight;
                    $barPlotHeight = $barHeight - $barPaddingTop - $barPaddingBottom;
                    $barMaxValue = max(5, (int) ceil(((int) ($barData->max('value') ?? 0)) / 5) * 5);
                    $barGridTicks = collect(range(0, 4))->map(function (int $index) use ($barMaxValue, $barPaddingTop, $barPlotHeight): array {
                        $value = ($barMaxValue / 4) * (4 - $index);
                        $y = $barPaddingTop + (($barPlotHeight / 4) * $index);

                        return [
                            'y' => round($y, 2),
                            'value' => $value,
                        ];
                    });

                    $barSlotWidth = $barCount > 0 ? $barPlotWidth / $barCount : $barPlotWidth;
                    $barRectWidth = min(56, max(26, (int) round($barSlotWidth * 0.55)));
                @endphp
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                    <div class="flex flex-col gap-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Métricas por curso</h2>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">PCPM promedio</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">
                                    {{ $directivoMetrics['global_avg_pcpm'] !== null ? number_format($directivoMetrics['global_avg_pcpm'], 1) : '--' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Intentos totales</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">
                                    {{ number_format($directivoMetrics['total_attempts']) }}
                                </p>
                            </div>
                        </div>

                        @if ($chartCourses->isNotEmpty())
                            <div class="grid gap-4 xl:grid-cols-2">
                                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Promedio PCPM por curso</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Eje X: cursos · Eje Y: promedio PCPM</p>

                                    <div class="mt-3 overflow-x-auto">
                                        <svg
                                            class="h-64 w-full text-slate-700 dark:text-slate-200"
                                            style="min-width: {{ $lineWidth }}px;"
                                            viewBox="0 0 {{ $lineWidth }} {{ $lineHeight }}"
                                            role="img"
                                            aria-label="Gráfico de línea con promedio PCPM por curso">
                                            @foreach ($lineGridTicks as $tick)
                                                <line
                                                    x1="{{ $linePaddingLeft }}"
                                                    y1="{{ $tick['y'] }}"
                                                    x2="{{ $lineWidth - $linePaddingRight }}"
                                                    y2="{{ $tick['y'] }}"
                                                    stroke="currentColor"
                                                    stroke-opacity="0.14" />
                                                <text
                                                    x="{{ $linePaddingLeft - 8 }}"
                                                    y="{{ $tick['y'] + 4 }}"
                                                    text-anchor="end"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.75">
                                                    {{ number_format($tick['value'], 0) }}
                                                </text>
                                            @endforeach

                                            <line
                                                x1="{{ $linePaddingLeft }}"
                                                y1="{{ $lineHeight - $linePaddingBottom }}"
                                                x2="{{ $lineWidth - $linePaddingRight }}"
                                                y2="{{ $lineHeight - $linePaddingBottom }}"
                                                stroke="currentColor"
                                                stroke-opacity="0.32" />

                                            @if ($lineCount > 1)
                                                <polyline
                                                    fill="none"
                                                    stroke="rgb(14 165 233)"
                                                    stroke-width="3"
                                                    points="{{ $linePolyline }}" />
                                            @endif

                                            @foreach ($linePoints as $point)
                                                <circle
                                                    cx="{{ $point['x'] }}"
                                                    cy="{{ $point['y'] }}"
                                                    r="4.5"
                                                    fill="rgb(14 165 233)" />
                                                <text
                                                    x="{{ $point['x'] }}"
                                                    y="{{ $point['y'] - 10 }}"
                                                    text-anchor="middle"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.88">
                                                    {{ number_format($point['value'], 1) }}
                                                </text>
                                                <text
                                                    x="{{ $point['x'] }}"
                                                    y="{{ $lineHeight - 20 }}"
                                                    text-anchor="middle"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.8">
                                                    {{ \Illuminate\Support\Str::limit($point['label'], 14) }}
                                                </text>
                                            @endforeach
                                        </svg>
                                    </div>
                                </article>

                                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Intentos por curso</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Eje X: cursos · Eje Y: intentos</p>

                                    <div class="mt-3 overflow-x-auto">
                                        <svg
                                            class="h-64 w-full text-slate-700 dark:text-slate-200"
                                            style="min-width: {{ $barWidth }}px;"
                                            viewBox="0 0 {{ $barWidth }} {{ $barHeight }}"
                                            role="img"
                                            aria-label="Gráfico de barras de intentos por curso">
                                            @foreach ($barGridTicks as $tick)
                                                <line
                                                    x1="{{ $barPaddingLeft }}"
                                                    y1="{{ $tick['y'] }}"
                                                    x2="{{ $barWidth - $barPaddingRight }}"
                                                    y2="{{ $tick['y'] }}"
                                                    stroke="currentColor"
                                                    stroke-opacity="0.14" />
                                                <text
                                                    x="{{ $barPaddingLeft - 8 }}"
                                                    y="{{ $tick['y'] + 4 }}"
                                                    text-anchor="end"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.78">
                                                    {{ number_format($tick['value'], 0) }}
                                                </text>
                                            @endforeach

                                            <line
                                                x1="{{ $barPaddingLeft }}"
                                                y1="{{ $barHeight - $barPaddingBottom }}"
                                                x2="{{ $barWidth - $barPaddingRight }}"
                                                y2="{{ $barHeight - $barPaddingBottom }}"
                                                stroke="currentColor"
                                                stroke-opacity="0.32" />

                                            @foreach ($barData as $index => $bar)
                                                @php
                                                    $slotX = $barPaddingLeft + ($index * $barSlotWidth);
                                                    $barX = $slotX + (($barSlotWidth - $barRectWidth) / 2);
                                                    $heightRatio = $barMaxValue > 0 ? ($bar['value'] / $barMaxValue) : 0;
                                                    $barValueHeight = round($heightRatio * $barPlotHeight, 2);
                                                    $barY = $barPaddingTop + ($barPlotHeight - $barValueHeight);
                                                    $barCenterX = round($barX + ($barRectWidth / 2), 2);
                                                @endphp

                                                <rect
                                                    x="{{ round($barX, 2) }}"
                                                    y="{{ $barPaddingTop }}"
                                                    width="{{ $barRectWidth }}"
                                                    height="{{ $barPlotHeight }}"
                                                    rx="8"
                                                    fill="currentColor"
                                                    fill-opacity="0.08" />

                                                <rect
                                                    x="{{ round($barX, 2) }}"
                                                    y="{{ round($barY, 2) }}"
                                                    width="{{ $barRectWidth }}"
                                                    height="{{ $barValueHeight }}"
                                                    rx="8"
                                                    fill="rgb(16 185 129)" />

                                                <text
                                                    x="{{ $barCenterX }}"
                                                    y="{{ max(12, $barY - 8) }}"
                                                    text-anchor="middle"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.88">
                                                    {{ number_format($bar['value']) }}
                                                </text>

                                                <text
                                                    x="{{ $barCenterX }}"
                                                    y="{{ $barHeight - 20 }}"
                                                    text-anchor="middle"
                                                    font-size="11"
                                                    fill="currentColor"
                                                    fill-opacity="0.8">
                                                    {{ \Illuminate\Support\Str::limit($bar['label'], 14) }}
                                                </text>
                                            @endforeach
                                        </svg>
                                    </div>
                                </article>
                            </div>
                        @endif

                        <div class="space-y-3">
                            @forelse ($directivoMetrics['courses'] as $metric)
                                <article class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $metric['course_name'] }}</p>
                                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                                            {{ $metric['avg_pcpm'] !== null ? number_format($metric['avg_pcpm'], 1) : '--' }} PCPM
                                        </p>
                                    </div>
                                    <div class="mt-2 h-2 w-full rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div
                                            class="h-2 rounded-full bg-primary-500"
                                            style="width: {{ $metric['avg_pcpm_bar_percent'] }}%;"></div>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                        Intentos: {{ number_format($metric['attempts_count']) }} · Estudiantes: {{ number_format($metric['students_count']) }}
                                    </p>
                                </article>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">No hay métricas disponibles para el filtro actual.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @forelse ($cards as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span
                                    class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-bold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700"
                                    style="width: 44px; height: 44px; min-width: 44px; min-height: 44px; border-radius: 9999px; aspect-ratio: 1 / 1;">
                                    {{ $card['initials'] }}
                                </span>
                                <div>
                                    <h3 class="text-xl font-bold leading-tight text-slate-900 dark:text-slate-100">{{ $card['student_name'] }}</h3>
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['course_name'] }}</p>
                                </div>
                            </div>

                            @php
                                $trendMark = match ($card['trend']) {
                                    'up' => '↑',
                                    'down' => '↓',
                                    default => '→',
                                };
                                $trendColor = match ($card['trend']) {
                                    'up' => 'text-emerald-600 dark:text-emerald-400',
                                    'down' => 'text-rose-600 dark:text-rose-400',
                                    default => 'text-amber-600 dark:text-amber-400',
                                };
                                $scorePanel = match ($card['status_color']) {
                                    'success' => 'border-emerald-200 bg-emerald-50/80 text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200',
                                    'warning' => 'border-amber-200 bg-amber-50/80 text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200',
                                    'danger' => 'border-rose-200 bg-rose-50/80 text-rose-900 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-200',
                                    default => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200',
                                };
                            @endphp

                            <span class="text-xl font-bold {{ $trendColor }}">{{ $trendMark }}</span>
                        </div>

                        <div class="mt-4 rounded-2xl border p-4 {{ $scorePanel }}">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] opacity-75">Última puntuación</p>
                            <p class="mt-2 font-mono text-4xl font-bold leading-none">
                                {{ $card['pcpm'] ?? '--' }}
                                <span class="text-xl">PCPM</span>
                            </p>
                        </div>

                        <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                @if ($card['evaluated_human'])
                                    Evaluado: {{ $card['evaluated_human'] }}
                                @else
                                    Sin evaluación registrada
                                @endif
                            </p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-sm">
                                <span class="font-semibold {{ $card['status_color'] === 'success' ? 'text-emerald-700 dark:text-emerald-400' : ($card['status_color'] === 'warning' ? 'text-amber-700 dark:text-amber-400' : ($card['status_color'] === 'danger' ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-300')) }}">
                                    {{ $card['status_label'] }}
                                </span>
                                <span class="font-semibold text-slate-500 dark:text-slate-400">
                                    @if ($card['delta'] !== null)
                                        Δ {{ sprintf('%+d', $card['delta']) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                        No hay estudiantes o evaluaciones para mostrar con el filtro actual.
                    </article>
                @endforelse

                @if ($this->canShowAddStudentCta())
                    <a
                        href="{{ \App\Filament\Resources\StudentResource::getUrl('create') }}"
                        class="flex min-h-[18rem] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/40 p-6 text-center transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-primary-500/60 dark:hover:bg-primary-500/10">
                        <x-heroicon-m-user-plus class="h-10 w-10 text-slate-400 dark:text-slate-500" />
                        <h3 class="mt-4 text-2xl font-semibold text-slate-700 dark:text-slate-200">Agregar Estudiante</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registrar un nuevo alumno en este grupo</p>
                    </a>
                @endif
            </section>
        </div>
    @endif
</x-filament::page>
