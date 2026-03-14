<x-filament-panels::page.simple>
    @php
        $institutionBranding = $institutionBranding ?? \App\Support\Institution\InstitutionTheme::branding();
        $primaryColor = (string) data_get($institutionBranding ?? [], 'palette.primary', '#f50404');
        $institutionName = trim((string) data_get($institutionBranding ?? [], 'name', config('app.name', 'Institución')));
    @endphp

    <div class="mx-auto w-full max-w-5xl">
        <div class="grid overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-[0_30px_80px_rgba(20,22,28,0.14)] lg:grid-cols-[1.1fr_0.9fr]">
            <section class="relative overflow-hidden p-10 text-white" style="background: {{ $primaryColor }}">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;"></div>
                <div class="relative">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.3em] text-white/70">Lectura</p>
                    <h1 class="max-w-md text-4xl font-bold leading-tight">Controla cada intento de lectura desde el panel docente.</h1>
                    <p class="mt-5 max-w-md text-sm leading-7 text-white/80">
                        Gestiona estudiantes, banco de lecturas y resultados históricos de velocidad lectora en una sola plataforma.
                    </p>
                </div>
            </section>
            <section class="p-10">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.28em] text-slate-400">{{ $institutionName }}</p>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Acceso institucional</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Ingrese con su cuenta educativa para administrar lecturas, iniciar sesiones y revisar el progreso de los estudiantes.
                </p>

                @if ($errors->has('sso'))
                    <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                        {{ $errors->first('sso') }}
                    </div>
                @endif

                <a href="{{ route('sso.login') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-2xl px-6 py-4 text-base font-semibold text-white transition hover:opacity-95" style="background: {{ $primaryColor }}">
                    Ingresar con cuenta institucional
                </a>
            </section>
        </div>
    </div>
</x-filament-panels::page.simple>
