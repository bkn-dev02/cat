<?php
/**
 * CAT Enterprise - Entry Point
 * SMA Unggul Binakasih Nusantara
 */

// ============================================
// PHP PERFORMANCE OPTIMIZATION
// ============================================
// IMPORTANT: Must be set BEFORE session_start()

// Session optimization
@ini_set('session.gc_probability', 1);
@ini_set('session.gc_divisor', 100);
@ini_set('session.gc_maxlifetime', 3600);

// Disable output buffering for faster response
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 'On');

// Memory and execution limits
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '30');
@ini_set('max_input_time', '30');

// Upload limits
@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');

// NOW start session
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan agar tidak ganggu header ExamBro check

// Set timezone ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/views');

// Autoloader
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', str_replace('App\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load helpers
require_once APP_PATH . '/helpers.php';

// ============================================
// GLOBAL EXAMBRO CHECK
// Siswa WAJIB menggunakan ExamBro untuk akses apapun.
// Admin/Guru bebas pakai browser biasa.
// ============================================
// if (isLoggedIn()) {
//     $__user = auth();
//     if (isset($__user['role']) && in_array($__user['role'], ['student', 'siswa']) && !isExamBro()) {
//         session_destroy();
//         requireExamBro();
//     }
// }

// Get URL
$url = $_GET['url'] ?? 'auth/login';
$url = filter_var($url, FILTER_SANITIZE_URL);
$parts = explode('/', $url);

$controller = ucfirst($parts[0] ?? 'Auth') . 'Controller';
$method = $parts[1] ?? null;
$params = array_slice($parts, 2);

// Handle camelCase controller names (e.g. questionGroup -> QuestionGroupController)
$controllerMap = [
    'questiongroup' => 'QuestionGroupController',
];
$controllerKey = strtolower($parts[0] ?? '');
if (isset($controllerMap[$controllerKey])) {
    $controller = $controllerMap[$controllerKey];
}

// Keep the existing exam monitor URLs while routing them to their own controller.
$controllerActionMap = [
    'exam/monitor' => ['controller' => 'MonitorController', 'method' => 'monitor'],
    'exam/monitordata' => ['controller' => 'MonitorController', 'method' => 'monitorData'],
    'exam/monitorviolations' => ['controller' => 'MonitorController', 'method' => 'monitorViolations'],
];
$actionKey = strtolower(($parts[0] ?? '') . '/' . ($method ?? ''));
if (isset($controllerActionMap[$actionKey])) {
    $controller = $controllerActionMap[$actionKey]['controller'];
    $method = $controllerActionMap[$actionKey]['method'];
}

// Default method based on controller
if ($method === null) {
    if ($controller === 'AuthController') {
        $method = 'login';
    } else {
        $method = 'index';
    }
}

$controllerFile = APP_PATH . '/Controllers/' . $controller . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = 'App\\Controllers\\' . $controller;
    $obj = new $controllerClass();
    
    if (method_exists($obj, $method)) {
        call_user_func_array([$obj, $method], $params);
    } else {
        http_response_code(404);
        echo '404 - Method Not Found';
    }
} else {
    http_response_code(404);
    echo '404 - Page Not Found';
}
