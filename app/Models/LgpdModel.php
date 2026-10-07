<?php
namespace app\Models;

use PDO;
use Throwable;
use app\Helpers\Lgpd;

/**
 * Direitos do titular (LGPD, art. 18): solicitações, exportação e anonimização.
 */
class LgpdModel extends Model {

    // ==========================================
    // SOLICITAÇÕES
    // ==========================================

    public function countOpenByUser($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lgpd_requests WHERE user_id = :id AND status = 'aberta'");
        $stmt->bindValue(':id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function createRequest($userId, $tipo, $mensagem) {
        if (!isset(Lgpd::TIPOS_SOLICITACAO[$tipo])) {
            return false;
        }

        $mensagem = trim((string)$mensagem);
        $mensagem = function_exists('mb_substr') ? mb_substr($mensagem, 0, 2000, 'UTF-8') : substr($mensagem, 0, 2000);

        $stmt = $this->db->prepare("INSERT INTO lgpd_requests (user_id, tipo, mensagem) VALUES (:user_id, :tipo, :mensagem)");
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':mensagem', $mensagem !== '' ? $mensagem : null);
        return $stmt->execute() ? (int)$this->db->lastInsertId() : false;
    }

    public function getRequestsByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM lgpd_requests WHERE user_id = :id ORDER BY created_at DESC, id DESC");
        $stmt->bindValue(':id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllRequests() {
        return $this->db->query("
            SELECT r.*, u.nome AS titular_nome, u.email AS titular_email, u.perfil AS titular_perfil,
                   u.anonimizado_em AS titular_anonimizado_em, adm.nome AS atendido_por_nome
            FROM lgpd_requests r
            JOIN users u ON u.id = r.user_id
            LEFT JOIN users adm ON adm.id = r.atendido_por
            ORDER BY (r.status = 'aberta') DESC, r.created_at DESC, r.id DESC
        ")->fetchAll();
    }

    public function getRequestById($id) {
        $stmt = $this->db->prepare("SELECT * FROM lgpd_requests WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function countOpen() {
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM lgpd_requests WHERE status = 'aberta'")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function answerRequest($id, $status, $resposta, $adminId) {
        if (!in_array($status, ['atendida', 'negada'], true)) {
            return false;
        }

        $resposta = trim((string)$resposta);
        $resposta = function_exists('mb_substr') ? mb_substr($resposta, 0, 2000, 'UTF-8') : substr($resposta, 0, 2000);

        $stmt = $this->db->prepare("
            UPDATE lgpd_requests
            SET status = :status, resposta = :resposta, atendido_por = :admin, atendido_em = NOW()
            WHERE id = :id AND status = 'aberta'
        ");
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':resposta', $resposta !== '' ? $resposta : null);
        $stmt->bindValue(':admin', (int)$adminId, PDO::PARAM_INT);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // EXPORTAÇÃO (acesso / portabilidade)
    // ==========================================

    private function safeFetchAll($sql, array $params, array $allowedKeys = null) {
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if ($allowedKeys !== null) {
                $flip = array_flip($allowedKeys);
                $rows = array_map(function ($row) use ($flip) {
                    return array_intersect_key($row, $flip);
                }, $rows);
            }
            return $rows;
        } catch (Throwable $e) {
            error_log('Exportação LGPD - consulta indisponível: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Reúne os dados pessoais do titular em formato estruturado. Nunca inclui hash de senha,
     * permissões internas nem dados de terceiros.
     */
    public function exportUserData($userId) {
        $userId = (int)$userId;

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $perfil = array_intersect_key($user, array_flip([
            'id', 'nome', 'cpf_cnpj', 'email', 'telefone', 'perfil', 'responsavel_nome',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
            'consentimento_em', 'consentimento_versao', 'consentimento_ip', 'created_at', 'updated_at'
        ]));

        $orcamentos = $this->safeFetchAll(
            "SELECT id, titulo, descricao, valor_total, valor_pecas, valor_mao_obra, status, data_validade,
                    data_autorizacao, data_rejeicao, motivo_rejeicao, created_at
             FROM budgets WHERE cliente_id = :id ORDER BY id",
            [':id' => $userId]
        );
        foreach ($orcamentos as &$orc) {
            $orc['itens'] = $this->safeFetchAll(
                "SELECT tipo, descricao, quantidade, valor_unitario, subtotal FROM budget_items WHERE budget_id = :id ORDER BY id",
                [':id' => (int)$orc['id']]
            );
            $orc['historico'] = $this->safeFetchAll(
                "SELECT acao, status_anterior, status_novo, origem, motivo, ip_address, user_agent, created_at
                 FROM budget_history WHERE budget_id = :id ORDER BY created_at, id",
                [':id' => (int)$orc['id']]
            );
        }
        unset($orc);

        return [
            'gerado_em' => date('c'),
            'versao_politica_privacidade' => Lgpd::VERSAO_POLITICA,
            'titular' => $perfil,
            'chamados' => $this->safeFetchAll(
                "SELECT id, tipo_servico, descricao, atendimento, status, relatorio_final, valor_pecas, valor_mao_obra,
                        valor_servico, forma_pagamento, created_at, updated_at, closed_at
                 FROM tickets WHERE cliente_id = :id ORDER BY id",
                [':id' => $userId]
            ),
            'anexos_de_chamados' => $this->safeFetchAll(
                "SELECT m.ticket_id, m.tipo, m.file_url, m.created_at
                 FROM ticket_media m JOIN tickets t ON t.id = m.ticket_id WHERE t.cliente_id = :id ORDER BY m.id",
                [':id' => $userId]
            ),
            'orcamentos' => $orcamentos,
            'contratos' => $this->safeFetchAll(
                "SELECT id, valor_mensal, data_inicio, data_validade, prazo_renovacao_anos, conteudo_sla, created_at
                 FROM contracts WHERE cliente_id = :id ORDER BY id",
                [':id' => $userId]
            ),
            'notas_fiscais' => $this->safeFetchAll(
                "SELECT id, numero_nf, valor, data_emissao, created_at FROM invoices WHERE cliente_id = :id ORDER BY id",
                [':id' => $userId]
            ),
            'patrimonio' => $this->safeFetchAll(
                "SELECT id, nome_equipamento, numero_serie, data_compra, data_venda, garantia_meses, created_at
                 FROM assets WHERE cliente_id = :id ORDER BY id",
                [':id' => $userId]
            ),
            'servicos_avulsos' => $this->safeFetchAll(
                "SELECT id, descricao, valor, data_servico, status, created_at FROM avulso_services WHERE cliente_id = :id ORDER BY id",
                [':id' => $userId]
            ),
            'solicitacoes_lgpd' => $this->safeFetchAll(
                "SELECT id, tipo, mensagem, status, resposta, atendido_em, created_at FROM lgpd_requests WHERE user_id = :id ORDER BY id",
                [':id' => $userId]
            ),
        ];
    }

    // ==========================================
    // ANONIMIZAÇÃO (eliminação/anonimização - art. 18, IV e VI)
    // ==========================================

    /**
     * Anonimiza o titular preservando os registros que a lei exige manter (fiscais, contratos e
     * histórico de decisões de orçamentos - art. 16, I e art. 7º, VI). Dados de contato, endereço,
     * credenciais e anexos enviados pelo titular são eliminados.
     *
     * @return array ['ok' => bool, 'message' => string]
     */
    public function anonymizeUser($userId, $adminId) {
        $userId = (int)$userId;

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, perfil, anonimizado_em FROM users WHERE id = :id FOR UPDATE");
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Titular não encontrado.'];
            }
            if ($userId === (int)$adminId) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Você não pode anonimizar a própria conta.'];
            }
            if ($user['perfil'] === 'admin') {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Contas de administrador não podem ser anonimizadas. Altere o perfil antes.'];
            }
            if (!empty($user['anonimizado_em'])) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'Este titular já foi anonimizado.'];
            }

            $media = $this->db->prepare("
                SELECT m.id, m.file_url FROM ticket_media m
                JOIN tickets t ON t.id = m.ticket_id
                WHERE t.cliente_id = :id OR m.user_id = :id2
            ");
            $media->bindValue(':id', $userId, PDO::PARAM_INT);
            $media->bindValue(':id2', $userId, PDO::PARAM_INT);
            $media->execute();
            $arquivos = $media->fetchAll();

            $delMedia = $this->db->prepare("
                DELETE m FROM ticket_media m
                JOIN tickets t ON t.id = m.ticket_id
                WHERE t.cliente_id = :id OR m.user_id = :id2
            ");
            $delMedia->bindValue(':id', $userId, PDO::PARAM_INT);
            $delMedia->bindValue(':id2', $userId, PDO::PARAM_INT);
            $delMedia->execute();

            $upd = $this->db->prepare("
                UPDATE users SET
                    nome = :nome,
                    cpf_cnpj = :cpf,
                    email = :email,
                    telefone = NULL,
                    senha = :senha,
                    responsavel_nome = NULL,
                    cep = NULL, logradouro = NULL, numero = NULL, complemento = NULL,
                    bairro = NULL, cidade = NULL, estado = NULL,
                    permissoes = NULL,
                    consentimento_ip = NULL,
                    anonimizado_em = NOW()
                WHERE id = :id
            ");
            $upd->bindValue(':nome', 'Titular anonimizado #' . $userId);
            $upd->bindValue(':cpf', 'ANON-' . $userId);
            $upd->bindValue(':email', 'anonimizado-' . $userId . '@anonimizado.invalid');
            $upd->bindValue(':senha', password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT));
            $upd->bindValue(':id', $userId, PDO::PARAM_INT);
            $upd->execute();

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao anonimizar titular #' . $userId . ': ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Não foi possível anonimizar o titular. Nada foi alterado.'];
        }

        // Arquivos só são removidos do disco depois que o banco foi atualizado com sucesso.
        foreach ($arquivos as $arquivo) {
            $path = \app\Helpers\UploadHelper::resolveStoredPath($arquivo['file_url'], 'tickets');
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        return ['ok' => true, 'message' => 'Titular anonimizado. Registros fiscais, contratos e o histórico de decisões foram mantidos sem identificação pessoal.'];
    }
}
