<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Services\WhatsApp\QRCodeService;

class QRCodeController extends Controller
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Generate QR code for a specific table
     *
     * @param  int  $tableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate($tableId)
    {
        $table = Table::findOrFail($tableId);

        $qrCodeUrl = $this->qrCodeService->generateTableQRCode($table);

        return response()->json([
            'message' => 'QR code generated successfully',
            'table' => $table->name,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Generate QR codes for all tables
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAll()
    {
        $results = $this->qrCodeService->generateAllTableQRCodes();

        return response()->json([
            'message' => 'QR codes generated for all tables',
            'tables' => $results,
        ]);
    }

    /**
     * Get QR code for a table
     *
     * @param  int  $tableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($tableId)
    {
        $table = Table::findOrFail($tableId);

        $qrCodeUrl = $this->qrCodeService->getTableQRCodeUrl($table);

        if (! $qrCodeUrl) {
            $qrCodeUrl = $this->qrCodeService->generateTableQRCode($table);
        }

        return response()->json([
            'table' => $table->name,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }
}
