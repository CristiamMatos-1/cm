<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\UI;
use app\Helpers\Security;

$pendente = $orcamento['status'] === 'pendente';
$expirado = $orcamento['status'] === 'expirado';
$decidido = in_array($orcamento['status'], ['aprovado', 'rejeitado'], true);
$ehAdmin = !empty($ehAdmin);
$historico = $historico ?? [];
$temItens = !empty($itens);
$decisionText = UI::budgetDecisionText($orcamento);
$tiposItem = ['peca' => 'Peça', 'mao_obra' => 'Mão de obra', 'servico' => 'Serviço'];
$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-corpBlue-500 disabled:bg-gray-50 disabled:text-gray-500';
?>

<div class="max-w-4xl mx-auto" data-budget-card data-budget-id="<?= (int)$orcamento['id'] ?>" data-status="<?= Security::esc($orcamento['status']) ?>">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-800">Orçamento #<?= (int)$orcamento['id'] ?></h2>
                <span data-budget-status-badge><?= UI::budgetStatusBadge($orcamento['status']) ?></span>
            </div>
            <p data-budget-decision-text class="text-sm text-gray-500 mt-1 <?= $decisionText === '' ? 'hidden' : '' ?>"><?= Security::esc($decisionText) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/admin/orcamentos" class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista
        </a>
    </div>

    <?php if ($orcamento['status'] === 'rejeitado' && !empty($orcamento['motivo_rejeicao'])): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-sm text-red-800">
            <i class="fas fa-comment-dots mr-1"></i> <strong>Motivo da rejeição:</strong> <?= Security::esc($orcamento['motivo_rejeicao']) ?>
        </div>
    <?php endif; ?>

    <?php if (!$editavel): ?>
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-800">
            <i class="fas fa-lock mr-1"></i> Este orçamento já foi <?= Security::esc($orcamento['status']) ?> e está bloqueado para edição.
            O link enviado ao cliente exibe esse resultado e não aceita nova resposta.
        </div>
    <?php endif; ?>

    <div data-budget-decided-only data-for="decided" class="<?= $decidido ? '' : 'hidden' ?> mb-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800"><i class="fas fa-unlock-alt mr-2 text-corpBlue-500"></i> Nova resposta do cliente</h3>
        <p class="text-sm text-gray-600 mt-1">
            Apenas um administrador pode reabrir um orçamento decidido. A decisão atual continuará registrada no histórico abaixo
            (data, autor, origem e justificativa) mesmo após a reabertura.
        </p>
        <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <?php if ($ehAdmin): ?>
                <form action="<?= BASE_URL ?>/admin/reabrirOrcamento/<?= (int)$orcamento['id'] ?>" method="POST"
                      data-confirm="O orçamento voltará para &quot;Pendente&quot; e o mesmo link do cliente voltará a aceitar uma resposta. O registro da decisão anterior será mantido."
                      data-confirm-title="Reabrir orçamento?" data-confirm-button="Sim, reabrir" data-confirm-variant="primary"
                      data-confirm-reason="Justificativa da reabertura (obrigatória)" data-confirm-reason-placeholder="Ex.: cliente pediu para reavaliar após ajuste de valores...">
                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                    <button type="submit" data-no-loading class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-corpBlue-600 text-white text-sm font-semibold hover:bg-corpBlue-700">
                        <i class="fas fa-unlock mr-2"></i> Reabrir para nova resposta
                    </button>
                </form>
            <?php else: ?>
                <p class="text-sm text-gray-500"><i class="fas fa-user-shield mr-1"></i> Solicite a um administrador para reabrir este orçamento.</p>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>/admin/duplicarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST"
                  data-confirm="Será criado um novo orçamento pendente, com os mesmos dados e itens e um novo link de aprovação. Este orçamento permanece como está."
                  data-confirm-title="Criar nova versão?" data-confirm-button="Criar nova versão" data-confirm-variant="primary">
                <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                <button type="submit" data-no-loading class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 bg-white text-sm font-semibold hover:bg-gray-50">
                    <i class="fas fa-copy mr-2"></i> Criar novo orçamento (nova versão)
                </button>
            </form>
        </div>
    </div>

    <?php if ($pendente): ?>
        <div data-budget-pending-only class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-semibold text-gray-800">Decisão do orçamento</h3>
                <p class="text-sm text-gray-500">Registre a resposta do cliente. Após decidir, o orçamento não poderá ser alterado.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <form action="<?= BASE_URL ?>/admin/rejeitarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-budget-decision-form data-decision="rejeitar" data-budget-ref="<?= (int)$orcamento['id'] ?>" data-budget-title="<?= Security::esc($orcamento['titulo']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg border border-red-500 text-red-600 bg-white text-sm font-semibold hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <i class="fas fa-times mr-2"></i> Não aprovar
                    </button>
                </form>
                <form action="<?= BASE_URL ?>/admin/aprovarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-budget-decision-form data-decision="aprovar" data-budget-ref="<?= (int)$orcamento['id'] ?>" data-budget-title="<?= Security::esc($orcamento['titulo']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-check mr-2"></i> Aprovar orçamento
                    </button>
                </form>
            </div>
        </div>
    <?php elseif ($expirado): ?>
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <p class="text-sm text-gray-600"><i class="fas fa-hourglass-end mr-1"></i> Este orçamento expirou em <?= UI::date($orcamento['data_validade']) ?>. Reative para voltar a receber decisões.</p>
            <form action="<?= BASE_URL ?>/admin/reativarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-corpBlue-600 text-white text-sm font-semibold hover:bg-corpBlue-700">
                    <i class="fas fa-redo mr-2"></i> Reativar por 30 dias
                </button>
            </form>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/salvarEdicaoOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">

        <fieldset data-budget-lockable <?= $editavel ? '' : 'disabled' ?>>
            <legend class="sr-only">Dados do orçamento</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                    <input type="text" value="<?= Security::esc($orcamento['cliente_nome']) ?>" readonly class="<?= $inputClass ?> bg-gray-50 text-gray-500 cursor-not-allowed">
                </div>

                <div>
                    <label for="ticket_id" class="block text-sm font-medium text-gray-700 mb-1">Chamado vinculado (opcional)</label>
                    <select id="ticket_id" name="ticket_id" class="<?= $inputClass ?>">
                        <option value="">Nenhum chamado vinculado</option>
                        <?php foreach ($chamados_abertos as $chamado): ?>
                            <option value="<?= (int)$chamado['id'] ?>" <?= ((int)$orcamento['ticket_id'] === (int)$chamado['id']) ? 'selected' : '' ?>>
                                #<?= (int)$chamado['id'] ?> - <?= Security::esc($chamado['cliente_nome']) ?> (<?= Security::esc(ucfirst($chamado['status'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= Security::esc($orcamento['titulo']) ?>" required maxlength="150" class="<?= $inputClass ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição detalhada *</label>
                    <textarea id="descricao" name="descricao" rows="4" required class="<?= $inputClass ?>"><?= Security::esc($orcamento['descricao']) ?></textarea>
                </div>

                <div>
                    <label for="data_validade" class="block text-sm font-medium text-gray-700 mb-1">Validade</label>
                    <input type="date" id="data_validade" name="data_validade" value="<?= Security::esc($orcamento['data_validade']) ?>" class="<?= $inputClass ?>">
                </div>
                <div class="hidden md:block"></div>

                <div>
                    <label for="valor_pecas" class="block text-sm font-medium text-gray-700 mb-1">Valor de peças (R$)</label>
                    <input type="text" inputmode="decimal" id="valor_pecas" name="valor_pecas" value="<?= number_format($orcamento['valor_pecas'] ?? 0, 2, ',', '.') ?>" <?= $temItens ? 'readonly' : '' ?> class="<?= $inputClass ?> <?= $temItens ? 'bg-gray-50 text-gray-500' : '' ?>">
                </div>
                <div>
                    <label for="valor_mao_obra" class="block text-sm font-medium text-gray-700 mb-1">Valor de mão de obra (R$)</label>
                    <input type="text" inputmode="decimal" id="valor_mao_obra" name="valor_mao_obra" value="<?= number_format($orcamento['valor_mao_obra'] ?? 0, 2, ',', '.') ?>" <?= $temItens ? 'readonly' : '' ?> class="<?= $inputClass ?> <?= $temItens ? 'bg-gray-50 text-gray-500' : '' ?>">
                </div>
                <?php if ($temItens): ?>
                    <p class="md:col-span-2 text-xs text-gray-500"><i class="fas fa-info-circle"></i> Os valores são calculados automaticamente a partir dos itens detalhados abaixo.</p>
                <?php endif; ?>

                <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center">
                    <span class="font-medium text-corpBlue-800">Valor total</span>
                    <span class="text-2xl font-bold text-corpBlue-900" id="valor_total_display"><?= UI::money($orcamento['valor_total']) ?></span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="w-full sm:w-auto bg-corpBlue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-corpBlue-700 transition-colors text-sm font-semibold">
                    <i class="fas fa-save mr-1"></i> Salvar alterações
                </button>
            </div>
        </fieldset>
    </form>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" aria-labelledby="itens-titulo">
        <h3 id="itens-titulo" class="font-semibold text-gray-800 mb-4"><i class="fas fa-list mr-2 text-corpBlue-500"></i> Itens detalhados</h3>

        <?php if ($temItens): ?>
            <div class="overflow-x-auto mb-4 border border-gray-200 rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">Tipo</th>
                            <th class="px-3 py-2 text-left">Descrição</th>
                            <th class="px-3 py-2 text-center">Qtd.</th>
                            <th class="px-3 py-2 text-right">Unit.</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap"><?= Security::esc($tiposItem[$item['tipo']] ?? $item['tipo']) ?></td>
                                <td class="px-3 py-2"><?= Security::esc($item['descricao']) ?></td>
                                <td class="px-3 py-2 text-center"><?= (int)$item['quantidade'] ?></td>
                                <td class="px-3 py-2 text-right whitespace-nowrap"><?= UI::money($item['valor_unitario']) ?></td>
                                <td class="px-3 py-2 text-right whitespace-nowrap font-medium"><?= UI::money($item['subtotal']) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form action="<?= BASE_URL ?>/admin/removerItemOrcamento/<?= (int)$item['id'] ?>" method="POST" data-confirm="Remover o item &quot;<?= Security::esc($item['descricao']) ?>&quot; do orçamento?" data-confirm-title="Remover item?" data-confirm-button="Sim, remover">
                                        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                        <fieldset data-budget-lockable <?= $editavel ? '' : 'disabled' ?> class="contents">
                                            <button type="submit" data-no-loading class="text-red-500 hover:text-red-700 disabled:opacity-30 disabled:cursor-not-allowed" title="Remover item"><i class="fas fa-trash"></i></button>
                                        </fieldset>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 mb-4">Nenhum item detalhado. Use o formulário abaixo para discriminar peças, mão de obra e serviços; o total será calculado automaticamente.</p>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/admin/adicionarItemOrcamento" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
            <input type="hidden" name="budget_id" value="<?= (int)$orcamento['id'] ?>">
            <fieldset data-budget-lockable <?= $editavel ? '' : 'disabled' ?>>
                <legend class="sr-only">Adicionar item</legend>
                <div class="grid grid-cols-2 md:grid-cols-12 gap-3 items-end">
                    <div class="col-span-2 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1" for="item_tipo">Tipo</label>
                        <select id="item_tipo" name="tipo" class="<?= $inputClass ?>">
                            <?php foreach ($tiposItem as $valor => $rotulo): ?>
                                <option value="<?= $valor ?>"><?= $rotulo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1" for="item_descricao">Descrição</label>
                        <input type="text" id="item_descricao" name="descricao" required maxlength="255" class="<?= $inputClass ?>">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1" for="item_qtd">Qtd.</label>
                        <input type="number" id="item_qtd" name="quantidade" value="1" min="1" class="<?= $inputClass ?>">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1" for="item_valor">Valor unitário (R$)</label>
                        <input type="text" inputmode="decimal" id="item_valor" name="valor_unitario" required placeholder="0,00" class="<?= $inputClass ?>">
                    </div>
                    <div class="col-span-2 md:col-span-2">
                        <button type="submit" class="w-full bg-gray-800 text-white px-3 py-2 rounded-lg text-sm font-semibold hover:bg-gray-900 disabled:opacity-40">
                            <i class="fas fa-plus mr-1"></i> Adicionar
                        </button>
                    </div>
                </div>
            </fieldset>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" aria-labelledby="acoes-titulo">
        <h3 id="acoes-titulo" class="font-semibold text-gray-800 mb-4"><i class="fas fa-paper-plane mr-2 text-corpBlue-500"></i> Compartilhar e imprimir</h3>
        <div class="flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/admin/imprimirOrcamentoNovo/<?= (int)$orcamento['id'] ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-print mr-2"></i> Imprimir / PDF
            </a>
            <?php if (!empty($orcamento['cliente_telefone'])): ?>
                <a href="<?= BASE_URL ?>/admin/enviarWhatsAppOrcamento/<?= (int)$orcamento['id'] ?>" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 rounded-lg border border-green-600 text-sm font-medium text-green-700 hover:bg-green-50">
                    <i class="fab fa-whatsapp mr-2"></i> Enviar por WhatsApp
                </a>
            <?php endif; ?>
            <?php if (!empty($orcamento['cliente_email'])): ?>
                <form action="<?= BASE_URL ?>/admin/enviarEmailOrcamento/<?= (int)$orcamento['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-envelope mr-2"></i> Enviar por e-mail
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!empty($podeExcluir) && !$decidido): ?>
                <form action="<?= BASE_URL ?>/admin/excluirOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-hide-when-approved
                      data-confirm="O orçamento #<?= (int)$orcamento['id'] ?> será excluído permanentemente." data-confirm-title="Excluir orçamento?" data-confirm-button="Sim, excluir">
                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                    <button type="submit" data-no-loading class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-sm font-medium text-red-600 hover:bg-red-50">
                        <i class="fas fa-trash mr-2"></i> Excluir
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>

<section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6" aria-labelledby="historico-titulo">
    <h3 id="historico-titulo" class="font-semibold text-gray-800 mb-1"><i class="fas fa-history mr-2 text-corpBlue-500"></i> Histórico e auditoria</h3>
    <p class="text-xs text-gray-500 mb-4">Registro permanente (não é apagado ao reabrir). Contém data/hora, autor, origem, IP e justificativa, conforme a <a class="underline" href="<?= BASE_URL ?>/auth/privacidade" target="_blank" rel="noopener">Política de Privacidade</a>.</p>
    <?php if (empty($historico)): ?>
        <p class="text-sm text-gray-500">Nenhum evento registrado ainda.</p>
    <?php else: ?>
        <ol class="relative border-l border-gray-200 ml-2 space-y-5">
            <?php foreach ($historico as $evento): $info = UI::historyAction($evento['acao']); ?>
                <li class="ml-5">
                    <span class="absolute -left-[9px] flex h-[18px] w-[18px] items-center justify-center rounded-full bg-white"><i class="fas <?= $info['icon'] ?> <?= $info['color'] ?> text-sm"></i></span>
                    <p class="text-sm font-semibold text-gray-800"><?= Security::esc($info['label']) ?>
                        <span class="font-normal text-gray-500">- <?= UI::date($evento['created_at'], true) ?></span>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?= Security::esc(UI::historyOrigin($evento['origem'])) ?>
                        <?php if (!empty($evento['usuario_nome'])): ?> &middot; <?= Security::esc($evento['usuario_nome']) ?><?php endif; ?>
                        <?php if (!empty($evento['ip_address'])): ?> &middot; IP <?= Security::esc($evento['ip_address']) ?><?php endif; ?>
                    </p>
                    <?php if (!empty($evento['motivo'])): ?>
                        <p class="text-sm text-gray-700 mt-1 break-words"><i class="fas fa-comment-dots text-gray-400 mr-1"></i><?= Security::esc($evento['motivo']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
</div>

<script>
document.addEventListener('budget:decided', function () {
    setTimeout(function () { window.location.reload(); }, 1200);
});
(function () {
    function parseMoney(value) {
        value = String(value || '').trim();
        if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
        var n = parseFloat(value.replace(/[^0-9.\-]/g, ''));
        return isNaN(n) ? 0 : n;
    }
    var pecas = document.getElementById('valor_pecas');
    var mao = document.getElementById('valor_mao_obra');
    var display = document.getElementById('valor_total_display');
    <?php if (!$temItens): ?>
    function update() {
        var total = parseMoney(pecas.value) + parseMoney(mao.value);
        display.textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    pecas.addEventListener('input', update);
    mao.addEventListener('input', update);
    <?php endif; ?>
})();
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
