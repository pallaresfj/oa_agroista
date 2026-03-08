<div class="w-full">
    {{-- Info Message (Already Registered) --}}
    @if($alreadyRegistered)
        <div style="padding: 1rem 1.25rem; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
            <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div style="flex: 1; min-width: 0;">
                <p style="font-weight: 600; color: #1d4ed8; font-size: 0.9375rem; margin: 0;">Asistencia ya registrada</p>
                <p style="color: #2563eb; font-size: 0.8125rem; margin: 0.125rem 0 0 0;">{{ $infoMessage }}</p>
            </div>
        </div>
    @endif

    {{-- Scan Button --}}
    @if(!$showScanner && !$registrationSuccess && !$alreadyRegistered)
        <button wire:click="openScanner"
            style="width: 100%; padding: 1.25rem 2rem; background: linear-gradient(135deg, #2a8a88 0%, #1d6362 100%); color: white; font-weight: 600; font-size: 1.125rem; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; transition: all 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h2m10 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
            <span>Escanear QR</span>
        </button>
    @endif

    {{-- Success Message --}}
    @if($registrationSuccess)
        @php
            $isLate = $lastStatus === 'late';
            $successBg = $isLate ? '#fffbeb' : '#f0fdf4';
            $successBorder = $isLate ? '#fde68a' : '#bbf7d0';
            $successText = $isLate ? '#b45309' : '#15803d';
            $successSubtext = $isLate ? '#d97706' : '#16a34a';
            $successIcon = $isLate ? '#d97706' : '#16a34a';
            $successSecondaryText = $isLate
                ? 'Su asistencia ha sido registrada con retardo.'
                : 'Su asistencia ha sido registrada correctamente.';
        @endphp
        <div style="padding: 1rem 1.25rem; background-color: {{ $successBg }}; border: 1px solid {{ $successBorder }}; border-radius: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
            <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: {{ $successIcon }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($isLate)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                @endif
            </svg>
            <div style="flex: 1; min-width: 0;">
                <p style="font-weight: 600; color: {{ $successText }}; font-size: 0.9375rem; margin: 0;">{{ $scanStatus }}</p>
                <p style="color: {{ $successSubtext }}; font-size: 0.8125rem; margin: 0.125rem 0 0 0;">{{ $successSecondaryText }}</p>
            </div>
        </div>
    @endif

    {{-- Scanner Modal --}}
    @if($showScanner)
        <div style="position: fixed; inset: 0; z-index: 9999; background-color: rgba(0, 0, 0, 0.85); display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div style="background-color: white; border-radius: 1rem; width: 100%; max-width: 28rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                {{-- Header --}}
                <div style="background: linear-gradient(135deg, #2a8a88 0%, #1d6362 100%); color: white; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0;">Escaneando QR</h3>
                    <button wire:click="closeScanner" style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 0.5rem; border: none; cursor: pointer; color: white; transition: all 0.2s;">
                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Camera View --}}
                <div style="position: relative; aspect-ratio: 1; background-color: #111827;">
                    <video id="qr-video" style="width: 100%; height: 100%; object-fit: cover;" playsinline autoplay muted></video>
                    <canvas id="qr-canvas" style="display: none;"></canvas>

                    {{-- Scan overlay --}}
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                        <div style="width: 16rem; height: 16rem; border: 2px solid rgba(255,255,255,0.5); border-radius: 0.5rem; position: relative;">
                            <div style="position: absolute; top: 0; left: 0; width: 2rem; height: 2rem; border-top: 4px solid #1d6362; border-left: 4px solid #1d6362; border-radius: 0.5rem 0 0 0;"></div>
                            <div style="position: absolute; top: 0; right: 0; width: 2rem; height: 2rem; border-top: 4px solid #1d6362; border-right: 4px solid #1d6362; border-radius: 0 0.5rem 0 0;"></div>
                            <div style="position: absolute; bottom: 0; left: 0; width: 2rem; height: 2rem; border-bottom: 4px solid #1d6362; border-left: 4px solid #1d6362; border-radius: 0 0 0 0.5rem;"></div>
                            <div style="position: absolute; bottom: 0; right: 0; width: 2rem; height: 2rem; border-bottom: 4px solid #1d6362; border-right: 4px solid #1d6362; border-radius: 0 0 0.5rem 0;"></div>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    {{-- Location Status --}}
                    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; color: {{ $locationObtained ? '#16a34a' : '#d97706' }};">
                        @if($locationObtained)
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <svg style="width: 1.25rem; height: 1.25rem; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        @endif
                        {{ $locationStatus }}
                    </div>

                    {{-- Error Message --}}
                    @if($errorMessage)
                        <div style="padding: 0.75rem; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; color: #dc2626; font-size: 0.875rem;">
                            {{ $errorMessage }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
@endassets

@script
<script>
    let video = null;
    let canvas = null;
    let ctx = null;
    let animationId = null;

    $wire.on('startScanner', () => {
        console.log('startScanner event received');
        setTimeout(() => {
            startCamera();
            getLocation();
        }, 100);
    });

    $wire.on('stopScanner', () => {
        console.log('stopScanner event received');
        stopCamera();
    });

    async function startCamera() {
        console.log('Starting camera...');
        video = document.getElementById('qr-video');
        canvas = document.getElementById('qr-canvas');

        console.log('Video element:', video);
        console.log('Canvas element:', canvas);

        if (!video || !canvas) {
            console.error('Video or canvas element not found');
            return;
        }

        ctx = canvas.getContext('2d');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            console.log('Camera stream obtained:', stream);
            video.srcObject = stream;
            await video.play();
            console.log('Video playing');
            scanQRCode();
        } catch (err) {
            console.error('Camera error:', err);
            $wire.dispatch('locationError', { message: 'No se pudo acceder a la cámara: ' + err.message });
        }
    }

    function stopCamera() {
        console.log('Stopping camera...');
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
    }

    function scanQRCode() {
        if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) {
            animationId = requestAnimationFrame(scanQRCode);
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

        if (typeof jsQR !== 'undefined') {
            const code = jsQR(imageData.data, canvas.width, canvas.height);
            if (code) {
                console.log('QR Code detected:', code.data);
                $wire.dispatch('qrScanned', { qrData: code.data });
                stopCamera();
                return;
            }
        } else {
            console.warn('jsQR library not loaded');
        }

        animationId = requestAnimationFrame(scanQRCode);
    }

    function getLocation() {
        console.log('Getting location...');
        if (!navigator.geolocation) {
            $wire.dispatch('locationError', { message: 'Geolocalización no soportada' });
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log('Location received:', position.coords);
                $wire.dispatch('locationReceived', {
                    lat: position.coords.latitude,
                    lon: position.coords.longitude,
                    accuracy: Math.round(position.coords.accuracy)
                });
            },
            (error) => {
                console.error('Location error:', error);
                let msg = 'Error desconocido';
                switch (error.code) {
                    case 1: msg = 'Permiso denegado'; break;
                    case 2: msg = 'Posición no disponible'; break;
                    case 3: msg = 'Tiempo de espera agotado'; break;
                }
                $wire.dispatch('locationError', { message: msg });
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }
</script>
@endscript
