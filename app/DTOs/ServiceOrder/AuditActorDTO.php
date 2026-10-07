<?php
namespace app\DTOs\ServiceOrder;

/**
 * Quem está executando uma ação (para a trilha de auditoria).
 * Imutável. Criado nos controllers: AuditActorDTO::staff() para usuários logados,
 * AuditActorDTO::client() para o portal por token e AuditActorDTO::system() para rotinas.
 */
final class AuditActorDTO
{
    public const TIPO_EQUIPE = 'equipe';
    public const TIPO_CLIENTE = 'cliente';
    public const TIPO_SISTEMA = 'sistema';

    /** @var string */
    private $tipo;
    /** @var int|null */
    private $usuarioId;
    /** @var string */
    private $nome;
    /** @var string|null */
    private $ip;
    /** @var string|null */
    private $userAgent;

    private function __construct(string $tipo, ?int $usuarioId, string $nome, ?string $ip, ?string $userAgent)
    {
        $this->tipo = $tipo;
        $this->usuarioId = $usuarioId;
        $this->nome = self::cut($nome, 150);
        $this->ip = $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
        $this->userAgent = $userAgent !== null && $userAgent !== '' ? self::cut($userAgent, 255) : null;
    }

    public static function staff(int $userId, string $name, ?string $ip = null, ?string $userAgent = null): self
    {
        return new self(self::TIPO_EQUIPE, $userId, $name, $ip, $userAgent);
    }

    /**
     * Cliente acessando pelo link seguro (sem login). $clientName costuma ser o nome do cliente da OS.
     */
    public static function client(?int $userId, string $clientName, ?string $ip = null, ?string $userAgent = null): self
    {
        return new self(self::TIPO_CLIENTE, $userId, $clientName, $ip, $userAgent);
    }

    public static function system(string $name = 'Sistema'): self
    {
        return new self(self::TIPO_SISTEMA, null, $name, null, null);
    }

    /**
     * Monta o ator a partir da requisição e da sessão atual (uso nos controllers).
     */
    public static function fromSession(array $session, array $server): self
    {
        return self::staff(
            (int)($session['user_id'] ?? 0),
            (string)($session['user_name'] ?? 'Usuário'),
            $server['REMOTE_ADDR'] ?? null,
            $server['HTTP_USER_AGENT'] ?? null
        );
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function ip(): ?string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    private static function cut(string $text, int $max): string
    {
        return function_exists('mb_substr') ? mb_substr($text, 0, $max, 'UTF-8') : (preg_match('/^.{0,' . $max . '}/su', $text, $m) ? $m[0] : substr($text, 0, $max));
    }
}
