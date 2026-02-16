<?php
require __DIR__ . '/includes/email_helper.php';
$result = sendEmail('eaglecoug2026@gmail.com', 'Test from GetAIWired', '<h1>Test</h1><p>This is a test email from GetAIWired.</p>', 'Test plain text');
echo "Result: \n";
print_r($result);
?>
