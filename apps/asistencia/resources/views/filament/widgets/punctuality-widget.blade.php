<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📊 Puntualidad de la Institución
        </x-slot>

        @php
            $punctuality = $this->getPunctuality();
        @endphp

        <div style="display: flex; flex-direction: column; align-items: center; padding: 1rem;">
            {{-- Circular Progress --}}
            <div style="position: relative; width: 160px; height: 160px;">
                <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 36 36">
                    {{-- Background Circle --}}
                    <path
                        style="fill: none; stroke: rgba(107, 114, 128, 0.2); stroke-width: 3;"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    {{-- Progress Circle --}}
                    <path
                        style="fill: none; stroke: #22c55e; stroke-width: 3; stroke-linecap: round; stroke-dasharray: {{ $punctuality }}, 100;"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                </svg>
                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-size: 1.875rem; font-weight: 700;">{{ $punctuality }}%</span>
                    <span style="font-size: 0.75rem; color: #6b7280;">Puntualidad</span>
                </div>
            </div>

            <p style="margin-top: 1rem; text-align: center; font-size: 0.875rem; color: #6b7280;">
                Docentes que llegan a tiempo
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
