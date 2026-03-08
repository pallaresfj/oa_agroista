/**
 * QR Scanner functionality using jsQR
 */
export function initQRScanner() {
    return {
        video: null,
        canvas: null,
        ctx: null,
        animationId: null,
        scanning: false,

        async start(videoId = 'qr-video', canvasId = 'qr-canvas') {
            this.video = document.getElementById(videoId);
            this.canvas = document.getElementById(canvasId);
            
            if (!this.video || !this.canvas) {
                console.error('Video or canvas element not found');
                return false;
            }
            
            this.ctx = this.canvas.getContext('2d');
            this.scanning = true;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });
                this.video.srcObject = stream;
                await this.video.play();
                this.scan();
                return true;
            } catch (err) {
                console.error('Camera access error:', err);
                return false;
            }
        },

        stop() {
            this.scanning = false;
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
            if (this.video && this.video.srcObject) {
                this.video.srcObject.getTracks().forEach(track => track.stop());
            }
        },

        scan() {
            if (!this.scanning || !this.video) return;

            if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
                this.canvas.width = this.video.videoWidth;
                this.canvas.height = this.video.videoHeight;
                this.ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

                const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                
                if (typeof jsQR !== 'undefined') {
                    const code = jsQR(imageData.data, this.canvas.width, this.canvas.height, {
                        inversionAttempts: 'dontInvert',
                    });
                    
                    if (code) {
                        this.onQRCodeDetected(code.data);
                        return;
                    }
                }
            }

            this.animationId = requestAnimationFrame(() => this.scan());
        },

        onQRCodeDetected(data) {
            // Override this method to handle QR code detection
            console.log('QR Code detected:', data);
            window.dispatchEvent(new CustomEvent('qr-detected', { detail: { data } }));
        }
    };
}

// Alpine.js component
if (typeof window !== 'undefined') {
    window.qrScanner = initQRScanner;
}
