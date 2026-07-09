<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Gestão de Funcionários / Permissões</h2>
    <a href="<?= BASE_URL ?>/admin/novoUsuario" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
        <i class="fas fa-user-plus mr-1"></i> Cadastrar Novo Usuário
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Funcionário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perfil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum funcionário encontrado.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($u['nome']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($u['cpf_cnpj']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div><?= htmlspecialchars($u['email']) ?></div>
                            <div><?= htmlspecialchars($u['telefone']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-bold
                                <?= $u['perfil'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= ucfirst($u['perfil']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="<?= BASE_URL ?>/admin/editarUsuario/<?= $u['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-user-edit mr-1"></i> Editar Perfil
                            </a>
                            <a href="<?= BASE_URL ?>/admin/excluirUsuario/<?= $u['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza que deseja excluir este funcionário? Isso pode afetar chamados vinculados a ele.');">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
