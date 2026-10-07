<?php
namespace app\DTOs\ServiceOrder;

/**
 * Resposta do cliente no portal: aprovar ou rejeitar a proposta.
 */
final class ClientDecisionDTO
{
    public const APROVAR = 'aprovar';
    public const REJEITAR = 'rejeitar';

    /** @var string */
    private $decisao;
    /** @var string|null */
    private $motivo;

    private function __construct()
    {
    }

    /**
     * @param  array<string, mixed> $input chaves: decisao ("aprovar"|"rejeitar"), motivo (opcional, até 1000 caracteres)
     * @throws \app\Domain\ServiceOrder\Exception\ValidationException
     */
    public static function fromArray(array $input): self
    {
        $in = new Input($input);
        $dto = new self();

        $dto->decisao = $in->enum('decisao', 'a decisão', function (string $v): bool {
            return $v === self::APROVAR || $v === self::REJEITAR;
        });
        $dto->motivo = $in->longText('motivo', 'o motivo', 1000, false);

        $in->assertValid();
        return $dto;
    }

    public function decisao(): string
    {
        return $this->decisao;
    }

    public function isApproval(): bool
    {
        return $this->decisao === self::APROVAR;
    }

    /** Motivo informado pelo cliente (só faz sentido na rejeição). */
    public function motivo(): ?string
    {
        return $this->motivo;
    }
}
