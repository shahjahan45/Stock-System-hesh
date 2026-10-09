<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
$local = dirname(__DIR__) . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    $config = array_replace_recursive($config, is_array($override) ? $override : []);
}
date_default_timezone_set($config['app']['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('HFO_SESSION');
    session_set_cookie_params([
        'lifetime'=>0, 'path'=>'/', 'secure'=>!empty($_SERVER['HTTPS']),
        'httponly'=>true, 'samesite'=>'Lax'
    ]);
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), publickey-credentials-get=(self), publickey-credentials-create=(self)");

require_once __DIR__ . '/functions.php';

try {
    $db = $config['database'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    $safe = $config['app']['environment'] === 'development' ? e($e->getMessage()) : 'Database connection is unavailable.';
    exit('<!doctype html><meta name="viewport" content="width=device-width"><style>body{font:16px system-ui;background:#eef2f7;display:grid;place-items:center;min-height:100vh}.box{background:white;padding:32px;border-radius:24px;box-shadow:10px 10px 24px #cbd2db,-10px -10px 24px #fff;max-width:520px}</style><div class="box"><h1>Connection required</h1><p>'.$safe.'</p><p>Import <b>database/install.sql</b>, then configure <b>config.local.php</b>.</p></div>');
}

if (isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > $config['app']['session_timeout']) {
    session_unset(); session_destroy(); session_start();
    flash('warning', 'Your secure session expired. Please sign in again.');
}
if (!user() && !empty($_COOKIE['HFO_REMEMBER'])) {
    try { restore_remembered_user($pdo); } catch (Throwable $e) { setcookie('HFO_REMEMBER','',remember_cookie_options(time()-3600)); }
}
$_SESSION['last_activity'] = time();
