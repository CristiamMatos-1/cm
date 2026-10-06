<?php
/**
 * Ponto de entrada da aplicação (Front Controller)
 */

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// 1. Configurações de Segurança de Sessão (Mitigação de Session Hijacking e Fixation)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if ($isHttps) {
    ini_set('session.cookie_secure', 1);
}

session_name('ITSM_SESSION');
session_start();

// 2. Configurações Globais
define('BASE_URL', '/cm');
define('APP_PATH', __DIR__ . '/app');

// Erros só são exibidos com APP_DEBUG=1 no ambiente; em produção vão para o log.
$appDebug = getenv('APP_DEBUG') === '1';
define('APP_DEBUG', $appDebug);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 3. Autoload simples para as classes MVC
spl_autoload_register(function ($class) {
    if (!preg_match('/^app(\\\\[A-Za-z0-9_]+)+$/', $class)) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// 4. Inclusão de Helpers
require_once APP_PATH . '/Helpers/Security.php';

function renderHttpError(int $code, string $message): void
{
    http_response_code($code);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos($accept, 'application/json') !== false;

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        return;
    }

    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(BASE_URL . '/', ENT_QUOTES, 'UTF-8');
    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'>"
        . "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
        . "<title>{$code} - ITSM</title>"
        . "<style>body{font-family:system-ui,sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}"
        . ".c{background:#fff;padding:2rem 2.5rem;border-radius:.75rem;box-shadow:0 4px 12px rgba(0,0,0,.08);text-align:center;max-width:26rem}"
        . "h1{color:#1e3a8a;margin:0 0 .5rem;font-size:3rem}p{color:#4b5563}a{color:#2563eb}</style></head>"
        . "<body><div class='c'><h1>{$code}</h1><p>{$safe}</p><a href='{$home}'>Voltar ao início</a></div></body></html>";
}

// 5. Roteador Básico
$url = isset($_GET['url']) ? trim((string)$_GET['url'], '/') : '';
$urlParts = $url === '' ? [] : explode('/', $url);

$controllerSlug = $urlParts[0] ?? '';
$actionName = $urlParts[1] ?? 'index';

if (($controllerSlug !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $controllerSlug))
    || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $actionName)) {
    renderHttpError(404, 'Página não encontrada.');
    exit;
}

$controllerName = $controllerSlug !== '' ? ucfirst($controllerSlug) . 'Controller' : 'AuthController';
$controllerClass = "app\\Controllers\\" . $controllerName;

try {
    if (!class_exists($controllerClass) || (new ReflectionClass($controllerClass))->isAbstract()) {
        renderHttpError(404, 'Página não encontrada.');
        exit;
    }

    $controller = new $controllerClass();

    $isPublicAction = false;
    if (method_exists($controller, $actionName) && $actionName[0] !== '_') {
        $method = new ReflectionMethod($controller, $actionName);
        $isPublicAction = $method->isPublic() && !$method->isStatic() && !$method->isConstructor();
    }

    if (!$isPublicAction) {
        renderHttpError(404, 'Página não encontrada.');
        exit;
    }

    $params = array_slice($urlParts, 2);
    $required = $method->getNumberOfRequiredParameters();
    if (count($params) < $required || count($params) > $method->getNumberOfParameters()) {
        renderHttpError(404, 'Página não encontrada.');
        exit;
    }

    $method->invokeArgs($controller, array_map('urldecode', $params));
} catch (Throwable $e) {
    error_log('Erro não tratado: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    $message = APP_DEBUG ? $e->getMessage() : 'Ocorreu um erro inesperado. Tente novamente em instantes.';
    renderHttpError(500, $message);
}
