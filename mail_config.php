<?php
// SMTP / Mail configuration placeholder.
// Update these values with your SMTP provider details if you want reliable email delivery.

// If left blank, the code will fall back to PHP mail().
// You can set these via environment variables instead of editing this file.
$MAIL_SMTP_HOST = getenv('MAIL_SMTP_HOST') ?: '';
$MAIL_SMTP_PORT = (int)(getenv('MAIL_SMTP_PORT') ?: 587);
$MAIL_SMTP_USERNAME = getenv('MAIL_SMTP_USERNAME') ?: '';
$MAIL_SMTP_PASSWORD = getenv('MAIL_SMTP_PASSWORD') ?: '';
$MAIL_SMTP_SECURE = getenv('MAIL_SMTP_SECURE') ?: 'tls'; // or 'ssl'
$MAIL_FROM = getenv('MAIL_FROM') ?: 'realitydreaminternational@gmail.com';
$MAIL_FROM_NAME = getenv('MAIL_FROM_NAME') ?: 'Reality Dream Institute';

// If Composer autoload exists, require it to load PHPMailer
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Helper: send mail using SMTP if configured, otherwise use mail()
 * For production use, install PHPMailer and replace this with PHPMailer implementation.
 */
function send_site_mail($to, $subject, $body, $headers = '') {
    global $MAIL_SMTP_HOST, $MAIL_SMTP_PORT, $MAIL_SMTP_USERNAME, $MAIL_SMTP_PASSWORD, $MAIL_SMTP_SECURE, $MAIL_FROM, $MAIL_FROM_NAME;

    $replyTo = '';
    if (is_string($headers) && preg_match('/^Reply-To:\s*([^\r\n]+)/mi', $headers, $match)) {
        $replyTo = trim($match[1]);
    }

    // If SMTP credentials provided and PHPMailer exists, try PHPMailer
    if (!empty($MAIL_SMTP_HOST) && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $MAIL_SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = $MAIL_SMTP_USERNAME;
            $mail->Password = $MAIL_SMTP_PASSWORD;
            $mail->SMTPSecure = $MAIL_SMTP_SECURE ?? 'tls';
            $mail->Port = $MAIL_SMTP_PORT;
            $mail->setFrom($MAIL_FROM, $MAIL_FROM_NAME);
            if (!empty($replyTo)) {
                $mail->addReplyTo($replyTo);
            }
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback to PHP mail (force a safe sender and preserve Reply-To when provided)
    $fallbackHeaders = "MIME-Version: 1.0\r\n";
    $fallbackHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $fallbackHeaders .= "From: {$MAIL_FROM}\r\n";
    if (!empty($replyTo)) {
        $fallbackHeaders .= "Reply-To: {$replyTo}\r\n";
    }

    $sent = @mail($to, $subject, $body, $fallbackHeaders);
    if (!$sent) {
        error_log('mail() failed. Configure SMTP credentials in mail_config.php for reliable delivery.');
    }
    return $sent;
}
