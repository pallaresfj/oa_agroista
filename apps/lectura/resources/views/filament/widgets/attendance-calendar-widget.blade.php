<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <span>📆 Calendario de Asistencias</span>
                
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-filament::icon-button 
                        icon="heroicon-m-chevron-left"
                        wire:click="previousMonth"
                        label="Mes anterior"
                    />
                    
                    <span style="min-width: 120px; text-align: center; font-size: 0.875rem; font-weight: 500;">
                        {{ \Carbon\Carbon::parse($selectedMonth)->locale('es')->isoFormat('MMMM YYYY') }}
                    </span>
                    
                    <x-filament::icon-button 
                        icon="heroicon-m-chevron-right"
                        wire:click="nextMonth"
                        label="Mes siguiente"
                    />
                </div>
            </div>
        </x-slot>

        {{-- Legend --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; padding: 0.75rem 1rem; background-color: rgba(107, 114, 128, 0.1); border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.75rem;">
            <span style="font-weight: 600; color: #9ca3af;">Convenciones:</span>
            <div style="display: flex; align-items: center; gap: 0.375rem;">
                <svg style="width: 1rem; height: 1rem; color: #10b981;" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                </svg>
                <span>A tiempo</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.375rem;">
                <svg style="width: 1rem; height: 1rem; color: #f59e0b;" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
                </svg>
                <span>Retardo</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.375rem;">
                <svg style="width: 1rem; height: 1rem; color: #3b82f6;" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5zm6.61 10.936a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                </svg>
                <span>Justificada</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.375rem;">
                <svg style="width: 1rem; height: 1rem; color: #ef4444;" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                </svg>
                <span>Inasistencia</span>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.375rem;">
            {{-- Day headers (starting from Monday) --}}
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $index => $day)
                <div style="text-align: center; font-size: 0.75rem; font-weight: 600; padding: 0.5rem 0; color: {{ $index === 6 ? '#f87171' : '#9ca3af' }};">
                    {{ $day }}
                </div>
            @endforeach

            @php
                $calendarData = $this->getCalendarData();
                // Calculate offset for Monday start (0=Mon, 1=Tue, ..., 6=Sun)
                $dayOfWeek = count($calendarData) > 0 ? reset($calendarData)['date']->dayOfWeek : 1;
                $startDay = $dayOfWeek === 0 ? 6 : $dayOfWeek - 1;
            @endphp

            {{-- Empty cells for offset --}}
            @for($i = 0; $i < $startDay; $i++)
                <div style="aspect-ratio: 1 / 1;"></div>
            @endfor

            {{-- Calendar days --}}
            @foreach($calendarData as $dateKey => $data)
                @php
                    $isToday = $data['date']->isToday();
                    $isSunday = $data['date']->dayOfWeek === 0;
                    $isSaturday = $data['date']->dayOfWeek === 6;
                    $isWeekend = $isSunday || $isSaturday;
                    $isPast = $data['date']->isPast() && !$isToday;
                    $statusValue = $data['hasAttendance'] ? $data['status']->value : null;
                    $hasSchedule = $data['hasSchedule'] ?? false;
                    $isNonWorkingDay = $data['isNonWorkingDay'] ?? false;
                    // Solo mostrar inasistencia si: es día pasado, tiene horario asignado, no es día no laborable y no tiene registro
                    $showAsAbsent = $isPast && $hasSchedule && !$isNonWorkingDay && !$data['hasAttendance'];
                @endphp
                <div 
                    style="aspect-ratio: 1 / 1; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 0.5rem; {{ $isToday ? 'box-shadow: 0 0 0 2px #3b82f6;' : '' }} {{ $data['hasAttendance'] ? 'background-color: rgba(255,255,255,0.1);' : '' }}"
                    title="{{ $data['date']->isoFormat('dddd, D [de] MMMM') }}{{ $data['hasAttendance'] ? ' - ' . $data['status']->label() : ($showAsAbsent ? ' - Sin registro' : '') }}"
                >
                    {{-- Day number --}}
                    <span style="font-size: 0.75rem; font-weight: 500; color: {{ $isToday ? '#60a5fa' : ($isWeekend ? '#f87171' : '#9ca3af') }};">
                        {{ $data['day'] }}
                    </span>
                    
                    {{-- Status Icon --}}
                    @if($data['hasAttendance'])
                        <div style="margin-top: 0.125rem;">
                            @switch($statusValue)
                                @case('on_time')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #10b981;" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('late')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b;" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('absent')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #ef4444;" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('justified')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #3b82f6;" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5zm6.61 10.936a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                        <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                                    </svg>
                                    @break
                            @endswitch
                        </div>
                    @elseif($showAsAbsent)
                        {{-- Inasistencia: día pasado con horario asignado sin registro --}}
                        <div style="margin-top: 0.125rem;">
                            <svg style="width: 1.25rem; height: 1.25rem; color: #ef4444;" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
