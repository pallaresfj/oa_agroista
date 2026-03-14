<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<?php
    $institutionBranding = $institutionBranding ?? \App\Support\Institution\InstitutionTheme::branding();
    $institutionName = trim((string) data_get($institutionBranding ?? [], 'name', config('app.name', 'Institución')));
    $primaryColor = (string) data_get($institutionBranding ?? [], 'palette.primary', '#f50404');
    $rgb = sscanf(ltrim($primaryColor, '#'), '%02x%02x%02x') ?: [245, 4, 4];
    $primaryRgb = implode(', ', array_map(static fn ($value): int => (int) $value, array_slice($rgb, 0, 3)));
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($institutionName); ?> | Lectura</title>
    <style>
        :root {
            --brand-primary: <?php echo e($primaryColor); ?>;
            --brand-primary-rgb: <?php echo e($primaryRgb); ?>;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Lexend, ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(var(--brand-primary-rgb), 0.16), transparent 30%),
                linear-gradient(180deg, #faf7f2 0%, #f3efe8 100%);
            color: #1a1c22;
        }
        .shell { max-width: 1100px; margin: 0 auto; padding: 48px 24px 64px; }
        .hero { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: stretch; }
        .card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            border-radius: 28px;
            backdrop-filter: blur(14px);
            box-shadow: 0 30px 80px rgba(20, 22, 28, 0.08);
        }
        .hero-copy { padding: 40px; }
        .eyebrow {
            display: inline-flex; padding: 8px 14px; border-radius: 999px;
            background: rgba(var(--brand-primary-rgb), 0.1); color: var(--brand-primary);
            font-size: 13px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
        }
        h1 { font-size: clamp(2.4rem, 6vw, 4.4rem); line-height: 0.95; margin: 20px 0 16px; }
        p { font-size: 1.05rem; line-height: 1.7; color: #4a5262; margin: 0 0 20px; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .button {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 14px 20px; border-radius: 16px; text-decoration: none; font-weight: 700;
        }
        .button-primary { background: var(--brand-primary); color: #fff; }
        .button-secondary {
            background: #fff; color: #1a1c22; border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
        }
        .hero-panel { padding: 28px; display: grid; gap: 16px; }
        .metric { border-radius: 22px; padding: 20px; background: #fff; border: 1px solid rgba(var(--brand-primary-rgb), 0.08); }
        .metric strong { display: block; font-size: 2rem; margin-top: 8px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: 18px; }
        .feature { padding: 24px; }
        .feature h2 { margin: 0 0 10px; font-size: 1.05rem; }
        @media (max-width: 900px) {
            .hero, .grid { grid-template-columns: 1fr; }
            .hero-copy, .hero-panel { padding: 28px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <article class="card hero-copy">
                <span class="eyebrow">Evaluación lectora</span>
                <h1>Velocidad, tiempo y errores registrados por el docente.</h1>
                <p>
                    <?php echo e($institutionName); ?> puede administrar estudiantes, textos y resultados de lectura
                    desde un solo panel web. Cada intento conserva tiempo, palabras por minuto y errores por tipo
                    para seguir la evolución de cada estudiante.
                </p>
                <div class="actions">
                    <a class="button button-primary" href="<?php echo e(route('sso.login')); ?>">Ingresar con cuenta institucional</a>
                    <a class="button button-secondary" href="/app/login">Abrir panel</a>
                </div>
            </article>
            <aside class="card hero-panel">
                <div class="metric">
                    <span>Sesión guiada</span>
                    <strong>1 clic</strong>
                    <p>El docente inicia, registra errores y finaliza la lectura sin depender de reconocimiento de voz.</p>
                </div>
                <div class="metric">
                    <span>Métricas base</span>
                    <strong>WPM + errores</strong>
                    <p>La app calcula tiempo total, palabras del texto, palabras por minuto y distribución de errores.</p>
                </div>
            </aside>
        </section>
        <section class="grid">
            <article class="card feature">
                <h2>Banco de lecturas</h2>
                <p>Organiza textos por dificultad y conserva el conteo automático de palabras para cada lectura.</p>
            </article>
            <article class="card feature">
                <h2>Historial por estudiante</h2>
                <p>Consulta intentos anteriores, promedios y progresión de velocidad lectora en el tiempo.</p>
            </article>
            <article class="card feature">
                <h2>Errores tipificados</h2>
                <p>Registra omisión, sustitución, inserción y vacilación durante la lectura con marca temporal.</p>
            </article>
        </section>
    </div>
</body>
</html>
<?php /**PATH /Users/pallaresfj/Herd/oa_agroista/apps/lectura/resources/views/welcome.blade.php ENDPATH**/ ?>