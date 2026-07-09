<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Meus Contratos e Notas Fiscais</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Lista de Contratos do Cliente -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border-t-4 border-corpBlue-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-800"><i class="fas fa-file-contract mr-2 text-corpBlue-500"></i> Meus Contratos Ativos</h3>
        </div>
        <div class="p-6">
            <?php if (empty($contratos)): ?>
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-folder-open text-3xl mb-2 text-gray-300"></i>
                    <p>Você ainda não possui contratos registrados.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($contratos as $c): ?>
                    <div class="border rounded-lg p-4 hover:border-corpBlue-500 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-gray-800">Contrato #<?= htmlspecialchars($c['id']) ?></h4>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">Ativo</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-1"><strong>Valor Mensal:</strong> R$ <?= number_format($c['valor_mensal'], 2, ',', '.') ?></p>
                        <p class="text-sm text-gray-600 mb-1"><strong>Validade:</strong> <?= date('d/m/Y', strtotime($c['data_validade'])) ?></p>
                        <p class="text-sm text-gray-600"><strong>Renovação:</strong> a cada <?= $c['prazo_renovacao_anos'] ?> ano(s)</p>
                        
                        <?php if (!empty($c['conteudo_sla'])): ?>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-500 font-medium mb-1">Termos (SLA):</p>
                            <p class="text-xs text-gray-600 italic bg-gray-50 p-2 rounded"><?= nl2br(htmlspecialchars($c['conteudo_sla'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de Notas Fiscais do Cliente -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border-t-4 border-green-500">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-800"><i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Minhas Notas Fiscais</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php if (empty($notas)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhuma nota fiscal disponível para download.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notas as $n): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= htmlspecialchars($n['numero_nf']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= date('d/m/Y', strtotime($n['data_emissao'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">R$ <?= number_format($n['valor'], 2, ',', '.') ?></td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="<?= BASE_URL ?>/<?= $n['arquivo_url'] ?>" target="_blank" class="inline-flex items-center text-corpBlue-600 hover:text-corpBlue-900 bg-blue-50 px-3 py-1 rounded transition-colors">
                                    <i class="fas fa-download mr-1"></i> Baixar
                                </a>
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