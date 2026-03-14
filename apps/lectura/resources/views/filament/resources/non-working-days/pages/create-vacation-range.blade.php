<x-filament::page>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <div class="fi-form-actions">
            <x-filament::button type="submit">
                Crear Vacaciones
            </x-filament::button>

            <x-filament::button
                color="gray"
                tag="a"
                :href="$this->getResource()::getUrl('index')"
            >
                Cancelar
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
