<?php
namespace app\DTOs\ServiceOrder;

use app\Domain\ServiceOrder\EquipmentType;
use app\Domain\ServiceOrder\ServiceOrderType;

/**
 * Dados de abertura de uma OS/Projeto (formulário "Nova OS").
 *
 * Campos de intake dependem do tipo:
 *  - manutenção:           equipamento_tipo, relato_defeito_cliente (+ modelo e série opcionais)
 *  - projeto/consultoria/redes: problema_apresentado
 */
final class OpenServiceOrderDTO
{
    /** @var int */
    private $clienteId;
    /** @var string */
    private $tipoServico;
    /** @var string */
    private $titulo;
    /** @var int|null */
    private $tecnicoId;
    /** @var int|null */
    private $engenheiroId;
    /** @var int|null */
    private $ticketId;
    /** @var string|null */
    private $equipamentoTipo;
    /** @var string|null */
    private $equipamentoDescricao;
    /** @var string|null */
    private $numeroSerie;
    /** @var string|null */
    private $relatoDefeitoCliente;
    /** @var string|null */
    private $problemaApresentado;

    private function __construct()
    {
    }

    /**
     * Valida e normaliza os dados do formulário de abertura.
     *
     * @param  array<string, mixed> $input chaves: cliente_id, tipo_servico, titulo, tecnico_id, engenheiro_id,
     *                                     ticket_id, equipamento_tipo, equipamento_descricao, numero_serie,
     *                                     relato_defeito_cliente, problema_apresentado
     * @return self
     * @throws \app\Domain\ServiceOrder\Exception\ValidationException com todos os campos inválidos
     */
    public static function fromArray(array $input): self
    {
        $in = new Input($input);
        $dto = new self();

        $dto->clienteId = $in->id('cliente_id', 'o cliente');
        $dto->tipoServico = $in->enum('tipo_servico', 'o tipo de serviço', [ServiceOrderType::class, 'isValid']);
        $dto->titulo = $in->line('titulo', 'o título', 150, true);
        $dto->tecnicoId = $in->id('tecnico_id', 'o técnico', false);
        $dto->engenheiroId = $in->id('engenheiro_id', 'o engenheiro', false);
        $dto->ticketId = $in->id('ticket_id', 'o chamado', false);

        if ($dto->tipoServico !== null && ServiceOrderType::usesMaintenanceModule($dto->tipoServico)) {
            $dto->equipamentoTipo = $in->enum('equipamento_tipo', 'o tipo de equipamento', [EquipmentType::class, 'isValid']);
            $dto->equipamentoDescricao = $in->line('equipamento_descricao', 'o modelo do equipamento', 150, false);
            $dto->numeroSerie = $in->line('numero_serie', 'o número de série', 100, false);
            $dto->relatoDefeitoCliente = $in->longText('relato_defeito_cliente', 'o relato do defeito', 10000, true);
        } elseif ($dto->tipoServico !== null) {
            $dto->problemaApresentado = $in->longText('problema_apresentado', 'o problema apresentado / escopo inicial', 20000, true);
        }

        $in->assertValid();
        return $dto;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function tipoServico(): string
    {
        return $this->tipoServico;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function tecnicoId(): ?int
    {
        return $this->tecnicoId;
    }

    public function engenheiroId(): ?int
    {
        return $this->engenheiroId;
    }

    public function ticketId(): ?int
    {
        return $this->ticketId;
    }

    public function equipamentoTipo(): ?string
    {
        return $this->equipamentoTipo;
    }

    public function equipamentoDescricao(): ?string
    {
        return $this->equipamentoDescricao;
    }

    public function numeroSerie(): ?string
    {
        return $this->numeroSerie;
    }

    public function relatoDefeitoCliente(): ?string
    {
        return $this->relatoDefeitoCliente;
    }

    public function problemaApresentado(): ?string
    {
        return $this->problemaApresentado;
    }
}
