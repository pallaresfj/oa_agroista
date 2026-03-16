@php
    /** @var \App\Filament\Pages\ReadingSession $this */
    $attempt = $this->getActiveAttemptRecord();
    $studentOptions = $this->getStudentOptions();
    $passageOptions = $this->getPassageOptions();
    $selectedPassage = $attempt?->passage ?? $this->getSelectedPassage();
    $errorTypeLabels = $this->getErrorTypeLabels();
@endphp

<x-filament::page>
    <script>
        window.oaReadingTimer = function (startedAt, fallbackCentiseconds) {
            return {
                startedAt,
                fallbackCentiseconds,
                now: Date.now(),
                ticker: null,
                init() {
                    if (this.startedAt) {
                        this.ticker = setInterval(() => {
                            this.now = Date.now();
                        }, 10);
                    }
                },
                centiseconds() {
                    if (! this.startedAt) {
                        return this.fallbackCentiseconds;
                    }

                    return Math.max(0, Math.floor((this.now - this.startedAt) / 10));
                },
                formatted() {
                    const centiseconds = this.centiseconds();
                    const minutes = String(Math.floor(centiseconds / 6000)).padStart(2, '0');
                    const seconds = String(Math.floor((centiseconds % 6000) / 100)).padStart(2, '0');
                    const hundredths = String(centiseconds % 100).padStart(2, '0');

                    return `${minutes}:${seconds}.${hundredths}`;
                },
                main() {
                    return this.formatted().slice(0, 5);
                },
                decimals() {
                    return this.formatted().slice(5);
                },
            };
        };
    </script>

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="block text-sm font-semibold text-slate-700">Estudiante</span>
                    <select
                        wire:model.live="studentId"
                        @disabled($attempt !== null)
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-slate-100">
                        <option value="">Seleccionar estudiante...</option>
                        @foreach ($studentOptions as $optionId => $label)
                            <option value="{{ $optionId }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="block text-sm font-semibold text-slate-700">Lectura</span>
                    <select
                        wire:model.live="passageId"
                        @disabled($attempt !== null)
                        class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-slate-100">
                        <option value="">Seleccionar lectura...</option>
                        @foreach ($passageOptions as $optionId => $label)
                            <option value="{{ $optionId }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if ($studentOptions === [])
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay estudiantes disponibles para su perfil. Si su rol es docente, primero asigne uno o más cursos al usuario.
                </div>
            @endif
        </section>

        <section
            x-data="oaReadingTimer({{ $attempt?->started_at?->valueOf() ?? 'null' }}, {{ (int) $finalCentiseconds }})"
            x-init="init()"
            class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
            <div class="font-mono text-7xl font-bold leading-none tracking-tight text-slate-900 md:text-8xl">
                <span x-text="main()">00:00</span><span class="text-4xl text-primary-600 md:text-5xl" x-text="decimals()">.00</span>
            </div>

            <div class="mt-7">
                @if ($attempt)
                    <x-filament::button
                        wire:click="stopAttempt"
                        color="danger"
                        icon="heroicon-m-stop-circle"
                        size="xl"
                        class="min-w-72">
                        Detener
                    </x-filament::button>
                @else
                    <x-filament::button
                        wire:click="startAttempt"
                        color="success"
                        icon="heroicon-m-play-circle"
                        size="xl"
                        class="min-w-72"
                        :disabled="$studentOptions === [] || $passageOptions === []">
                        Iniciar
                    </x-filament::button>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-10">
            <h2 class="mb-5 flex items-center gap-2 text-2xl font-semibold text-primary-600">
                <x-heroicon-m-book-open class="h-6 w-6" />
                Texto de lectura
            </h2>

            @if ($selectedPassage)
                <div class="space-y-4 text-xl leading-relaxed text-slate-800">
                    {!! nl2br(e($selectedPassage->content)) !!}
                </div>
            @else
                <p class="text-base text-slate-500">
                    Seleccione una lectura para mostrar el texto.
                </p>
            @endif
        </section>

        @if ($this->lastResult)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Resultado guardado</p>
                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl bg-white p-4">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Tiempo</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ gmdate('i:s', $this->lastResult['duration']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Palabras</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $this->lastResult['word_count'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">WPM</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($this->lastResult['wpm'], 0) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Errores</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $this->lastResult['errors'] }}</p>
                    </div>
                </div>
            </section>
        @endif
    </div>

    @if ($showFinalizeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-4xl overflow-hidden rounded-[2rem] bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-8 py-6">
                    <h3 class="text-5xl font-bold text-slate-900">Finalizar Evaluación</h3>
                    <button
                        type="button"
                        wire:click="closeFinalizeModal"
                        class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Cerrar">
                        <x-heroicon-m-x-mark class="h-9 w-9" />
                    </button>
                </div>

                <div class="space-y-8 px-8 py-8">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl bg-slate-100 p-6 text-center">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Tiempo final</p>
                            <p class="mt-2 font-mono text-6xl font-bold text-primary-600">{{ $this->formatCentiseconds($finalCentiseconds) }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-100 p-6 text-center">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">PPM (Aprox)</p>
                            <p class="mt-2 text-6xl font-bold text-slate-800">{{ number_format($this->getApproxWpm(), 0) }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-center text-4xl font-semibold text-slate-900">Cantidad de Errores</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($errorTypeLabels as $type => $label)
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-lg font-semibold text-slate-700">{{ $label }}</p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <button
                                            type="button"
                                            wire:click="adjustErrorCount('{{ $type }}', -1)"
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-3xl font-semibold text-primary-600 transition hover:bg-slate-300">
                                            -
                                        </button>
                                        <span class="text-5xl font-bold text-slate-900">{{ (int) ($pendingErrorCounts[$type] ?? 0) }}</span>
                                        <button
                                            type="button"
                                            wire:click="adjustErrorCount('{{ $type }}', 1)"
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-3xl font-semibold text-primary-600 transition hover:bg-slate-300">
                                            +
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-center text-xl font-semibold text-slate-900">
                            Total errores: {{ $this->getTotalErrors() }}
                        </p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <x-filament::button
                            color="warning"
                            icon="heroicon-m-bookmark-square"
                            wire:click="saveEvaluation"
                            size="xl"
                            class="w-full justify-center">
                            Guardar Evaluación
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            wire:click="discardAndReset"
                            size="xl"
                            class="w-full justify-center">
                            Descartar y Reiniciar
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament::page>
