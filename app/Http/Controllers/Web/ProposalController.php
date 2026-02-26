<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index()
    {
        $proposals = Proposal::with('creator')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('proposals.index', compact('proposals'));
    }

    public function create()
    {
        return view('proposals.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([Proposal::STATUS_DRAFT, Proposal::STATUS_SENT])],
        ]);

        $validated['reference'] = Proposal::generateReference();
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? Proposal::STATUS_DRAFT;
        $validated['currency'] = $validated['currency'] ?? 'ZAR';

        $proposal = Proposal::create($validated);

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposal created successfully.');
    }

    public function show(Proposal $proposal)
    {
        $proposal->load('creator');

        return view('proposals.show', compact('proposal'));
    }

    public function edit(Proposal $proposal)
    {
        return view('proposals.edit', compact('proposal'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([
                Proposal::STATUS_DRAFT,
                Proposal::STATUS_SENT,
                Proposal::STATUS_ACCEPTED,
                Proposal::STATUS_DECLINED,
            ])],
        ]);

        $validated['status'] = $validated['status'] ?? $proposal->status;
        $validated['currency'] = $validated['currency'] ?? 'ZAR';

        $proposal->update($validated);

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Proposal updated successfully.');
    }

    /**
     * Download proposal as PDF. Also saves a copy to the project pdf/ folder.
     */
    public function downloadPdf(Proposal $proposal)
    {
        $pdf = Pdf::loadView('pdf.proposal', compact('proposal'));
        $pdf->setPaper('a4', 'portrait');

        $filename = Str::slug($proposal->title) . '-' . $proposal->reference . '.pdf';

        $pdfDir = base_path('pdf');
        if (! is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        $pdf->save($pdfDir . DIRECTORY_SEPARATOR . $filename);

        return $pdf->download($filename);
    }
}
