<?php
declare(strict_types=1);

use App\Csrf;
use App\HttpException;

/** HTML escape. */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Iznos u formatu 12.345 RSD (ili sa decimalama ako postoje). */
function money(float|int|string|null $amount, string $currency = 'RSD'): string
{
    $amount = (float)$amount;
    $decimals = abs($amount - round($amount)) > 0.001 ? 2 : 0;
    return number_format($amount, $decimals, ',', '.') . ' ' . $currency;
}

function qty(float|string $value): string
{
    $value = (float)$value;
    return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
}

/** Datum u formatu 05.11.2026. */
function date_sr(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d.m.Y.', $ts) : '';
}

function day_name_sr(string $date): string
{
    $names = ['nedelja', 'ponedeljak', 'utorak', 'sreda', 'četvrtak', 'petak', 'subota'];
    return $names[(int)date('w', strtotime($date))];
}

function datetime_sr(?string $date): string
{
    if (!$date) {
        return '';
    }
    return date('d.m.Y. H:i', strtotime($date));
}

function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function json_response(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code, string $message = ''): never
{
    throw new HttpException($code, $message);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

/** Vrednost iz POST/GET kao string, bez razmaka na krajevima. */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function input_int(string $key, int $default = 0): int
{
    $value = input($key);
    return is_numeric($value) ? (int)$value : $default;
}

function input_float(string $key, float $default = 0): float
{
    $value = str_replace(',', '.', input($key));
    return is_numeric($value) ? (float)$value : $default;
}

function random_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim(rawurldecode($path), '/');
    return $path;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/** JSON telo zahteva (za fetch pozive iz browsera). */
function json_input(): array
{
    static $data = null;
    if ($data === null) {
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        $data = is_array($decoded) ? $decoded : [];
    }
    return $data;
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string)filemtime($file) : '1';
    return '/assets/' . ltrim($path, '/') . '?v=' . $version;
}

function upload_url(?string $path): string
{
    return $path ? '/uploads/' . ltrim($path, '/') : '';
}

function logger(string $message, array $context = []): void
{
    $line = '[' . now() . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    @file_put_contents(APP_DIR . '/storage/logs/app.log', $line . "\n", FILE_APPEND);
}
