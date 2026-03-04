<?php
/**
 * GetAIWired - Drip Email Cron Job
 * Cron: every 15 minutes
 * Example: star-slash-15 * * * * php /path/to/cron/send-drip-emails.php >> /tmp/getaiwired-drip.log 2>&1
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../includes/drip_campaign.php';

$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

echo "[$timestamp] Drip email cron starting...\n";

try {
    $stats = processDripEmails();
    $elapsed = round(microtime(true) - $startTime, 2);

    echo "[$timestamp] Done in {$elapsed}s — " .
         "Processed: {$stats['processed']}, " .
         "Sent: {$stats['sent']}, " .
         "Skipped: {$stats['skipped']}, " .
         "Failed: {$stats['failed']}, " .
         "Completed: {$stats['completed']}\n";

} catch (Exception $e) {
    echo "[$timestamp] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
