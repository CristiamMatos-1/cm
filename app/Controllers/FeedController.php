<?php
namespace app\Controllers;

use app\Models\FeedModel;

/**
 * Feed de Postagens Rápidas (área pública, sem login).
 * Expõe apenas leitura: a gestão fica em AdminController (/admin/feed).
 */
class FeedController extends Controller {

    private const LIMIT_PADRAO = 5;
    private const LIMIT_MAXIMO = 20;

    public function index() {
        $this->view('feed/publico', [
            'title' => 'Feed Rápido',
            'limit' => self::LIMIT_PADRAO
        ]);
    }

    /**
     * Endpoint JSON da rolagem infinita.
     * GET /feed/carregar?offset=0&limit=5
     */
    public function carregar() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $this->json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }

        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $limit = (int)($_GET['limit'] ?? self::LIMIT_PADRAO);
        $limit = min(max($limit, 1), self::LIMIT_MAXIMO);

        try {
            $feedModel = new FeedModel();
            // Busca um item a mais só para saber se ainda há páginas.
            $posts = $feedModel->getPublishedPosts($limit + 1, $offset);
        } catch (\Throwable $e) {
            error_log('Erro ao carregar feed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Não foi possível carregar as postagens agora.'], 500);
        }

        $hasMore = count($posts) > $limit;
        $posts = array_slice($posts, 0, $limit);

        $this->json([
            'success' => true,
            'html' => $this->renderCards($posts),
            'count' => count($posts),
            'has_more' => $hasMore,
            'next_offset' => $offset + count($posts)
        ]);
    }

    private function renderCards(array $posts) {
        ob_start();
        foreach ($posts as $post) {
            include APP_PATH . '/Views/feed/_card.php';
        }
        return ob_get_clean();
    }
}
