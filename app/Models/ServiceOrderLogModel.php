<?php
namespace app\Models;

use app\DTOs\ServiceOrder\AuditActorDTO;
use DateTimeImmutable;
use PDO;

/**
 * Trilha de auditoria da OS (tabela append-only service_order_logs).
 * Só expõe INSERT e leitura: logs nunca são alterados nem apagados pela aplicação.
 */
class ServiceOrderLogModel extends Model
{
    /**
     * Registra um evento da OS com data/hora, ator, IP e user-agent.
     *
     * @param int                    $serviceOrderId OS afetada
     * @param AuditActorDTO          $actor          quem executou (equipe, cliente via portal ou sistema)
     * @param string                 $action         código curto: os_criada, status_alterado, cliente_aprovou, cliente_rejeitou, ...
     * @param string|null            $from           status anterior
     * @param string|null            $to             novo status
     * @param string|null            $description    texto livre (até 500 caracteres)
     * @param DateTimeImmutable|null $at             instante do evento (padrão: agora); permite usar o mesmo relógio da operação
     */
    public function add(
        int $serviceOrderId,
        AuditActorDTO $actor,
        string $action,
        ?string $from = null,
        ?string $to = null,
        ?string $description = null,
        ?DateTimeImmutable $at = null
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO service_order_logs
                (service_order_id, ator_tipo, usuario_id, ator_nome, acao, status_anterior, status_novo, descricao, ip, user_agent, created_at)
            VALUES
                (:os, :tipo, :usuario, :nome, :acao, :de, :para, :descricao, :ip, :ua, :quando)
        ");
        $stmt->bindValue(':os', $serviceOrderId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $actor->tipo());
        $stmt->bindValue(':usuario', $actor->usuarioId(), $actor->usuarioId() === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':nome', $actor->nome());
        $stmt->bindValue(':acao', $action);
        $stmt->bindValue(':de', $from);
        $stmt->bindValue(':para', $to);
        $stmt->bindValue(':descricao', $description !== null ? self::cut($description, 500) : null);
        $stmt->bindValue(':ip', $actor->ip());
        $stmt->bindValue(':ua', $actor->userAgent());
        $stmt->bindValue(':quando', ($at ?? new DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->execute();
    }

    /**
     * Histórico da OS, do mais antigo para o mais recente.
     *
     * @return array<int, array<string, mixed>> linhas de service_order_logs
     */
    public function listByOrder(int $serviceOrderId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, ator_tipo, usuario_id, ator_nome, acao, status_anterior, status_novo, descricao, ip, created_at
            FROM service_order_logs
            WHERE service_order_id = :os
            ORDER BY id ASC
        ");
        $stmt->bindValue(':os', $serviceOrderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function cut(string $text, int $max): string
    {
        return function_exists('mb_substr') ? mb_substr($text, 0, $max, 'UTF-8') : (preg_match('/^.{0,' . $max . '}/su', $text, $m) ? $m[0] : substr($text, 0, $max));
    }
}
