<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-corpBlue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Meus Chamados</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($totalChamados) ?></p>
            </div>
            <div class="p-3 bg-blue-50 text-corpBlue-600 rounded-full">
                <i class="fas fa-ticket-alt text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Contratos Ativos</p>
                <p class="text-3xl font-bold text-gray-800">0</p>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-full">
                <i class="fas fa-file-signature text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Seção Principal -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">Últimos Chamados</h2>
        <a href="<?= BASE_URL ?>/client/chamados" class="text-sm text-corpBlue-600 hover:text-corpBlue-800 font-medium">Ver todos</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Serviço</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 bg-gray-50"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($ultimosChamados)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum chamado encontrado.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($ultimosChamados as $chamado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?= htmlspecialchars($chamado['id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($chamado['tipo_servico']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $chamado['status'] === 'aberto' ? 'bg-yellow-100 text-yellow-800' : ($chamado['status'] === 'andamento' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                <?= ucfirst(htmlspecialchars($chamado['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($chamado['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="<?= BASE_URL ?>/client/verChamado/<?= $chamado['id'] ?>" class="text-corpBlue-600 hover:text-corpBlue-900">Detalhes</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>