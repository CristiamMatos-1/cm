<?php
namespace app\Models;

use PDO;
use Throwable;

/**
 * Trilha de auditoria das ações críticas feitas no painel administrativo.
 * Alimenta o bloco "Logs Administrativos" da Visão Geral.
 *
 * A gravação nunca interrompe o fluxo principal: se a tabela admin_logs ainda
 * não existir (migração não executada), o erro apenas vai para o error_log.
 */
class AuditLogModel extends Model {

    public const NIVEL_INFO = 'info';
    public const NIVEL_AVISO = 'aviso';
    public const NIVEL_CRITICO = 'critico';

    /**
     * @param array|null $ator ['id' => int, 'nome' => string]; se omitido usa o usuário da sessão.
     */
    public static function record($acao, $descricao, $entidade = null, $entidadeId = null, $nivel = self::NIVEL_INFO, $ator = null) {
        try {
            (new self())->insert($acao, $descricao, $entidade, $entidadeId, $nivel, $ator);
        } catch (Throwable $e) {
            error_log('Falha ao gravar admin_logs: ' . $e->getMessage());
        }
    }

    private function insert($acao, $descricao, $entidade, $entidadeId, $nivel, $ator) {
        if (!in_array($nivel, [self::NIVEL_INFO, self::NIVEL_AVISO, self::NIVEL_CRITICO], true)) {
            $nivel = self::NIVEL_INFO;
        }

        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (user_id, user_name, acao, entidade, entidade_id, descricao, nivel, ip)
            VALUES (:user_id, :user_name, :acao, :entidade, :entidade_id, :descricao, :nivel, :ip)
        ");
        $userId = $ator['id'] ?? ($_SESSION['user_id'] ?? null);
        $userName = $ator['nome'] ?? ($_SESSION['user_name'] ?? 'Sistema');
        $stmt->bindValue(':user_id', $userId !== null ? (int)$userId : null, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':user_name', self::cut((string)$userName, 150));
        $stmt->bindValue(':acao', self::cut((string)$acao, 60));
        $stmt->bindValue(':entidade', $entidade !== null ? self::cut((string)$entidade, 60) : null);
        $stmt->bindValue(':entidade_id', $entidadeId !== null ? (int)$entidadeId : null, $entidadeId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':descricao', self::cut((string)$descricao, 255));
        $stmt->bindValue(':nivel', $nivel);
        $stmt->bindValue(':ip', self::cut((string)($_SERVER['REMOTE_ADDR'] ?? ''), 45));
        $stmt->execute();
    }

    /**
     * Corta em N caracteres sem depender da extensão mbstring (comum faltar em hospedagem compartilhada).
     */
    private static function cut($text, $max) {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max);
        }
        return preg_match('/^.{0,' . (int)$max . '}/su', $text, $m) ? $m[0] : substr($text, 0, $max);
    }
}
