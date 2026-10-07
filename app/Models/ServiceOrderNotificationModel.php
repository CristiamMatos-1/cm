<?php
namespace app\Models;

use DateTimeImmutable;
use PDO;

/**
 * Notificações internas da OS para a equipe (admins + técnico + engenheiro responsáveis).
 * O envio por e-mail pode ser acoplado depois sem mudar este contrato.
 */
class ServiceOrderNotificationModel extends Model
{
    /**
     * Destinatários da equipe de uma OS: todos os administradores + técnico e engenheiro atribuídos.
     *
     * @return int[] ids de usuários, sem repetição
     */
    public function staffRecipients(?int $technicianId, ?int $engineerId): array
    {
        $ids = array_map('intval', $this->db->query("SELECT id FROM users WHERE perfil = 'admin'")->fetchAll(PDO::FETCH_COLUMN));
        foreach ([$technicianId, $engineerId] as $id) {
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Cria uma notificação para cada destinatário.
     *
     * @param int[]  $recipientIds ids de users
     * @param string $type         ex.: cliente_aprovou, cliente_rejeitou
     * @param string $message      texto curto exibido na lista de notificações (até 255 caracteres)
     */
    public function notify(int $serviceOrderId, array $recipientIds, string $type, string $message, ?DateTimeImmutable $at = null): void
    {
        if ($recipientIds === []) {
            return;
        }
        $stmt = $this->db->prepare("
            INSERT INTO service_order_notifications (service_order_id, destinatario_id, tipo, mensagem, created_at)
            VALUES (:os, :dest, :tipo, :msg, :quando)
        ");
        $quando = ($at ?? new DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach (array_unique($recipientIds) as $recipientId) {
            $stmt->bindValue(':os', $serviceOrderId, PDO::PARAM_INT);
            $stmt->bindValue(':dest', (int)$recipientId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $type);
            $stmt->bindValue(':msg', function_exists('mb_substr') ? mb_substr($message, 0, 255, 'UTF-8') : substr($message, 0, 255));
            $stmt->bindValue(':quando', $quando);
            $stmt->execute();
        }
    }
}
