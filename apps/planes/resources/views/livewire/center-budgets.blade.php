<div>
    <h2 class="text-xl font-semibold mb-4">Recursos</h2>

    @if ($budgets->count())
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b text-right text-sm font-semibold text-gray-700">Cantidad</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Item</th>
                        <th class="px-4 py-2 border-b text-right text-sm font-semibold text-gray-700">Valor Unitario</th>
                        <th class="px-4 py-2 border-b text-right text-sm font-semibold text-gray-700">Total</th>
                        <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($budgets as $budget)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b text-sm text-gray-700 text-right">{{ $budget->quantity }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700">{{ $budget->item }}</td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 text-right">
                                {{ '$' . number_format($budget->unit_value, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700 text-right">
                                {{ '$' . number_format($budget->quantity * $budget->unit_value, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border-b text-sm text-gray-700">{{ $budget->observations }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="px-4 py-2 border-t text-right text-sm font-semibold text-gray-700">Subtotal (página actual):</td>
                        <td class="px-4 py-2 border-t text-sm text-gray-700 text-right">
                            <strong>
                                {{ '$' . number_format($budgets->sum(fn($b) => $b->quantity * $b->unit_value), 0, ',', '.') }}
                            </strong>
                        </td>
                        <td class="px-4 py-2 border-t"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-4 py-2 border-t text-right text-sm font-semibold text-gray-700">Total General:</td>
                        <td class="px-4 py-2 border-t text-sm text-gray-700 text-right">
                            <strong>
                                {{ '$' . number_format($totalBudgets, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td class="px-4 py-2 border-t"></td>
                    </tr>
                </tfoot>
            </table>
            <div class="mt-4 flex justify-between">
                @if ($budgets->hasPages())
                    <div>
                        @if ($budgets->onFirstPage())
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
                        @if ($budgets->hasMorePages())
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
        <p class="text-gray-500">No hay recursos registrados para este centro de interés.</p>
    @endif
</div>
