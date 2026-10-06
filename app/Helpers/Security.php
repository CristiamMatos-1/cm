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
        if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Previne ataques XSS sanitizando a saída HTML.
     */
    public static function esc($string) {
        if ($string === null) return '';
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
            $input = trim(strip_tags((string)$input));
        }
        return $input;
    }

    /**
     * Converte um valor monetário digitado (ex: "1.234,56" ou "12,5") em float.
     */
    public static function parseMoney($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return 0.0;
        }
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        return is_numeric($value) ? round((float)$value, 2) : 0.0;
    }
}
