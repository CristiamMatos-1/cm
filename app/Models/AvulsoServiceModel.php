<?php
namespace app\Models;

class AvulsoServiceModel extends Model {

    public function getAllServices() {
        $stmt = $this->db->query("
            SELECT s.*, u.nome as cliente_nome 
            FROM avulso_services s
            JOIN users u ON s.cliente_id = u.id
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getServicesByClient($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM avulso_services 
            WHERE cliente_id = :cliente_id
            ORDER BY data_servico DESC
        ");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getServiceById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.nome as cliente_nome, u.email as cliente_email
            FROM avulso_services s
            JOIN users u ON s.cliente_id = u.id
            WHERE s.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createService($data) {
        $stmt = $this->db->prepare("
            INSERT INTO avulso_services (cliente_id, descricao, valor, data_servico, status)
            VALUES (:cliente_id, :descricao, :valor, :data_servico, :status)
        ");
        
        $status = $data['status'] ?? 'pendente';
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':data_servico', $data['data_servico']);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateService($id, $data) {
        $sql = "UPDATE avulso_services SET ";
        $updates = [];
        $params = [':id' => $id];

        if (isset($data['descricao'])) {
            $updates[] = "descricao = :descricao";
            $params[':descricao'] = $data['descricao'];
        }
        if (isset($data['valor'])) {
            $updates[] = "valor = :valor";
            $params[':valor'] = $data['valor'];
        }
        if (isset($data['data_servico'])) {
            $updates[] = "data_servico = :data_servico";
            $params[':data_servico'] = $data['data_servico'];
        }
        if (isset($data['status'])) {
            $updates[] = "status = :status";
            $params[':status'] = $data['status'];
        }

        $updates[] = "updated_at = NOW()";
        $sql .= implode(", ", $updates) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE avulso_services 
            SET status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function deleteService($id) {
        $stmt = $this->db->prepare("DELETE FROM avulso_services WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
