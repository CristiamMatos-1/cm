<?php
namespace app\Helpers;

use app\Config\Database;
use Throwable;

/**
 * Trilha de auditoria para operações sensíveis (LGPD, art. 37 e 46).
 * Nunca interrompe o fluxo principal: falhas são apenas registradas no log do servidor.
 */
class Audit {

    /**
     * Endereço IP da conexão. X-Forwarded-For é ignorado de propósito (pode ser forjado).
     */
    public static function clientIp() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    public static function userAgent() {
        $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return null;
        }
        return function_exists('mb_substr') ? mb_substr($ua, 0, 255, 'UTF-8') : substr($ua, 0, 255);
    }

    /**
     * @param string     $acao       ex.: lgpd_exportacao, lgpd_anonimizacao, usuario_excluido
     * @param string     $entidade   ex.: user, budget, lgpd_request
     * @param int|null   $entidadeId
     * @param array|null $detalhes   dados mínimos necessários (evite incluir dados pessoais)
     */
    public static function log($acao, $entidade, $entidadeId = null, $detalhes = null) {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_log (usuario_id, acao, entidade, entidade_id, detalhes, ip_address)
                VALUES (:usuario_id, :acao, :entidade, :entidade_id, :detalhes, :ip)
            ");
            $stmt->bindValue(':usuario_id', isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null, \PDO::PARAM_INT);
            $stmt->bindValue(':acao', (string)$acao);
            $stmt->bindValue(':entidade', (string)$entidade);
            $stmt->bindValue(':entidade_id', $entidadeId !== null ? (int)$entidadeId : null, \PDO::PARAM_INT);
            $stmt->bindValue(':detalhes', $detalhes === null ? null : json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $stmt->bindValue(':ip', self::clientIp());
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Falha ao gravar auditoria (' . $acao . '): ' . $e->getMessage());
        }
    }
}
