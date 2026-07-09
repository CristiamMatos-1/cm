<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Todos os Chamados</h2>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serviço</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Técnico</th>
                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($chamados)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-3 text-green-300"></i>
                        <p>Não há chamados registrados.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($chamados as $chamado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">#<?= htmlspecialchars($chamado['id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($chamado['cliente_nome']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($chamado['tipo_servico']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $chamado['status'] === 'aberto' ? 'bg-yellow-100 text-yellow-800' : ($chamado['status'] === 'finalizado' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($chamado['status']))) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= empty($chamado['tecnico_id']) ? '<span class="text-red-500 font-medium">Não Atribuído</span>' : htmlspecialchars($chamado['tecnico_nome'] ?? 'Atribuído') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if(!empty($chamado['cliente_telefone'])): ?>
                                <?php 
                                    $zap = preg_replace('/\D/', '', $chamado['cliente_telefone']);
                                    $msg = urlencode("Olá " . $chamado['cliente_nome'] . "! Seu chamado #" . $chamado['id'] . " está com o status: " . ucfirst(str_replace('_', ' ', $chamado['status'])) . ". " . (!empty($chamado['valor_servico']) && $chamado['valor_servico'] > 0 ? "O valor ficou em R$ " . number_format($chamado['valor_servico'], 2, ',', '.') : ""));
                                ?>
                                <a href="https://wa.me/55<?= $zap ?>?text=<?= $msg ?>" target="_blank" class="text-green-600 hover:text-green-900 mr-3" title="Notificar via WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/admin/editarChamado/<?= $chamado['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i> Editar</a>
                            <a href="<?= BASE_URL ?>/admin/excluirChamado/<?= $chamado['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza que deseja excluir este chamado?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
