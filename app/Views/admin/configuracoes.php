<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Configurações e Patrimônio</h2>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/admin/novoAtivo" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
            <i class="fas fa-desktop mr-1"></i> Cadastrar Ativo
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Dados da Empresa -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-building mr-2 text-corpBlue-500"></i> Dados da Minha Empresa</h3>
        </div>
        <div class="p-6">
            <form action="<?= BASE_URL ?>/admin/salvarEmpresa" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                    <input type="text" name="razao_social" value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                        <input type="text" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="matriz_filial" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none">
                            <option value="matriz" <?= ($empresa['matriz_filial'] ?? '') === 'matriz' ? 'selected' : '' ?>>Matriz</option>
                            <option value="filial" <?= ($empresa['matriz_filial'] ?? '') === 'filial' ? 'selected' : '' ?>>Filial</option>
                            <option value="parceira" <?= ($empresa['matriz_filial'] ?? '') === 'parceira' ? 'selected' : '' ?>>Parceira</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-corpBlue-600 text-white py-2 px-4 rounded hover:bg-corpBlue-700 text-sm font-medium">Salvar Empresa</button>
            </form>
        </div>
    </div>

    <!-- Fornecedores -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800"><i class="fas fa-truck mr-2 text-green-500"></i> Cadastrar Fornecedor</h3>
        </div>
        <div class="p-6">
            <form action="<?= BASE_URL ?>/admin/salvarFornecedor" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Fornecedor</label>
                        <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                        <input type="text" name="cnpj" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-sm font-medium">Adicionar Fornecedor</button>
            </form>
        </div>
    </div>
</div>

<!-- Tabela de Ativos -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="font-bold text-gray-800"><i class="fas fa-server mr-2 text-indigo-500"></i> Gestão de Patrimônio e Ativos</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipamento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Série</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proprietário (Cliente)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Garantia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($ativos)): ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum equipamento cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($ativos as $a): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($a['nome_equipamento']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($a['numero_serie']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= empty($a['cliente_id']) ? '<span class="text-corpBlue-600 font-medium">Patrimônio Próprio</span>' : htmlspecialchars($a['cliente_nome']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($a['fornecedor_nome'] ?? '-') ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php
                            $compra = strtotime($a['data_compra']);
                            $vencimento = strtotime("+" . $a['garantia_meses'] . " months", $compra);
                            if (time() > $vencimento) {
                                echo '<span class="text-red-500 text-xs font-semibold">Expirada</span>';
                            } else {
                                echo '<span class="text-green-600 text-xs font-semibold">Até ' . date('m/Y', $vencimento) . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>