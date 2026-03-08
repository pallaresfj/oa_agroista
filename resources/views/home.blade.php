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
          <li><a href="/" class="text-green-600 font-semibold">Inicio</a></li>
          <li><a href="/planes" class="hover:text-green-500 transition">Planes</a></li>
          <li><a href="/centers" class="hover:text-green-500 transition">Centros</a></li>
          <li><a href="/admin" class="hover:text-green-500 transition">Ingresar</a></li>
        </ul>
      </nav>
    </header>

    <!-- Banner Starts Here -->
    <div class="bg-gray-50 text-center py-12">
      <section class="max-w-7xl mx-auto px-4">
        <h4 class="text-green-600 text-lg font-semibold mb-2">IED Agropecuaria José María Herrera</h4>
        <h2 class="text-4xl font-bold">Documentos de Planeación</h2>
      </section>
    </div>
    <!-- Banner Ends Here -->

    <section class="max-w-7xl mx-auto px-4 pt-10 pb-20">
      <div class="grid gap-8 md:grid-cols-2 md:gap-12">
        <!-- Call to Action 1: Planes de área -->
        <div class="bg-white shadow-lg border border-gray-200 rounded-2xl flex flex-col">
          <a href="/planes">
            <img src="{{ asset('images/planes.jpg') }}" alt="Planes de área" class="w-full h-48 object-cover rounded-t-2xl">
          </a>
          <div class="p-6 flex flex-col flex-grow">
            <a href="/planes">
              <h3 class="text-3xl font-extrabold mb-4 text-green-900">Planes de área</h3>
            </a>
            <p class="text-gray-600 mb-4 flex-grow">
              Los planes de área son documentos pedagógicos que organizan y estructuran los contenidos, objetivos, competencias y metodologías para cada asignatura, permitiendo orientar el proceso educativo de manera coherente y sistemática a lo largo del año escolar.
            </p>
            <div class="mt-6">
              <a href="/planes" class="inline-block bg-green-600 text-white px-5 py-3 rounded-lg text-base font-semibold hover:bg-green-500 transition">Ver</a>
            </div>
          </div>
        </div>

        <!-- Call to Action 2: Centros de interés -->
        <div class="bg-white shadow-lg border border-gray-200 rounded-2xl flex flex-col">
          <a href="/centers">
            <img src="{{ asset('images/centros.jpg') }}" alt="Centros de interés" class="w-full h-48 object-cover rounded-t-2xl">
          </a>
          <div class="p-6 flex flex-col flex-grow">
            <a href="/centers">
              <h3 class="text-3xl font-extrabold mb-4 text-green-900">Centros de interés</h3>
            </a>
            <p class="text-gray-600 mb-4 flex-grow">
              Los centros de interés son estrategias pedagógicas que agrupan actividades y recursos educativos en torno a temas relevantes y motivadores para los estudiantes, fomentando el aprendizaje significativo y la participación activa en el desarrollo de competencias clave.
            </p>
            <div class="mt-6">
              <a href="/centers" class="inline-block bg-green-600 text-white px-5 py-3 rounded-lg text-base font-semibold hover:bg-green-500 transition">Ver</a>
            </div>
          </div>
        </div>
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