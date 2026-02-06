#!/usr/bin/env php
<?php

/**
 * Comprehensive Test Analysis Script
 *
 * This script analyzes test failures and categorizes them for Story 52
 */
echo "===== STORY 52: COMPREHENSIVE TEST ANALYSIS =====\n\n";

// Run tests and capture output
$output = shell_exec('cd /Volumes/DATA/PROJECTS/HOSPITALITYSYSTEM/laravel-app && php artisan test 2>&1');

// Extract test summary
preg_match('/Tests:\s+(\d+)\s+failed,\s+(\d+)\s+skipped,\s+(\d+)\s+passed/', $output, $matches);

$failed = $matches[1] ?? 0;
$skipped = $matches[2] ?? 0;
$passed = $matches[3] ?? 0;
$total = $failed + $skipped + $passed;

echo "Test Summary:\n";
echo "-------------\n";
echo "Total Tests: $total\n";
echo "Passed: $passed (".round(($passed / $total) * 100, 2)."%)\n";
echo "Failed: $failed\n";
echo "Skipped: $skipped\n";
echo 'Coverage: '.round(($passed / $total) * 100, 2)."%\n\n";

// Extract failed tests
preg_match_all('/FAILED\s+([^\n]+)/', $output, $failedTests);

if (! empty($failedTests[1])) {
    echo "Failed Tests:\n";
    echo "-------------\n";
    foreach ($failedTests[1] as $index => $test) {
        echo ($index + 1).'. '.trim($test)."\n";
    }
    echo "\n";
}

// Categorize failures
$categories = [
    'Database Schema' => 0,
    'Authentication' => 0,
    'Authorization' => 0,
    'API Endpoints' => 0,
    'Business Logic' => 0,
    'Other' => 0,
];

foreach ($failedTests[1] as $test) {
    if (stripos($test, 'SQLSTATE') !== false || stripos($test, 'column') !== false) {
        $categories['Database Schema']++;
    } elseif (stripos($test, 'auth') !== false) {
        $categories['Authentication']++;
    } elseif (stripos($test, 'permission') !== false || stripos($test, '403') !== false) {
        $categories['Authorization']++;
    } elseif (stripos($test, 'api') !== false || stripos($test, '422') !== false || stripos($test, '404') !== false) {
        $categories['API Endpoints']++;
    } else {
        $categories['Business Logic']++;
    }
}

echo "Failure Categories:\n";
echo "-------------------\n";
foreach ($categories as $category => $count) {
    if ($count > 0) {
        echo "$category: $count\n";
    }
}

echo "\n===== ANALYSIS COMPLETE =====\n";
