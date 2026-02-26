<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateNbcBankProposalPdf extends Command
{
    protected $signature = 'proposal:nbc-bank-pdf {--output=NBC-Bank-POS-Proposal.pdf}';

    protected $description = 'Generate the NBC Bank POS proposal PDF (VISA/Mastercard/international cards) into the project pdf/ folder';

    public function handle(): int
    {
        $pdf = Pdf::loadView('pdf.proposal-nbc-bank-pos');
        $pdf->setPaper('a4', 'portrait');

        $dir = base_path('pdf');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $this->option('output');
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($path);

        $this->info('NBC Bank proposal PDF saved: ' . $path);

        return self::SUCCESS;
    }
}
