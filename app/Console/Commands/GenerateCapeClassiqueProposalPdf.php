<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateCapeClassiqueProposalPdf extends Command
{
    protected $signature = 'proposal:cape-classique-pdf {--output=Cape-Classique-Smart-Dining-Proposal.pdf} {--dir=}';

    protected $description = 'Generate the Cape Classique Smart Dining proposal PDF (default: resources/views/pdf; use --dir to override)';

    public function handle(): int
    {
        $pdf = Pdf::loadView('pdf.proposal-cape-classique-smart-dining', ['forPdf' => true]);
        $pdf->setPaper('a4', 'portrait');

        $filename = $this->option('output');
        $dirOption = $this->option('dir');
        if ($dirOption !== null && $dirOption !== '') {
            $dir = is_dir($dirOption) ? realpath($dirOption) : $dirOption;
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        } else {
            $dir = base_path('resources/views/pdf');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($path);

        $this->info('Cape Classique Smart Dining proposal PDF saved: ' . $path);

        return self::SUCCESS;
    }
}
