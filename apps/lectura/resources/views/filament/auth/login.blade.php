<x-filament-panels::page.simple>
    @php
        $institutionBranding = $institutionBranding ?? \App\Support\Institution\InstitutionTheme::branding();
        $primaryColor = (string) data_get($institutionBranding ?? [], 'palette.primary', '#f50404');
        $institutionName = trim((string) data_get($institutionBranding ?? [], 'name', config('app.name', 'Institución')));
    @endphp

    <style>
        .fi-simple-layout {
            background: linear-gradient(160deg, #f8fafc 0%, #eef2ff 52%, #e2e8f0 100%);
            padding: 1.5rem !important;
        }

        .fi-simple-main-ctn,
        .fi-simple-main {
            width: 100% !important;
            max-width: none !important;
        }

        .lectura-login-wrap {
            width: 100%;
            max-width: 68rem;
            margin: 0 auto;
        }

        .lectura-login-card {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 2rem;
            background: #fff;
            box-shadow: 0 30px 80px rgba(20, 22, 28, 0.14);
        }

        .lectura-login-hero,
        .lectura-login-panel {
            padding: 2.5rem;
        }

        .lectura-login-hero {
            position: relative;
            color: #fff;
        }

        .lectura-login-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0.2;
            background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
            background-size: 18px 18px;
        }

        .lectura-login-hero > * {
            position: relative;
        }

        .lectura-login-kicker {
            margin: 0 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .lectura-login-title {
            margin: 0;
            max-width: 32rem;
            font-size: 2.5rem;
            line-height: 1.1;
            font-weight: 700;
        }

        .lectura-login-copy {
            margin: 1.25rem 0 0;
            max-width: 32rem;
            font-size: 0.98rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.82);
        }

        .lectura-login-panel {
            background: #fff;
        }

        .lectura-login-brand {
            margin: 0 0 0.5rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .lectura-login-heading {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .lectura-login-text {
            margin: 0.9rem 0 0;
            font-size: 0.98rem;
            line-height: 1.8;
            color: #64748b;
        }

        .lectura-login-alert {
            margin-top: 1.5rem;
            border: 1px solid #fcd34d;
            border-radius: 1rem;
            background: #fffbeb;
            color: #92400e;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
        }

        .lectura-login-button {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            margin-top: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 140ms ease, opacity 140ms ease;
        }

        .lectura-login-button:hover {
            opacity: 0.96;
            transform: translateY(-1px);
        }

        @media (max-width: 960px) {
            .fi-simple-layout {
                padding: 1rem !important;
            }

            .lectura-login-card {
                grid-template-columns: 1fr;
            }

            .lectura-login-hero,
            .lectura-login-panel {
                padding: 2rem;
            }

            .lectura-login-title,
            .lectura-login-heading {
                font-size: 2rem;
            }
        }
    </style>

    <div class="lectura-login-wrap">
        <div class="lectura-login-card">
            <section class="lectura-login-hero" style="background: {{ $primaryColor }}">
                <div>
                    <p class="lectura-login-kicker">Lectura</p>
                    <h1 class="lectura-login-title">Controla cada intento de lectura desde el panel docente.</h1>
                    <p class="lectura-login-copy">
                        Gestiona estudiantes, banco de lecturas y resultados históricos de velocidad lectora en una sola plataforma.
                    </p>
                </div>
            </section>
            <section class="lectura-login-panel">
                <p class="lectura-login-brand">{{ $institutionName }}</p>
                <h2 class="lectura-login-heading">Acceso institucional</h2>
                <p class="lectura-login-text">
                    Ingrese con su cuenta educativa para administrar lecturas, iniciar sesiones y revisar el progreso de los estudiantes.
                </p>

                @if ($errors->has('sso'))
                    <div class="lectura-login-alert" role="alert">
                        {{ $errors->first('sso') }}
                    </div>
                @endif

                <a href="{{ route('sso.login') }}" class="lectura-login-button" style="background: {{ $primaryColor }}">
                    Ingresar con cuenta institucional
                </a>
            </section>
        </div>
    </div>
</x-filament-panels::page.simple>
