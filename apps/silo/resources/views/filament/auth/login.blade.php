<x-filament-panels::page.simple>
    @php
        $institutionBranding = $institutionBranding ?? \App\Support\Institution\InstitutionTheme::branding();
        $primaryColor = (string) data_get($institutionBranding ?? [], 'palette.primary', '#f50404');
        $rgb = sscanf(ltrim($primaryColor, '#'), '%02x%02x%02x') ?: [245, 4, 4];
        $primaryRgb = implode(', ', array_map(static fn ($value): int => (int) $value, array_slice($rgb, 0, 3)));
    @endphp
    <style>
        .silo-access-brand-icon,
        .silo-access-card-accent,
        .silo-access-illustration-graphic,
        .silo-access-legal a {
            color: {{ $primaryColor }} !important;
        }

        .silo-access-card-accent,
        .silo-access-button {
            background: {{ $primaryColor }} !important;
        }

        .silo-access-brand p {
            color: rgba({{ $primaryRgb }}, 0.72) !important;
        }

        .silo-access-card {
            box-shadow: 0 24px 40px rgba({{ $primaryRgb }}, 0.08) !important;
        }

        .silo-access-illustration {
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.05), rgba({{ $primaryRgb }}, 0.2)) !important;
            border: 1px solid rgba({{ $primaryRgb }}, 0.12) !important;
        }

        .silo-access-button:hover {
            background: rgba({{ $primaryRgb }}, 0.92) !important;
            box-shadow: 0 10px 22px rgba({{ $primaryRgb }}, 0.24) !important;
        }

        .silo-access-blob {
            background: rgba({{ $primaryRgb }}, 0.08) !important;
        }
    </style>
    <div class="silo-access-page">
        <div class="silo-access-blob silo-access-blob--left" aria-hidden="true"></div>
        <div class="silo-access-blob silo-access-blob--right" aria-hidden="true"></div>

        <main class="silo-access-main">
            <header class="silo-access-brand">
                <div class="silo-access-brand-row">
                    <svg class="silo-access-brand-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <path
                            fill="currentColor"
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M12.08 24L4 19.2479L9.95537 8.75216L18.04 13.4961L18.0446 4H29.9554L29.96 13.4961L38.0446 8.75216L44 19.2479L35.92 24L44 28.7521L38.0446 39.2479L29.96 34.5039L29.9554 44H18.0446L18.04 34.5039L9.95537 39.2479L4 28.7521L12.08 24Z"
                        />
                    </svg>
                    <h1>{{ config('app.name', 'SILO') }}</h1>
                </div>
                <p>SISTEMA DE GESTIÓN DOCUMENTAL</p>
            </header>

            <section class="silo-access-card" aria-labelledby="silo-access-title">
                <div class="silo-access-card-accent"></div>

                <div class="silo-access-card-body">
                    <header class="silo-access-card-header">
                        <h2 id="silo-access-title">Acceso Institucional</h2>
                        <p>Utilice sus credenciales educativas para ingresar al ecosistema de aprendizaje.</p>
                    </header>

                    @if ($errors->has('sso') || $errors->has('auth'))
                        <div class="silo-access-alert" role="alert">
                            {{ $errors->first('sso') ?: $errors->first('auth') }}
                        </div>
                    @endif

                    <div class="silo-access-illustration" aria-hidden="true">
                        <svg class="silo-access-illustration-graphic" viewBox="0 0 280 110" fill="none">
                            <path d="M30 36c0-5.52 4.48-10 10-10h62l10 12h98c5.52 0 10 4.48 10 10v32c0 5.52-4.48 10-10 10H40c-5.52 0-10-4.48-10-10V36Z" fill="currentColor" opacity="0.14"/>
                            <rect x="74" y="26" width="78" height="54" rx="6" fill="currentColor" opacity="0.2"/>
                            <rect x="90" y="16" width="78" height="54" rx="6" fill="currentColor" opacity="0.34"/>
                            <path d="M108 34h42M108 44h42M108 54h30" stroke="white" stroke-linecap="round" stroke-width="4"/>
                            <circle cx="197" cy="61" r="17" stroke="currentColor" stroke-width="8" opacity="0.72"/>
                            <path d="m210 74 14 14" stroke="currentColor" stroke-linecap="round" stroke-width="8" opacity="0.72"/>
                            <path d="m188 61 6 6 11-11" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="5" opacity="0.9"/>
                        </svg>
                    </div>

                    <a class="silo-access-button" href="{{ route('sso.login') }}">
                        <svg class="silo-access-button-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M10 17v-3H3v-4h7V7l5 5-5 5Zm9-12h-6v2h6v10h-6v2h6a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"/>
                        </svg>
                        <span>Ingresar con Cuenta Institucional</span>
                    </a>
                </div>
            </section>

            <footer class="silo-access-legal">
                <p>
                    © {{ date('Y') }} {{ config('app.name', 'SILO') }} - {{ data_get($institutionBranding ?? [], 'name', 'Institucion') }}. Desarrollado por
                    <a href="https://www.asyservicios.com" target="_blank" rel="noreferrer noopener">AS&amp;Servicios.com</a>
                </p>
            </footer>
        </main>
    </div>
</x-filament-panels::page.simple>
