@props([
    'title',
    'value',
    'icon' => 'chart-bar',
    'color' => 'blue'
])

@php
    $colors = [
        'blue' => ['bg' => 'bg-blue-50', 'icon' => 'bg-blue-100', 'text' => 'text-blue-600'],
        'green' => ['bg' => 'bg-green-50', 'icon' => 'bg-green-100', 'text' => 'text-green-600'],
        'amber' => ['bg' => 'bg-amber-50', 'icon' => 'bg-amber-100', 'text' => 'text-amber-600'],
        'red' => ['bg' => 'bg-red-50', 'icon' => 'bg-red-100', 'text' => 'text-red-600'],
        'indigo' => ['bg' => 'bg-indigo-50', 'icon' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
    ];
    $colorSet = $colors[$color] ?? $colors['blue'];
@endphp

<div class="bg-white rounded-xl shadow-sm border p-4">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 {{ $colorSet['icon'] }} rounded-xl flex items-center justify-center flex-shrink-0">
            @switch($icon)
                @case('clipboard-check')
                    <svg class="w-6 h-6 {{ $colorSet['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                @break
                @case('check-circle')
                    <svg class="w-6 h-6 {{ $colorSet['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @break
                @case('clock')
                    <svg class="w-6 h-6 {{ $colorSet['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @break
                @case('x-circle')
                    <svg class="w-6 h-6 {{ $colorSet['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @break
                @case('chart-bar')
                @default
                    <svg class="w-6 h-6 {{ $colorSet['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
            @endswitch
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500 truncate">{{ $title }}</p>
            <p class="text-xl font-bold text-gray-900">{{ $value }}</p>
        </div>
    </div>
</div>
