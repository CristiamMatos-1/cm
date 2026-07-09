<?php
namespace app\Helpers;

class Security {
    
    /**
     * Gera um token CSRF e salva na sessão.
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida o token CSRF enviado via POST.
     */
    public static function validateCsrfToken($token) {
        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }

    /**
     * Previne ataques XSS sanitizando a saída HTML.
     */
    public static function esc($string) {
        if ($string === null) return '';
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitiza inputs recebidos (Remove tags indesejadas)
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
        } else {
            $input = trim(strip_tags($input));
        }
        return $input;
    }
}
