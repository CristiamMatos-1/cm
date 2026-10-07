<?php
namespace app\Controllers;

use app\Models\FinanceiroModel;
use app\Models\ChamadoModel;
use app\Models\UserModel;
use app\Helpers\UploadHelper;

/**
 * Entrega arquivos privados (notas fiscais e anexos de chamados) somente a quem tem permissão.
 * O acesso direto à pasta uploads/ é bloqueado no .htaccess (LGPD, art. 46: controle de acesso).
 */
class ArquivoController extends Controller {

    private const MIMES_INLINE = [
        'image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm', 'application/pdf',
    ];

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }

    private function permissoes() {
        $userModel = new UserModel();
        $user = $userModel->getUserById($_SESSION['user_id']);
        return json_decode($user['permissoes'] ?? '[]', true) ?: [];
    }

    private function negar() {
        http_response_code(403);
        die('Acesso negado.');
    }

    private function naoEncontrado() {
        http_response_code(404);
        die('Arquivo não encontrado.');
    }

    public function nota($id = 0) {
        $model = new FinanceiroModel();
        $nota = $model->getNotaById((int)$id);
        if (!$nota) {
            $this->naoEncontrado();
        }

        $tipo = $_SESSION['user_type'] ?? '';
        $permitido = $tipo === 'admin'
            || ($tipo === 'tecnico' && in_array('acesso_financeiro', $this->permissoes(), true))
            || ($tipo === 'cliente' && (int)$nota['cliente_id'] === (int)$_SESSION['user_id']);

        if (!$permitido) {
            $this->negar();
        }

        $this->entregar(UploadHelper::resolveStoredPath($nota['arquivo_url'] ?? '', 'invoices'), 'nota-fiscal-' . (int)$nota['id']);
    }

    public function midia($id = 0) {
        $model = new ChamadoModel();
        $midia = $model->getMediaById((int)$id);

        if (!$midia) {
            $this->naoEncontrado();
        }

        $tipo = $_SESSION['user_type'] ?? '';
        $uid = (int)$_SESSION['user_id'];
        $permitido = $tipo === 'admin'
            || ($tipo === 'cliente' && (int)$midia['cliente_id'] === $uid)
            || ($tipo === 'tecnico' && (
                (int)$midia['tecnico_id'] === $uid
                || $midia['status'] === 'aberto'
                || in_array('abrir_chamado_admin', $this->permissoes(), true)
            ));

        if (!$permitido) {
            $this->negar();
        }

        $this->entregar(UploadHelper::resolveStoredPath($midia['file_url'] ?? '', 'tickets'), 'anexo-' . (int)$id);
    }

    private function entregar($path, $baseName) {
        if (!$path || !is_file($path)) {
            $this->naoEncontrado();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
        finfo_close($finfo);

        $inline = in_array($mime, self::MIMES_INLINE, true);
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $baseName . '.' . preg_replace('/[^a-z0-9]/i', '', $ext) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
