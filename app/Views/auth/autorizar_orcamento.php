<?php
use app\Helpers\UI;
use app\Helpers\Security;

$pendente = $budget['status'] === 'pendente';
$decisionText = UI::budgetDecisionText($budget);
$expirado = $budget['status'] === 'expirado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Orçamento #<?= (int)$budget['id'] ?> - Autorização</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { corpBlue: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' } } } } };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen py-6 sm:py-10 px-4 text-gray-800">
    <div class="w-full max-w-2xl mx-auto">
        <div data-budget-card data-budget-id="<?= (int)$budget['id'] ?>" data-status="<?= Security::esc($budget['status']) ?>" class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-corpBlue-700 to-indigo-600 px-6 py-6 sm:py-8">
                <p class="text-blue-100 text-sm font-medium uppercase tracking-wide">Orçamento #<?= (int)$budget['id'] ?></p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mt-1 break-words"><?= Security::esc($budget['titulo']) ?></h1>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span data-budget-status-badge><?= UI::budgetStatusBadge($budget['status']) ?></span>
                    <span data-budget-decision-text class="text-xs text-blue-100 <?= $decisionText === '' ? 'hidden' : '' ?>"><?= Security::esc($decisionText) ?></span>
                </div>
            </div>

            <div class="p-5 sm:p-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Cliente</p>
                        <p class="text-base font-semibold text-gray-800"><?= Security::esc($budget['cliente_nome']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Data de emissão</p>
                        <p class="text-base font-semibold text-gray-800"><?= UI::date($budget['created_at']) ?></p>
                    </div>
                </div>

                <?php if (!empty($budget['descricao'])): ?>
                    <div class="p-4 bg-gray-50 rounded-lg border-l-4 border-corpBlue-500">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Descrição</p>
                        <p class="text-gray-800 text-sm break-words"><?= nl2br(Security::esc($budget['descricao'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($items)): ?>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-center">Qtd.</th>
                                    <th class="px-3 py-2 text-right">Unit.</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-3 py-2"><?= Security::esc($item['descricao']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= (int)$item['quantidade'] ?></td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap"><?= UI::money($item['valor_unitario']) ?></td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium"><?= UI::money($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="space-y-2 pt-2 border-t border-gray-200">
                    <?php if ($budget['valor_pecas'] > 0): ?>
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Peças</span><span class="font-semibold"><?= UI::money($budget['valor_pecas']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($budget['valor_mao_obra'] > 0): ?>
                        <div class="flex justify-between text-sm"><span class="text-gray-600">Mão de obra</span><span class="font-semibold"><?= UI::money($budget['valor_mao_obra']) ?></span></div>
                    <?php endif; ?>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                        <span class="font-bold text-gray-800">VALOR TOTAL</span>
                        <span class="text-2xl font-bold text-corpBlue-700"><?= UI::money($budget['valor_total']) ?></span>
                    </div>
                </div>

                <?php if (!empty($budget['data_validade'])): ?>
                    <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded text-sm text-yellow-800">
                        <strong>Válido até:</strong> <?= UI::date($budget['data_validade']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($pendente): ?>
                    <div data-budget-pending-only class="flex flex-col sm:flex-row gap-3 pt-2">
                        <form class="flex-1" action="<?= BASE_URL ?>/auth/aprovarOrcamento" method="POST" data-budget-decision-form data-decision="aprovar" data-budget-ref="<?= (int)$budget['id'] ?>" data-budget-title="<?= Security::esc($budget['titulo']) ?>">
                            <input type="hidden" name="token" value="<?= Security::esc($budget['token_autorizacao']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-check mr-2"></i> Aprovar Orçamento
                            </button>
                        </form>
                        <form class="flex-1" action="<?= BASE_URL ?>/auth/rejeitarOrcamento" method="POST" data-budget-decision-form data-decision="rejeitar" data-budget-ref="<?= (int)$budget['id'] ?>" data-budget-title="<?= Security::esc($budget['titulo']) ?>">
                            <input type="hidden" name="token" value="<?= Security::esc($budget['token_autorizacao']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                            <button type="submit" class="w-full bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 font-bold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fas fa-times mr-2"></i> Rejeitar Orçamento
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div data-budget-decided-only data-for="decided" class="<?= in_array($budget['status'], ['aprovado', 'rejeitado'], true) ? '' : 'hidden' ?> p-4 rounded-xl text-sm text-center bg-blue-50 text-blue-800">
                    <i class="fas fa-check-circle mr-1"></i> Sua resposta foi registrada e não pode mais ser alterada. Obrigado!
                </div>
                <div data-budget-decided-only data-for="expirado" class="<?= $expirado ? '' : 'hidden' ?> p-4 rounded-xl text-sm text-center bg-gray-50 text-gray-700">
                    <i class="fas fa-hourglass-end mr-1"></i> Este orçamento expirou. Entre em contato com a empresa para solicitar a reativação.
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 text-center text-xs text-gray-500">
                Sua resposta é registrada com data e hora e comunicada automaticamente ao responsável.
            </div>
        </div>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 left-4 sm:left-auto sm:w-96 z-[100] space-y-2 pointer-events-none" aria-live="polite"></div>
    <script src="<?= BASE_URL ?>/assets/js/ui.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/budgets.js"></script>
</body>
</html>
