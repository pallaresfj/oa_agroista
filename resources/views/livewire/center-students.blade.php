<div>
    <h2 class="text-xl font-semibold mb-4">Estudiantes</h2>

    @if ($students->count())
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Curso</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Nombre</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b text-sm text-gray-700">{{ $student->grade }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700">{{ $student->full_name }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700">{{ $student->identification }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 flex justify-between">
                @if ($students->hasPages())
                    <div>
                        @if ($students->onFirstPage())
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
                        @if ($students->hasMorePages())
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
        <p class="text-gray-500">No hay estudiantes registrados para este centro de interés.</p>
    @endif
</div>
