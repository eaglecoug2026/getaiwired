<?php
/**
 * GetAIWired - Unsubscribe Handler
 * Handles unsubscribe requests from drip email links
 */

require_once __DIR__ . '/../includes/drip_campaign.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    header('Location: /unsubscribe.html?status=invalid');
    exit;
}

$result = unsubscribeByToken($token);

if ($result) {
    header('Location: /unsubscribe.html?status=success');
} else {
    header('Location: /unsubscribe.html?status=already');
}
exit;
?>
