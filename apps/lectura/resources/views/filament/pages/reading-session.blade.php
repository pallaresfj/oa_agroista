@php
    /** @var \App\Filament\Pages\ReadingSession $this */
    $attempt = $this->getActiveAttemptRecord();
    $errorCounters = $this->getErrorCounters();
@endphp

<x-filament::page>
    <div class="space-y-6">
        <form wire:submit="startAttempt" class="space-y-4">
            {{ $this->form }}

            @if (! $attempt)
                <x-filament::button type="submit" icon="heroicon-m-play">
                    Iniciar lectura
                </x-filament::button>
            @endif
        </form>

        @if ($attempt)
            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Lectura en curso</p>
                            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $attempt->student?->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $attempt->passage?->title }} · {{ $attempt->word_count }} palabras</p>
                        </div>

                        <div
                            x-data="{ startedAt: new Date('{{ $attempt->started_at?->toIso8601String() }}').getTime(), now: Date.now(), tick() { this.now = Date.now() } }"
                            x-init="setInterval(() => tick(), 1000)"
                            class="rounded-2xl bg-slate-950 px-5 py-4 text-center text-white">
                            <p class="text-xs uppercase tracking-[0.28em] text-white/60">Tiempo</p>
                            <p class="mt-2 text-3xl font-semibold tabular-nums" x-text="new Date(now - startedAt).toISOString().slice(14, 19)"></p>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-50 p-6 text-lg leading-9 text-slate-800">
                        {!! nl2br(e($attempt->passage?->content ?? '')) !!}
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Marcadores de error</h3>
                        <p class="mt-1 text-sm text-slate-500">Opcionalmente agrega posición de palabra o comentario antes de marcar el error.</p>

                        <div class="mt-4 grid gap-4">
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="1" wire:model.live="errorWordIndex" placeholder="Número de palabra" />
                            </x-filament::input.wrapper>

                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model.live="errorComment" placeholder="Comentario opcional" />
                            </x-filament::input.wrapper>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Enums\ReadingErrorType::cases() as $type)
                                <button
                                    type="button"
                                    wire:click="registerError('{{ $type->value }}')"
                                    class="rounded-2xl border border-slate-200 px-4 py-4 text-left transition hover:border-slate-300 hover:bg-slate-50">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $type->label() }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">Registrados: {{ $errorCounters[$type->value] ?? 0 }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Cierre del intento</h3>
                        <div class="mt-4">
                            <x-filament::input.wrapper>
                                <textarea
                                    wire:model.live="attemptNotes"
                                    rows="4"
                                    class="block min-h-24 w-full rounded-xl border-0 bg-transparent text-sm text-slate-900 focus:ring-0"
                                    placeholder="Observaciones finales del docente"></textarea>
                            </x-filament::input.wrapper>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <x-filament::button color="success" wire:click="finishAttempt" icon="heroicon-m-stop">
                                Finalizar
                            </x-filament::button>
                            <x-filament::button color="danger" wire:click="cancelAttempt" icon="heroicon-m-x-circle">
                                Cancelar
                            </x-filament::button>
                        </div>
                    </section>
                </aside>
            </div>
        @endif

        @if ($this->lastResult)
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Resultado guardado</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-4">
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
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($this->lastResult['wpm'], 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Errores</span>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $this->lastResult['errors'] }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ $this->lastResult['attempt_url'] }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                        Ver detalle del intento
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-filament::page>
