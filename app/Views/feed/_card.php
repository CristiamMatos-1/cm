<?php
use app\Helpers\Security;

$mediaUrl = !empty($post['caminho_midia']) ? BASE_URL . '/' . ltrim($post['caminho_midia'], '/') : null;
$timestamp = strtotime($post['data_criacao']);
?>
<article class="feed-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" data-post-id="<?= (int)$post['id'] ?>">
    <div class="p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-corpBlue-100 text-corpBlue-700"><i class="fas fa-bullhorn"></i></span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Novidades</p>
                <time class="text-xs text-gray-500" datetime="<?= Security::esc(date('c', $timestamp)) ?>"><?= Security::esc(date('d/m/Y \à\s H:i', $timestamp)) ?></time>
            </div>
        </div>
        <?php if (trim((string)$post['texto_conteudo']) !== ''): ?>
            <p class="text-gray-800 text-[15px] leading-relaxed whitespace-pre-line break-words"><?= Security::esc($post['texto_conteudo']) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($mediaUrl && $post['tipo_midia'] === 'image'): ?>
        <img src="<?= Security::esc($mediaUrl) ?>" alt="" loading="lazy" class="w-full max-h-[32rem] object-cover bg-gray-100">
    <?php elseif ($mediaUrl && $post['tipo_midia'] === 'video'): ?>
        <video controls preload="metadata" playsinline class="w-full max-h-[32rem] bg-black">
            <source src="<?= Security::esc($mediaUrl) ?>" type="video/mp4">
            Seu navegador não suporta a reprodução de vídeo.
        </video>
    <?php endif; ?>
</article>
