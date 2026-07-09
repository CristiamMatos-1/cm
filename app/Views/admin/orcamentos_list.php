<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Orçamentos Enviados</h2>
    <a href="<?= BASE_URL ?>/admin/novoOrcamento" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> Criar Orçamento
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título / Ref.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orcamentos)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-hand-holding-usd text-4xl mb-3 text-gray-300"></i>
                        <p>Nenhum orçamento registrado.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($orcamentos as $orcamento): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">#<?= htmlspecialchars($orcamento['id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($orcamento['cliente_nome']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium"><?= htmlspecialchars($orcamento['titulo']) ?></div>
                            <div class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($orcamento['descricao']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">R$ <?= number_format($orcamento['valor'], 2, ',', '.') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $orcamento['ticket_id'] ? '<a href="'.BASE_URL.'/admin/chamados" class="text-corpBlue-600 hover:underline">#'.$orcamento['ticket_id'].'</a>' : 'N/A' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                    if ($orcamento['status'] === 'pendente') echo 'bg-yellow-100 text-yellow-800';
                                    elseif ($orcamento['status'] === 'aprovado') echo 'bg-green-100 text-green-800';
                                    elseif ($orcamento['status'] === 'rejeitado') echo 'bg-red-100 text-red-800';
                                    else echo 'bg-blue-100 text-blue-800';
                                ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($orcamento['status']))) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if(!empty($orcamento['cliente_telefone'])): ?>
                                <?php 
                                    $zap = preg_replace('/\D/', '', $orcamento['cliente_telefone']);
                                    $msg = urlencode("Olá " . $orcamento['cliente_nome'] . "! Seu orçamento #" . $orcamento['id'] . " ('" . $orcamento['titulo'] . "') está com o status: " . ucfirst(str_replace('_', ' ', $orcamento['status'])) . ". O valor total estimado é de R$ " . number_format($orcamento['valor'], 2, ',', '.') . ".");
                                ?>
                                <a href="https://wa.me/55<?= $zap ?>?text=<?= $msg ?>" target="_blank" class="text-green-600 hover:text-green-900 mr-3" title="Notificar via WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/admin/editarOrcamento/<?= $orcamento['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i> Editar</a>
                            <a href="<?= BASE_URL ?>/admin/excluirOrcamento/<?= $orcamento['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza que deseja excluir permanentemente este orçamento?');"><i class="fas fa-trash"></i> Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>