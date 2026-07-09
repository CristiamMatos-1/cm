<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Gestão de Contratos e Notas Fiscais</h2>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/admin/novoContrato" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
            <i class="fas fa-file-signature mr-1"></i> Novo Contrato
        </a>
        <a href="<?= BASE_URL ?>/admin/novaNota" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition-colors text-sm font-medium">
            <i class="fas fa-upload mr-1"></i> Enviar NF
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Lista de Contratos -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-file-contract mr-2 text-indigo-500"></i> Contratos de SLA</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($contratos)): ?>
                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhum contrato cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contratos as $c): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($c['cliente_nome']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">R$ <?= number_format($c['valor_mensal'], 2, ',', '.') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= date('d/m/Y', strtotime($c['data_validade'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if(!empty($c['cliente_telefone'])): ?>
                                <?php 
                                    $zap = preg_replace('/\D/', '', $c['cliente_telefone']);
                                    $msg = urlencode("Olá " . $c['cliente_nome'] . "! Informamos que seu contrato #" . $c['id'] . " conosco encontra-se " . ($c['status'] === 'ativo' ? 'Ativo' : 'Inativo') . ". O valor mensal é de R$ " . number_format($c['valor_mensal'], 2, ',', '.') . " com validade até " . date('d/m/Y', strtotime($c['data_validade'])) . ".");
                                ?>
                                <a href="https://wa.me/55<?= $zap ?>?text=<?= $msg ?>" target="_blank" class="text-green-600 hover:text-green-900 mr-3" title="Notificar via WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/admin/editarContrato/<?= $c['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                        </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lista de Notas Fiscais -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Notas Fiscais Emitidas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($notas)): ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhuma nota fiscal anexada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notas as $n): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= htmlspecialchars($n['numero_nf']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($n['cliente_nome']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= date('d/m/Y', strtotime($n['data_emissao'])) ?></td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="<?= BASE_URL . '/' . htmlspecialchars($n['arquivo_url']) ?>" target="_blank" class="text-corpBlue-600 hover:text-corpBlue-900 mr-3">
                                    <i class="fas fa-file-pdf"></i> Visualizar
                                </a>
                                <a href="<?= BASE_URL ?>/admin/editarNota/<?= $n['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
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