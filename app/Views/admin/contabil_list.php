<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Financeiro Contábil</h2>
</div>

<!-- Resumo do Balanço -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
        <p class="text-sm text-gray-500 font-medium">Total de Receitas (Pagas)</p>
        <p class="text-2xl font-bold text-green-600">R$ <?= number_format($balanco['receitas'], 2, ',', '.') ?></p>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
        <p class="text-sm text-gray-500 font-medium">Total de Despesas (Pagas)</p>
        <p class="text-2xl font-bold text-red-600">R$ <?= number_format($balanco['despesas'], 2, ',', '.') ?></p>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 <?= $balanco['saldo'] >= 0 ? 'border-blue-500' : 'border-red-500' ?>">
        <p class="text-sm text-gray-500 font-medium">Saldo Geral (Lucro/Prejuízo)</p>
        <p class="text-2xl font-bold <?= $balanco['saldo'] >= 0 ? 'text-blue-600' : 'text-red-600' ?>">R$ <?= number_format($balanco['saldo'], 2, ',', '.') ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Formulário Rápido de Lançamento -->
    <div class="bg-white rounded-lg shadow-sm p-6 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Novo Lançamento Manual</h3>
        <form action="<?= BASE_URL ?>/admin/novoLancamentoContabil" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 font-bold">
                        <option value="receita" class="text-green-600">Receita (Entrada)</option>
                        <option value="despesa" class="text-red-600">Despesa (Saída)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                    <input type="text" name="descricao" required placeholder="Ex: Pagamento Hospedagem Servidor" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                    <input type="text" name="valor" required placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                    <input type="date" name="data_vencimento" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Atual</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="pendente">Pendente (Não Pago)</option>
                        <option value="pago">Pago / Recebido</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-corpBlue-600 text-white px-4 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors font-medium mt-2">
                    Registrar Lançamento
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Lançamentos -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Histórico de Lançamentos</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($lancamentos)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhum lançamento registrado.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($lancamentos as $lanc): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($lanc['descricao']) ?></div>
                                <?php if($lanc['ticket_id']): ?>
                                    <div class="text-xs text-gray-500">Ref. Chamado #<?= $lanc['ticket_id'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm font-bold <?= $lanc['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $lanc['tipo'] === 'receita' ? '+' : '-' ?> R$ <?= number_format($lanc['valor'], 2, ',', '.') ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?= date('d/m/Y', strtotime($lanc['data_vencimento'])) ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <form action="<?= BASE_URL ?>/admin/alterarStatusLancamento/<?= $lanc['id'] ?>" method="POST" class="flex items-center">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs border-gray-300 rounded focus:ring-corpBlue-500 focus:border-corpBlue-500 <?= $lanc['status'] === 'pago' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' ?>">
                                        <option value="pendente" <?= $lanc['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="pago" <?= $lanc['status'] === 'pago' ? 'selected' : '' ?>>Pago/Recebido</option>
                                        <option value="cancelado" <?= $lanc['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
