<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            ⚠️ Docentes Sin Registro Hoy
        </x-slot>

        @php
            $absentUsers = $this->getAbsentUsers();
        @endphp

        @if($absentUsers->count() > 0)
            <div style="overflow-x: auto; padding: 0 0.5rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #6b7280;">
                            <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit; width: 60px;">
                                No
                            </th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit;">
                                Docente
                            </th>
                            <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background-color: rgba(107, 114, 128, 0.2); color: inherit; width: 120px;">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($absentUsers as $index => $user)
                            <tr style="border-bottom: 1px solid rgba(107, 114, 128, 0.3);">
                                <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 500;">
                                    {{ $index + 1 }}
                                </td>
                                <td style="padding: 0.75rem 1rem; font-weight: 500;">
                                    {{ $user->name }}
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: rgba(239, 68, 68, 0.2); color: #ef4444;">
                                        Sin registro
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 1rem; text-align: right;">
                Total: {{ $absentUsers->count() }} {{ $absentUsers->count() === 1 ? 'docente' : 'docentes' }}
            </div>
        @else
            <div style="text-align: center; padding: 2rem 0;">
                <div style="color: #22c55e; font-size: 2.5rem; margin-bottom: 0.5rem;">✓</div>
                <p style="color: #6b7280;">
                    Todos los docentes han registrado su asistencia
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
