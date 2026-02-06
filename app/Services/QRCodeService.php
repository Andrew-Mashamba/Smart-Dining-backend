<?php

namespace App\Services;

use App\Models\GuestSession;
use App\Models\Table;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Generate QR code for a specific table.
     *
     * @return string Path to the generated QR code file
     */
    public function generateTableQR(int $tableId): string
    {
        // Find the table
        $table = Table::findOrFail($tableId);

        // Create a new guest session with a unique token
        $guestSession = GuestSession::create([
            'table_id' => $tableId,
            'guest_id' => null, // Guest not assigned yet
            'started_at' => now(),
        ]);

        // Generate the URL for the guest ordering page with session token
        $url = url("/guest/order?token={$guestSession->session_token}");

        // Generate QR code as SVG first (works without Imagick)
        $svgQrCode = QrCode::format('svg')
            ->size(400)
            ->errorCorrection('H')
            ->margin(2)
            ->generate($url);

        // Convert SVG to PNG using GD
        $pngData = $this->convertSvgToPng($svgQrCode, 400, 400);

        // Define the file path
        $fileName = "{$tableId}.png";
        $filePath = "qrcodes/{$fileName}";

        // Ensure the qrcodes directory exists
        Storage::disk('public')->makeDirectory('qrcodes');

        // Save the PNG QR code to storage
        Storage::disk('public')->put($filePath, $pngData);

        // Update the table with the QR code path
        $table->update([
            'qr_code' => $filePath,
        ]);

        return $filePath;
    }

    /**
     * Regenerate QR code for a specific table.
     * This will create a new session and QR code.
     *
     * @return string Path to the regenerated QR code file
     */
    public function regenerateTableQR(int $tableId): string
    {
        // Close any active sessions for this table
        GuestSession::where('table_id', $tableId)
            ->active()
            ->each(function ($session) {
                $session->close();
            });

        // Generate a new QR code
        return $this->generateTableQR($tableId);
    }

    /**
     * Get the full URL path to a table's QR code.
     */
    public function getQRCodeUrl(string $filePath): string
    {
        return Storage::disk('public')->url($filePath);
    }

    /**
     * Get the full storage path to a table's QR code.
     */
    public function getQRCodePath(string $filePath): string
    {
        return Storage::disk('public')->path($filePath);
    }

    /**
     * Delete QR code for a specific table.
     */
    public function deleteTableQR(int $tableId): bool
    {
        $table = Table::find($tableId);

        if ($table && $table->qr_code) {
            // Delete the file
            Storage::disk('public')->delete($table->qr_code);

            // Update the table
            $table->update(['qr_code' => null]);

            return true;
        }

        return false;
    }

    /**
     * Convert SVG to PNG using GD library.
     * This is a workaround since simple-qrcode PNG format requires Imagick.
     *
     * @return string PNG binary data
     */
    private function convertSvgToPng(string $svgContent, int $width, int $height): string
    {
        // Create a white background image
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // Parse SVG and draw QR code
        // Extract rect elements from SVG (QR code modules)
        preg_match_all('/<rect[^>]*x="([^"]*)"[^>]*y="([^"]*)"[^>]*width="([^"]*)"[^>]*height="([^"]*)"[^>]*fill="([^"]*)"/', $svgContent, $matches, PREG_SET_ORDER);

        $black = imagecolorallocate($image, 0, 0, 0);

        foreach ($matches as $match) {
            $x = floatval($match[1]);
            $y = floatval($match[2]);
            $w = floatval($match[3]);
            $h = floatval($match[4]);
            $fill = $match[5];

            // Only draw black rectangles (QR code modules)
            if (stripos($fill, '#000') !== false || stripos($fill, 'black') !== false || $fill === 'rgb(0,0,0)') {
                imagefilledrectangle($image, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $black);
            }
        }

        // Capture PNG output
        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        return $pngData;
    }
}
