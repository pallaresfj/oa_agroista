<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $institutionBranding = $institutionBranding ?? \App\Support\Institution\InstitutionTheme::branding();
    $palette = data_get($institutionBranding ?? [], 'palette', []);
    $institutionName = (string) data_get($institutionBranding ?? [], 'name', 'Institucion');
    $institutionLocation = (string) data_get($institutionBranding ?? [], 'location', 'Pivijay, Magdalena - Colombia');
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Teaching Assistance - Sistema de control de asistencia para instituciones educativas">

    <title>{{ config('app.name', 'Teaching Assistance') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ data_get($palette, 'primary', '#f50404') }}">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Custom palette */
            --primary: {{ data_get($palette, 'primary', '#f50404') }};
            --primary-light: {{ data_get($palette, 'info', data_get($palette, 'primary', '#f50404')) }};
            --primary-dark: {{ data_get($palette, 'danger', data_get($palette, 'primary', '#f50404')) }};
            --success: {{ data_get($palette, 'success', '#00c853') }};
            --success-light: {{ data_get($palette, 'info', data_get($palette, 'success', '#00c853')) }};
            --info: {{ data_get($palette, 'info', '#0288d1') }};
            --info-light: {{ data_get($palette, 'info', '#0288d1') }};
            --warning: {{ data_get($palette, 'warning', '#ff9800') }};
            --warning-light: {{ data_get($palette, 'warning', '#ff9800') }};
            --danger: {{ data_get($palette, 'danger', '#b71c1c') }};
            --danger-light: {{ data_get($palette, 'danger', '#b71c1c') }};
            /* Gray scale */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --surface: #ffffff;
            --bg: linear-gradient(135deg, #e6f2f2 0%, #fff 50%, var(--gray-50) 100%);
            --text: var(--gray-800);
            --muted: var(--gray-600);
            --border: rgba(0, 0, 0, 0.05);
            --card-border: rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            z-index: 100;
            padding: 1rem 2rem;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--gray-900);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-link-ghost {
            color: var(--gray-600);
        }

        .nav-link-ghost:hover {
            color: var(--gray-900);
            background: var(--gray-100);
        }

        .nav-link-primary {
            background: var(--gray-900);
            color: white;
        }

        .nav-link-primary:hover {
            background: var(--gray-700);
            transform: translateY(-1px);
        }

        /* Hero Section */
        .hero {
            padding: 10rem 2rem 6rem;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--info-light);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-600) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto 3rem;
        }

        .hero-cta {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            max-width: 600px;
            padding: 0.95rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: white;
            box-shadow: 0 10px 24px -12px rgba(29, 99, 98, 0.6);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 30px -14px rgba(29, 99, 98, 0.7);
        }

        /* Access Cards Section */
        .access-section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray-500);
            margin-bottom: 3rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .access-card {
            background: var(--surface);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 40px -10px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .card-docente .card-icon {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }

        .card-directivo .card-icon {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        }

        .card-admin .card-icon {
            background: linear-gradient(135deg, var(--info-light), var(--info));
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .card-description {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        .card-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: gap 0.2s ease;
        }

        .card-docente .card-button {
            color: #2563eb;
        }

        .card-directivo .card-button {
            color: #16a34a;
        }

        .card-admin .card-button {
            color: var(--primary);
        }

        .access-card:hover .card-button {
            gap: 0.75rem;
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem 6rem;
            background: linear-gradient(180deg, transparent, var(--gray-50));
        }

        .features-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature {
            text-align: center;
            padding: 1.5rem;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: var(--info-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .feature h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .feature p {
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        /* Footer */
        .footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        .footer-content a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --surface: #0f172a;
                --bg: linear-gradient(135deg, #0b1020 0%, #0f172a 60%, #111827 100%);
                --text: #e5e7eb;
                --muted: #cbd5f5;
                --border: rgba(255, 255, 255, 0.08);
                --card-border: rgba(255, 255, 255, 0.08);
            }

            body {
                color: var(--text);
            }

            .header {
                background: rgba(15, 23, 42, 0.85);
                border-bottom: 1px solid var(--border);
            }

            .logo {
                color: #e2e8f0;
            }

            .nav-link-ghost {
                color: #cbd5f5;
            }

            .nav-link-ghost:hover {
                color: #f8fafc;
                background: rgba(148, 163, 184, 0.12);
            }

            .nav-link-primary {
                background: #e2e8f0;
                color: #0f172a;
            }

            .nav-link-primary:hover {
                background: #f8fafc;
            }

            .hero h1 {
                background: linear-gradient(135deg, #f8fafc 0%, #94a3b8 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .hero h1 span {
                background: linear-gradient(135deg, #7dd3fc 0%, #22d3ee 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .hero-badge {
                background: rgba(148, 163, 184, 0.15);
                color: #e2e8f0;
            }

            .features {
                background: linear-gradient(180deg, transparent, rgba(15, 23, 42, 0.7));
            }

            .feature h3 {
                color: #e2e8f0;
            }

            .feature p {
                color: #cbd5f5;
            }

            .footer {
                color: #94a3b8;
                border-top: 1px solid var(--border);
            }

            .footer a {
                color: #67e8f9;
            }
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .logo span {
                display: none;
            }

            .hero {
                padding: 8rem 1.5rem 4rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .hero-cta {
                width: 100%;
                max-width: none;
            }

            .access-section {
                padding: 2rem 1rem;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .features {
                padding: 3rem 1rem 4rem;
            }

            .footer {
                padding: 1rem;
            }

            .footer-content {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="/" class="logo">
                <div class="logo-icon">TA</div>
                <span>{{ config('app.name', 'Teaching Assistance') }}</span>
            </a>
            <nav class="nav-links">
                @auth
                    <a href="{{ url('/dashboard') }}" class="nav-link nav-link-primary">
                        Ingresar
                    </a>
                @else
                    <a href="/app/login" class="nav-link nav-link-ghost">
                        Ingresar
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            Sistema de Control de Asistencia
        </div>
        <h1>
            Gestiona la asistencia de {{ $institutionName }} de forma <span>simple y eficiente</span>
        </h1>
        <p>
            Plataforma PWA para el registro de asistencia docente mediante códigos QR y validación por geolocalización.
        </p>
        @auth
            <a class="hero-cta" href="{{ url('/dashboard') }}">Mi Panel →</a>
        @else
            <a class="hero-cta" href="/app/login">Iniciar Sesión</a>
        @endauth
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">📱</div>
                <h3>Aplicación PWA</h3>
                <p>Instala la app en tu dispositivo móvil para acceso rápido sin necesidad de tiendas de aplicaciones.
                </p>
            </div>
            <div class="feature">
                <div class="feature-icon">📍</div>
                <h3>Geolocalización</h3>
                <p>Verifica automáticamente que el registro se realiza dentro del radio permitido de la sede asignada.
                </p>
            </div>
            <div class="feature">
                <div class="feature-icon">🔐</div>
                <h3>QR Seguro</h3>
                <p>Códigos QR únicos por sede con tokens regenerables para máxima seguridad en el registro.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>© {{ date('Y') }} {{ $institutionName }}. {{ $institutionLocation }}</p>
            <p>Desarrollado por <a href="https://asyservicios.com" target="_blank" rel="noopener noreferrer">AS&amp;Servicios.com</a></p>
        </div>
    </footer>
</body>

</html>
