<?php

$scopes = preg_split('/\s+/', trim((string) env('SSO_SCOPES', 'openid email profile'))) ?: [];
$csv = static fn (string $value): array => array_values(array_filter(array_map(
    static fn (string $item): string => mb_strtolower(trim($item)),
    explode(',', $value),
)));

return [
    'discovery_url' => env('SSO_DISCOVERY_URL', 'http://localhost:8000/.well-known/openid-configuration'),
    'issuer' => env('SSO_ISSUER', 'http://localhost:8000'),
    'client_id' => env('SSO_CLIENT_ID', ''),
    'client_secret' => env('SSO_CLIENT_SECRET', ''),
    'institution_code' => mb_strtolower(trim((string) env('INSTITUTION_CODE', 'default'))),
    'redirect_uri' => env('SSO_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/sso/callback'),
    'scopes' => array_values(array_filter($scopes, static fn (string $scope): bool => $scope !== '')),
    'prompt' => env('SSO_PROMPT', 'login'),
    'session_check_enabled' => (bool) env('SSO_SESSION_CHECK_ENABLED', false),
    'session_check_interval_seconds' => (int) env('SSO_SESSION_CHECK_INTERVAL_SECONDS', 60),
    'session_check_timeout_seconds' => (int) env('SSO_SESSION_CHECK_TIMEOUT_SECONDS', 12),
    'session_check_prompt' => env('SSO_SESSION_CHECK_PROMPT', 'none'),
    'session_check_redirect_uri' => env('SSO_SESSION_CHECK_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/sso/session-check/callback'),
    'session_watchdog_enabled' => (bool) env('SSO_SESSION_WATCHDOG_ENABLED', false),
    'session_watchdog_interval_seconds' => (int) env('SSO_SESSION_WATCHDOG_INTERVAL_SECONDS', 30),
    'idp_logout_url' => env('SSO_IDP_LOGOUT_URL', 'http://localhost:9000/logout'),
    'frontchannel_logout_client_key' => mb_strtolower(trim((string) env('SSO_FRONTCHANNEL_LOGOUT_CLIENT_KEY', 'teachingassistance'))),
    'frontchannel_logout_secret' => (string) env('SSO_FRONTCHANNEL_LOGOUT_SECRET', ''),
    'frontchannel_logout_ttl_seconds' => (int) env('SSO_FRONTCHANNEL_LOGOUT_TTL_SECONDS', 120),
    'frontchannel_logout_next_hosts' => $csv(env(
        'SSO_FRONTCHANNEL_LOGOUT_NEXT_HOSTS',
        'localhost,127.0.0.1,teachingassistance.test,auth.iedagropivijay.edu.co,silo.iedagropivijay.edu.co,accounts.google.com,appengine.google.com'
    )),
    'http_timeout' => (int) env('SSO_HTTP_TIMEOUT', 10),
    'discovery_cache_seconds' => (int) env('SSO_DISCOVERY_CACHE_SECONDS', 3600),
    'jwks_cache_seconds' => (int) env('SSO_JWKS_CACHE_SECONDS', 3600),
];
