<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class HelpController extends Controller
{
    /**
     * Display the help page with role-based documentation links.
     */
    public function index()
    {
        $user = auth()->user();
        $role = $user ? $user->role : 'guest';

        // Define available documentation by role
        $documentation = $this->getDocumentationForRole($role);

        return view('help.index', [
            'role' => $role,
            'documentation' => $documentation,
            'user' => $user,
        ]);
    }

    /**
     * Get documentation files available for a specific role.
     */
    private function getDocumentationForRole($role)
    {
        $docs = [
            'common' => [
                [
                    'title' => 'API Documentation',
                    'file' => 'API.md',
                    'description' => 'Complete API reference with endpoints, authentication, and examples',
                    'icon' => 'code',
                ],
            ],
        ];

        // Role-specific documentation
        $roleDocs = [
            'admin' => [
                'title' => 'Admin Guide',
                'file' => 'ADMIN_GUIDE.md',
                'description' => 'Staff management, system settings, reports, and configuration',
                'icon' => 'shield',
            ],
            'manager' => [
                'title' => 'Manager Guide',
                'file' => 'MANAGER_GUIDE.md',
                'description' => 'Menu management, table management, inventory, and reporting',
                'icon' => 'briefcase',
            ],
            'waiter' => [
                'title' => 'Waiter Guide',
                'file' => 'WAITER_GUIDE.md',
                'description' => 'Creating orders, processing payments, using POS interface',
                'icon' => 'users',
            ],
            'chef' => [
                'title' => 'Chef Guide',
                'file' => 'CHEF_GUIDE.md',
                'description' => 'Using kitchen display, updating order status, managing preparations',
                'icon' => 'utensils',
            ],
            'bartender' => [
                'title' => 'Bartender Guide',
                'file' => 'BARTENDER_GUIDE.md',
                'description' => 'Using bar display, updating drink status, managing bar orders',
                'icon' => 'glass-martini',
            ],
        ];

        // Add role-specific guide
        if (isset($roleDocs[$role])) {
            $docs['role'] = [$roleDocs[$role]];
        }

        // Managers and admins can see all guides
        if (in_array($role, ['admin', 'manager'])) {
            $docs['all_guides'] = array_values($roleDocs);
        }

        return $docs;
    }

    /**
     * Display a specific documentation file.
     */
    public function show($filename)
    {
        $docsPath = base_path('docs');
        $filePath = $docsPath.'/'.$filename;

        // Security: only allow .md files in docs directory
        if (! str_ends_with($filename, '.md') || ! file_exists($filePath)) {
            abort(404, 'Documentation not found');
        }

        // Read and parse markdown content
        $content = file_get_contents($filePath);

        // Simple markdown to HTML conversion (basic implementation)
        // For production, consider using a proper markdown parser like parsedown
        $html = $this->markdownToHtml($content);

        return view('help.show', [
            'title' => str_replace(['.md', '_'], ['', ' '], $filename),
            'content' => $html,
            'filename' => $filename,
        ]);
    }

    /**
     * Export documentation as PDF.
     */
    public function exportPdf($filename)
    {
        $docsPath = base_path('docs');
        $filePath = $docsPath.'/'.$filename;

        // Security: only allow .md files in docs directory
        if (! str_ends_with($filename, '.md') || ! file_exists($filePath)) {
            abort(404, 'Documentation not found');
        }

        // Read and parse markdown content
        $content = file_get_contents($filePath);
        $html = $this->markdownToHtml($content);

        // Generate PDF
        $pdf = Pdf::loadView('help.pdf', [
            'title' => str_replace(['.md', '_'], ['', ' '], $filename),
            'content' => $html,
        ]);

        $pdfFilename = str_replace('.md', '.pdf', $filename);

        return $pdf->download($pdfFilename);
    }

    /**
     * Basic markdown to HTML conversion.
     * For production, use a library like parsedown/commonmark.
     */
    private function markdownToHtml($markdown)
    {
        // Headers
        $markdown = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $markdown);

        // Bold and italic
        $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);

        // Links
        $markdown = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $markdown);

        // Code blocks
        $markdown = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $markdown);
        $markdown = preg_replace('/`(.*?)`/', '<code>$1</code>', $markdown);

        // Lists
        $markdown = preg_replace('/^\- (.*?)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $markdown);

        // Paragraphs
        $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
        $markdown = '<p>'.$markdown.'</p>';

        // Horizontal rules
        $markdown = preg_replace('/^---$/m', '<hr>', $markdown);

        return $markdown;
    }
}
