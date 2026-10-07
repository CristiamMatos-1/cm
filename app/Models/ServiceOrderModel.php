<?php
namespace app\Models;

use app\Domain\ServiceOrder\ConsultingDetails;
use app\Domain\ServiceOrder\Exception\InvalidAccessTokenException;
use app\Domain\ServiceOrder\Exception\ServiceOrderException;
use app\Domain\ServiceOrder\Exception\ServiceOrderNotFoundException;
use app\Domain\ServiceOrder\Exception\ValidationException;
use app\Domain\ServiceOrder\MaintenanceDetails;
use app\Domain\ServiceOrder\Money;
use app\Domain\ServiceOrder\ServiceOrder;
use app\Domain\ServiceOrder\ServiceOrderStatus;
use app\Domain\ServiceOrder\ServiceOrderType;
use app\DTOs\ServiceOrder\AuditActorDTO;
use app\DTOs\ServiceOrder\ClientDecisionDTO;
use app\DTOs\ServiceOrder\ConsultingReportDTO;
use app\DTOs\ServiceOrder\MaintenanceQuoteDTO;
use app\DTOs\ServiceOrder\OpenServiceOrderDTO;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Repositório / caso de uso da Ordem de Serviço.
 *
 * Responsabilidades:
 *  - persistir e carregar o aggregate {@see ServiceOrder} (service_orders + módulo de manutenção/consultoria);
 *  - executar cada comando de forma ATÔMICA: transação + lock da linha (SELECT ... FOR UPDATE),
 *    aplicação da regra no domínio, gravação e registro em service_order_logs;
 *  - emitir/validar o link seguro do cliente e registrar a decisão dele com notificação à equipe.
 *
 * Regras de negócio ficam no domínio (ServiceOrder); aqui só orquestração e SQL.
 * Erros de regra são lançados como {@see ServiceOrderException} (e subclasses); falhas de banco
 * (PDOException) sobem sem tradução e o front controller as registra no error_log.
 */
class ServiceOrderModel extends Model
{
    /** @var ServiceOrderLogModel */
    private $logs;
    /** @var ServiceOrderAccessTokenModel */
    private $tokens;
    /** @var ServiceOrderNotificationModel */
    private $notifications;

    /**
     * @param PDO|null $db conexão existente (testes ou transações maiores); omitida = nova conexão
     */
    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        // Compartilham a MESMA conexão para participar da mesma transação.
        $this->logs = new ServiceOrderLogModel($this->db);
        $this->tokens = new ServiceOrderAccessTokenModel($this->db);
        $this->notifications = new ServiceOrderNotificationModel($this->db);
    }

    // ==================================================================
    // Comandos
    // ==================================================================

    /**
     * Abre uma OS em "rascunho", gera o código (OS-AAAA-000123) e registra o log "os_criada".
     *
     * @param  OpenServiceOrderDTO $dto   dados validados do formulário de abertura
     * @param  AuditActorDTO       $actor quem está abrindo
     * @return ServiceOrder        OS persistida (com id e código)
     * @throws ValidationException se cliente/técnico/engenheiro/chamado não existirem
     */
    public function create(OpenServiceOrderDTO $dto, AuditActorDTO $actor): ServiceOrder
    {
        $this->assertReferences($dto->clienteId(), $dto->tecnicoId(), $dto->engenheiroId(), $dto->ticketId());

        $now = new DateTimeImmutable();
        $order = ServiceOrder::open($dto, $now);

        $id = $this->transactional(function () use ($order, $actor, $now): int {
            $stmt = $this->db->prepare("
                INSERT INTO service_orders
                    (cliente_id, tecnico_id, engenheiro_id, ticket_id, tipo_servico, status, titulo, data_abertura, data_atualizacao)
                VALUES
                    (:cliente, :tecnico, :engenheiro, :ticket, :tipo, :status, :titulo, :agora, :agora2)
            ");
            $stmt->bindValue(':cliente', $order->clientId(), PDO::PARAM_INT);
            $this->bindNullableInt($stmt, ':tecnico', $order->technicianId());
            $this->bindNullableInt($stmt, ':engenheiro', $order->engineerId());
            $this->bindNullableInt($stmt, ':ticket', $order->ticketId());
            $stmt->bindValue(':tipo', $order->type());
            $stmt->bindValue(':status', $order->status());
            $stmt->bindValue(':titulo', $order->title());
            $stmt->bindValue(':agora', self::dt($now));
            $stmt->bindValue(':agora2', self::dt($now));
            $stmt->execute();
            $id = (int)$this->db->lastInsertId();

            $code = sprintf('OS-%s-%06d', $now->format('Y'), $id);
            $upd = $this->db->prepare("UPDATE service_orders SET codigo = :codigo WHERE id = :id");
            $upd->execute([':codigo' => $code, ':id' => $id]);

            $this->insertDetails($id, $order);
            $this->logs->add($id, $actor, 'os_criada', null, $order->status(), 'OS ' . $code . ' aberta (' . ServiceOrderType::label($order->type()) . ').', $now);

            return $id;
        });

        return $this->findOrFail($id);
    }

    /**
     * Salva diagnóstico técnico e orçamento discriminado (peças, mão de obra, software) de uma OS de manutenção.
     * O total é calculado, nunca informado.
     *
     * @throws ServiceOrderNotFoundException
     * @throws \app\Domain\ServiceOrder\Exception\ServiceOrderLockedException se a OS já foi enviada ao cliente
     */
    public function saveMaintenanceQuote(int $id, MaintenanceQuoteDTO $quote, AuditActorDTO $actor): ServiceOrder
    {
        return $this->mutate($id, $actor, function (ServiceOrder $order) use ($quote): array {
            $order->applyMaintenanceQuote($quote);
            return [
                'action' => 'orcamento_atualizado',
                'description' => 'Diagnóstico e orçamento atualizados. Total: ' . $order->total()->format() . '.',
            ];
        });
    }

    /**
     * Salva análise, solução, relatórios técnico/engenheiro e valor de uma OS de consultoria/projeto/infraestrutura.
     *
     * @throws ServiceOrderNotFoundException
     * @throws \app\Domain\ServiceOrder\Exception\ServiceOrderLockedException se a OS já foi enviada ao cliente
     */
    public function saveConsultingReport(int $id, ConsultingReportDTO $report, AuditActorDTO $actor): ServiceOrder
    {
        return $this->mutate($id, $actor, function (ServiceOrder $order) use ($report): array {
            $order->applyConsultingReport($report);
            return [
                'action' => 'relatorio_atualizado',
                'description' => 'Relatórios atualizados. Valor: ' . $order->total()->format() . '.',
            ];
        });
    }

    /**
     * Atribui técnico e engenheiro responsáveis (null remove).
     *
     * @throws ValidationException se algum usuário não existir ou não for da equipe
     */
    public function assignStaff(int $id, ?int $technicianId, ?int $engineerId, AuditActorDTO $actor): ServiceOrder
    {
        $this->assertReferences(null, $technicianId, $engineerId, null);
        return $this->mutate($id, $actor, function (ServiceOrder $order) use ($technicianId, $engineerId): array {
            $order->assignStaff($technicianId, $engineerId);
            return ['action' => 'responsaveis_alterados', 'description' => 'Responsáveis da OS atualizados.'];
        });
    }

    /**
     * Avança o status seguindo o workflow (ex.: rascunho -> aguardando diagnóstico -> enviado ao cliente -> em execução -> concluído).
     * Aprovação/rejeição NÃO passam por aqui: são do cliente ({@see decideByClient()}).
     *
     * @param  string      $to   constante de ServiceOrderStatus
     * @param  string|null $note observação opcional gravada no log
     * @throws \app\Domain\ServiceOrder\Exception\InvalidStatusTransitionException
     * @throws ValidationException com as pendências se a OS estiver incompleta para enviar ao cliente
     */
    public function changeStatus(int $id, string $to, AuditActorDTO $actor, ?string $note = null): ServiceOrder
    {
        return $this->mutate($id, $actor, function (ServiceOrder $order, DateTimeImmutable $now) use ($to, $note): array {
            $from = $order->status();
            $order->changeStatus($to, $now);
            return [
                'action' => 'status_alterado',
                'description' => ServiceOrderStatus::label($from) . ' → ' . ServiceOrderStatus::label($to) . ($note ? '. ' . $note : ''),
            ];
        });
    }

    /**
     * Gera o link seguro do portal do cliente (revoga os anteriores).
     *
     * @param  int $validDays validade em dias (1 a 365)
     * @return string token em texto puro, mostrado uma única vez; URL sugerida: BASE_URL . '/portal/os/' . $token
     * @throws ServiceOrderException se a OS ainda é rascunho/em diagnóstico (não visível ao cliente)
     */
    public function issueClientLink(int $id, AuditActorDTO $actor, int $validDays = 30): string
    {
        $now = new DateTimeImmutable();
        return $this->transactional(function () use ($id, $actor, $validDays, $now): string {
            $order = $this->loadLocked($id);
            if (!ServiceOrderStatus::isVisibleToClient($order->status())) {
                throw new ServiceOrderException('Envie a OS para aprovação do cliente antes de gerar o link de acesso.');
            }
            $token = $this->tokens->issue($id, $actor->usuarioId(), $validDays, $now);
            $this->logs->add($id, $actor, 'link_cliente_gerado', null, null, 'Link do portal do cliente gerado (válido por ' . max(1, min(365, $validDays)) . ' dias).', $now);
            return $token;
        });
    }

    /**
     * Registra a decisão do cliente pelo link seguro (sem login): aprova ou rejeita, uma única vez.
     *
     * Em uma só transação: valida o token, trava a OS, aplica a decisão no domínio, grava status +
     * data/hora, registra o log de auditoria (IP e user-agent) e cria as notificações da equipe.
     *
     * @param  string            $token     token do link (64 hex)
     * @param  ClientDecisionDTO $decision  aprovar/rejeitar + motivo
     * @param  string|null       $ip        IP do cliente ($_SERVER['REMOTE_ADDR'])
     * @param  string|null       $userAgent navegador do cliente
     * @return ServiceOrder      OS já com o novo status
     * @throws InvalidAccessTokenException token inexistente, expirado ou revogado
     * @throws \app\Domain\ServiceOrder\Exception\AlreadyDecidedException proposta já respondida (HTTP 409)
     * @throws ServiceOrderException proposta ainda não enviada para aprovação
     */
    public function decideByClient(string $token, ClientDecisionDTO $decision, ?string $ip = null, ?string $userAgent = null): ServiceOrder
    {
        $now = new DateTimeImmutable();

        $id = $this->transactional(function () use ($token, $decision, $ip, $userAgent, $now): int {
            $id = $this->tokens->resolveOrderId($token, $now);
            if ($id === null) {
                throw new InvalidAccessTokenException();
            }

            $order = $this->loadLocked($id);
            $from = $order->status();
            $actor = AuditActorDTO::client($order->clientId(), $this->userName($order->clientId()), $ip, $userAgent);

            if ($decision->isApproval()) {
                $order->approveByClient($now);
                $action = 'cliente_aprovou';
                $text = 'Cliente aprovou a proposta (' . $order->total()->format() . ').';
            } else {
                $order->rejectByClient($decision->motivo(), $now);
                $action = 'cliente_rejeitou';
                $text = 'Cliente rejeitou a proposta.' . ($order->rejectionReason() !== null ? ' Motivo: ' . $order->rejectionReason() : '');
            }

            $this->persist($order, $now);
            $this->logs->add($id, $actor, $action, $from, $order->status(), $text, $now);
            $this->notifications->notify(
                $id,
                $this->notifications->staffRecipients($order->technicianId(), $order->engineerId()),
                $action,
                $order->code() . ': ' . $text,
                $now
            );

            return $id;
        });

        return $this->findOrFail($id);
    }

    // ==================================================================
    // Consultas
    // ==================================================================

    /**
     * @return ServiceOrder|null null se não existir
     */
    public function find(int $id): ?ServiceOrder
    {
        $stmt = $this->db->prepare($this->selectSql() . " WHERE o.id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @throws ServiceOrderNotFoundException
     */
    public function findOrFail(int $id): ServiceOrder
    {
        $order = $this->find($id);
        if ($order === null) {
            throw new ServiceOrderNotFoundException();
        }
        return $order;
    }

    /**
     * Carrega a OS pelo link do cliente e registra o acesso. Só OS visíveis ao cliente
     * (já enviadas para aprovação) são retornadas.
     *
     * @throws InvalidAccessTokenException token inválido/expirado/revogado
     */
    public function findByAccessToken(string $token): ServiceOrder
    {
        $id = $this->tokens->resolveOrderId($token);
        $order = $id !== null ? $this->find($id) : null;
        if ($order === null || !ServiceOrderStatus::isVisibleToClient($order->status())) {
            throw new InvalidAccessTokenException();
        }
        $this->tokens->touch($token);
        return $order;
    }

    /**
     * Lista paginada para a tela de gestão.
     *
     * @param array<string, mixed> $filters status, tipo_servico, cliente_id, tecnico_id, q (código/título/cliente)
     * @return array{total: int, rows: array<int, array<string, mixed>>}
     *         rows: id, codigo, titulo, tipo_servico, status, data_abertura, data_atualizacao, cliente_nome, valor_total
     */
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status']) && ServiceOrderStatus::isValid((string)$filters['status'])) {
            $where[] = 'o.status = :status';
            $params[':status'] = (string)$filters['status'];
        }
        if (!empty($filters['tipo_servico']) && ServiceOrderType::isValid((string)$filters['tipo_servico'])) {
            $where[] = 'o.tipo_servico = :tipo';
            $params[':tipo'] = (string)$filters['tipo_servico'];
        }
        if (!empty($filters['cliente_id'])) {
            $where[] = 'o.cliente_id = :cliente';
            $params[':cliente'] = (int)$filters['cliente_id'];
        }
        if (!empty($filters['tecnico_id'])) {
            $where[] = 'o.tecnico_id = :tecnico';
            $params[':tecnico'] = (int)$filters['tecnico_id'];
        }
        if (isset($filters['q']) && trim((string)$filters['q']) !== '') {
            $like = '%' . addcslashes(trim((string)$filters['q']), '%_\\') . '%';
            $where[] = '(o.codigo LIKE :q1 OR o.titulo LIKE :q2 OR u.nome LIKE :q3)';
            $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $from = " FROM service_orders o JOIN users u ON u.id = o.cliente_id"
            . " LEFT JOIN service_order_maintenance m ON m.service_order_id = o.id"
            . " LEFT JOIN service_order_consulting c ON c.service_order_id = o.id" . $whereSql;

        $count = $this->db->prepare("SELECT COUNT(*)" . $from);
        $count->execute($params);

        $stmt = $this->db->prepare("
            SELECT o.id, o.codigo, o.titulo, o.tipo_servico, o.status, o.data_abertura, o.data_atualizacao,
                   u.nome AS cliente_nome, COALESCE(m.valor_total, c.valor_total, 0) AS valor_total"
            . $from . " ORDER BY o.id DESC LIMIT :limite OFFSET :deslocamento");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limite', max(1, min(200, $limit)), PDO::PARAM_INT);
        $stmt->bindValue(':deslocamento', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => (int)$count->fetchColumn(), 'rows' => $stmt->fetchAll()];
    }

    /**
     * Histórico de auditoria da OS (mais antigo primeiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function logs(int $id): array
    {
        return $this->logs->listByOrder($id);
    }

    // ==================================================================
    // Infraestrutura interna
    // ==================================================================

    /**
     * Template de comando: transação + lock + regra de domínio + persistência + log.
     *
     * @param callable $change fn(ServiceOrder $order, DateTimeImmutable $now): array{action: string, description?: string}
     *                         altera o aggregate e devolve o que deve ir para o log
     */
    private function mutate(int $id, AuditActorDTO $actor, callable $change): ServiceOrder
    {
        $now = new DateTimeImmutable();
        $this->transactional(function () use ($id, $actor, $change, $now): void {
            $order = $this->loadLocked($id);
            $from = $order->status();
            $log = $change($order, $now);
            $this->persist($order, $now);
            $this->logs->add($id, $actor, $log['action'], $from, $order->status(), $log['description'] ?? null, $now);
        });
        return $this->findOrFail($id);
    }

    /**
     * Executa $work em transação (reutiliza a transação aberta, se houver). Reverte em qualquer exceção.
     *
     * @param  callable $work
     * @return mixed retorno de $work
     */
    private function transactional(callable $work)
    {
        if ($this->db->inTransaction()) {
            return $work();
        }
        $this->db->beginTransaction();
        try {
            $result = $work();
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Trava a linha da OS (FOR UPDATE) e a carrega. Deve ser chamado dentro de transação. */
    private function loadLocked(int $id): ServiceOrder
    {
        $lock = $this->db->prepare("SELECT id FROM service_orders WHERE id = :id FOR UPDATE");
        $lock->bindValue(':id', $id, PDO::PARAM_INT);
        $lock->execute();
        if ($lock->fetchColumn() === false) {
            throw new ServiceOrderNotFoundException();
        }
        return $this->findOrFail($id);
    }

    private function insertDetails(int $id, ServiceOrder $order): void
    {
        if ($order->maintenance() !== null) {
            $m = $order->maintenance();
            $stmt = $this->db->prepare("
                INSERT INTO service_order_maintenance (service_order_id, equipamento_tipo, equipamento_descricao, numero_serie, relato_defeito_cliente)
                VALUES (:id, :tipo, :descricao, :serie, :relato)
            ");
            $stmt->execute([
                ':id' => $id,
                ':tipo' => $m->equipmentType(),
                ':descricao' => $m->equipmentDescription(),
                ':serie' => $m->serialNumber(),
                ':relato' => $m->customerComplaint(),
            ]);
        } elseif ($order->consulting() !== null) {
            $stmt = $this->db->prepare("INSERT INTO service_order_consulting (service_order_id, problema_apresentado) VALUES (:id, :problema)");
            $stmt->execute([':id' => $id, ':problema' => $order->consulting()->presentedProblem()]);
        }
    }

    /** Grava o estado mutável do aggregate (a linha já deve estar travada). O total é coluna gerada: nunca gravado. */
    private function persist(ServiceOrder $order, DateTimeImmutable $now): void
    {
        $stmt = $this->db->prepare("
            UPDATE service_orders SET
                tecnico_id = :tecnico, engenheiro_id = :engenheiro, status = :status,
                data_conclusao = :conclusao, decidido_em = :decidido, motivo_rejeicao = :motivo,
                data_atualizacao = :agora
            WHERE id = :id
        ");
        $this->bindNullableInt($stmt, ':tecnico', $order->technicianId());
        $this->bindNullableInt($stmt, ':engenheiro', $order->engineerId());
        $stmt->bindValue(':status', $order->status());
        $stmt->bindValue(':conclusao', $order->completedAt() ? self::dt($order->completedAt()) : null);
        $stmt->bindValue(':decidido', $order->decidedAt() ? self::dt($order->decidedAt()) : null);
        $stmt->bindValue(':motivo', $order->rejectionReason());
        $stmt->bindValue(':agora', self::dt($now));
        $stmt->bindValue(':id', (int)$order->id(), PDO::PARAM_INT);
        $stmt->execute();

        if ($order->maintenance() !== null) {
            $m = $order->maintenance();
            $upd = $this->db->prepare("
                UPDATE service_order_maintenance SET
                    diagnostico_tecnico = :diagnostico, valor_pecas = :pecas, valor_mao_de_obra = :mao, valor_software = :software
                WHERE service_order_id = :id
            ");
            $upd->execute([
                ':diagnostico' => $m->technicalDiagnosis(),
                ':pecas' => $m->partsCost()->toDecimal(),
                ':mao' => $m->laborCost()->toDecimal(),
                ':software' => $m->softwareCost()->toDecimal(),
                ':id' => $order->id(),
            ]);
        } elseif ($order->consulting() !== null) {
            $c = $order->consulting();
            $upd = $this->db->prepare("
                UPDATE service_order_consulting SET
                    problema_diagnosticado = :diagnosticado, solucao_proposta = :solucao,
                    relatorio_tecnico = :tecnico, relatorio_engenheiro = :engenheiro, valor_consultoria = :valor
                WHERE service_order_id = :id
            ");
            $upd->execute([
                ':diagnosticado' => $c->diagnosedProblem(),
                ':solucao' => $c->proposedSolution(),
                ':tecnico' => $c->technicalReport(),
                ':engenheiro' => $c->engineerReport(),
                ':valor' => $c->consultingFee()->toDecimal(),
                ':id' => $order->id(),
            ]);
        }
    }

    private function selectSql(): string
    {
        return "
            SELECT o.*,
                   m.equipamento_tipo, m.equipamento_descricao, m.numero_serie, m.relato_defeito_cliente,
                   m.diagnostico_tecnico, m.valor_pecas, m.valor_mao_de_obra, m.valor_software,
                   c.problema_apresentado, c.problema_diagnosticado, c.solucao_proposta,
                   c.relatorio_tecnico, c.relatorio_engenheiro, c.valor_consultoria
            FROM service_orders o
            LEFT JOIN service_order_maintenance m ON m.service_order_id = o.id
            LEFT JOIN service_order_consulting c ON c.service_order_id = o.id";
    }

    /**
     * @param array<string, mixed> $row linha de selectSql()
     */
    private function hydrate(array $row): ServiceOrder
    {
        $maintenance = null;
        $consulting = null;
        if (ServiceOrderType::usesMaintenanceModule($row['tipo_servico'])) {
            $maintenance = new MaintenanceDetails(
                (string)$row['equipamento_tipo'],
                $row['equipamento_descricao'],
                $row['numero_serie'],
                (string)$row['relato_defeito_cliente'],
                $row['diagnostico_tecnico'],
                Money::fromDecimal($row['valor_pecas']),
                Money::fromDecimal($row['valor_mao_de_obra']),
                Money::fromDecimal($row['valor_software'])
            );
        } else {
            $consulting = new ConsultingDetails(
                (string)$row['problema_apresentado'],
                $row['problema_diagnosticado'],
                $row['solucao_proposta'],
                $row['relatorio_tecnico'],
                $row['relatorio_engenheiro'],
                Money::fromDecimal($row['valor_consultoria'])
            );
        }

        return ServiceOrder::reconstitute([
            'id' => $row['id'],
            'code' => $row['codigo'],
            'client_id' => $row['cliente_id'],
            'technician_id' => $row['tecnico_id'],
            'engineer_id' => $row['engenheiro_id'],
            'ticket_id' => $row['ticket_id'],
            'type' => $row['tipo_servico'],
            'status' => $row['status'],
            'title' => $row['titulo'],
            'opened_at' => new DateTimeImmutable($row['data_abertura']),
            'updated_at' => $row['data_atualizacao'] ? new DateTimeImmutable($row['data_atualizacao']) : null,
            'completed_at' => $row['data_conclusao'] ? new DateTimeImmutable($row['data_conclusao']) : null,
            'decided_at' => $row['decidido_em'] ? new DateTimeImmutable($row['decidido_em']) : null,
            'rejection_reason' => $row['motivo_rejeicao'],
            'maintenance' => $maintenance,
            'consulting' => $consulting,
        ]);
    }

    /**
     * Garante que cliente, técnico, engenheiro e chamado informados existem (mensagem amigável em vez de erro de FK).
     *
     * @throws ValidationException
     */
    private function assertReferences(?int $clientId, ?int $technicianId, ?int $engineerId, ?int $ticketId): void
    {
        $errors = [];
        if ($clientId !== null && !$this->userHasProfile($clientId, ['cliente'])) {
            $errors['cliente_id'] = 'Cliente não encontrado.';
        }
        if ($technicianId !== null && !$this->userHasProfile($technicianId, ['tecnico', 'admin'])) {
            $errors['tecnico_id'] = 'Técnico não encontrado.';
        }
        if ($engineerId !== null && !$this->userHasProfile($engineerId, ['tecnico', 'admin'])) {
            $errors['engenheiro_id'] = 'Engenheiro não encontrado.';
        }
        if ($ticketId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM tickets WHERE id = :id");
            $stmt->execute([':id' => $ticketId]);
            if ($stmt->fetchColumn() === false) {
                $errors['ticket_id'] = 'Chamado não encontrado.';
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param string[] $profiles */
    private function userHasProfile(int $userId, array $profiles): bool
    {
        $stmt = $this->db->prepare("SELECT perfil FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $perfil = $stmt->fetchColumn();
        return $perfil !== false && in_array($perfil, $profiles, true);
    }

    private function userName(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT nome FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $name = $stmt->fetchColumn();
        return $name !== false ? (string)$name : 'Cliente';
    }

    private function bindNullableInt(\PDOStatement $stmt, string $name, ?int $value): void
    {
        $stmt->bindValue($name, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    private static function dt(DateTimeImmutable $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
