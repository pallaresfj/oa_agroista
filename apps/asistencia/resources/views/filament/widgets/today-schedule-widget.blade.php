<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $schedule = $this->getSchedule();
        @endphp

        <x-slot name="heading">
            📅 Hoy
        </x-slot>

        @if($schedule)
            <style>
                .schedule-grid {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
                @media (min-width: 768px) {
                    .schedule-grid {
                        grid-template-columns: repeat(4, 1fr);
                    }
                }
            </style>
            <div class="schedule-grid">
                {{-- Sede Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-wi-stats-overview-stat-content grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                Sede
                            </span>
                        </div>
                        <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $schedule->campus->name }}
                        </div>
                    </div>
                </div>
                
                {{-- Entrada Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-wi-stats-overview-stat-content grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                Entrada
                            </span>
                        </div>
                        <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white tabular-nums">
                            {{ \Carbon\Carbon::parse($schedule->check_in_time)->format('H:i') }}
                        </div>
                    </div>
                </div>
                
                {{-- Salida Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-wi-stats-overview-stat-content grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                Salida
                            </span>
                        </div>
                        <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white tabular-nums">
                            {{ \Carbon\Carbon::parse($schedule->check_out_time)->format('H:i') }}
                        </div>
                    </div>
                </div>
                
                {{-- Tolerancia Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-wi-stats-overview-stat-content grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                            <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                Tolerancia
                            </span>
                        </div>
                        <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $schedule->tolerance_minutes }} <span class="text-lg font-normal">min</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 2.5rem 1rem; color: #9ca3af;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5;">📅</div>
                <p style="font-size: 0.875rem;">No tienes horario asignado para hoy</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
