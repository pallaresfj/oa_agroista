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
    <meta name="description" content="OA Lectura - Plataforma de seguimiento de desempeno lector">
    <title>{{ config('app.name', 'OA Lectura') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: {{ data_get($palette, 'primary', '#f50404') }};
            --primary-light: {{ data_get($palette, 'info', data_get($palette, 'primary', '#f50404')) }};
            --primary-dark: {{ data_get($palette, 'danger', data_get($palette, 'primary', '#f50404')) }};
            --success: {{ data_get($palette, 'success', '#00c853') }};
            --info: {{ data_get($palette, 'info', '#0288d1') }};
            --info-light: {{ data_get($palette, 'info', '#0288d1') }};
            --warning: {{ data_get($palette, 'warning', '#ff9800') }};
            --danger: {{ data_get($palette, 'danger', '#b71c1c') }};
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
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
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }

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
            font-size: 1.05rem;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            background: var(--gray-900);
            color: #fff;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--gray-700);
            transform: translateY(-1px);
        }

        .hero {
            padding: 10rem 2rem 5rem;
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
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }

        .hero h1 {
            font-size: clamp(2.3rem, 5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.25rem;
            color: var(--gray-900);
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero p {
            font-size: 1.15rem;
            color: var(--muted);
            max-width: 680px;
            margin: 0 auto 2.5rem;
        }

        .hero-cta {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 0.95rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: #fff;
            box-shadow: 0 10px 24px -12px rgba(29, 99, 98, 0.6);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 30px -14px rgba(29, 99, 98, 0.7);
        }

        .features {
            padding: 1rem 2rem 5rem;
        }

        .features-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .feature {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 18px -10px rgba(0, 0, 0, 0.15);
        }

        .feature h3 {
            font-size: 1.05rem;
            color: var(--gray-900);
            margin-bottom: 0.45rem;
        }

        .feature p {
            font-size: 0.93rem;
            color: var(--gray-600);
        }

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

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .logo span {
                display: none;
            }

            .hero {
                padding: 8rem 1.25rem 3.5rem;
            }

            .hero p {
                font-size: 1.05rem;
            }

            .features {
                padding: 0.5rem 1rem 4rem;
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
    <header class="header">
        <div class="header-content">
            <a href="/" class="logo">
                <div class="logo-icon">OL</div>
                <span>{{ config('app.name', 'OA Lectura') }}</span>
            </a>

            @auth
                <a href="{{ url('/dashboard') }}" class="nav-link">Mi Panel</a>
            @else
                <a href="/app/login" class="nav-link">Ingresar</a>
            @endauth
        </div>
    </header>

    <section class="hero">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            Seguimiento lector
        </div>

        <h1>
            Gestiona la lectura de <span>{{ $institutionName }}</span> desde un solo panel
        </h1>

        <p>
            Registra velocidad, tiempo y errores por estudiante para analizar su progreso y tomar decisiones pedagogicas
            con informacion clara y centralizada.
        </p>

        @auth
            <a class="hero-cta" href="{{ url('/dashboard') }}">Ir al panel</a>
        @else
            <a class="hero-cta" href="/app/login">Ingresar</a>
        @endauth
    </section>

    <section class="features">
        <div class="features-grid">
            <article class="feature">
                <h3>Banco de lecturas</h3>
                <p>Organiza pasajes por dificultad y conserva el conteo automatico de palabras.</p>
            </article>
            <article class="feature">
                <h3>Historial por estudiante</h3>
                <p>Consulta intentos anteriores y compara velocidad lectora en el tiempo.</p>
            </article>
            <article class="feature">
                <h3>Errores tipificados</h3>
                <p>Registra omision, insercion, sustitucion y vacilacion en cada sesion.</p>
            </article>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <p>© {{ date('Y') }} {{ $institutionName }}. {{ $institutionLocation }}</p>
            <p>Desarrollado por <a href="https://asyservicios.com" target="_blank" rel="noopener noreferrer">AS&amp;Servicios.com</a></p>
        </div>
    </footer>
</body>

</html>
