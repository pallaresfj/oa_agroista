@php
    $watchdogEnabled = (bool) config('sso.session_watchdog_enabled', true);
    $watchdogIntervalSeconds = (int) config('sso.session_watchdog_interval_seconds', 5);
    $sessionCheckStartUrl = route('sso.session-check.start');
@endphp

@if ($watchdogEnabled && auth()->check())
    <script>
        (() => {
            const startUrl = @json($sessionCheckStartUrl);
            const intervalMs = Math.max(1000, Number(@json($watchdogIntervalSeconds)) * 1000);
            const lockKey = 'asistencia-session-watchdog-lock';
            const lockWindowMs = 3000;

            const isSessionCheckPath = () => {
                const { pathname } = window.location;
                return pathname.includes('/session-check/start') || pathname.includes('/session-check/callback');
            };

            const hasRecentLock = () => {
                try {
                    const lastValue = Number(window.sessionStorage.getItem(lockKey) ?? 0);
                    return Number.isFinite(lastValue) && (Date.now() - lastValue) < lockWindowMs;
                } catch (error) {
                    return false;
                }
            };

            const setLock = () => {
                try {
                    window.sessionStorage.setItem(lockKey, String(Date.now()));
                } catch (error) {
                    // Ignore storage failures and continue navigation.
                }
            };

            const triggerSessionCheck = () => {
                if (document.hidden || isSessionCheckPath() || hasRecentLock()) {
                    return;
                }

                setLock();

                const targetUrl = new URL(startUrl, window.location.origin);
                targetUrl.searchParams.set('return_to', window.location.href);
                window.location.replace(targetUrl.toString());
            };

            window.setInterval(triggerSessionCheck, intervalMs);
            window.addEventListener('focus', triggerSessionCheck);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    triggerSessionCheck();
                }
            });
        })();
    </script>
@endif
