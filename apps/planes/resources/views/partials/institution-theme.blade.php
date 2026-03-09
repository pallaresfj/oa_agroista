@php
    $institutionPalette = data_get($institutionBranding ?? [], 'palette', []);
    $institutionPrimary = (string) ($institutionPalette['primary'] ?? '#f50404');
    $institutionSuccess = (string) ($institutionPalette['success'] ?? '#00c853');
@endphp
<style>
    :root {
        --institution-primary: {{ $institutionPrimary }};
        --institution-success: {{ $institutionSuccess }};
    }

    .text-green-600,
    .text-green-500 {
        color: var(--institution-primary) !important;
    }

    .hover\:text-green-500:hover,
    .hover\:text-green-600:hover {
        color: var(--institution-success) !important;
    }

    .bg-green-600,
    .bg-emerald-900 {
        background-color: var(--institution-primary) !important;
    }

    .hover\:bg-green-500:hover {
        background-color: var(--institution-success) !important;
    }

    .border-green-500 {
        border-color: var(--institution-primary) !important;
    }
</style>
