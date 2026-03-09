<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GESTIÓN ACADÉMICA</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.institution-theme')
    @livewireStyles
  </head>

  <body class="bg-white text-gray-700">

    <!-- Header -->
    <header class="bg-white shadow-md">
      <nav x-data="{ open: false }" class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="flex items-center space-x-2">
          <img src="{{ data_get($institutionBranding ?? [], 'logo_url') ?: asset('images/logo.svg') }}" alt="Logo" class="h-16">
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
          <li><a href="/planes" class="hover:text-green-500 transition">Planes</a></li>
          <li><a href="/centers" class="text-green-600 font-semibold">Centros</a></li>
          <li><a href="/admin" class="hover:text-green-500 transition">Ingresar</a></li>
        </ul>
      </nav>
    </header>

    <!-- Banner Starts Here -->
    <div class="bg-gray-50 text-center py-12">
      <section class="max-w-7xl mx-auto px-4">
        <a href="/centers">
          <h4 class="text-green-600 text-lg hover:underline font-semibold mb-2">Centros de interés</h4>
        </a>
        <h2 class="text-4xl font-bold">{{ $center->name }}</h2>
      </section>
    </div>
    <!-- Banner Ends Here -->

    <section class="max-w-7xl mx-auto px-4 py-10">
      <div class="w-full">
            <article class="bg-white shadow-md rounded-lg overflow-hidden w-full">
                <img src="{{ $center->image_url }}" alt="" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span class="text-green-600 text-sm font-semibold">{{ $center->academic_year }}</span>
                    <h4 class="block mt-2 mb-3 transition text-xl font-semibold">{{ $center->name }}</h4>
                    <ul class="flex space-x-4 text-sm text-gray-500 mb-4">
                      <li class="text-green-500 font-semibold mb-2">
                          Docentes del centro:
                          @if($center->teachers->count())
                              <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                  @foreach ($center->teachers as $teacher)
                                      <div class="flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1">
                                          <img src="{{ $teacher->profile_photo_path 
                                                    ? asset('storage/' . $teacher->profile_photo_path) 
                                                    : asset('images/default-avatar.png') }}"
                                               alt="{{ $teacher->full_name }}"
                                               class="w-8 h-8 rounded-full object-cover">
                                          <span class="text-sm font-normal">{{ $teacher->full_name }}</span>
                                      </div>
                                  @endforeach
                              </div>
                          @else
                              <div class="mt-2">
                                  No hay docentes asignados.
                              </div>
                          @endif
                      </li>
                    </ul>
                    <div x-data="{ 
                        tab: localStorage.getItem('centerTab') || 'description',
                        setTab(value) {
                            this.tab = value;
                            localStorage.setItem('centerTab', value);
                        }
                    }">
                        <div class="flex flex-wrap border-b border-gray-200 mb-6 gap-2 w-full">
                            <button @click="setTab('description')"
                                :class="tab === 'description' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Descripción
                            </button>
                            <button @click="setTab('objective')"
                                :class="tab === 'objective' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Objetivo
                            </button>
                            <button @click="setTab('students')"
                                :class="tab === 'students' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Estudiantes
                            </button>
                            <button @click="setTab('activities')"
                                :class="tab === 'activities' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Actividades
                            </button>
                            <button @click="setTab('budgets')"
                                :class="tab === 'budgets' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Recursos
                            </button>
                        </div>

                        <div>
                            <div x-show="tab === 'description'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Descripción</h2>
                                <div class="prose max-w-none">
                                    {!! $center->description !!}
                                </div>
                            </div>

                            <div x-show="tab === 'objective'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Objetivo</h2>
                                <div class="prose max-w-none">
                                    {!! $center->objective !!}
                                </div>
                            </div>

                            <div x-show="tab === 'students'" x-cloak>
                                <livewire:center-students :centerId="$center->id" wire:noscroll />
                            </div>

                            <div x-show="tab === 'activities'" x-cloak>
                                <livewire:center-activities :centerId="$center->id" wire:noscroll />
                            </div>

                            <div x-show="tab === 'budgets'" x-cloak>
                                <livewire:center-budgets :centerId="$center->id" wire:noscroll />
                            </div>

                        </div>
                    </div>
                </div>
            </article>
      </div>
    </section>

    <footer class="bg-gray-900 text-gray-300 py-6">
      <div class="max-w-7xl mx-auto text-center text-sm">
        <p>Copyright 2025. {{ data_get($institutionBranding ?? [], 'name', config('app.name', 'Institucion')) }} - Pivijay | Diseño <a rel="nofollow" href="https://asyservicios.com" target="_blank" class="text-green-500 hover:underline">AS&Servicios.com</a></p>
      </div>
    </footer>
    
</body>
    @livewireScripts
    {{-- <script src="//unpkg.com/alpinejs" defer></script> --}}

</html>
