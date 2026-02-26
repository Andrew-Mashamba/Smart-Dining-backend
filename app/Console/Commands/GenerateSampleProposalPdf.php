<?php

namespace App\Console\Commands;

use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateSampleProposalPdf extends Command
{
    protected $signature = 'proposal:sample-pdf {--output=proposal-sample.pdf}';

    protected $description = 'Generate a sample proposal PDF file in the project pdf/ folder';

    public function handle(): int
    {
        $proposal = new Proposal([
            'reference' => 'PROP-00001',
            'title' => 'Sample Proposal',
            'client_name' => 'Acme Corp',
            'client_email' => 'client@example.com',
            'client_company' => 'Acme Corp',
            'summary' => 'Sample catering and venue proposal for your event.',
            'body' => "We are pleased to submit this proposal for your consideration.\n\nScope:\n- Full venue hire\n- Catering for 50 guests\n- Bar service\n\nTerms as per our standard agreement.",
            'amount' => 25000.00,
            'currency' => 'ZAR',
            'valid_until' => now()->addDays(30),
            'status' => 'draft',
        ]);
        $proposal->id = 0;
        $proposal->created_at = now();

        $pdf = Pdf::loadView('pdf.proposal', ['proposal' => $proposal]);
        $pdf->setPaper('a4', 'portrait');

        $dir = base_path('pdf');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $this->option('output');
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($path);

        $this->info('Saved: ' . $path);

        return self::SUCCESS;
    }
}
