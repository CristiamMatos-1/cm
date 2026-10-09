<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;

$badges = [
    'publicado' => 'bg-green-100 text-green-800 ring-green-200',
    'rascunho' => 'bg-gray-100 text-gray-700 ring-gray-200',
];
$labels = ['publicado' => 'Publicado', 'rascunho' => 'Rascunho'];
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Feed Rápido</h2>
        <p class="text-sm text-gray-500">Postagens curtas exibidas em bloco na página inicial (não geram página individual).</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/novoFeedPost" class="inline-flex items-center justify-center bg-corpBlue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-corpBlue-700 transition-colors text-sm font-medium">
        <i class="fas fa-plus mr-2"></i> Nova Postagem
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mídia</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Texto</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($posts)): ?>
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Nenhuma postagem no feed. Clique em "Nova Postagem" para começar.</td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $p): ?>
                    <tr class="hover:bg-gray-50">
                        <td data-label="Data" class="px-4 py-4 whitespace-nowrap text-sm text-gray-600"><?= Security::esc(date('d/m/Y H:i', strtotime($p['data_criacao']))) ?></td>
                        <td data-label="Mídia" class="px-4 py-4 text-sm">
                            <?php if ($p['tipo_midia'] === 'image' && $p['caminho_midia']): ?>
                                <img src="<?= BASE_URL . '/' . Security::esc($p['caminho_midia']) ?>" alt="" loading="lazy" class="h-14 w-20 object-cover rounded border border-gray-200">
                            <?php elseif ($p['tipo_midia'] === 'video' && $p['caminho_midia']): ?>
                                <span class="inline-flex items-center text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full px-2.5 py-1"><i class="fas fa-video mr-1"></i> Vídeo MP4</span>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Somente texto</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Texto" class="px-4 py-4 text-sm text-gray-700 md:max-w-md break-words">
                            <?= Security::esc(mb_strimwidth((string)$p['texto_conteudo'], 0, 140, '…', 'UTF-8')) ?>
                        </td>
                        <td data-label="Status" class="px-4 py-4 text-sm">
                            <span class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full ring-1 ring-inset <?= $badges[$p['status']] ?? 'bg-gray-100 text-gray-700 ring-gray-200' ?>">
                                <?= Security::esc($labels[$p['status']] ?? $p['status']) ?>
                            </span>
                        </td>
                        <td data-label="" class="cell-actions px-4 py-4 text-sm md:text-right">
                            <div class="flex items-center md:justify-end gap-3">
                                <a href="<?= BASE_URL ?>/admin/editarFeedPost/<?= (int)$p['id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar"><i class="fas fa-edit"></i></a>
                                <form action="<?= BASE_URL ?>/admin/excluirFeedPost/<?= (int)$p['id'] ?>" method="POST"
                                      data-confirm="A postagem e a mídia anexada serão excluídas permanentemente." data-confirm-title="Excluir postagem?" data-confirm-button="Sim, excluir">
                                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                    <button type="submit" data-no-loading class="text-red-600 hover:text-red-800" title="Excluir"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
