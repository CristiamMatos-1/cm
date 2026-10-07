<?php
namespace app\Models;

use DateTimeImmutable;
use PDO;

/**
 * Links seguros do portal do cliente.
 *
 * O token em texto puro (64 hex = 256 bits) é devolvido UMA vez por issue() e nunca é gravado:
 * o banco guarda apenas o SHA-256. Emitir um novo link revoga os anteriores da mesma OS.
 */
class ServiceOrderAccessTokenModel extends Model
{
    /**
     * Gera um novo link para a OS e revoga os anteriores.
     *
     * @param  int      $serviceOrderId OS
     * @param  int|null $createdBy      usuário da equipe que gerou
     * @param  int      $validDays      validade em dias (1 a 365)
     * @return string   token em texto puro; monte a URL com ele e não o armazene
     */
    public function issue(int $serviceOrderId, ?int $createdBy, int $validDays, ?DateTimeImmutable $now = null): string
    {
        $now = $now ?? new DateTimeImmutable();
        $validDays = max(1, min(365, $validDays));
        $token = bin2hex(random_bytes(32));

        $this->revokeAll($serviceOrderId, $now);

        $stmt = $this->db->prepare("
            INSERT INTO service_order_access_tokens (service_order_id, token_hash, expira_em, criado_por, created_at)
            VALUES (:os, :hash, :expira, :criado_por, :agora)
        ");
        $stmt->bindValue(':os', $serviceOrderId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', self::hash($token));
        $stmt->bindValue(':expira', $now->modify('+' . $validDays . ' days')->format('Y-m-d H:i:s'));
        $stmt->bindValue(':criado_por', $createdBy, $createdBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':agora', $now->format('Y-m-d H:i:s'));
        $stmt->execute();

        return $token;
    }

    /**
     * Resolve o token para o id da OS se ele for válido (formato correto, existente, não revogado, não expirado).
     *
     * @return int|null id da OS ou null (sem distinguir o motivo, de propósito)
     */
    public function resolveOrderId(string $token, ?DateTimeImmutable $now = null): ?int
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT service_order_id
            FROM service_order_access_tokens
            WHERE token_hash = :hash AND revogado_em IS NULL AND expira_em >= :agora
            LIMIT 1
        ");
        $stmt->bindValue(':hash', self::hash($token));
        $stmt->bindValue(':agora', ($now ?? new DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    /** Marca o último acesso do cliente ao link (para a equipe saber se ele abriu). */
    public function touch(string $token, ?DateTimeImmutable $now = null): void
    {
        $stmt = $this->db->prepare("UPDATE service_order_access_tokens SET ultimo_acesso_em = :agora WHERE token_hash = :hash");
        $stmt->bindValue(':agora', ($now ?? new DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->bindValue(':hash', self::hash($token));
        $stmt->execute();
    }

    /** Revoga todos os links ativos da OS. */
    public function revokeAll(int $serviceOrderId, ?DateTimeImmutable $now = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE service_order_access_tokens SET revogado_em = :agora
            WHERE service_order_id = :os AND revogado_em IS NULL
        ");
        $stmt->bindValue(':agora', ($now ?? new DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->bindValue(':os', $serviceOrderId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
