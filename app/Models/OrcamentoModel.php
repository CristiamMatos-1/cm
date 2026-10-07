<?php
namespace app\Models;

use PDO;
use Throwable;
use app\Helpers\Audit;

class OrcamentoModel extends Model {

    public const STATUS_PENDENTE  = 'pendente';
    public const STATUS_APROVADO  = 'aprovado';
    public const STATUS_REJEITADO = 'rejeitado';
    public const STATUS_EXPIRADO  = 'expirado';

    public const DECISION_APPROVE = 'aprovar';
    public const DECISION_REJECT  = 'rejeitar';

    public const ITEM_TYPES = ['peca', 'mao_obra', 'servico'];

    // ==========================================
    // CONSULTAS
    // ==========================================

    public function getAllBudgets($includeExpired = true) {
        $sql = "
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
        ";

        if (!$includeExpired) {
            $sql .= " WHERE b.status != 'expirado'";
        }

        $sql .= " ORDER BY b.created_at DESC, b.id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getBudgetsByClient($cliente_id, $includeExpired = true) {
        $sql = "
            SELECT b.*, t.tipo_servico
            FROM budgets b
            LEFT JOIN tickets t ON b.ticket_id = t.id
            WHERE b.cliente_id = :cliente_id
        ";

        if (!$includeExpired) {
            $sql .= " AND b.status != 'expirado'";
        }

        $sql .= " ORDER BY b.created_at DESC, b.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cliente_id', (int)$cliente_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBudgetById($id) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            WHERE b.id = :id
        ");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getBudgetByToken($token) {
        $token = (string)$token;
        if ($token === '' || strlen($token) > 255) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            WHERE b.token_autorizacao = :token
        ");
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Orçamentos só podem ser editados enquanto não houver decisão final.
     */
    public function isEditable($budget) {
        return $budget && in_array($budget['status'], [self::STATUS_PENDENTE, self::STATUS_EXPIRADO], true);
    }

    // ==========================================
    // CRUD
    // ==========================================

    public function createBudget($data) {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare("
            INSERT INTO budgets (cliente_id, ticket_id, titulo, descricao, valor_total, valor_pecas, valor_mao_obra, data_validade, token_autorizacao) 
            VALUES (:cliente_id, :ticket_id, :titulo, :descricao, :valor_total, :valor_pecas, :valor_mao_obra, :data_validade, :token)
        ");

        $stmt->bindValue(':cliente_id', (int)$data['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':ticket_id', !empty($data['ticket_id']) ? (int)$data['ticket_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $data['titulo']);
        $stmt->bindValue(':descricao', $data['descricao']);
        $stmt->bindValue(':valor_total', $data['valor_total'] ?? 0);
        $stmt->bindValue(':valor_pecas', $data['valor_pecas'] ?? null);
        $stmt->bindValue(':valor_mao_obra', $data['valor_mao_obra'] ?? null);
        $stmt->bindValue(':data_validade', !empty($data['data_validade']) ? $data['data_validade'] : null);
        $stmt->bindValue(':token', $token);

        if ($stmt->execute()) {
            $newId = $this->db->lastInsertId();
            $this->logHistory($newId, 'criado', null, self::STATUS_PENDENTE, null, $data['motivo_historico'] ?? null);
            return $newId;
        }
        return false;
    }

    /**
     * Atualiza os dados cadastrais do orçamento. Nunca altera o status:
     * decisões passam exclusivamente por decide()/reactivateBudget().
     */
    public function updateBudget($id, $data) {
        $sql = "
            UPDATE budgets SET 
                titulo = :titulo,
                descricao = :descricao,
                ticket_id = :ticket_id,
                data_validade = :data_validade,
                status = IF(status = 'expirado' AND (:validade_check IS NULL OR :validade_check2 >= CURDATE()), 'pendente', status)
        ";

        $params = [
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
            ':ticket_id' => !empty($data['ticket_id']) ? (int)$data['ticket_id'] : null,
            ':data_validade' => !empty($data['data_validade']) ? $data['data_validade'] : null,
            ':validade_check' => !empty($data['data_validade']) ? $data['data_validade'] : null,
            ':validade_check2' => !empty($data['data_validade']) ? $data['data_validade'] : null,
        ];

        if (array_key_exists('valor_total', $data)) {
            $sql .= ", valor_total = :valor_total, valor_pecas = :valor_pecas, valor_mao_obra = :valor_mao_obra";
            $params[':valor_total'] = $data['valor_total'];
            $params[':valor_pecas'] = $data['valor_pecas'] ?? null;
            $params[':valor_mao_obra'] = $data['valor_mao_obra'] ?? null;
        }

        $sql .= " WHERE id = :id AND status IN ('pendente', 'expirado')";
        $params[':id'] = (int)$id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Orçamentos que já tiveram decisão (aprovado/rejeitado) nunca são excluídos:
     * o registro é mantido como trilha de auditoria. Para refazer, reabra ou crie nova versão.
     */
    public function deleteBudget($id) {
        if ($this->hasDecisionHistory($id)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = :id AND status NOT IN ('aprovado', 'rejeitado')");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // FLUXO DE DECISÃO (APROVAR / REJEITAR)
    // ==========================================

    /**
     * Registra a decisão (aprovar/rejeitar) sobre um orçamento de forma atômica.
     *
     * A linha é bloqueada (SELECT ... FOR UPDATE) dentro da transação, de modo que
     * duas decisões simultâneas nunca se sobrepõem: apenas a primeira é aplicada.
     *
     * @return array ['ok' => bool, 'code' => string, 'message' => string, 'budget' => array|null]
     *   code: ok | not_found | invalid | already_decided | expired | error
     */
    public function decide($id, $decision, $userId, $motivo = null, $origem = 'admin') {
        if (!in_array($decision, [self::DECISION_APPROVE, self::DECISION_REJECT], true)) {
            return $this->result(false, 'invalid', 'Ação inválida para o orçamento.');
        }

        $motivo = $motivo !== null ? trim((string)$motivo) : null;
        if ($motivo !== null) {
            $motivo = function_exists('mb_substr') ? mb_substr($motivo, 0, 1000, 'UTF-8') : substr($motivo, 0, 1000);
            if ($motivo === '') {
                $motivo = null;
            }
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT id, status, data_validade, data_autorizacao, data_rejeicao,
                       (data_validade IS NOT NULL AND data_validade < CURDATE()) AS vencido
                FROM budgets
                WHERE id = :id
                FOR UPDATE
            ");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $budget = $stmt->fetch();

            if (!$budget) {
                $this->db->rollBack();
                return $this->result(false, 'not_found', 'Orçamento não encontrado.');
            }

            if ($budget['status'] === self::STATUS_APROVADO || $budget['status'] === self::STATUS_REJEITADO) {
                $this->db->rollBack();
                $quando = $budget['status'] === self::STATUS_APROVADO ? $budget['data_autorizacao'] : $budget['data_rejeicao'];
                $label = $budget['status'] === self::STATUS_APROVADO ? 'aprovado' : 'rejeitado';
                $msg = 'Este orçamento já foi ' . $label;
                if (!empty($quando)) {
                    $msg .= ' em ' . date('d/m/Y \à\s H:i', strtotime($quando));
                }
                return $this->result(false, 'already_decided', $msg . '. Somente um administrador pode reabrir o orçamento para uma nova resposta.', $this->getBudgetById($id));
            }

            if ($budget['status'] === self::STATUS_EXPIRADO || (int)$budget['vencido'] === 1) {
                if ($budget['status'] !== self::STATUS_EXPIRADO) {
                    $upd = $this->db->prepare("UPDATE budgets SET status = 'expirado' WHERE id = :id AND status = 'pendente'");
                    $upd->bindValue(':id', (int)$id, PDO::PARAM_INT);
                    $upd->execute();
                }
                $this->db->commit();
                return $this->result(false, 'expired', 'Este orçamento está expirado e não pode mais ser decidido. Solicite a reativação.', $this->getBudgetById($id));
            }

            if ($decision === self::DECISION_APPROVE) {
                $upd = $this->db->prepare("
                    UPDATE budgets SET 
                        status = 'aprovado',
                        autorizado_por = :user_id,
                        data_autorizacao = NOW(),
                        rejeitado_por = NULL,
                        data_rejeicao = NULL,
                        motivo_rejeicao = NULL
                    WHERE id = :id AND status = 'pendente'
                ");
                $upd->bindValue(':user_id', $userId !== null ? (int)$userId : null, PDO::PARAM_INT);
            } else {
                $upd = $this->db->prepare("
                    UPDATE budgets SET 
                        status = 'rejeitado',
                        rejeitado_por = :user_id,
                        data_rejeicao = NOW(),
                        motivo_rejeicao = :motivo,
                        autorizado_por = NULL,
                        data_autorizacao = NULL
                    WHERE id = :id AND status = 'pendente'
                ");
                $upd->bindValue(':user_id', $userId !== null ? (int)$userId : null, PDO::PARAM_INT);
                $upd->bindValue(':motivo', $motivo);
            }
            $upd->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $upd->execute();

            if ($upd->rowCount() !== 1) {
                $this->db->rollBack();
                return $this->result(false, 'error', 'Não foi possível registrar a decisão. Tente novamente.');
            }

            $this->logHistory(
                $id,
                $decision === self::DECISION_APPROVE ? 'aprovado' : 'rejeitado',
                self::STATUS_PENDENTE,
                $decision === self::DECISION_APPROVE ? self::STATUS_APROVADO : self::STATUS_REJEITADO,
                $userId,
                $motivo,
                $origem
            );

            $this->db->commit();

            $updated = $this->getBudgetById($id);
            $msg = $decision === self::DECISION_APPROVE ? 'Orçamento aprovado com sucesso.' : 'Orçamento rejeitado com sucesso.';
            return $this->result(true, 'ok', $msg, $updated);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao decidir orçamento #' . (int)$id . ': ' . $e->getMessage());
            return $this->result(false, 'error', 'Erro interno ao processar a decisão. Tente novamente.');
        }
    }

    private function result($ok, $code, $message, $budget = null) {
        return ['ok' => $ok, 'code' => $code, 'message' => $message, 'budget' => $budget ?: null];
    }

    public function reactivateBudget($id) {
        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                status = 'pendente',
                data_validade = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            WHERE id = :id AND status = 'expirado'
        ");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $this->logHistory($id, 'reativado', self::STATUS_EXPIRADO, self::STATUS_PENDENTE, null, 'Validade renovada por 30 dias');
            return true;
        }
        return false;
    }

    /**
     * Reabre um orçamento já aprovado/rejeitado para uma nova resposta do cliente.
     * Somente administradores devem chamar este método (a checagem é feita no controller).
     *
     * A decisão anterior NÃO é perdida: ela permanece no histórico (budget_history)
     * com data, autor, origem e justificativa. Os campos "atuais" de decisão são limpos
     * para que o link público volte a aceitar uma nova resposta.
     *
     * @return array ['ok' => bool, 'code' => string, 'message' => string, 'budget' => array|null]
     */
    public function reopen($id, $adminId, $motivo) {
        $motivo = trim((string)$motivo);
        if ($motivo === '') {
            return $this->result(false, 'invalid', 'Informe a justificativa da reabertura.');
        }
        $motivo = function_exists('mb_substr') ? mb_substr($motivo, 0, 1000, 'UTF-8') : substr($motivo, 0, 1000);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, status FROM budgets WHERE id = :id FOR UPDATE");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $budget = $stmt->fetch();

            if (!$budget) {
                $this->db->rollBack();
                return $this->result(false, 'not_found', 'Orçamento não encontrado.');
            }

            if (!in_array($budget['status'], [self::STATUS_APROVADO, self::STATUS_REJEITADO], true)) {
                $this->db->rollBack();
                return $this->result(false, 'invalid', 'Somente orçamentos aprovados ou rejeitados podem ser reabertos.', $this->getBudgetById($id));
            }

            $upd = $this->db->prepare("
                UPDATE budgets SET
                    status = 'pendente',
                    autorizado_por = NULL,
                    data_autorizacao = NULL,
                    rejeitado_por = NULL,
                    data_rejeicao = NULL,
                    motivo_rejeicao = NULL,
                    data_validade = IF(data_validade IS NULL OR data_validade < CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), data_validade)
                WHERE id = :id AND status IN ('aprovado', 'rejeitado')
            ");
            $upd->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $upd->execute();

            if ($upd->rowCount() !== 1) {
                $this->db->rollBack();
                return $this->result(false, 'error', 'Não foi possível reabrir o orçamento. Tente novamente.');
            }

            $this->logHistory($id, 'reaberto', $budget['status'], self::STATUS_PENDENTE, $adminId, $motivo, 'admin');
            $this->db->commit();

            return $this->result(true, 'ok', 'Orçamento reaberto. O registro da decisão anterior foi mantido no histórico.', $this->getBudgetById($id));
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao reabrir orçamento #' . (int)$id . ': ' . $e->getMessage());
            return $this->result(false, 'error', 'Erro interno ao reabrir o orçamento. Tente novamente.');
        }
    }

    /**
     * Cria um novo orçamento (novo token, status pendente) a partir de um existente,
     * copiando dados e itens. O original permanece intacto com seu histórico.
     *
     * @return int|false id do novo orçamento
     */
    public function duplicateBudget($id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = :id FOR UPDATE");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $orig = $stmt->fetch();

            if (!$orig) {
                $this->db->rollBack();
                return false;
            }

            $sufixo = ' (nova versão)';
            $titulo = function_exists('mb_substr') ? mb_substr($orig['titulo'], 0, 150 - strlen($sufixo), 'UTF-8') : substr($orig['titulo'], 0, 150 - strlen($sufixo));

            $ins = $this->db->prepare("
                INSERT INTO budgets (cliente_id, ticket_id, titulo, descricao, valor_total, valor_pecas, valor_mao_obra, data_validade, token_autorizacao)
                VALUES (:cliente_id, :ticket_id, :titulo, :descricao, :valor_total, :valor_pecas, :valor_mao_obra, DATE_ADD(CURDATE(), INTERVAL 30 DAY), :token)
            ");
            $ins->bindValue(':cliente_id', (int)$orig['cliente_id'], PDO::PARAM_INT);
            $ins->bindValue(':ticket_id', $orig['ticket_id'] !== null ? (int)$orig['ticket_id'] : null, PDO::PARAM_INT);
            $ins->bindValue(':titulo', $titulo . $sufixo);
            $ins->bindValue(':descricao', $orig['descricao']);
            $ins->bindValue(':valor_total', $orig['valor_total']);
            $ins->bindValue(':valor_pecas', $orig['valor_pecas']);
            $ins->bindValue(':valor_mao_obra', $orig['valor_mao_obra']);
            $ins->bindValue(':token', bin2hex(random_bytes(32)));
            $ins->execute();
            $newId = (int)$this->db->lastInsertId();

            $items = $this->db->prepare("
                INSERT INTO budget_items (budget_id, tipo, descricao, quantidade, valor_unitario, subtotal)
                SELECT :new_id, tipo, descricao, quantidade, valor_unitario, subtotal FROM budget_items WHERE budget_id = :old_id
            ");
            $items->bindValue(':new_id', $newId, PDO::PARAM_INT);
            $items->bindValue(':old_id', (int)$id, PDO::PARAM_INT);
            $items->execute();

            $this->logHistory($newId, 'criado', null, self::STATUS_PENDENTE, null, 'Nova versão do orçamento #' . (int)$id);
            $this->logHistory($id, 'nova_versao', $orig['status'], $orig['status'], null, 'Gerado o novo orçamento #' . $newId);

            $this->db->commit();
            return $newId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao duplicar orçamento #' . (int)$id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Apenas orçamentos pendentes expiram; decisões já tomadas são preservadas.
     */
    public function checkExpiredBudgets() {
        try {
            $this->db->beginTransaction();

            try {
                $this->db->exec("
                    INSERT INTO budget_history (budget_id, acao, status_anterior, status_novo, origem, motivo)
                    SELECT id, 'expirado', 'pendente', 'expirado', 'sistema', 'Validade vencida'
                    FROM budgets
                    WHERE status = 'pendente' AND data_validade IS NOT NULL AND data_validade < CURDATE()
                ");
            } catch (Throwable $e) {
                error_log('Histórico de orçamentos indisponível (execute a migração do banco): ' . $e->getMessage());
            }

            $this->db->exec("
                UPDATE budgets SET status = 'expirado'
                WHERE status = 'pendente' AND data_validade IS NOT NULL AND data_validade < CURDATE()
            ");

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao expirar orçamentos: ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // HISTÓRICO / AUDITORIA
    // ==========================================

    private function sessionOrigin() {
        $type = $_SESSION['user_type'] ?? null;
        return in_array($type, ['admin', 'tecnico', 'cliente'], true) ? $type : 'sistema';
    }

    /**
     * Registra um evento na trilha de auditoria do orçamento. Nunca interrompe o fluxo
     * principal: se a tabela ainda não existir (migração pendente), apenas registra no log.
     */
    private function logHistory($budgetId, $acao, $anterior, $novo, $userId = null, $motivo = null, $origem = null) {
        try {
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            $stmt = $this->db->prepare("
                INSERT INTO budget_history (budget_id, acao, status_anterior, status_novo, usuario_id, origem, motivo, ip_address, user_agent)
                VALUES (:budget_id, :acao, :anterior, :novo, :usuario_id, :origem, :motivo, :ip, :ua)
            ");
            $stmt->bindValue(':budget_id', (int)$budgetId, PDO::PARAM_INT);
            $stmt->bindValue(':acao', $acao);
            $stmt->bindValue(':anterior', $anterior);
            $stmt->bindValue(':novo', $novo);
            $stmt->bindValue(':usuario_id', $userId !== null ? (int)$userId : null, PDO::PARAM_INT);
            $stmt->bindValue(':origem', $origem ?: $this->sessionOrigin());
            $stmt->bindValue(':motivo', $motivo);
            $stmt->bindValue(':ip', Audit::clientIp());
            $stmt->bindValue(':ua', Audit::userAgent());
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Não foi possível registrar o histórico do orçamento #' . (int)$budgetId . ': ' . $e->getMessage());
        }
    }

    /**
     * Linha do tempo completa (uso administrativo). Inclui autor, origem, IP e justificativa.
     */
    public function getHistory($budgetId) {
        try {
            $stmt = $this->db->prepare("
                SELECT h.*, u.nome AS usuario_nome
                FROM budget_history h
                LEFT JOIN users u ON u.id = h.usuario_id
                WHERE h.budget_id = :id
                ORDER BY h.created_at ASC, h.id ASC
            ");
            $stmt->bindValue(':id', (int)$budgetId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Histórico de orçamentos indisponível: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Linha do tempo para o link público: apenas ação e data (sem autor, IP ou justificativa).
     */
    public function getPublicHistory($budgetId) {
        return array_map(function ($row) {
            return ['acao' => $row['acao'], 'created_at' => $row['created_at']];
        }, array_values(array_filter($this->getHistory($budgetId), function ($row) {
            return in_array($row['acao'], ['aprovado', 'rejeitado', 'reaberto', 'reativado', 'expirado'], true);
        })));
    }

    public function hasDecisionHistory($budgetId) {
        $stmt = $this->db->prepare("SELECT status FROM budgets WHERE id = :id");
        $stmt->bindValue(':id', (int)$budgetId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetchColumn();
        if (in_array($status, [self::STATUS_APROVADO, self::STATUS_REJEITADO], true)) {
            return true;
        }

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM budget_history WHERE budget_id = :id AND acao IN ('aprovado', 'rejeitado')");
            $stmt->bindValue(':id', (int)$budgetId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ==========================================
    // ITENS DO ORÇAMENTO
    // ==========================================

    public function addBudgetItem($budget_id, $tipo, $descricao, $quantidade, $valor_unitario) {
        if (!in_array($tipo, self::ITEM_TYPES, true)) {
            return false;
        }

        $quantidade = max(1, (int)$quantidade);
        $subtotal = round($quantidade * $valor_unitario, 2);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO budget_items (budget_id, tipo, descricao, quantidade, valor_unitario, subtotal)
                VALUES (:budget_id, :tipo, :descricao, :quantidade, :valor_unitario, :subtotal)
            ");
            $stmt->bindValue(':budget_id', (int)$budget_id, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
            $stmt->bindValue(':valor_unitario', $valor_unitario);
            $stmt->bindValue(':subtotal', $subtotal);
            $stmt->execute();
            $itemId = $this->db->lastInsertId();

            $this->recalculateBudgetTotal($budget_id);
            $this->db->commit();
            return $itemId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao adicionar item de orçamento: ' . $e->getMessage());
            return false;
        }
    }

    public function getBudgetItems($budget_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM budget_items 
            WHERE budget_id = :budget_id
            ORDER BY tipo, id
        ");
        $stmt->bindValue(':budget_id', (int)$budget_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBudgetItemById($item_id) {
        $stmt = $this->db->prepare("SELECT * FROM budget_items WHERE id = :id");
        $stmt->bindValue(':id', (int)$item_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function deleteBudgetItem($item_id) {
        $item = $this->getBudgetItemById($item_id);
        if (!$item) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM budget_items WHERE id = :id");
            $stmt->bindValue(':id', (int)$item_id, PDO::PARAM_INT);
            $stmt->execute();

            $this->recalculateBudgetTotal($item['budget_id']);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao remover item de orçamento: ' . $e->getMessage());
            return false;
        }
    }

    public function recalculateBudgetTotal($budget_id) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CASE WHEN tipo = 'peca' THEN subtotal ELSE 0 END), 0) as valor_pecas,
                   COALESCE(SUM(CASE WHEN tipo = 'mao_obra' THEN subtotal ELSE 0 END), 0) as valor_mao_obra,
                   COALESCE(SUM(subtotal), 0) as valor_total
            FROM budget_items
            WHERE budget_id = :budget_id
        ");
        $stmt->bindValue(':budget_id', (int)$budget_id, PDO::PARAM_INT);
        $stmt->execute();
        $totals = $stmt->fetch();

        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                valor_pecas = :valor_pecas,
                valor_mao_obra = :valor_mao_obra,
                valor_total = :valor_total
            WHERE id = :budget_id
        ");
        $stmt->bindValue(':valor_pecas', $totals['valor_pecas']);
        $stmt->bindValue(':valor_mao_obra', $totals['valor_mao_obra']);
        $stmt->bindValue(':valor_total', $totals['valor_total']);
        $stmt->bindValue(':budget_id', (int)$budget_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
