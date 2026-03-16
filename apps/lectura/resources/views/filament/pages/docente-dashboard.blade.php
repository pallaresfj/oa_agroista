<x-filament::page>
    @if (! $this->isDocenteContext())
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
                        <x-filament::button
                            tag="a"
                            :href="$this->getNewEvaluationUrl()"
                            color="primary"
                            icon="heroicon-m-plus">
                            Nueva Evaluación
                        </x-filament::button>

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
