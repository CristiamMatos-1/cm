<?php
namespace app\Models;

use PDO;

class FeedModel extends Model {

    public const TIPOS_MIDIA = ['image', 'video', 'none'];
    public const STATUS = ['publicado', 'rascunho'];

    public function getAllPosts() {
        $stmt = $this->db->query("SELECT * FROM feed_posts ORDER BY data_criacao DESC, id DESC");
        return $stmt->fetchAll();
    }

    public function getPostById($id) {
        $stmt = $this->db->prepare("SELECT * FROM feed_posts WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Postagens publicadas, da mais recente para a mais antiga (paginação por offset/limit).
     */
    public function getPublishedPosts($limit, $offset) {
        $stmt = $this->db->prepare("
            SELECT id, texto_conteudo, tipo_midia, caminho_midia, data_criacao
            FROM feed_posts
            WHERE status = 'publicado'
            ORDER BY data_criacao DESC, id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createPost($data) {
        $stmt = $this->db->prepare("
            INSERT INTO feed_posts (texto_conteudo, tipo_midia, caminho_midia, status)
            VALUES (:texto_conteudo, :tipo_midia, :caminho_midia, :status)
        ");
        $stmt->bindValue(':texto_conteudo', $data['texto_conteudo']);
        $stmt->bindValue(':tipo_midia', $data['tipo_midia']);
        $stmt->bindValue(':caminho_midia', $data['caminho_midia']);
        $stmt->bindValue(':status', $data['status']);
        return $stmt->execute();
    }

    public function updatePost($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE feed_posts SET
                texto_conteudo = :texto_conteudo,
                tipo_midia = :tipo_midia,
                caminho_midia = :caminho_midia,
                status = :status
            WHERE id = :id
        ");
        $stmt->bindValue(':texto_conteudo', $data['texto_conteudo']);
        $stmt->bindValue(':tipo_midia', $data['tipo_midia']);
        $stmt->bindValue(':caminho_midia', $data['caminho_midia']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deletePost($id) {
        $stmt = $this->db->prepare("DELETE FROM feed_posts WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
