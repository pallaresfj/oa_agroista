<div style="text-align: center; padding: 1rem;">
    {{-- QR Code Image --}}
    <div style="background: white; padding: 1.5rem; border-radius: 1rem; display: inline-block; margin-bottom: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <img src="{{ $qrUrl }}" alt="Código QR - {{ $campus->name }}" style="width: 250px; height: 250px;">
    </div>
    
    {{-- Campus Info --}}
    <div style="margin-top: 1rem;">
        <h3 style="font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">
            {{ $campus->name }}
        </h3>
        @if($campus->address)
            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                📍 {{ $campus->address }}
            </p>
        @endif
        <p style="font-size: 0.75rem; color: #9ca3af;">
            Radio permitido: {{ $campus->radius_meters }} metros
        </p>
    </div>
    
    {{-- Instructions --}}
    <div style="margin-top: 1.5rem; padding: 1rem; background-color: #f3f4f6; border-radius: 0.5rem; text-align: left;">
        <p style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
            📱 Instrucciones:
        </p>
        <ol style="font-size: 0.75rem; color: #6b7280; margin: 0; padding-left: 1.25rem;">
            <li>Imprima este código QR y colóquelo en un lugar visible de la sede.</li>
            <li>Los docentes deben escanear este código desde la app para registrar su asistencia.</li>
            <li>El sistema verificará automáticamente que estén dentro del radio permitido.</li>
        </ol>
    </div>
    
    {{-- Download Button --}}
    <div style="margin-top: 1.5rem;">
        <a href="{{ $qrUrl }}" download="QR-{{ $campus->name }}.svg" 
           style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2a8a88 0%, #1d6362 100%); color: white; font-weight: 600; border-radius: 0.5rem; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Descargar QR
        </a>
    </div>
</div>
