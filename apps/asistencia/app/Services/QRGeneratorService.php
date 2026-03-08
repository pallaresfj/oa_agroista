<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRGeneratorService
{
    /**
     * Storage disk for QR codes.
     */
    private const QR_DISK = 'public';

    /**
     * Storage path for QR codes (relative to disk).
     */
    private const QR_STORAGE_PATH = 'qr-codes';

    /**
     * Check if QR Code package is available.
     */
    private function isQrPackageAvailable(): bool
    {
        return class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class);
    }

    /**
     * Generate QR code for a campus.
     *
     * @param Campus $campus
     * @return string Path to the generated QR code
     */
    public function generateCampusQR(Campus $campus): string
    {
        $disk = Storage::disk(self::QR_DISK);

        // Ensure directory exists
        $disk->makeDirectory(self::QR_STORAGE_PATH);

        // Generate unique token if not exists
        if (empty($campus->qr_token)) {
            $campus->qr_token = $this->generateUniqueToken();
        }

        // Create QR data as JSON
        $qrData = json_encode([
            'type' => 'teaching_assistance_campus',
            'token' => $campus->qr_token,
            'campus_id' => $campus->id,
            'timestamp' => now()->timestamp,
        ]);

        $filename = "campus_{$campus->id}_{$campus->qr_token}.svg";
        $path = self::QR_STORAGE_PATH . '/' . $filename;

        // Check if QR package is available
        if ($this->isQrPackageAvailable()) {
            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($qrData);

            $disk->put($path, $qrImage);
        } else {
            // Generate a placeholder SVG
            $placeholder = $this->generatePlaceholderSVG($campus->name, $campus->qr_token);
            $disk->put($path, $placeholder);
        }

        // Update campus with path
        $campus->qr_code_path = $path;
        $campus->save();

        return $path;
    }

    /**
     * Generate a placeholder SVG when QR package is not available.
     */
    private function generatePlaceholderSVG(string $campusName, string $token): string
    {
        $shortToken = substr($token, 0, 8);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#f3f4f6"/>
  <rect x="20" y="20" width="260" height="260" fill="white" stroke="#d1d5db" stroke-width="2"/>
  <text x="150" y="130" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#374151">QR Code</text>
  <text x="150" y="155" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#6b7280">{$campusName}</text>
  <text x="150" y="180" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#9ca3af">Token: {$shortToken}...</text>
  <text x="150" y="220" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" fill="#dc2626">Install: composer require simplesoftwareio/simple-qrcode</text>
</svg>
SVG;
    }

    /**
     * Validate QR token and return the campus.
     *
     * @param string $qrData Raw QR data string
     * @return Campus|null
     */
    public function validateQRToken(string $qrData): ?Campus
    {
        // Try to decode as JSON first
        $decoded = json_decode($qrData, true);

        if ($decoded && isset($decoded['token'])) {
            $token = $decoded['token'];
        } else {
            // Fallback: treat entire string as token
            $token = $qrData;
        }

        return Campus::where('qr_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Generate a unique token for QR codes.
     *
     * @return string
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Campus::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Regenerate QR code for a campus.
     *
     * @param Campus $campus
     * @param bool $newToken Generate a new token
     * @return string New path to the QR code
     */
    public function regenerateCampusQR(Campus $campus, bool $newToken = false): string
    {
        $disk = Storage::disk(self::QR_DISK);

        // Delete old QR file if exists
        if ($campus->qr_code_path && $disk->exists($campus->qr_code_path)) {
            $disk->delete($campus->qr_code_path);
        }

        // Generate new token if requested
        if ($newToken) {
            $campus->qr_token = $this->generateUniqueToken();
        }

        return $this->generateCampusQR($campus);
    }

    /**
     * Get the full URL for a campus QR code.
     *
     * @param Campus $campus
     * @return string|null
     */
    public function getQRCodeUrl(Campus $campus): ?string
    {
        if (!$campus->qr_code_path) {
            return null;
        }

        return Storage::disk(self::QR_DISK)->url($campus->qr_code_path);
    }
}
