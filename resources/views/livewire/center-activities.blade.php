<div>
    <h2 class="text-xl font-semibold mb-4">Actividades</h2>

    @if ($activities->count())
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed bg-white border border-gray-200">
                <colgroup>
                    <col style="width: 15%">
                    <col style="width: 15%">
                    <col style="width: 30%">
                    <col style="width: 15%">
                    <col style="width: 25%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Fecha</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Actividad</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Objetivo</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Metodología</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Materiales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">
                                {{ \Carbon\Carbon::parse($activity->week)->translatedFormat('F d') }}
                            </td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{{ $activity->activity }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 align-top break-words">{{ $activity->objective }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 align-top break-words">{!! $activity->methodology !!}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 align-top break-words">{!! $activity->materials !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 flex justify-between">
                @if ($activities->hasPages())
                    <div>
                        @if ($activities->onFirstPage())
                            <span class="px-4 py-2 text-sm text-gray-400 bg-gray-200 rounded cursor-not-allowed">Anterior</span>
                        @else
                            <button
                                wire:click.prevent="previousPage"
                                class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Anterior
                            </button>
                        @endif
                    </div>

                    <div>
                        @if ($activities->hasMorePages())
                            <button
                                wire:click.prevent="nextPage"
                                class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Siguiente
                            </button>
                        @else
                            <span class="px-4 py-2 text-sm text-gray-400 bg-gray-200 rounded cursor-not-allowed">Siguiente</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <p class="text-gray-500">No hay actividades registradas para este centro de interés.</p>
    @endif
</div>
