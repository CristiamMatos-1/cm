<?php
namespace app\Domain\ServiceOrder;

use app\Domain\ServiceOrder\Exception\AlreadyDecidedException;
use app\Domain\ServiceOrder\Exception\InvalidStatusTransitionException;
use app\Domain\ServiceOrder\Exception\ServiceOrderException;
use app\Domain\ServiceOrder\Exception\ServiceOrderLockedException;
use app\Domain\ServiceOrder\Exception\ValidationException;
use app\DTOs\ServiceOrder\ConsultingReportDTO;
use app\DTOs\ServiceOrder\MaintenanceQuoteDTO;
use app\DTOs\ServiceOrder\OpenServiceOrderDTO;
use DateTimeImmutable;

/**
 * Aggregate root da Ordem de Serviço / Projeto.
 *
 * Concentra TODAS as regras de negócio do workflow (sem acesso a banco, sessão ou HTTP):
 *  - qual módulo de detalhes cada tipo usa;
 *  - quando diagnóstico/valores podem ser editados;
 *  - transições de status válidas e pré-requisitos para enviar ao cliente;
 *  - decisão do cliente (aprovar/rejeitar) uma única vez por proposta.
 *
 * A persistência é feita por app\Models\ServiceOrderModel, que reconstrói o objeto com
 * reconstitute() e grava o estado após cada operação.
 */
final class ServiceOrder
{
    /** @var int|null */
    private $id;
    /** @var string|null */
    private $code;
    /** @var int */
    private $clientId;
    /** @var int|null */
    private $technicianId;
    /** @var int|null */
    private $engineerId;
    /** @var int|null */
    private $ticketId;
    /** @var string */
    private $type;
    /** @var string */
    private $status;
    /** @var string */
    private $title;
    /** @var DateTimeImmutable */
    private $openedAt;
    /** @var DateTimeImmutable|null */
    private $updatedAt;
    /** @var DateTimeImmutable|null */
    private $completedAt;
    /** @var DateTimeImmutable|null */
    private $decidedAt;
    /** @var string|null */
    private $rejectionReason;
    /** @var MaintenanceDetails|null */
    private $maintenance;
    /** @var ConsultingDetails|null */
    private $consulting;

    private function __construct()
    {
    }

    /**
     * Cria uma nova OS em "rascunho" a partir do formulário de abertura.
     *
     * @param  OpenServiceOrderDTO $dto dados já validados
     * @return self OS sem id/código (atribuídos na persistência)
     * @throws ValidationException se o DTO não trouxer os campos exigidos pelo tipo
     */
    public static function open(OpenServiceOrderDTO $dto, ?DateTimeImmutable $now = null): self
    {
        $order = new self();
        $order->clientId = $dto->clienteId();
        $order->technicianId = $dto->tecnicoId();
        $order->engineerId = $dto->engenheiroId();
        $order->ticketId = $dto->ticketId();
        $order->type = $dto->tipoServico();
        $order->title = $dto->titulo();
        $order->status = ServiceOrderStatus::RASCUNHO;
        $order->openedAt = $now ?? new DateTimeImmutable();

        if (ServiceOrderType::usesMaintenanceModule($order->type)) {
            $order->maintenance = new MaintenanceDetails(
                (string)$dto->equipamentoTipo(),
                $dto->equipamentoDescricao(),
                $dto->numeroSerie(),
                (string)$dto->relatoDefeitoCliente()
            );
        } else {
            $order->consulting = new ConsultingDetails((string)$dto->problemaApresentado());
        }

        return $order;
    }

    /**
     * Reconstrói a OS a partir do estado persistido (uso exclusivo do repositório).
     * Não valida transições: o banco é a fonte da verdade do estado atual.
     *
     * @param array<string, mixed> $state chaves: id, code, client_id, technician_id, engineer_id, ticket_id,
     *        type, status, title, opened_at, updated_at, completed_at, decided_at, rejection_reason,
     *        maintenance (MaintenanceDetails|null), consulting (ConsultingDetails|null)
     */
    public static function reconstitute(array $state): self
    {
        $order = new self();
        $order->id = (int)$state['id'];
        $order->code = $state['code'] ?? null;
        $order->clientId = (int)$state['client_id'];
        $order->technicianId = $state['technician_id'] !== null ? (int)$state['technician_id'] : null;
        $order->engineerId = $state['engineer_id'] !== null ? (int)$state['engineer_id'] : null;
        $order->ticketId = $state['ticket_id'] !== null ? (int)$state['ticket_id'] : null;
        $order->type = $state['type'];
        $order->status = $state['status'];
        $order->title = $state['title'];
        $order->openedAt = $state['opened_at'];
        $order->updatedAt = $state['updated_at'] ?? null;
        $order->completedAt = $state['completed_at'] ?? null;
        $order->decidedAt = $state['decided_at'] ?? null;
        $order->rejectionReason = $state['rejection_reason'] ?? null;
        $order->maintenance = $state['maintenance'] ?? null;
        $order->consulting = $state['consulting'] ?? null;
        return $order;
    }

    // ------------------------------------------------------------------
    // Comandos (alteram estado)
    // ------------------------------------------------------------------

    /**
     * Registra diagnóstico técnico e orçamento discriminado (manutenção).
     *
     * @throws ServiceOrderException      se a OS não for do tipo manutenção
     * @throws ServiceOrderLockedException se a OS já foi enviada ao cliente
     */
    public function applyMaintenanceQuote(MaintenanceQuoteDTO $quote): void
    {
        if ($this->maintenance === null) {
            throw new ServiceOrderException('Esta OS não é de manutenção de hardware.');
        }
        $this->assertEditable();
        $this->maintenance = $this->maintenance->withQuote($quote);
    }

    /**
     * Registra análise, solução, relatórios e valor (consultoria/projeto/infraestrutura).
     *
     * @throws ServiceOrderException      se a OS não for de consultoria/projeto/redes
     * @throws ServiceOrderLockedException se a OS já foi enviada ao cliente
     */
    public function applyConsultingReport(ConsultingReportDTO $report): void
    {
        if ($this->consulting === null) {
            throw new ServiceOrderException('Esta OS não é de consultoria, projeto ou infraestrutura.');
        }
        $this->assertEditable();
        $this->consulting = $this->consulting->withReport($report);
    }

    /**
     * Muda o status seguindo o workflow. Para "aguardando_aprovacao_cliente" exige que diagnóstico,
     * relatórios e valores estejam completos. Decisões do cliente usam approveByClient/rejectByClient.
     *
     * @param  string $to novo status (constante de ServiceOrderStatus)
     * @throws InvalidStatusTransitionException transição fora do workflow
     * @throws ValidationException              OS incompleta para enviar ao cliente
     */
    public function changeStatus(string $to, ?DateTimeImmutable $now = null): void
    {
        $now = $now ?? new DateTimeImmutable();

        if ($to === ServiceOrderStatus::APROVADO || $to === ServiceOrderStatus::REJEITADO) {
            throw new ServiceOrderException('A aprovação ou rejeição é registrada pelo cliente no portal.');
        }
        $this->assertTransition($to);

        if ($to === ServiceOrderStatus::AGUARDANDO_APROVACAO_CLIENTE) {
            $this->assertReadyForClient();
            $this->decidedAt = null;
            $this->rejectionReason = null;
        }
        if ($to === ServiceOrderStatus::CONCLUIDO) {
            $this->completedAt = $now;
        }

        $this->status = $to;
    }

    /**
     * Define técnico e engenheiro responsáveis (null remove a atribuição).
     *
     * @throws ServiceOrderException se a OS já foi concluída
     */
    public function assignStaff(?int $technicianId, ?int $engineerId): void
    {
        if (ServiceOrderStatus::isFinal($this->status)) {
            throw new ServiceOrderException('OS concluída não permite alterar os responsáveis.');
        }
        $this->technicianId = $technicianId;
        $this->engineerId = $engineerId;
    }

    /**
     * O cliente aprova a proposta.
     *
     * @throws AlreadyDecidedException            se a proposta já foi respondida
     * @throws InvalidStatusTransitionException   se a OS não está aguardando aprovação
     */
    public function approveByClient(?DateTimeImmutable $now = null): void
    {
        $this->assertAwaitingClientDecision();
        $this->status = ServiceOrderStatus::APROVADO;
        $this->decidedAt = $now ?? new DateTimeImmutable();
        $this->rejectionReason = null;
    }

    /**
     * O cliente rejeita a proposta (motivo opcional, recomendado).
     *
     * @throws AlreadyDecidedException            se a proposta já foi respondida
     * @throws InvalidStatusTransitionException   se a OS não está aguardando aprovação
     */
    public function rejectByClient(?string $reason, ?DateTimeImmutable $now = null): void
    {
        $this->assertAwaitingClientDecision();
        $this->status = ServiceOrderStatus::REJEITADO;
        $this->decidedAt = $now ?? new DateTimeImmutable();
        $this->rejectionReason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    /** Valor total da proposta (soma automática para manutenção; valor da consultoria nos demais tipos). */
    public function total(): Money
    {
        if ($this->maintenance !== null) {
            return $this->maintenance->total();
        }
        return $this->consulting !== null ? $this->consulting->total() : Money::zero();
    }

    public function isEditable(): bool
    {
        return ServiceOrderStatus::isEditable($this->status);
    }

    public function isAwaitingClientDecision(): bool
    {
        return $this->status === ServiceOrderStatus::AGUARDANDO_APROVACAO_CLIENTE;
    }

    /**
     * @return array<string, string> pendências (campo => mensagem) que impedem o envio ao cliente
     */
    public function pendingForClientApproval(): array
    {
        $errors = [];
        if ($this->maintenance !== null) {
            $errors = $this->maintenance->missingForClientApproval();
        } elseif ($this->consulting !== null) {
            $errors = $this->consulting->missingForClientApproval();
            if ($this->engineerId === null) {
                $errors['engenheiro_id'] = 'Atribua um engenheiro responsável antes de enviar ao cliente.';
            }
        }
        return $errors;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function clientId(): int
    {
        return $this->clientId;
    }

    public function technicianId(): ?int
    {
        return $this->technicianId;
    }

    public function engineerId(): ?int
    {
        return $this->engineerId;
    }

    public function ticketId(): ?int
    {
        return $this->ticketId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function openedAt(): DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function decidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function maintenance(): ?MaintenanceDetails
    {
        return $this->maintenance;
    }

    public function consulting(): ?ConsultingDetails
    {
        return $this->consulting;
    }

    // ------------------------------------------------------------------
    // Invariantes
    // ------------------------------------------------------------------

    private function assertEditable(): void
    {
        if (!$this->isEditable()) {
            throw new ServiceOrderLockedException(
                'Esta OS está em "' . ServiceOrderStatus::label($this->status) . '" e não pode mais ter diagnóstico ou valores alterados. '
                . 'Devolva-a para "Aguardando Diagnóstico" para revisar a proposta.'
            );
        }
    }

    private function assertTransition(string $to): void
    {
        if (!ServiceOrderStatus::isValid($to) || !ServiceOrderStatus::canTransition($this->status, $to)) {
            throw new InvalidStatusTransitionException($this->status, $to);
        }
    }

    private function assertReadyForClient(): void
    {
        $pending = $this->pendingForClientApproval();
        if ($pending !== []) {
            throw new ValidationException($pending);
        }
    }

    private function assertAwaitingClientDecision(): void
    {
        if ($this->isAwaitingClientDecision()) {
            return;
        }
        if (in_array($this->status, [ServiceOrderStatus::APROVADO, ServiceOrderStatus::REJEITADO, ServiceOrderStatus::EM_EXECUCAO, ServiceOrderStatus::CONCLUIDO], true)) {
            $when = $this->decidedAt !== null ? ' em ' . $this->decidedAt->format('d/m/Y \à\s H:i') : '';
            throw new AlreadyDecidedException('Esta proposta já foi respondida' . $when . ' e a decisão não pode ser alterada.');
        }
        throw new ServiceOrderException('Esta proposta ainda não foi enviada para aprovação.');
    }
}
