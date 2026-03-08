<x-filament-panels::page.simple>
    <style>
        .fi-simple-layout {
            background: #f6f6f8;
        }

        .dark .fi-simple-layout {
            background: #101622;
        }

        .silo-access-page {
            color: #0d121b;
            font-family: Lexend, Inter, ui-sans-serif, system-ui, sans-serif;
            position: relative;
        }

        .silo-access-main {
            align-items: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-inline: auto;
            max-width: 1200px;
            min-height: auto;
            padding: 0.65rem 0;
            position: relative;
            width: 90vw;
            z-index: 1;
        }

        .silo-access-brand {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .silo-access-brand-row {
            align-items: center;
            display: flex;
            gap: 0.7rem;
        }

        .silo-access-brand-icon {
            color: #0e5326;
            height: 2.45rem;
            width: 2.45rem;
        }

        .silo-access-brand h1 {
            color: #0d121b;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin: 0;
        }

        .dark .silo-access-brand h1 {
            color: #fff;
        }

        .silo-access-brand p {
            color: rgba(14, 83, 38, 0.72);
            font-size: 0.84rem;
            font-weight: 500;
            letter-spacing: 0.2em;
            margin: 0;
        }

        .silo-access-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 24px 40px rgba(14, 83, 38, 0.08);
            overflow: hidden;
            position: relative;
            width: min(100%, 640px);
        }

        .dark .silo-access-card {
            background: #1a2133;
            box-shadow: 0 24px 40px rgba(0, 0, 0, 0.24);
        }

        .silo-access-card-accent {
            background: #0e5326;
            height: 0.375rem;
            width: 100%;
        }

        .silo-access-card-body {
            padding: 1.75rem;
        }

        .silo-access-card-header {
            margin-bottom: 1.4rem;
            text-align: center;
        }

        .silo-access-card-header h2 {
            color: #0d121b;
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .dark .silo-access-card-header h2 {
            color: #fff;
        }

        .silo-access-card-header p {
            color: #4c669a;
            font-size: 1rem;
            line-height: 1.62;
            margin: 0.75rem 0 0;
        }

        .dark .silo-access-card-header p {
            color: #94a3b8;
        }

        .silo-access-alert {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 0.6rem;
            color: #9a3412;
            font-size: 0.92rem;
            margin-bottom: 1rem;
            padding: 0.75rem 0.9rem;
        }

        .silo-access-illustration {
            align-items: center;
            background: linear-gradient(135deg, rgba(14, 83, 38, 0.05), rgba(14, 83, 38, 0.2));
            border: 1px solid rgba(14, 83, 38, 0.12);
            border-radius: 0.5rem;
            display: flex;
            height: 8rem;
            justify-content: center;
            margin-bottom: 1.35rem;
            overflow: hidden;
            position: relative;
        }

        .silo-access-illustration::before {
            background-image: radial-gradient(circle at 1px 1px, rgba(17, 82, 212, 0.45) 1px, transparent 0);
            background-size: 16px 16px;
            content: '';
            inset: 0;
            opacity: 0.1;
            position: absolute;
        }

        .silo-access-illustration-icon {
            color: #0e5326;
            height: 3.25rem;
            opacity: 0.4;
            position: relative;
            width: 3.25rem;
        }

        .silo-access-button {
            align-items: center;
            background: #0e5326;
            border-radius: 0.5rem;
            color: #fff;
            display: inline-flex;
            font-size: 1.075rem;
            font-weight: 600;
            gap: 0.7rem;
            height: 3.5rem;
            justify-content: center;
            text-decoration: none;
            transition: all 150ms ease;
            width: 100%;
        }

        .silo-access-button:hover {
            background: rgba(14, 83, 38, 0.92);
            box-shadow: 0 10px 22px rgba(14, 83, 38, 0.24);
        }

        .silo-access-button:active {
            transform: scale(0.98);
        }

        .silo-access-button-icon {
            height: 1.45rem;
            width: 1.45rem;
        }

        .silo-access-button span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .silo-access-legal {
            margin-top: 1rem;
            text-align: center;
        }

        .silo-access-legal p {
            color: #94a3b8;
            font-size: 0.75rem;
            margin: 0;
        }

        .silo-access-legal a {
            color: #0e5326;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .silo-access-blob {
            background: rgba(14, 83, 38, 0.08);
            border-radius: 9999px;
            filter: blur(52px);
            height: 24rem;
            pointer-events: none;
            position: fixed;
            width: 24rem;
            z-index: 0;
        }

        .silo-access-blob--left {
            bottom: -6rem;
            left: -6rem;
        }

        .silo-access-blob--right {
            right: -6rem;
            top: -6rem;
        }

        @media (max-width: 640px) {
            .silo-access-card-body {
                padding: 1.35rem;
            }

            .silo-access-brand h1 {
                font-size: 1.7rem;
            }

            .silo-access-card-header h2 {
                font-size: 1.4rem;
            }

            .silo-access-button {
                font-size: 0.98rem;
            }
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
                    <h1>Teaching Assistance</h1>
                </div>
                <p>SISTEMA DE ASISTENCIA DOCENTE</p>
            </header>

            <section class="silo-access-card" aria-labelledby="silo-access-title">
                <div class="silo-access-card-accent"></div>

                <div class="silo-access-card-body">
                    <header class="silo-access-card-header">
                        <h2 id="silo-access-title">Acceso Institucional</h2>
                        <p>Utilice sus credenciales educativas para ingresar al panel de asistencia.</p>
                    </header>

                    @if ($errors->has('sso') || $errors->has('auth'))
                        <div class="silo-access-alert" role="alert">
                            {{ $errors->first('sso') ?: $errors->first('auth') }}
                        </div>
                    @endif

                    <div class="silo-access-illustration" aria-hidden="true">
                        <svg class="silo-access-illustration-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 10V7H14.5l-2-2H9v3h2.67l1.6 1.6V10H9.5A4.5 4.5 0 0 0 5 14.5V15a3 3 0 1 0 6 0v-.5c0-.53-.14-1.03-.38-1.46h5.76A4.5 4.5 0 0 0 16 14.5V15a3 3 0 1 0 6 0v-.5A4.5 4.5 0 0 0 18 10Zm-10 8a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm11 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/>
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
                    (c) 2026 Teaching Assistance - IED Agropecuaria Jose Maria Herrera. Desarrollado por
                    <a href="https://www.asyservicios.com" target="_blank" rel="noreferrer noopener">AS&amp;Servicios.com</a>
                </p>
            </footer>
        </main>
    </div>
</x-filament-panels::page.simple>
