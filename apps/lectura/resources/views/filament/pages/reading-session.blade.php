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
        window.oaReadingTimer = function ({ startedAtMs, finalCentiseconds }) {
            return {
                startedAtMs,
                finalCentiseconds,
                now: Date.now(),
                ticker: null,
                init() {
                    this.$watch('startedAtMs', (value) => {
                        if (value) {
                            this.startTicker();
                            return;
                        }

                        this.stopTicker();
                        this.now = Date.now();
                    });

                    if (this.startedAtMs) {
                        this.startTicker();
                    }
                },
                startTicker() {
                    this.stopTicker();
                    this.ticker = setInterval(() => {
                        this.now = Date.now();
                    }, 10);
                },
                stopTicker() {
                    if (! this.ticker) {
                        return;
                    }

                    clearInterval(this.ticker);
                    this.ticker = null;
                },
                centiseconds() {
                    if (! this.startedAtMs) {
                        return Number(this.finalCentiseconds) || 0;
                    }

                    return Math.max(0, Math.floor((this.now - Number(this.startedAtMs)) / 10));
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

    <style>
        .oa-reading-scroll {
            max-height: calc(100vh - 29rem);
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        @media (max-width: 768px) {
            .oa-reading-scroll {
                max-height: calc(100vh - 26rem);
            }
        }
    </style>

    <div class="space-y-6">
        <div
            data-reading-fixed-header
            class="space-y-6"
            style="position: sticky; top: 0.75rem; z-index: 30;">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                {{ $this->form }}

                @if ($studentOptions === [])
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-200">
                        No hay estudiantes disponibles para su perfil. Si su rol es docente, primero asigne uno o más cursos al usuario.
                    </div>
                @endif
            </section>
            <section
                x-data="oaReadingTimer({ startedAtMs: $wire.entangle('activeAttemptStartedAtMs'), finalCentiseconds: $wire.entangle('finalCentiseconds') })"
                x-init="init()"
                class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="font-mono text-7xl font-bold leading-none tracking-tight text-slate-900 dark:text-slate-100 md:text-8xl">
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
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:p-10">
            <h2 class="mb-5 flex items-center gap-2 text-2xl font-semibold text-primary-600">
                <x-heroicon-m-book-open class="h-6 w-6" />
                Texto de lectura
            </h2>

            @if ($selectedPassage)
                <div data-reading-scroll class="oa-reading-scroll space-y-4 text-2xl leading-relaxed text-slate-800 dark:text-slate-200 md:text-3xl">
                    {!! nl2br(e($selectedPassage->content)) !!}
                </div>
            @else
                <p class="text-base text-slate-500 dark:text-slate-400">
                    Seleccione una lectura para mostrar el texto.
                </p>
            @endif
        </section>

        @if ($this->lastResult)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 dark:border-emerald-700 dark:bg-emerald-900/30">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700 dark:text-emerald-300">Resultado guardado</p>
                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl bg-white p-4 dark:bg-slate-900">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Tiempo</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ gmdate('i:s', $this->lastResult['duration']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 dark:bg-slate-900">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Palabras</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $this->lastResult['word_count'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 dark:bg-slate-900">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">WPM</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($this->lastResult['wpm'], 0) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 dark:bg-slate-900">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Errores</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $this->lastResult['errors'] }}</p>
                    </div>
                </div>
            </section>
        @endif

    </div>

    @if ($showFinalizeModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-3 sm:items-center sm:p-4">
            <div class="flex max-h-[calc(100vh-1.5rem)] w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-slate-900 sm:max-h-[calc(100vh-2rem)]">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900 sm:px-8 sm:py-6">
                    <h3 class="text-3xl font-bold text-slate-900 dark:text-slate-100 sm:text-5xl">Finalizar Evaluación</h3>
                    <button
                        type="button"
                        wire:click="closeFinalizeModal"
                        class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        aria-label="Cerrar">
                        <x-heroicon-m-x-mark class="h-8 w-8 sm:h-9 sm:w-9" />
                    </button>
                </div>

                <div class="space-y-8 overflow-y-auto px-5 py-5 sm:px-8 sm:py-8">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl bg-slate-100 p-6 text-center dark:bg-slate-800">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tiempo final</p>
                            <p class="mt-2 font-mono text-6xl font-bold text-primary-600">{{ $this->formatCentiseconds($finalCentiseconds) }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-100 p-6 text-center dark:bg-slate-800">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">PPM (Aprox)</p>
                            <p class="mt-2 text-6xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($this->getApproxWpm(), 0) }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-center text-4xl font-semibold text-slate-900 dark:text-slate-100">Cantidad de Errores</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($errorTypeLabels as $type => $label)
                                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                                    <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <button
                                            type="button"
                                            wire:click="adjustErrorCount('{{ $type }}', -1)"
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-3xl font-semibold text-primary-600 transition hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600">
                                            -
                                        </button>
                                        <span class="text-5xl font-bold text-slate-900 dark:text-slate-100">{{ (int) ($pendingErrorCounts[$type] ?? 0) }}</span>
                                        <button
                                            type="button"
                                            wire:click="adjustErrorCount('{{ $type }}', 1)"
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-3xl font-semibold text-primary-600 transition hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600">
                                            +
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-center text-xl font-semibold text-slate-900 dark:text-slate-100">
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
