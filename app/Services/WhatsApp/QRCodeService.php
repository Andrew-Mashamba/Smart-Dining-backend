<?php

namespace App\Services\WhatsApp;

use App\Models\Table;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Generate QR code for a table
     *
     * @return string Path to QR code image
     */
    public function generateTableQRCode(Table $table): string
    {
        $restaurantPhone = config('whatsapp.restaurant.phone');
        $message = $this->generateTableMessage($table);

        // Create WhatsApp link
        $whatsappUrl = "https://wa.me/{$restaurantPhone}?text=".urlencode($message);

        // Generate QR code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($whatsappUrl);

        // Save QR code
        $filename = "table_{$table->id}_{$table->name}.png";
        $path = config('whatsapp.qr_code.storage_path', 'qr-codes/tables');
        $fullPath = "{$path}/{$filename}";

        Storage::disk('public')->put($fullPath, $qrCode);

        return Storage::disk('public')->url($fullPath);
    }

    /**
     * Generate message for table QR code
     */
    protected function generateTableMessage(Table $table): string
    {
        // Generate a unique token for the table
        $token = substr(md5($table->id.$table->name.now()->timestamp), 0, 8);

        return "TABLE_{$table->id}_{$token}";
    }

    /**
     * Generate QR codes for all tables
     */
    public function generateAllTableQRCodes(): array
    {
        $tables = Table::all();
        $results = [];

        foreach ($tables as $table) {
            $results[$table->id] = [
                'table' => $table->name,
                'qr_code_url' => $this->generateTableQRCode($table),
            ];
        }

        return $results;
    }

    /**
     * Get QR code URL for a table
     */
    public function getTableQRCodeUrl(Table $table): ?string
    {
        $filename = "table_{$table->id}_{$table->name}.png";
        $path = config('whatsapp.qr_code.storage_path', 'qr-codes/tables');
        $fullPath = "{$path}/{$filename}";

        if (Storage::disk('public')->exists($fullPath)) {
            return Storage::disk('public')->url($fullPath);
        }

        return null;
    }
}
