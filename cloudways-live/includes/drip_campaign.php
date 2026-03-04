<?php
/**
 * GetAIWired - Drip Campaign Logic
 * Enrollment, processing, and unsubscribe management
 */

require_once __DIR__ . '/drip_db.php';
require_once __DIR__ . '/drip_emails.php';
require_once __DIR__ . '/email_helper.php';

/**
 * Enroll a user in the drip campaign
 * Skips if email already has an active campaign
 * @return string|false Campaign ID or false if skipped
 */
function enrollInDripCampaign($email, $firstName, $company, $score, $level, $topOpportunity, $opportunities = [], $industry = '') {
    $db = getDripDb();

    // Check for existing active campaign for this email
    $stmt = $db->prepare('SELECT id FROM drip_campaigns WHERE email = :email AND status = \'active\'');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result->fetchArray()) {
        return false; // Already enrolled
    }

    $campaignId = bin2hex(random_bytes(16));
    $unsubToken = bin2hex(random_bytes(16));
    $opportunitiesJson = !empty($opportunities) ? json_encode($opportunities) : '';

    $stmt = $db->prepare('INSERT INTO drip_campaigns
        (id, email, first_name, company, score, level, top_opportunity, opportunities_json, industry, unsubscribe_token)
        VALUES (:id, :email, :first_name, :company, :score, :level, :top_opp, :opps_json, :industry, :unsub_token)');

    $stmt->bindValue(':id', $campaignId, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':first_name', $firstName, SQLITE3_TEXT);
    $stmt->bindValue(':company', $company, SQLITE3_TEXT);
    $stmt->bindValue(':score', $score, SQLITE3_INTEGER);
    $stmt->bindValue(':level', $level, SQLITE3_TEXT);
    $stmt->bindValue(':top_opp', $topOpportunity, SQLITE3_TEXT);
    $stmt->bindValue(':opps_json', $opportunitiesJson, SQLITE3_TEXT);
    $stmt->bindValue(':industry', $industry, SQLITE3_TEXT);
    $stmt->bindValue(':unsub_token', $unsubToken, SQLITE3_TEXT);

    $stmt->execute();

    return $campaignId;
}

/**
 * Process all due drip emails
 * Called by cron every 15 minutes
 * @return array Stats about what was processed
 */
function processDripEmails() {
    $db = getDripDb();
    $stats = ['processed' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'completed' => 0];

    // Get all active campaigns
    $result = $db->query('SELECT * FROM drip_campaigns WHERE status = \'active\'');

    while ($campaign = $result->fetchArray(SQLITE3_ASSOC)) {
        $stats['processed']++;
        $nextStage = $campaign['current_stage'] + 1;

        // Campaign complete?
        if ($nextStage > 5) {
            $stmt = $db->prepare('UPDATE drip_campaigns SET status = \'completed\', completed_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->bindValue(':id', $campaign['id'], SQLITE3_TEXT);
            $stmt->execute();
            $stats['completed']++;
            continue;
        }

        // Check if it's time for the next email
        $schedule = DRIP_SCHEDULE[$nextStage] ?? null;
        if (!$schedule) continue;

        $createdAt = strtotime($campaign['created_at']);
        $sendAfter = $createdAt + ($schedule['hours'] * 3600);

        if (time() < $sendAfter) {
            $stats['skipped']++;
            continue; // Not time yet
        }

        // Check if we already sent this stage (prevent duplicates)
        $stmt = $db->prepare('SELECT id FROM drip_email_log WHERE campaign_id = :cid AND email_number = :num AND send_result = \'success\'');
        $stmt->bindValue(':cid', $campaign['id'], SQLITE3_TEXT);
        $stmt->bindValue(':num', $nextStage, SQLITE3_INTEGER);
        $logResult = $stmt->execute();
        if ($logResult->fetchArray()) {
            // Already sent, advance stage
            $stmt = $db->prepare('UPDATE drip_campaigns SET current_stage = :stage WHERE id = :id');
            $stmt->bindValue(':stage', $nextStage, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $campaign['id'], SQLITE3_TEXT);
            $stmt->execute();
            continue;
        }

        // Build and send the email
        $emailData = getDripEmail($nextStage, $campaign);
        if (!$emailData) {
            logDripEmail($campaign['id'], $campaign['email'], $nextStage, 'unknown', 'skip', 'No template for stage ' . $nextStage);
            $stats['skipped']++;
            continue;
        }

        $sendResult = sendEmail(
            $campaign['email'],
            $emailData['subject'],
            $emailData['html'],
            $emailData['text']
        );

        if ($sendResult['success']) {
            // Update campaign stage
            $stmt = $db->prepare('UPDATE drip_campaigns SET current_stage = :stage WHERE id = :id');
            $stmt->bindValue(':stage', $nextStage, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $campaign['id'], SQLITE3_TEXT);
            $stmt->execute();

            logDripEmail($campaign['id'], $campaign['email'], $nextStage, $emailData['subject'], 'success');
            $stats['sent']++;
        } else {
            logDripEmail($campaign['id'], $campaign['email'], $nextStage, $emailData['subject'], 'fail', '', $sendResult['error'] ?? 'Unknown error');
            $stats['failed']++;
        }
    }

    return $stats;
}

/**
 * Log a drip email send attempt
 */
function logDripEmail($campaignId, $email, $emailNumber, $subject, $result, $skipReason = '', $errorMessage = '') {
    $db = getDripDb();
    $logId = bin2hex(random_bytes(16));

    $stmt = $db->prepare('INSERT INTO drip_email_log
        (id, campaign_id, email, email_number, subject, send_result, skip_reason, error_message)
        VALUES (:id, :cid, :email, :num, :subject, :result, :skip, :error)');

    $stmt->bindValue(':id', $logId, SQLITE3_TEXT);
    $stmt->bindValue(':cid', $campaignId, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':num', $emailNumber, SQLITE3_INTEGER);
    $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
    $stmt->bindValue(':result', $result, SQLITE3_TEXT);
    $stmt->bindValue(':skip', $skipReason, SQLITE3_TEXT);
    $stmt->bindValue(':error', $errorMessage, SQLITE3_TEXT);

    $stmt->execute();
}

/**
 * Unsubscribe by token
 * @return bool True if found and unsubscribed
 */
function unsubscribeByToken($token) {
    $db = getDripDb();

    $stmt = $db->prepare('UPDATE drip_campaigns SET status = \'unsubscribed\', completed_at = CURRENT_TIMESTAMP WHERE unsubscribe_token = :token AND status = \'active\'');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->execute();

    return $db->changes() > 0;
}
?>
