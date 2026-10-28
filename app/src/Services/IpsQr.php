<?php
declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

/**
 * NBS IPS QR kod za uplatu (skenira se u mobilnom bankarstvu).
 * Format: K:PR|V:01|C:1|R:račun(18 cifara)|N:primalac|I:RSDiznos|SF:šifra|S:svrha
 */
final class IpsQr
{
    /** Pretvara "160-12345-67" u 18 cifara (banka 3 + partija 13 + kontrolni broj 2). */
    public static function normalizeAccount(string $account): ?string
    {
        $account = trim($account);
        if (preg_match('/^(\d{3})-?(\d{1,13})-?(\d{2})$/', $account, $m)) {
            return $m[1] . str_pad($m[2], 13, '0', STR_PAD_LEFT) . $m[3];
        }
        $digits = preg_replace('/\D/', '', $account) ?? '';
        return strlen($digits) === 18 ? $digits : null;
    }

    /** Kontrolni broj računa po modulu 97. */
    public static function isValidAccount(string $account18): bool
    {
        if (!preg_match('/^\d{18}$/', $account18)) {
            return false;
        }
        return self::mod97(substr($account18, 0, 16) . '00') === 98 - (int)substr($account18, 16, 2);
    }

    public static function payload(string $account, string $recipient, float $amount, string $purpose, string $paymentCode = '289'): ?string
    {
        $acc = self::normalizeAccount($account);
        if ($acc === null || $amount <= 0 || trim($recipient) === '') {
            return null;
        }
        $clean = fn(string $s, int $max) => mb_substr(str_replace('|', ' ', trim($s)), 0, $max);
        return implode('|', [
            'K:PR',
            'V:01',
            'C:1',
            'R:' . $acc,
            'N:' . $clean($recipient, 70),
            'I:RSD' . number_format($amount, 2, ',', ''),
            'SF:' . (preg_match('/^\d{3}$/', $paymentCode) ? $paymentCode : '289'),
            'S:' . $clean($purpose, 35),
        ]);
    }

    public static function svg(string $payload): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
            'addQuietzone' => true,
        ]);
        return (new QRCode($options))->render($payload);
    }

    /** Ostatak deljenja velikog broja sa 97 (bez bcmath ekstenzije). */
    private static function mod97(string $number): int
    {
        $rest = 0;
        foreach (str_split($number) as $digit) {
            $rest = ($rest * 10 + (int)$digit) % 97;
        }
        return $rest;
    }
}
