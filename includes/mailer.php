<?php
/**
 * Transactional Email & Notification Dispatcher
 * MITS Alumni Portal - MITS Connect
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Send an email using configured SMTP or PHP mail() fallback
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $html_body HTML formatted body
 * @param string $alt_body Plain text fallback body
 * @return bool True if sent or accepted, false on failure
 */
function send_email($to, $subject, $html_body, $alt_body = '') {
    $to = trim($to);
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $from_email = defined('MAIL_FROM_ADDRESS') && !empty(MAIL_FROM_ADDRESS) ? MAIL_FROM_ADDRESS : 'noreply@mits.ac.in';
    $from_name  = defined('MAIL_FROM_NAME') && !empty(MAIL_FROM_NAME) ? MAIL_FROM_NAME : 'MITS Connect';
    $driver     = defined('MAIL_DRIVER') ? strtolower(MAIL_DRIVER) : 'smtp';

    $sent = false;
    $error_msg = '';

    // Attempt SMTP if driver is SMTP and credentials provided
    if ($driver === 'smtp' && defined('MAIL_HOST') && !empty(MAIL_HOST) && defined('MAIL_USERNAME') && !empty(MAIL_USERNAME)) {
        $sent = smtp_send_socket($to, $subject, $html_body, $from_email, $from_name, $error_msg);
    }

    // Fallback to native PHP mail() if SMTP was not configured or failed
    if (!$sent) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>\r\n";
        $headers .= "Reply-To: {$from_email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $sent = @mail($to, $encoded_subject, $html_body, $headers);
    }

    // Log email activity for debugging and audit trail
    log_email_dispatch($to, $subject, $sent, $error_msg);

    return $sent;
}

/**
 * Lightweight native SMTP socket sender without external dependencies
 */
function smtp_send_socket($to, $subject, $html_body, $from_email, $from_name, &$error_msg = '') {
    $host = MAIL_HOST;
    $port = MAIL_PORT;
    $encryption = strtolower(MAIL_ENCRYPTION);
    $user = MAIL_USERNAME;
    $pass = MAIL_PASSWORD;
    $timeout = 10;

    $socket_host = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
    $socket = @stream_socket_client("{$socket_host}:{$port}", $errno, $errstr, $timeout);

    if (!$socket) {
        $error_msg = "Could not connect to SMTP server: $errstr ($errno)";
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read = function() use ($socket) {
        $data = '';
        while ($line = fgets($socket, 512)) {
            $data .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $data;
    };

    $send = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $greeting = $read();
    if (substr($greeting, 0, 3) !== '220') {
        $error_msg = "Invalid SMTP greeting: $greeting";
        fclose($socket);
        return false;
    }

    $send("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $ehlo = $read();

    // STARTTLS for TLS encryption
    if ($encryption === 'tls' || ($port == 587 && strpos($ehlo, 'STARTTLS') !== false)) {
        $send("STARTTLS");
        $starttls_response = $read();
        if (substr($starttls_response, 0, 3) === '220') {
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $error_msg = "TLS encryption handshake failed";
                fclose($socket);
                return false;
            }
            $send("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $read();
        }
    }

    // AUTH LOGIN
    $send("AUTH LOGIN");
    $auth = $read();
    if (substr($auth, 0, 3) === '334') {
        $send(base64_encode($user));
        $u_resp = $read();
        if (substr($u_resp, 0, 3) === '334') {
            $send(base64_encode($pass));
            $p_resp = $read();
            if (substr($p_resp, 0, 3) !== '235') {
                $error_msg = "SMTP Authentication failed: $p_resp";
                fclose($socket);
                return false;
            }
        } else {
            $error_msg = "SMTP User rejected: $u_resp";
            fclose($socket);
            return false;
        }
    }

    // MAIL FROM
    $send("MAIL FROM:<{$from_email}>");
    $mf_resp = $read();
    if (substr($mf_resp, 0, 3) !== '250') {
        $error_msg = "MAIL FROM rejected: $mf_resp";
        fclose($socket);
        return false;
    }

    // RCPT TO
    $send("RCPT TO:<{$to}>");
    $rcpt_resp = $read();
    if (substr($rcpt_resp, 0, 3) !== '250' && substr($rcpt_resp, 0, 3) !== '251') {
        $error_msg = "RCPT TO rejected: $rcpt_resp";
        fclose($socket);
        return false;
    }

    // DATA
    $send("DATA");
    $data_resp = $read();
    if (substr($data_resp, 0, 3) !== '354') {
        $error_msg = "DATA rejected: $data_resp";
        fclose($socket);
        return false;
    }

    // Payload
    $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encoded_from = "=?UTF-8?B?" . base64_encode($from_name) . "?=";
    $date = date('r');

    $message  = "Date: {$date}\r\n";
    $message .= "From: {$encoded_from} <{$from_email}>\r\n";
    $message .= "To: <{$to}>\r\n";
    $message .= "Subject: {$encoded_subject}\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $html_body . "\r\n.\r\n";

    $send($message);
    $send_resp = $read();

    $send("QUIT");
    fclose($socket);

    if (substr($send_resp, 0, 3) === '250') {
        return true;
    }

    $error_msg = "Message rejected: $send_resp";
    return false;
}

/**
 * Log email dispatch results to uploads/mail_logs for audit
 */
function log_email_dispatch($to, $subject, $status, $error = '') {
    $log_dir = UPLOAD_DIR . '/mail_logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $file = $log_dir . '/mail_' . date('Y-m') . '.log';
    $time = date('Y-m-d H:i:s');
    $status_str = $status ? 'SUCCESS' : 'FAILED';
    $line = "[{$time}] [{$status_str}] To: {$to} | Subject: {$subject}" . (!empty($error) ? " | Error: {$error}" : "") . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

/**
 * Send the official "Alumni Profile Verified" notification email
 */
function send_alumni_verified_email($recipient_email, $recipient_name) {
    $subject = 'Welcome to MITS Connect! Your Alumni Account has been Verified';
    $feed_url = BASE_URL . '/feed.php';
    $current_year = date('Y');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; color: #1e293b;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #d8151c 0%, #991b1b 100%); padding: 36px 30px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">MITS CONNECT</h1>
                            <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Old Student Association of MITS (OSAM)</p>
                            <p style="margin: 2px 0 0; font-size: 12px; opacity: 0.8;">Muthoot Institute of Technology and Science</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 36px;">
                            <div style="display: inline-block; background-color: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
                                &#10004; Account Verified
                            </div>

                            <h2 style="margin: 0 0 16px; color: #0f172a; font-size: 20px;">Dear {$recipient_name},</h2>

                            <p style="line-height: 1.7; font-size: 15px; color: #475569; margin: 0 0 18px;">
                                We are thrilled to let you know that your registration credentials have been <strong>successfully verified</strong> against the college academic records by the MITS administration.
                            </p>

                            <p style="line-height: 1.7; font-size: 15px; color: #475569; margin: 0 0 24px;">
                                Your official <strong>Verified Alumnus</strong> status is now active, granting you unrestricted access to the entire MITS alumni portal and network.
                            </p>

                            <!-- Feature Highlights Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 28px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 10px; font-weight: 600; font-size: 14px; color: #0f172a;">What you can do now:</p>
                                        <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #475569; line-height: 1.8;">
                                            <li><strong>Alumni Directory:</strong> Reconnect with batchmates and graduates across the globe.</li>
                                            <li><strong>Community Feed:</strong> Share professional achievements, campus memories, and updates.</li>
                                            <li><strong>Career & Jobs Board:</strong> Explore exclusive alumni job referrals or post career openings.</li>
                                            <li><strong>Student Mentorship:</strong> Guide budding engineering minds from your alma mater.</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 10px 0 24px;">
                                        <a href="{$feed_url}" target="_blank" style="background-color: #d8151c; color: #ffffff; padding: 14px 34px; text-decoration: none; border-radius: 50px; font-size: 15px; font-weight: 600; display: inline-block; box-shadow: 0 4px 12px rgba(216, 21, 28, 0.35);">
                                            Enter MITS Connect Portal &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0;">
                                If you have any questions or need assistance, please contact the OSAM Alumni Committee at <a href="mailto:info@mgits.ac.in" style="color: #d8151c; text-decoration: none;">info@mgits.ac.in</a>.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 30px; font-size: 12px; color: #64748b; line-height: 1.6;">
                            <p style="margin: 0 0 4px; font-weight: 600; color: #334155;">Muthoot Institute of Technology and Science (Autonomous)</p>
                            <p style="margin: 0 0 8px;">Varikoli P.O., Puthencruz, Ernakulam, Kerala - 682308</p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">&copy; {$current_year} MITS Connect & OSAM. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    return send_email($recipient_email, $subject, $html);
}
