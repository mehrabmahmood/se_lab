<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function send_email(string $to, string $subject, string $html_body): bool {
    // PHPMailer via Composer
    if (SMTP_ENABLED && file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = strip_tags($html_body);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Fallback: PHP mail()
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $from = SMTP_FROM_EMAIL ?: 'no-reply@localhost';
    $fromName = SMTP_FROM_NAME ?: APP_NAME;
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';

    return mail($to, $subject, $html_body, implode("\r\n", $headers));
}

function log_otp(string $email, string $otp, string $purpose): void {
    if (!DEV_LOG_OTP) return;

    // Local testing: if email sending fails, write OTP to log file.
    // For 2-step login OTP, write to a separate log (requested: storage/opt.log).
    $file = OTP_LOG_FILE;
    if ($purpose === 'login2fa' && defined('TWO_STEP_OTP_LOG_FILE')) {
        $file = TWO_STEP_OTP_LOG_FILE;
    }

    $line = '[' . gmdate('Y-m-d H:i:s') . ' UTC] ' . $purpose . ' OTP for ' . $email . ': ' . $otp . "
";
    @file_put_contents($file, $line, FILE_APPEND);
}

function send_otp_email(string $email, string $otp, string $purpose): bool {
    $subject = APP_NAME . ' OTP Verification';
    if ($purpose === 'reset') {
        $subject = APP_NAME . ' Password Reset OTP';
    } elseif ($purpose === 'login2fa') {
        $subject = APP_NAME . ' Login OTP (Two-Step Verification)';
    }

    $html = '<div style="font-family:Arial,sans-serif;line-height:1.5">'
          . '<h2>' . htmlspecialchars(APP_NAME) . '</h2>'
          . '<p>Your OTP code is:</p>'
          . '<div style="font-size:28px;font-weight:bold;letter-spacing:4px">' . htmlspecialchars($otp) . '</div>'
          . '<p>This code will expire in ' . (int)OTP_TTL_MINUTES . ' minutes.</p>'
          . '<p>If you did not request this, you can ignore this email.</p>'
          . '</div>';

    $ok = send_email($email, $subject, $html);
    if (!$ok) {
        log_otp($email, $otp, $purpose);
    }
    return $ok;
}