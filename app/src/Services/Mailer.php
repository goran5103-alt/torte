<?php
declare(strict_types=1);

namespace App\Services;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;

/** Slanje emailova (SMTP, PHP mail() ili upis u log za testiranje). */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, ?string $replyTo = null, ?string $fromName = null): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $cfg = Config::get('mail', []);
        $transport = $cfg['transport'] ?? 'log';

        if ($transport === 'log') {
            $entry = sprintf("=== %s\nTo: %s\nReply-To: %s\nSubject: %s\n\n%s\n\n", now(), $to, $replyTo ?? '-', $subject, strip_tags(str_replace(['<br>', '</p>', '</tr>', '</h2>'], "\n", $html)));
            file_put_contents(APP_DIR . '/storage/logs/mail.log', $entry, FILE_APPEND);
            return true;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            if ($transport === 'smtp') {
                $mail->isSMTP();
                $mail->Host = $cfg['host'];
                $mail->Port = (int)$cfg['port'];
                $mail->SMTPAuth = ($cfg['user'] ?? '') !== '';
                $mail->Username = $cfg['user'] ?? '';
                $mail->Password = $cfg['pass'] ?? '';
                $mail->SMTPSecure = $cfg['secure'] ?? 'tls';
                $mail->Timeout = 15;
            }
            $mail->setFrom($cfg['from_email'], $fromName ?? ($cfg['from_name'] ?? ''));
            $mail->addAddress($to);
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $html))));
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            logger('Email nije poslat', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
