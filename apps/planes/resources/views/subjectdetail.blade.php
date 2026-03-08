<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GESTIÓN ACADÉMICA</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-white text-gray-700">

    <!-- Header -->
    <header class="bg-white shadow-md">
      <nav x-data="{ open: false }" class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="flex items-center space-x-2">
          <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="h-16">
        </a>

        <button @click="open = !open" class="md:hidden text-gray-700 focus:outline-none transition-transform duration-300 transform" :class="{'rotate-45': open}">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <ul
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 transform scale-95"
          x-transition:enter-end="opacity-100 transform scale-100"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100 transform scale-100"
          x-transition:leave-end="opacity-0 transform scale-95"
          :class="{'block': open, 'hidden': !open, 'md:flex': true}"
          class="flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-6 text-gray-700"
        >
          <li><a href="/" class="hover:text-green-500 transition">Inicio</a></li>
          <li><a href="/planes" class="text-green-600 font-semibold">Planes</a></li>
          <li><a href="/centers" class="hover:text-green-500 transition">Centros</a></li>
          <li><a href="/admin" class="hover:text-green-500 transition">Ingresar</a></li>
        </ul>
      </nav>
    </header>

    <!-- Banner Starts Here -->
    <div class="bg-gray-50 text-center py-12">
      <section class="max-w-7xl mx-auto px-4">
        <a href="/plan/{{ $subject->plan_id }}">
            <h4 class="text-green-600 text-lg hover:underline font-semibold mb-2">{{ $subject->plan->name }}</h4>
        </a>
        <h2 class="text-4xl font-bold mb-2">{{ $subject->name }}</h2>
      </section>
    </div>
    <!-- Banner Ends Here -->

    <section class="max-w-7xl mx-auto px-4 py-10">
      <div class="w-full">
            <article class="bg-white shadow-md rounded-lg overflow-hidden w-full">
                <img src="{{ asset('images/portada.jpg') }}" alt="Portada" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span class="text-green-600 text-sm font-semibold block mt-0">{{ $subject->grade }}° Grado | {{ $subject->weekly_hours }} Horas</span>
                    <h4 class="block mt-2 mb-3 text-xl font-semibold">{{ $subject->name }}</h4>
                    <ul class="flex space-x-4 text-sm text-gray-500 mb-4">
                        <li class="text-green-600 font-semibold mb-2">
                            Docentes de la asignatura:
                            <div class="mt-2 space-y-2">
                                @foreach ($subject->users as $user)
                                    <div class="flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1">
                                        <img src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : asset('images/default-avatar.png') }}"
                                             alt="{{ $user->name }}"
                                             class="w-8 h-8 rounded-full object-cover">
                                        <span class="text-sm font-normal">{{ $user->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                    <div x-data="{ tab: 'interest_centers' }">
                        <div class="flex flex-wrap border-b border-gray-200 mb-6 gap-2 w-full">
                            <button @click="tab = 'interest_centers'"
                                :class="tab === 'interest_centers' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Centros de interés
                            </button>
                            <button @click="tab = 'topics'"
                                :class="tab === 'topics' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Contenidos
                            </button>
                            <button @click="tab = 'rubrics'"
                                :class="tab === 'rubrics' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Rúbricas
                            </button>
                        </div>

                        <div>
                            <div x-show="tab === 'interest_centers'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Contribuciones</h2>
                                <div class="prose max-w-none">
                                    {!! $subject->contributions !!}
                                </div>
                                <h2 class="text-xl font-semibold my-4">Estrategias</h2>
                                <div class="prose max-w-none">
                                    {!! $subject->strategies !!}
                                </div>
                            </div>

                            <div x-show="tab === 'topics'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Contenidos</h2>
                                @if ($subject->topics->count())
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full bg-white border border-gray-200">
                                            @php
                                                $standardLabel = ((string) $subject->grade === '0') ? 'Principio' : 'Estándar';
                                            @endphp
                                            <thead>
                                                <tr>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Periodo</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">{{ $standardLabel }}</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">DBA</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Competencias</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Contenidos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($subject->topics as $topic)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{{ $topic->period }}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $topic->standard !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $topic->dba !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $topic->competencies !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $topic->contents !!}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-gray-500">No hay contenidos registrados para esta asignatura.</p>
                                @endif
                            </div>

                            <div x-show="tab === 'rubrics'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Rúbricas</h2>
                                @if ($subject->rubrics->count())
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full bg-white border border-gray-200">
                                            <thead>
                                                <tr>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Periodo</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Criterio</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Superior</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Alto</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Básico</th>
                                                    <th class="px-4 py-2 border-b text-left text-sm font-semibold text-gray-700">Bajo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($subject->rubrics as $rubric)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{{ $rubric->period }}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $rubric->criterion !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $rubric->superior_level !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $rubric->high_level !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $rubric->basic_level !!}</td>
                                                        <td class="px-4 py-2 border-b text-sm text-gray-700 align-top">{!! $rubric->low_level !!}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-gray-500">No hay rúbricas registradas para esta asignatura.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </article>
      </div>
    </section>

    <footer class="bg-gray-900 text-gray-300 py-6">
      <div class="max-w-7xl mx-auto text-center text-sm">
        <p>Copyright 2025. IED Agropecuaria José María Herrera - Pivijay | Diseño <a rel="nofollow" href="https://asyservicios.com" target="_blank" class="text-green-500 hover:underline">AS&Servicios.com</a></p>
      </div>
    </footer>

</body>
    <script src="//unpkg.com/alpinejs" defer></script>
</html>
