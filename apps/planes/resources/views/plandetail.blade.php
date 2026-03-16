<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GESTIÓN ACADÉMICA</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.institution-theme')
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
          <li><a href="/planes" class="text-green-600 font-semibold">Planes</a></li>
          <li><a href="/centers" class="hover:text-green-500 transition">Centros</a></li>
          <li><a href="/admin" class="hover:text-green-500 transition">Ingresar</a></li>
        </ul>
      </nav>
    </header>

    <!-- Banner Starts Here -->
    <div class="bg-gray-50 text-center py-12">
      <section class="max-w-7xl mx-auto px-4">
        <a href="/planes">
          <h4 class="text-green-600 text-lg hover:underline font-semibold mb-2">Planes de área</h4>
        </a>
        
        <h2 class="text-4xl font-bold">{{ $plan->name }}</h2>
      </section>
    </div>
    <!-- Banner Ends Here -->

    <section class="max-w-7xl mx-auto px-4 py-10">
      <div class="w-full">
            <article class="bg-white shadow-md rounded-lg overflow-hidden w-full">
                <img src="{{ $plan->cover_url }}" alt="" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span class="text-green-600 text-sm font-semibold">{{ $plan->year }}</span>
                    <h4 class="block mt-2 mb-3 transition text-xl font-semibold">{{ $plan->name }}</h4>
                    <ul class="flex space-x-4 text-sm text-gray-500 mb-4">
                      <li class="text-green-500 font-semibold mb-2">
                        Docentes del área:
                        <div class="mt-2 space-y-2">
                            @foreach ($plan->users as $user)
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
                    <div x-data="{ tab: 'justification' }">
                        <div class="flex flex-wrap border-b border-gray-200 mb-6 gap-2 w-full">
                            <button @click="tab = 'justification'"
                                :class="tab === 'justification' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Justificación
                            </button>
                            <button @click="tab = 'principios'"
                                :class="tab === 'principios' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Principios
                            </button>
                            <button @click="tab = 'objectives'"
                                :class="tab === 'objectives' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Objetivos
                            </button>
                            <button @click="tab = 'methodology'"
                                :class="tab === 'methodology' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Metodología
                            </button>
                            <button @click="tab = 'subjects'"
                                :class="tab === 'subjects' ? 'border-b-2 border-green-500 text-green-500' : 'text-gray-500 hover:text-green-500'"
                                class="flex-1 px-4 py-2 focus:outline-none min-w-[120px] text-center">
                                Asignaturas
                            </button>
                        </div>

                        <div>
                            <div x-show="tab === 'justification'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Justificación</h2>
                                <div class="prose max-w-none">
                                    {!! $plan->justification !!}
                                </div>
                            </div>

                            <div x-show="tab === 'principios'" x-cloak>
                              <h2 class="text-xl font-semibold mb-4">Misión</h2>
                              <div class="prose max-w-none">
                                  {!! $plan->schoolProfile->mission !!}
                              </div>
                              <h2 class="text-xl font-semibold my-4">Visión</h2>
                              <div class="prose max-w-none">
                                {!! $plan->schoolProfile->vision !!}
                              </div>
                          </div>

                            <div x-show="tab === 'objectives'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Objetivos</h2>
                                <div class="prose max-w-none">
                                    {!! $plan->objectives !!}
                                </div>
                            </div>

                            <div x-show="tab === 'methodology'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Metodología</h2>
                                <div class="prose max-w-none">
                                    {!! $plan->methodology !!}
                                </div>
                            </div>

                            <div x-show="tab === 'subjects'" x-cloak>
                                <h2 class="text-xl font-semibold mb-4">Asignaturas</h2>
                                @if ($plan->subjects->count())
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        @foreach ($plan->subjects as $subject)
                                            <div class="bg-gray-50 rounded-lg p-4 shadow-sm hover:shadow transition">
                                                <a href="/subject/{{ $subject->id }}" class="font-medium text-green-600 hover:underline">
                                                    {{ $subject->name }}
                                                </a>
                                                <p class="text-sm text-gray-500 mb-2">{{ $subject->grade }}° Grado | {{ $subject->weekly_hours }} Horas.</p>
                                                @if ($subject->users->count())
                                                    <p class="text-sm text-gray-700 font-semibold mb-1">Docentes:</p>
                                                    <ul class="list-disc list-inside text-sm text-gray-600">
                                                        @foreach ($subject->users as $user)
                                                            <li>{{ $user->name }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-sm text-gray-500">Sin docentes asignados.</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500">No hay asignaturas asociadas a este plan.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </article>
      </div>
    </section>

    <footer class="bg-gray-900 text-gray-300 py-6">
      <div class="max-w-7xl mx-auto px-4 text-sm flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <p>&copy; {{ date('Y') }} {{ data_get($institutionBranding ?? [], 'name', config('app.name', 'Institucion')) }}. {{ data_get($institutionBranding ?? [], 'location', 'Pivijay, Magdalena - Colombia') }}</p>
        <p>Desarrollado por <a rel="nofollow noopener noreferrer" href="https://asyservicios.com" target="_blank" class="text-green-500 hover:underline">AS&amp;Servicios.com</a></p>
      </div>
    </footer>

</body>
    <script src="//unpkg.com/alpinejs" defer></script>
</html>
