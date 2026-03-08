<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🏫 Resumen por Sede
        </x-slot>

        @php
            $summary = $this->getCampusSummary();
        @endphp

        <div style="overflow-x: auto; padding: 0 0.5rem;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #6b7280;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Sede
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Total
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            A tiempo
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Retardos
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Justificadas
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Inasistencias
                        </th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                            Puntualidad
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $item)
                        <tr style="border-bottom: 1px solid rgba(107, 114, 128, 0.3);">
                            <td style="padding: 0.75rem 1rem; font-weight: 500;">
                                {{ $item->campus->name }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                {{ $item->total }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #22c55e; font-weight: 500;">
                                {{ $item->on_time }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #eab308; font-weight: 500;">
                                {{ $item->late }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #3b82f6; font-weight: 500;">
                                {{ $item->justified }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center; color: #ef4444; font-weight: 500;">
                                {{ $item->absent }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;
                                    @if($item->punctuality >= 90) background-color: rgba(34, 197, 94, 0.2); color: #22c55e;
                                    @elseif($item->punctuality >= 75) background-color: rgba(234, 179, 8, 0.2); color: #eab308;
                                    @else background-color: rgba(239, 68, 68, 0.2); color: #ef4444;
                                    @endif
                                ">
                                    {{ $item->punctuality }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 2rem 1rem; text-align: center; color: #6b7280;">
                                No hay sedes activas registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
