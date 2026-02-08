<?php
/**
 * GitHub Webhook Auto-Deploy Script
 * URL: https://getaiwired.com/api/webhook-deploy.php
 * 
 * Setup in GitHub:
 * 1. Go to repo Settings → Webhooks → Add webhook
 * 2. Payload URL: https://getaiwired.com/api/webhook-deploy.php
 * 3. Content type: application/json
 * 4. Secret: getaiwired_deploy_2026
 * 5. Events: Just the push event
 */

$secret = 'getaiwired_deploy_2026';
$repo_path = '/home/1576910.cloudwaysapps.com/wgscdpther/public_html';

// Verify GitHub signature
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (!empty($secret) && !empty($signature)) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid signature']));
    }
}

// Pull latest code
$command = "cd $repo_path && git fetch origin 2>&1 && git reset --hard origin/master 2>&1";
$output = shell_exec($command);

header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'output' => $output,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
