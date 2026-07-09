<?php
namespace app\Controllers;

use app\Helpers\Security;

abstract class Controller {
    /**
     * Renderiza uma view passando dados para ela
     */
    protected function view($viewPath, $data = []) {
        // Extrai as variáveis para serem usadas na view
        extract($data);

        $file = APP_PATH . '/Views/' . $viewPath . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            die("View não encontrada: " . $viewPath);
        }
    }

    /**
     * Redireciona para uma URL específica usando BASE_URL
     */
    protected function redirect($url) {
        header("Location: " . BASE_URL . $url);
        exit;
    }

    /**
     * Valida se a requisição é POST e se o CSRF Token é válido
     */
    protected function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Método não permitido.");
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($token)) {
            die("Token de segurança inválido. Por favor, recarregue a página e tente novamente.");
        }
    }
}
