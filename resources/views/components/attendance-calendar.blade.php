@props([
    'data' => []
])

@php
    // Days of week headers (starting from Monday)
    $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    
    // Get the first day of the month to calculate offset (Monday = 0)
    $firstDate = collect($data)->keys()->first();
    $dayOfWeek = $firstDate ? \Carbon\Carbon::parse($firstDate)->dayOfWeek : 1;
    $startOffset = $dayOfWeek === 0 ? 6 : $dayOfWeek - 1;
    
    // Get current month name
    $monthName = $firstDate ? \Carbon\Carbon::parse($firstDate)->isoFormat('MMMM YYYY') : '';
@endphp

<div>
    {{-- Calendar Grid --}}
    <div class="bg-gray-50 rounded-xl p-4">
        {{-- Month Display --}}
        <div class="text-center mb-4">
            <span class="text-lg font-semibold text-gray-700 capitalize">{{ $monthName }}</span>
        </div>

        {{-- Day headers --}}
        <div class="grid grid-cols-7 gap-1 mb-2">
            @foreach($days as $index => $day)
                <div class="text-center text-xs font-semibold py-2 {{ $index >= 5 ? 'text-red-400' : 'text-gray-500' }}">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Calendar days --}}
        <div class="grid grid-cols-7 gap-1">
            {{-- Empty cells for offset --}}
            @for($i = 0; $i < $startOffset; $i++)
                <div class="aspect-square"></div>
            @endfor

            {{-- Calendar days --}}
            @foreach($data as $date => $dayData)
                @php
                    $isToday = \Carbon\Carbon::parse($date)->isToday();
                    $isSunday = \Carbon\Carbon::parse($date)->dayOfWeek === 0;
                    $isSaturday = \Carbon\Carbon::parse($date)->dayOfWeek === 6;
                    $isWeekend = $isSunday || $isSaturday;
                    $isPast = \Carbon\Carbon::parse($date)->isPast() && !$isToday;
                @endphp
                <div 
                    class="aspect-square rounded-lg flex flex-col items-center justify-center transition-all {{ $isToday ? 'ring-2 ring-blue-500 ring-offset-1' : '' }} {{ $dayData['status'] ? 'bg-white shadow-sm' : 'bg-gray-100' }}"
                    title="{{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D [de] MMMM') }}{{ $dayData['status'] ? ' - ' . \App\Enums\AttendanceStatus::tryFrom($dayData['status'])?->label() : '' }}"
                >
                    {{-- Day number --}}
                    <span class="text-xs font-medium {{ $isToday ? 'text-blue-600' : ($isWeekend ? 'text-red-400' : 'text-gray-600') }}">
                        {{ $dayData['day'] }}
                    </span>
                    
                    {{-- Status Icon --}}
                    @if($dayData['status'])
                        <div class="mt-0.5">
                            @switch($dayData['status'])
                                @case('on_time')
                                    {{-- Check circle - A tiempo --}}
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('late')
                                    {{-- Clock - Retardo --}}
                                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('absent')
                                    {{-- X circle - Falta --}}
                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('justified')
                                    {{-- Document check - Justificado --}}
                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5zm6.61 10.936a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                        <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                                    </svg>
                                    @break
                            @endswitch
                        </div>
                    @elseif($isPast && !$isWeekend)
                        {{-- Inasistencia para días pasados (excepto fines de semana) --}}
                        <div class="mt-0.5">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
