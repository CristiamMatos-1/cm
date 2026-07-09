<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Meus Clientes</h2>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPF/CNPJ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($c['nome']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                        <?= htmlspecialchars($c['cpf_cnpj']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <div><?= htmlspecialchars($c['email']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="<?= BASE_URL ?>/admin/editarCliente/<?= $c['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
