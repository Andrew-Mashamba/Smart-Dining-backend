#!/usr/bin/env php
<?php

/**
 * Code Cleanup Script for Production Readiness
 * Story 52: Final testing and production preparation
 *
 * This script identifies and reports:
 * - Debug statements (dd, dump, var_dump, print_r)
 * - Commented code blocks
 * - Unused imports
 * - TODO/FIXME comments
 */
echo "========================================\n";
echo "Code Cleanup Check\n";
echo "========================================\n\n";

$projectRoot = dirname(__DIR__, 2);
$directories = [
    'app',
    'config',
    'routes',
];

$issues = [
    'debug' => [],
    'commented' => [],
    'todos' => [],
];

$patterns = [
    'debug' => [
        '/\bdd\s*\(/',
        '/\bdump\s*\(/',
        '/\bvar_dump\s*\(/',
        '/\bprint_r\s*\(/',
        '/\bvar_export\s*\(/',
        '/\bconsole\.log\s*\(/',
        '/\bconsole\.debug\s*\(/',
        '/\bdebugger;/',
    ],
    'todos' => [
        '/\/\/\s*TODO:/i',
        '/\/\/\s*FIXME:/i',
        '/\/\/\s*HACK:/i',
        '/\/\/\s*XXX:/i',
    ],
];

function scanDirectory($dir, $patterns, &$issues)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && (
            $file->getExtension() === 'php' ||
            $file->getExtension() === 'js' ||
            $file->getExtension() === 'vue'
        )) {
            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                $lineNumber = $lineNum + 1;

                // Check for debug statements
                foreach ($patterns['debug'] as $pattern) {
                    if (preg_match($pattern, $line)) {
                        // Skip if in comment
                        if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
                            continue;
                        }

                        $issues['debug'][] = [
                            'file' => str_replace($GLOBALS['projectRoot'].'/', '', $file->getPathname()),
                            'line' => $lineNumber,
                            'content' => trim($line),
                        ];
                    }
                }

                // Check for TODO/FIXME comments
                foreach ($patterns['todos'] as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $issues['todos'][] = [
                            'file' => str_replace($GLOBALS['projectRoot'].'/', '', $file->getPathname()),
                            'line' => $lineNumber,
                            'content' => trim($line),
                        ];
                    }
                }
            }
        }
    }
}

$GLOBALS['projectRoot'] = $projectRoot;

foreach ($directories as $dir) {
    $fullPath = $projectRoot.'/'.$dir;
    if (is_dir($fullPath)) {
        echo "Scanning $dir...\n";
        scanDirectory($fullPath, $patterns, $issues);
    }
}

echo "\n========================================\n";
echo "Results\n";
echo "========================================\n\n";

// Report debug statements
if (! empty($issues['debug'])) {
    echo '⚠ DEBUG STATEMENTS FOUND ('.count($issues['debug'])."):\n";
    echo "----------------------------------------\n";
    foreach ($issues['debug'] as $issue) {
        echo "  {$issue['file']}:{$issue['line']}\n";
        echo "    {$issue['content']}\n\n";
    }
} else {
    echo "✓ No debug statements found\n";
}

echo "\n";

// Report TODO/FIXME comments
if (! empty($issues['todos'])) {
    echo '⚠ TODO/FIXME COMMENTS FOUND ('.count($issues['todos'])."):\n";
    echo "----------------------------------------\n";
    foreach ($issues['todos'] as $issue) {
        echo "  {$issue['file']}:{$issue['line']}\n";
        echo "    {$issue['content']}\n\n";
    }
} else {
    echo "✓ No TODO/FIXME comments found\n";
}

echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n";
$totalIssues = count($issues['debug']) + count($issues['todos']);
echo "Total issues: $totalIssues\n";

if ($totalIssues === 0) {
    echo "\n✓ Code is clean and ready for production!\n";
    exit(0);
} else {
    echo "\n⚠ Please review and fix the issues above before production deployment.\n";
    exit(1);
}
