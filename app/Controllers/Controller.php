<?php
namespace app\Controllers;

use app\Helpers\Security;

abstract class Controller {
    /**
     * Renderiza uma view passando dados para ela
     */
    protected function view($viewPath, $data = []) {
        extract($data);

        $file = APP_PATH . '/Views/' . $viewPath . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            http_response_code(500);
            error_log("View não encontrada: " . $viewPath);
            die("Tela indisponível no momento.");
        }
    }

    /**
     * Indica se a requisição foi feita via fetch/AJAX e espera JSON.
     */
    protected function isAjax() {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }
        return strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    protected function json(array $payload, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Registra uma mensagem para ser exibida como toast na próxima tela.
     */
    protected function flash($type, $message) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Redireciona para uma URL específica usando BASE_URL.
     * Em requisições AJAX responde JSON (ex.: sessão expirada) em vez de HTML.
     */
    protected function redirect($url) {
        $target = BASE_URL . $url;

        if ($this->isAjax()) {
            $this->json([
                'success' => false,
                'message' => 'Sua sessão expirou ou você não tem acesso a esta área. Faça login novamente.',
                'redirect' => $target
            ], 401);
        }

        header("Location: " . $target);
        exit;
    }

    /**
     * Redireciona para um endereço externo (ex.: WhatsApp), permitindo apenas http(s).
     */
    protected function redirectExternal($url) {
        if (!preg_match('#^https?://#i', $url)) {
            $this->redirect('/');
        }
        header("Location: " . $url);
        exit;
    }

    /**
     * Valida se a requisição é POST e se o CSRF Token é válido
     */
    protected function requirePost() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Método não permitido.'], 405);
            }
            http_response_code(405);
            header('Allow: POST');
            die("Método não permitido.");
        }

        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Security::validateCsrfToken($token)) {
            $message = "Token de segurança inválido. Por favor, recarregue a página e tente novamente.";
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $message], 419);
            }
            http_response_code(419);
            die($message);
        }
    }

    /**
     * Responde (JSON) o resultado de OrcamentoModel::decide().
     */
    protected function respondBudgetDecision(array $resultado) {
        $status = 200;
        if (!$resultado['ok']) {
            $map = ['not_found' => 404, 'already_decided' => 409, 'expired' => 409, 'invalid' => 422];
            $status = $map[$resultado['code']] ?? 500;
        }

        $budget = $resultado['budget'] ?? null;
        $this->json([
            'success' => $resultado['ok'],
            'code' => $resultado['code'],
            'message' => $resultado['message'],
            'budget' => $budget ? [
                'id' => (int)$budget['id'],
                'status' => $budget['status'],
                'status_label' => \app\Helpers\UI::budgetStatusLabel($budget['status']),
                'badge_html' => \app\Helpers\UI::budgetStatusBadge($budget['status']),
                'decision_text' => \app\Helpers\UI::budgetDecisionText($budget),
                'motivo_rejeicao' => $budget['motivo_rejeicao'] ?? null
            ] : null
        ], $status);
    }

    protected function flashBudgetDecision(array $resultado) {
        $type = $resultado['ok'] ? 'success' : ($resultado['code'] === 'error' ? 'error' : 'warning');
        $this->flash($type, $resultado['message']);
    }
}
