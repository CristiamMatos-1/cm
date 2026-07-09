<?php
/**
 * Ponto de entrada da aplicação (Front Controller)
 */

// 1. Configurações de Segurança de Sessão (Mitigação de Session Hijacking e Fixation)
ini_set('session.cookie_httponly', 1); // Impede acesso via JS (XSS)
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax'); // Proteção CSRF básica
$sessionCookieSecure = getenv('SESSION_COOKIE_SECURE');
if ($sessionCookieSecure === false) {
    $sessionCookieSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}
ini_set('session.cookie_secure', filter_var($sessionCookieSecure, FILTER_VALIDATE_BOOL) ? 1 : 0);

session_name('ITSM_SESSION');
session_start();

// 2. Configurações Globais
define('BASE_URL', getenv('BASE_URL') ?: '/cm'); // Caminho base configurado para o domínio
define('APP_PATH', __DIR__ . '/app');

// Controle de debug via variável de ambiente (produção deve ser false)
$appDebug = filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL);
ini_set('display_errors', $appDebug ? 1 : 0);
ini_set('display_startup_errors', $appDebug ? 1 : 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 3. Autoload simples para as classes MVC
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', '/', $class);
    $file = dirname(__FILE__) . '/' . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 4. Inclusão de Helpers
require_once APP_PATH . '/Helpers/Security.php';

// 5. Roteador Básico
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// Define Controller e Action padrão
$controllerName = !empty($urlParts[0]) ? ucfirst($urlParts[0]) . 'Controller' : 'AuthController';
$actionName = isset($urlParts[1]) ? $urlParts[1] : 'index';

// Instancia e executa
$controllerClass = "app\\Controllers\\" . $controllerName;

if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    if (method_exists($controller, $actionName)) {
        // Passa os parâmetros restantes, se houver
        $params = array_slice($urlParts, 2);
        call_user_func_array([$controller, $actionName], $params);
    } else {
        http_response_code(404);
        echo "404 - Ação não encontrada";
    }
} else {
    http_response_code(404);
    echo "404 - Controlador não encontrado";
}
