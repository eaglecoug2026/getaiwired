<?php
/**
 * GetAIWired Email Helper
 * Centralized email sending using Postmark SMTP
 */

// =====================================================
// POSTMARK SMTP CONFIGURATION
// =====================================================
define('SMTP_HOST', 'smtp.postmarkapp.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '5033af6f-232c-460d-b247-3571db4a12e2');
define('SMTP_PASSWORD', '5033af6f-232c-460d-b247-3571db4a12e2');
define('SMTP_FROM_EMAIL', 'info@intellismartstaffing.com');
define('SMTP_FROM_NAME', 'GetAIWired');

/**
 * Send email via Postmark SMTP
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '', $replyTo = '') {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USERNAME;
    $password = SMTP_PASSWORD;
    $from = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    if (empty($username) || empty($password)) {
        return ['success' => false, 'error' => 'SMTP credentials not configured'];
    }

    try {
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);

        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
        }

        stream_set_timeout($socket, 30);

        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "Server greeting failed: $response"];
        }

        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }

        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "STARTTLS failed: $response"];
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['success' => false, 'error' => 'TLS encryption failed'];
        }

        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "AUTH LOGIN failed: $response"];
        }

        fwrite($socket, base64_encode($username) . "\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "Username rejected: $response"];
        }

        fwrite($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'error' => "Authentication failed: $response"];
        }

        fwrite($socket, "MAIL FROM:<$from>\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "MAIL FROM failed: $response"];
        }

        fwrite($socket, "RCPT TO:<$to>\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "RCPT TO failed: $response"];
        }

        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'error' => "DATA failed: $response"];
        }

        $boundary = md5(uniqid(time()));

        $message = "MIME-Version: 1.0\r\n";
        $message .= "From: $fromName <$from>\r\n";
        $message .= "To: $to\r\n";
        if (!empty($replyTo)) {
            $message .= "Reply-To: $replyTo\r\n";
        }
        $message .= "Subject: $subject\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $message .= "\r\n";

        if (!empty($textBody)) {
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n";
            $message .= "\r\n";
            $message .= $textBody . "\r\n";
            $message .= "\r\n";
        }

        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "\r\n";

        $message .= "--$boundary--\r\n";
        $message .= ".\r\n";

        fwrite($socket, $message);
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "Message send failed: $response"];
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'method' => 'postmark_smtp'];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'SMTP Exception: ' . $e->getMessage()];
    }
}
?>
