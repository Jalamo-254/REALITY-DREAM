<?php
// SMTP / Mail configuration placeholder.
// Update these values with your SMTP provider details if you want reliable email delivery.

// SMTP defaults are pre-set for Gmail; override via environment variables in production.
$MAIL_SMTP_HOST = getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com';
$MAIL_SMTP_PORT = (int)(getenv('MAIL_SMTP_PORT') ?: 587);
$MAIL_SMTP_USERNAME = getenv('MAIL_SMTP_USERNAME') ?: 'realitydreamacademy@gmail.com';
$MAIL_SMTP_PASSWORD = getenv('MAIL_SMTP_PASSWORD') ?: 'RDGmailDream##2026';
$MAIL_SMTP_SECURE = getenv('MAIL_SMTP_SECURE') ?: 'tls'; // or 'ssl'
$MAIL_FROM = getenv('MAIL_FROM') ?: 'realitydreamacademy@gmail.com';
$MAIL_FROM_NAME = getenv('MAIL_FROM_NAME') ?: 'Reality Dream Institute';

/**
 * Return one or more admin notification emails.
 * Preferred env: ADMIN_CONTACT_EMAILS (comma/semicolon separated)
 * Fallback env: ADMIN_CONTACT_EMAIL
 */
function get_admin_contact_emails() {
    $raw = getenv('ADMIN_CONTACT_EMAILS');
    if ($raw === false || trim($raw) === '') {
        $raw = getenv('ADMIN_CONTACT_EMAIL') ?: 'hayestechnologyisolution@gmail.com,realitydreamacademy@gmail.com';
    }

    $parts = preg_split('/[;,]/', (string)$raw);
    $valid = [];
    foreach ($parts as $part) {
        $email = trim($part);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[] = strtolower($email);
        }
    }

    $valid = array_values(array_unique($valid));
    if (empty($valid)) {
        $valid[] = 'realitydreamacademy@gmail.com';
    }
    return $valid;
}

// If Composer autoload exists, require it to load PHPMailer
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Basic SMTP sender without external dependencies.
 * Used as a fallback when PHPMailer is not installed.
 */
function send_site_mail_via_smtp_socket($to, $subject, $body, $fromEmail, $fromName, $replyTo = '') {
    global $MAIL_SMTP_HOST, $MAIL_SMTP_PORT, $MAIL_SMTP_USERNAME, $MAIL_SMTP_PASSWORD, $MAIL_SMTP_SECURE;

    if (empty($MAIL_SMTP_HOST) || empty($MAIL_SMTP_USERNAME) || empty($MAIL_SMTP_PASSWORD)) {
        return false;
    }

    $host = $MAIL_SMTP_HOST;
    $port = (int)$MAIL_SMTP_PORT;
    $secure = strtolower((string)$MAIL_SMTP_SECURE);
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;

    $socket = @fsockopen($remote, $port, $errno, $errstr, 20);
    if (!$socket) {
        error_log("SMTP socket connect failed: {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 20);

    $expectCode = function ($line, $codes) {
        $code = (int)substr((string)$line, 0, 3);
        return in_array($code, $codes, true);
    };

    $readReply = function () use ($socket) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCmd = function ($cmd, $codes) use ($socket, $readReply, $expectCode) {
        fwrite($socket, $cmd . "\r\n");
        $reply = $readReply();
        if (!$expectCode($reply, $codes)) {
            return [false, $reply];
        }
        return [true, $reply];
    };

    $greeting = $readReply();
    if (!$expectCode($greeting, [220])) {
        fclose($socket);
        error_log('SMTP greeting failed: ' . trim($greeting));
        return false;
    }

    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    [$ok, $reply] = $sendCmd("EHLO {$serverName}", [250]);
    if (!$ok) {
        [$ok, $reply] = $sendCmd("HELO {$serverName}", [250]);
        if (!$ok) {
            fclose($socket);
            error_log('SMTP EHLO/HELO failed: ' . trim($reply));
            return false;
        }
    }

    if ($secure === 'tls') {
        [$ok, $reply] = $sendCmd('STARTTLS', [220]);
        if (!$ok) {
            fclose($socket);
            error_log('SMTP STARTTLS failed: ' . trim($reply));
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            error_log('SMTP TLS negotiation failed.');
            return false;
        }
        [$ok, $reply] = $sendCmd("EHLO {$serverName}", [250]);
        if (!$ok) {
            fclose($socket);
            error_log('SMTP EHLO after TLS failed: ' . trim($reply));
            return false;
        }
    }

    [$ok, $reply] = $sendCmd('AUTH LOGIN', [334]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP AUTH LOGIN failed: ' . trim($reply));
        return false;
    }
    [$ok, $reply] = $sendCmd(base64_encode($MAIL_SMTP_USERNAME), [334]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP username rejected: ' . trim($reply));
        return false;
    }
    [$ok, $reply] = $sendCmd(base64_encode($MAIL_SMTP_PASSWORD), [235]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP password rejected: ' . trim($reply));
        return false;
    }

    [$ok, $reply] = $sendCmd("MAIL FROM:<{$fromEmail}>", [250]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP MAIL FROM failed: ' . trim($reply));
        return false;
    }
    [$ok, $reply] = $sendCmd("RCPT TO:<{$to}>", [250, 251]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP RCPT TO failed: ' . trim($reply));
        return false;
    }
    [$ok, $reply] = $sendCmd('DATA', [354]);
    if (!$ok) {
        fclose($socket);
        error_log('SMTP DATA failed: ' . trim($reply));
        return false;
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $safeFromName = str_replace(['"', "\r", "\n"], '', $fromName);
    $safeTo = str_replace(["\r", "\n"], '', $to);
    $safeReplyTo = str_replace(["\r", "\n"], '', $replyTo);

    $dataHeaders = [];
    $dataHeaders[] = "From: \"{$safeFromName}\" <{$fromEmail}>";
    $dataHeaders[] = "To: {$safeTo}";
    $dataHeaders[] = "Subject: {$encodedSubject}";
    $dataHeaders[] = "MIME-Version: 1.0";
    $dataHeaders[] = "Content-Type: text/html; charset=UTF-8";
    if (!empty($safeReplyTo)) {
        $dataHeaders[] = "Reply-To: {$safeReplyTo}";
    }

    $data = implode("\r\n", $dataHeaders) . "\r\n\r\n" . $body;
    $data = str_replace("\r\n.\r\n", "\r\n..\r\n", $data);
    fwrite($socket, $data . "\r\n.\r\n");

    $reply = $readReply();
    if (!$expectCode($reply, [250])) {
        fclose($socket);
        error_log('SMTP message not accepted: ' . trim($reply));
        return false;
    }

    @fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
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

    // If SMTP credentials provided and PHPMailer exists, try PHPMailer.
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

    // If SMTP is configured but PHPMailer is unavailable, use socket SMTP fallback.
    if (!empty($MAIL_SMTP_HOST) && !empty($MAIL_SMTP_USERNAME) && !empty($MAIL_SMTP_PASSWORD)) {
        $smtpSent = send_site_mail_via_smtp_socket($to, $subject, $body, $MAIL_FROM, $MAIL_FROM_NAME, $replyTo);
        if ($smtpSent) {
            return true;
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

