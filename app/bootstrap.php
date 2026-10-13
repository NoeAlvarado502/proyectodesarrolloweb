<?php
declare(strict_types=1);

use flight\Engine;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

date_default_timezone_set('America/Guatemala');

$envFile = ROOT_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name); $value = trim(trim($value), "\"'");
        if ($name !== '' && getenv($name) === false) { putenv($name . '=' . $value); $_ENV[$name] = $value; }
    }
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'control_academico';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string
{
    $base = rtrim((string)($GLOBALS['base_url'] ?? ''), '/');
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $base !== '' ? $base : '/';
    }
    return ($base !== '' ? $base : '') . '/' . $cleanPath;
}

function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type', 'message'); }
function takeFlash(): ?array { $value = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $value; }
function csrfToken(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrfField(): string { return '<input type="hidden" name="_token" value="' . e(csrfToken()) . '">'; }
function isNavActive(string $path = ''): bool
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $base = rtrim((string)($GLOBALS['base_url'] ?? ''), '/');
    $current = '/' . trim(substr($uri, strlen($base)), '/');
    if ($path === '' || $path === '/') {
        return $current === '/';
    }
    return str_starts_with($current, '/' . trim($path, '/'));
}
function icon(string $name, string $class = ''): string
{
    static $icons = [
        'home' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'users' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'user' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'grades' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'catalogs' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
        'report-users' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>',
        'report-grades' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>',
        'search' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>',
        'plus' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>',
        'edit' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
        'trash' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>',
        'arrow-right' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
        'arrow-left' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="12" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
        'print' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>',
        'check' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'academic' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
        'info' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>',
        'book' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>',
        'x'      => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>',
        'close'  => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>',
        'star'   => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'percent'=> '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
        'calendar'=>'<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
        'refresh' => '<svg class=":class" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>'
    ];
    $template = $icons[$name] ?? '';
    $finalClass = trim('icon ' . str_replace('icon', '', $class));
    return str_replace(':class', htmlspecialchars($finalClass, ENT_QUOTES, 'UTF-8'), $template);
}
function verifyCsrf(): void
{
    if (!hash_equals(csrfToken(), (string)($_POST['_token'] ?? ''))) {
        http_response_code(419); exit('La sesión expiró. Regresa y vuelve a enviar el formulario.');
    }
}
function render(string $page, array $__data = []): void
{
    extract($__data, EXTR_OVERWRITE);
    $contentFile = __DIR__ . '/views/' . $page . '.php';
    if (!is_file($contentFile)) { http_response_code(500); exit('Vista no encontrada.'); }
    ob_start(); require $contentFile; $content = ob_get_clean();
    $flash = takeFlash(); require __DIR__ . '/views/layout.php';
}
function partial(string $path, array $__data = []): string
{
    extract($__data, EXTR_OVERWRITE);
    $file = __DIR__ . '/views/' . ltrim($path, '/') . '.php';
    if (!is_file($file)) { return ''; }
    ob_start(); require $file; return (string)ob_get_clean();
}

function postString(string $key): string { return trim((string)($_POST[$key] ?? '')); }
function validName(string $name): bool
{
    $trimmed = trim(preg_replace('/\s+/', ' ', $name));
    $len = mb_strlen($trimmed, 'UTF-8');
    if ($len < 2 || $len > 100) {
        return false;
    }
    return (bool)preg_match('/^[\p{L}\s\'-]+$/u', $trimmed);
}
function validCourseName(string $name): bool
{
    $trimmed = trim(preg_replace('/\s+/', ' ', $name));
    $len = mb_strlen($trimmed, 'UTF-8');
    if ($len < 3 || $len > 100) {
        return false;
    }
    return (bool)preg_match('/^[\p{L}0-9\s\.\:\'\-\(\)]{3,100}$/u', $trimmed)
        && (bool)preg_match('/\p{L}/u', $trimmed);
}
function validBirthDate(string $date, int $minAge = 14, int $maxAge = 95): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    $errors = DateTime::getLastErrors();
    if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return false;
    }
    if ($parsed->format('Y-m-d') !== $date) {
        return false;
    }
    $today = new DateTime('today');
    if ($parsed > $today) {
        return false;
    }
    $diff = $parsed->diff($today);
    $age = (int)$diff->y;
    return $age >= $minAge && $age <= $maxAge;
}
function uploadDirectory(): string
{
    $custom = getenv('UPLOAD_DIR') ?: getenv('STORAGE_PATH');
    if ($custom !== false && trim($custom) !== '') {
        return rtrim(trim($custom), '/\\');
    }
    return ROOT_PATH . '/storage/uploads';
}

function uploadPhoto(?array $file, ?string $oldPhoto = null): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $oldPhoto;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) throw new RuntimeException('La fotografía no debe superar 3 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Usa una fotografía JPG, PNG o WEBP válida.');
    $directory = uploadDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('No se pudo preparar la carpeta de fotografías.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('No se pudo guardar la fotografía.');
    if ($oldPhoto && is_file($directory . '/' . basename($oldPhoto))) unlink($directory . '/' . basename($oldPhoto));
    return $name;
}

function photoUrl(?string $photo): string { return $photo ? url('storage/uploads/' . rawurlencode(basename($photo))) : url('assets/avatar.svg'); }

$envBaseUrl = getenv('APP_URL') ?: getenv('BASE_URL');
if ($envBaseUrl !== false && trim($envBaseUrl) !== '') {
    $GLOBALS['base_url'] = rtrim(trim($envBaseUrl), '/');
} else {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $GLOBALS['base_url'] = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
}

require __DIR__ . '/routes.php';
