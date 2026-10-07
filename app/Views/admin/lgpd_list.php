<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;
use app\Helpers\UI;
use app\Helpers\Lgpd;

$abertas = count(array_filter($solicitacoes, fn($s) => $s['status'] === 'aberta'));
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-user-lock mr-2 text-corpBlue-500"></i> Privacidade (LGPD)</h2>
    <p class="text-sm text-gray-500">Solicitações dos titulares (art. 18 da Lei 13.709/2018). O prazo para resposta deve ser cumprido pelo encarregado/controlador.
        <?= $abertas > 0 ? '<strong class="text-yellow-700">' . (int)$abertas . ' em análise.</strong>' : 'Nenhuma solicitação em análise.' ?></p>
</div>

<?php if (empty($solicitacoes)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500 text-sm">
        Nenhuma solicitação registrada até o momento.
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($solicitacoes as $s):
            $anonimizado = !empty($s['titular_anonimizado_em']);
            $aberta = $s['status'] === 'aberta';
        ?>
            <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-gray-800">Protocolo #<?= (int)$s['id'] ?></span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= Lgpd::statusClass($s['status']) ?>"><?= Security::esc(Lgpd::statusLabel($s['status'])) ?></span>
                    <span class="text-xs text-gray-500"><?= UI::date($s['created_at'], true) ?></span>
                    <?php if ($anonimizado): ?><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">Titular anonimizado</span><?php endif; ?>
                </div>
                <p class="text-sm text-gray-700 mt-2">
                    <strong><?= Security::esc($s['titular_nome']) ?></strong>
                    <span class="text-gray-500">(<?= Security::esc($s['titular_perfil']) ?><?= $anonimizado ? '' : ' - ' . Security::esc($s['titular_email']) ?>)</span>
                </p>
                <p class="text-sm text-gray-800 mt-1"><?= Security::esc(Lgpd::tipoLabel($s['tipo'])) ?></p>
                <?php if (!empty($s['mensagem'])): ?>
                    <p class="text-sm text-gray-500 mt-1 break-words"><?= nl2br(Security::esc($s['mensagem'])) ?></p>
                <?php endif; ?>

                <?php if (!$aberta): ?>
                    <p class="text-sm text-gray-800 mt-3 p-3 bg-gray-50 rounded-lg border border-gray-100 break-words">
                        <strong>Resposta<?= !empty($s['atendido_por_nome']) ? ' de ' . Security::esc($s['atendido_por_nome']) : '' ?><?= !empty($s['atendido_em']) ? ' em ' . UI::date($s['atendido_em'], true) : '' ?>:</strong>
                        <?= nl2br(Security::esc($s['resposta'] ?? '')) ?>
                    </p>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if (!$anonimizado): ?>
                        <form action="<?= BASE_URL ?>/admin/exportarTitular/<?= (int)$s['user_id'] ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                            <button type="submit" data-no-loading class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i> Exportar dados
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($aberta): ?>
                        <?php if (!$anonimizado && $s['titular_perfil'] === 'cliente' && in_array($s['tipo'], ['anonimizacao', 'revogacao_consentimento'], true)): ?>
                            <form action="<?= BASE_URL ?>/admin/anonimizarTitular/<?= (int)$s['user_id'] ?>" method="POST"
                                  data-confirm="Os dados pessoais do titular serão removidos de forma irreversível (nome, contato, endereço, mídias dos chamados). Dados fiscais e contratuais exigidos por lei serão mantidos sem identificação."
                                  data-confirm-title="Anonimizar titular?" data-confirm-button="Sim, anonimizar">
                                <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                <input type="hidden" name="solicitacao_id" value="<?= (int)$s['id'] ?>">
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700">
                                    <i class="fas fa-user-slash mr-2"></i> Anonimizar titular
                                </button>
                            </form>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>/admin/responderLgpd/<?= (int)$s['id'] ?>" method="POST"
                              data-confirm="Registrar esta solicitação como atendida e enviar a resposta ao titular."
                              data-confirm-title="Marcar como atendida?" data-confirm-button="Atender" data-confirm-variant="primary"
                              data-confirm-reason="Resposta ao titular (obrigatória)" data-confirm-reason-placeholder="Descreva o que foi feito...">
                            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                            <input type="hidden" name="status" value="atendida">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
                                <i class="fas fa-check mr-2"></i> Atender
                            </button>
                        </form>

                        <form action="<?= BASE_URL ?>/admin/responderLgpd/<?= (int)$s['id'] ?>" method="POST"
                              data-confirm="Registrar a negativa com a justificativa legal ou técnica."
                              data-confirm-title="Negar solicitação?" data-confirm-button="Negar"
                              data-confirm-reason="Justificativa da negativa (obrigatória)" data-confirm-reason-placeholder="Ex.: dados exigidos por obrigação fiscal (art. 16, I)...">
                            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                            <input type="hidden" name="status" value="negada">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-sm hover:bg-red-50">
                                <i class="fas fa-ban mr-2"></i> Negar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
