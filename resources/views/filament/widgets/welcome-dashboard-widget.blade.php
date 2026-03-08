<x-filament-widgets::widget>
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <div class="space-y-2 lg:max-w-4xl">
                <p class="text-3xl font-extrabold tracking-tight text-primary-600 dark:text-primary-400">
                    Centro de control academico
                </p>

                <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Bienvenido de nuevo, {{ $userName }}
                </h2>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Monitorea planes, centros, asignaturas y actividad academica desde una vista unificada.
                </p>
            </div>

            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="shrink-0 lg:ml-2">
                @csrf

                <x-filament::button
                    color="success"
                    icon="heroicon-o-arrow-left-on-rectangle"
                    type="submit"
                    size="lg"
                >
                    Salir
                </x-filament::button>
            </form>
        </div>
    </section>
</x-filament-widgets::widget>
