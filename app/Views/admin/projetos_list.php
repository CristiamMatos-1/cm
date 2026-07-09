<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Projetos de Software</h2>
    <a href="<?= BASE_URL ?>/admin/novoProjeto" class="bg-corpBlue-600 text-white px-4 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> Criar Novo Projeto
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projeto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engenheiro Responsável</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php if (empty($projetos)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum projeto de software registrado.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($projetos as $p): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($p['nome_projeto']) ?></div>
                            <div class="text-xs text-gray-500 truncate max-w-xs mt-1"><?= htmlspecialchars($p['descricao']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($p['cliente_nome']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= $p['engenheiro_id'] ? htmlspecialchars($p['engenheiro_nome']) : '<span class="text-red-500 text-xs">Não Atribuído</span>' ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                    if ($p['status'] === 'planejamento') echo 'bg-gray-100 text-gray-800';
                                    elseif ($p['status'] === 'desenvolvimento') echo 'bg-blue-100 text-blue-800';
                                    elseif ($p['status'] === 'testes' || $p['status'] === 'homologacao') echo 'bg-yellow-100 text-yellow-800';
                                    elseif ($p['status'] === 'producao') echo 'bg-indigo-100 text-indigo-800';
                                    else echo 'bg-green-100 text-green-800';
                                ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="<?= BASE_URL ?>/admin/editarProjeto/<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Gerenciar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
