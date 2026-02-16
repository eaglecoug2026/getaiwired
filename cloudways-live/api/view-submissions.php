<?php
/**
 * GetAIWired - View Test Submissions Log
 * Simple viewer for submissions.jsonl
 */

header('Content-Type: application/json');

$logFile = __DIR__ . '/../logs/submissions.jsonl';

if (!file_exists($logFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'No submissions logged yet',
        'submissions' => []
    ]);
    exit;
}

// Read all lines and parse as JSON
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$submissions = [];

foreach ($lines as $line) {
    $entry = json_decode($line, true);
    if ($entry) {
        $submissions[] = $entry;
    }
}

// Sort by timestamp descending (newest first)
usort($submissions, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

echo json_encode([
    'success' => true,
    'count' => count($submissions),
    'submissions' => $submissions
], JSON_PRETTY_PRINT);
?>
