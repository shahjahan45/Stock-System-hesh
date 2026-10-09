<?php
declare(strict_types=1);

// Detect the installation folder automatically (for example /heshapp on XAMPP).
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$projectRoot = realpath(__DIR__);
$basePath = '';
if ($documentRoot && $projectRoot) {
    $normalizedDocumentRoot = str_replace('\\', '/', $documentRoot);
    $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);
    if (stripos($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
        $basePath = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
    }
}
$autoUrl = $scheme . '://' . $host . '/' . trim($basePath, '/');

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'HESH & FRN Operations',
        'url' => rtrim(getenv('APP_URL') ?: $autoUrl, '/'),
        'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Qatar',
        'currency' => getenv('APP_CURRENCY') ?: 'QAR',
        'environment' => getenv('APP_ENV') ?: 'production',
        'session_timeout' => 7200,
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'hesh_frn_operations',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
