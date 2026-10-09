<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;

$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-corpBlue-500';
$editando = !empty($post['id']);
$action = $editando
    ? BASE_URL . '/admin/salvarEdicaoFeedPost/' . (int)$post['id']
    : BASE_URL . '/admin/salvarFeedPost';
$temMidia = !empty($post['caminho_midia']) && ($post['tipo_midia'] ?? 'none') !== 'none';
$statusAtual = $post['status'] ?? 'publicado';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?= $editando ? 'Editar Postagem' : 'Nova Postagem' ?></h2>
        <a href="<?= BASE_URL ?>/admin/feed" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-arrow-left mr-1"></i> Voltar</a>
    </div>

    <form action="<?= $action ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">

        <div>
            <label for="texto_conteudo" class="block text-sm font-medium text-gray-700 mb-1">Texto da postagem</label>
            <textarea id="texto_conteudo" name="texto_conteudo" rows="5" maxlength="5000" class="<?= $inputClass ?>" placeholder="O que você quer compartilhar?"><?= Security::esc($post['texto_conteudo'] ?? '') ?></textarea>
        </div>

        <div>
            <?php if ($temMidia): ?>
                <p class="block text-sm font-medium text-gray-700 mb-2">Mídia atual</p>
                <div class="mb-3 rounded-lg border border-gray-200 bg-gray-50 p-2 inline-block max-w-full">
                    <?php if ($post['tipo_midia'] === 'video'): ?>
                        <video controls preload="metadata" playsinline class="max-h-56 max-w-full rounded">
                            <source src="<?= BASE_URL . '/' . Security::esc($post['caminho_midia']) ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <img src="<?= BASE_URL . '/' . Security::esc($post['caminho_midia']) ?>" alt="Mídia atual" class="max-h-56 max-w-full rounded">
                    <?php endif; ?>
                </div>
                <label class="flex items-center text-sm text-gray-600 mb-3">
                    <input type="checkbox" name="remover_midia" value="1" class="mr-2 rounded border-gray-300">
                    Remover a mídia atual (ignorado se você enviar um novo arquivo)
                </label>
            <?php endif; ?>

            <label for="midia" class="block text-sm font-medium text-gray-700 mb-1"><?= $temMidia ? 'Substituir mídia' : 'Imagem ou vídeo (opcional)' ?></label>
            <input type="file" id="midia" name="midia" accept="image/jpeg,image/png,image/webp,video/mp4" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-corpBlue-50 file:text-corpBlue-700 hover:file:bg-corpBlue-100">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP ou MP4 &middot; até 20 MB.</p>
            <div id="feed-preview" class="mt-3 hidden"></div>
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="<?= $inputClass ?>">
                <option value="publicado" <?= $statusAtual === 'publicado' ? 'selected' : '' ?>>Publicado (visível no site)</option>
                <option value="rascunho" <?= $statusAtual === 'rascunho' ? 'selected' : '' ?>>Rascunho (oculto)</option>
            </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="<?= BASE_URL ?>/admin/feed" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-corpBlue-700 transition-colors text-sm font-medium">
                <?= $editando ? 'Salvar alterações' : 'Publicar' ?>
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        var input = document.getElementById('midia');
        var box = document.getElementById('feed-preview');
        if (!input || !box) return;
        input.addEventListener('change', function () {
            box.innerHTML = '';
            var file = input.files && input.files[0];
            if (!file) { box.classList.add('hidden'); return; }
            if (file.size > 20 * 1024 * 1024) {
                input.value = '';
                box.classList.add('hidden');
                if (window.UI) UI.toast('error', 'O arquivo excede o limite de 20 MB.');
                return;
            }
            var url = URL.createObjectURL(file);
            var media = document.createElement(file.type.indexOf('video/') === 0 ? 'video' : 'img');
            media.src = url;
            media.className = 'max-h-56 max-w-full rounded border border-gray-200';
            if (media.tagName === 'VIDEO') { media.controls = true; media.preload = 'metadata'; }
            box.appendChild(media);
            box.classList.remove('hidden');
        });
    })();
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
