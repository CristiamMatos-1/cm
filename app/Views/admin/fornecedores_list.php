<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-truck mr-2 text-corpBlue-600"></i> Fornecedores
    </h2>
    <a href="<?= BASE_URL ?>/admin/novoFornecedor"
       class="inline-flex items-center gap-2 bg-corpBlue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-corpBlue-700 transition-colors">
        <i class="fas fa-plus"></i> Novo Fornecedor
    </a>
</div>

<!-- Barra de Busca -->
<form method="GET" action="" class="mb-6">
    <div class="flex gap-2">
        <input type="hidden" name="url" value="admin/fornecedores">
        <input type="text" name="busca" value="<?= htmlspecialchars($busca ?? '') ?>"
               placeholder="Buscar por Razão Social, Nome Fantasia ou CNPJ..."
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
        <button type="submit"
                class="bg-gray-700 text-white px-5 py-2 rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-search mr-1"></i> Buscar
        </button>
        <?php if (!empty($busca)): ?>
        <a href="<?= BASE_URL ?>/admin/fornecedores"
           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-times"></i>
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Notificações -->
<?php if (isset($_SESSION['success'])): ?>
<div class="mb-4 bg-green-50 border border-green-300 text-green-800 rounded-lg px-4 py-3 flex items-center gap-2">
    <i class="fas fa-check-circle text-green-600"></i>
    <?= htmlspecialchars($_SESSION['success']) ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="mb-4 bg-red-50 border border-red-300 text-red-800 rounded-lg px-4 py-3 flex items-center gap-2">
    <i class="fas fa-exclamation-circle text-red-600"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Tabela -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
    <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
        <p class="text-sm text-gray-600">
            <strong><?= count($fornecedores) ?></strong> fornecedor(es) encontrado(s)
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Razão Social / Fantasia</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">CNPJ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Cidade/UF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">WhatsApp</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">CRT</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                <?php if (empty($fornecedores)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-truck text-4xl mb-3 block text-gray-200"></i>
                        Nenhum fornecedor cadastrado ainda.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($fornecedores as $f): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-4">
                        <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($f['razao_social']) ?></p>
                        <?php if (!empty($f['nome_fantasia'])): ?>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($f['nome_fantasia']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-sm font-mono text-gray-700">
                        <?= htmlspecialchars($f['cnpj'] ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $f['cnpj']) : '-') ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600 hidden md:table-cell">
                        <?= htmlspecialchars($f['cidade'] ?? '-') ?><?= !empty($f['uf']) ? '/' . $f['uf'] : '' ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600 hidden lg:table-cell">
                        <?php if (!empty($f['whatsapp'])): ?>
                        <a href="https://wa.me/55<?= preg_replace('/\D/', '', $f['whatsapp']) ?>" target="_blank"
                           class="text-green-600 hover:text-green-800">
                            <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars($f['whatsapp']) ?>
                        </a>
                        <?php else: ?> — <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600 hidden lg:table-cell">
                        <?php
                            $crt = ['1' => 'Simples Nacional', '2' => 'Simples Excesso', '3' => 'Lucro Presumido/Real'];
                            echo $crt[$f['crt'] ?? ''] ?? '-';
                        ?>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex gap-2 justify-end">
                            <a href="<?= BASE_URL ?>/admin/editarFornecedor/<?= $f['id'] ?>"
                               class="inline-flex items-center text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </a>
                            <a href="<?= BASE_URL ?>/admin/excluirFornecedor/<?= $f['id'] ?>"
                               onclick="return confirm('Confirma exclusão de <?= htmlspecialchars(addslashes($f['razao_social'])) ?>?')"
                               class="inline-flex items-center text-xs bg-red-50 text-red-700 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-trash mr-1"></i> Excluir
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
