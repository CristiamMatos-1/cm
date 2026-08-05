<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Perfil do Cliente</h2>
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>/admin/editarCliente/<?= $cliente['id'] ?>" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition-colors">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/clientes" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Informações Básicas -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Informações Básicas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nome/Razão Social</label>
                        <p class="text-gray-900 font-medium"><?= htmlspecialchars($cliente['nome']) ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600">CPF/CNPJ</label>
                        <p class="text-gray-900 font-medium font-mono"><?= htmlspecialchars($cliente['cpf_cnpj']) ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600">E-mail</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cliente['email'] ?? '-') ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600">Telefone/WhatsApp</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cliente['telefone'] ?? '-') ?></p>
                    </div>

                    <?php if (!empty($cliente['responsavel_nome'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Responsável (Empresa)</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cliente['responsavel_nome']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="my-4 border-gray-200">

                <h4 class="font-bold text-gray-700 mb-3">Endereço</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <?php if (!empty($cliente['logradouro'])): ?>
                        <div>
                            <p><?= htmlspecialchars($cliente['logradouro']) ?>, <?= htmlspecialchars($cliente['numero'] ?? '') ?></p>
                            <?php if (!empty($cliente['complemento'])): ?>
                                <p><?= htmlspecialchars($cliente['complemento']) ?></p>
                            <?php endif; ?>
                            <p><?= htmlspecialchars($cliente['bairro'] ?? '') ?>, <?= htmlspecialchars($cliente['cidade'] ?? '') ?> - <?= htmlspecialchars($cliente['estado'] ?? '') ?></p>
                            <p class="font-mono"><?= htmlspecialchars($cliente['cep'] ?? '') ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Nenhum endereço cadastrado</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Resumo</h3>
            
            <div class="space-y-4">
                <div class="text-center p-3 bg-blue-50 rounded">
                    <p class="text-2xl font-bold text-blue-600"><?= count($contratos) ?></p>
                    <p class="text-sm text-gray-600">Contratos</p>
                </div>

                <div class="text-center p-3 bg-green-50 rounded">
                    <p class="text-2xl font-bold text-green-600"><?= count($orcamentos) ?></p>
                    <p class="text-sm text-gray-600">Orçamentos</p>
                </div>

                <div class="text-center p-3 bg-orange-50 rounded">
                    <p class="text-2xl font-bold text-orange-600"><?= count($chamados) ?></p>
                    <p class="text-sm text-gray-600">Chamados</p>
                </div>

                <div class="text-center p-3 bg-purple-50 rounded">
                    <p class="text-2xl font-bold text-purple-600"><?= count($servicos) ?></p>
                    <p class="text-sm text-gray-600">Serviços Avulsos</p>
                </div>

                <div class="text-center p-3 bg-yellow-50 rounded">
                    <p class="text-2xl font-bold text-yellow-600"><?= count($notas) ?></p>
                    <p class="text-sm text-gray-600">Notas/Documentos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Anotações -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Anotações Visíveis para o Cliente -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3 text-green-700">
                <i class="fas fa-eye mr-2"></i> Anotações Visíveis para o Cliente
            </h3>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded min-h-24 max-h-48 overflow-y-auto">
                <?php if (!empty($cliente['anotacoes_visivel'])): ?>
                    <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($cliente['anotacoes_visivel']) ?></p>
                <?php else: ?>
                    <p class="text-gray-400 italic">Nenhuma anotação visível</p>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-2">O cliente pode visualizar estas anotações em sua conta</p>
        </div>

        <!-- Anotações Internas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3 text-yellow-700">
                <i class="fas fa-lock mr-2"></i> Anotações Internas (Apenas Sua Empresa)
            </h3>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded min-h-24 max-h-48 overflow-y-auto">
                <?php if (!empty($cliente['anotacoes_interna'])): ?>
                    <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($cliente['anotacoes_interna']) ?></p>
                <?php else: ?>
                    <p class="text-gray-400 italic">Nenhuma anotação interna</p>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-2">Anotações confidenciais - somente sua empresa vê</p>
        </div>
    </div>

    <!-- Tabs para Documentos -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="border-b border-gray-200">
            <div class="flex overflow-x-auto">
                <button onclick="mostrarTab('contratos')" class="tab-btn flex-1 py-3 px-4 text-center font-medium text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-300 active">
                    <i class="fas fa-file-contract mr-2"></i> Contratos (<?= count($contratos) ?>)
                </button>
                <button onclick="mostrarTab('orcamentos')" class="tab-btn flex-1 py-3 px-4 text-center font-medium text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-300">
                    <i class="fas fa-quote-left mr-2"></i> Orçamentos (<?= count($orcamentos) ?>)
                </button>
                <button onclick="mostrarTab('chamados')" class="tab-btn flex-1 py-3 px-4 text-center font-medium text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-300">
                    <i class="fas fa-headset mr-2"></i> Chamados (<?= count($chamados) ?>)
                </button>
                <button onclick="mostrarTab('servicos')" class="tab-btn flex-1 py-3 px-4 text-center font-medium text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-300">
                    <i class="fas fa-tools mr-2"></i> Serviços (<?= count($servicos) ?>)
                </button>
                <button onclick="mostrarTab('notas')" class="tab-btn flex-1 py-3 px-4 text-center font-medium text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-300">
                    <i class="fas fa-file-alt mr-2"></i> Notas (<?= count($notas) ?>)
                </button>
            </div>
        </div>

        <div class="p-6">
            <!-- Contratos Tab -->
            <div id="contratos" class="tab-content">
                <?php if (count($contratos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php foreach ($contratos as $c): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?= $c['id'] ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($c['numero_contrato'] ?? '') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($c['data_contrato'] ?? 'now')) ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($c['status'] ?? 'Ativo') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic text-center py-6">Nenhum contrato cadastrado</p>
                <?php endif; ?>
            </div>

            <!-- Orçamentos Tab -->
            <div id="orcamentos" class="tab-content hidden">
                <?php if (count($orcamentos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php foreach ($orcamentos as $o): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?= $o['id'] ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars(substr($o['titulo'] ?? '', 0, 40)) ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">R$ <?= number_format($o['valor_total'] ?? $o['valor'] ?? 0, 2, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-<?= $o['status'] == 'aprovado' ? 'green' : ($o['status'] == 'rejeitado' ? 'red' : 'yellow') ?>-100 text-<?= $o['status'] == 'aprovado' ? 'green' : ($o['status'] == 'rejeitado' ? 'red' : 'yellow') ?>-800">
                                            <?= htmlspecialchars($o['status'] ?? 'pendente') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($o['created_at'] ?? 'now')) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic text-center py-6">Nenhum orçamento cadastrado</p>
                <?php endif; ?>
            </div>

            <!-- Chamados Tab -->
            <div id="chamados" class="tab-content hidden">
                <?php if (count($chamados) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php foreach ($chamados as $ch): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?= $ch['id'] ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars(substr($ch['titulo'] ?? '', 0, 40)) ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-<?= $ch['prioridade'] == 'alta' ? 'red' : ($ch['prioridade'] == 'media' ? 'yellow' : 'green') ?>-100 text-<?= $ch['prioridade'] == 'alta' ? 'red' : ($ch['prioridade'] == 'media' ? 'yellow' : 'green') ?>-800">
                                            <?= htmlspecialchars($ch['prioridade'] ?? 'normal') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm"><?= htmlspecialchars($ch['status'] ?? 'aberto') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($ch['created_at'] ?? 'now')) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic text-center py-6">Nenhum chamado cadastrado</p>
                <?php endif; ?>
            </div>

            <!-- Serviços Tab -->
            <div id="servicos" class="tab-content hidden">
                <?php if (count($servicos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php foreach ($servicos as $s): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?= $s['id'] ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars(substr($s['descricao'] ?? '', 0, 40)) ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">R$ <?= number_format($s['valor'] ?? 0, 2, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-sm"><?= htmlspecialchars($s['status'] ?? 'pendente') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($s['created_at'] ?? 'now')) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic text-center py-6">Nenhum serviço avulso cadastrado</p>
                <?php endif; ?>
            </div>

            <!-- Notas Tab -->
            <div id="notas" class="tab-content hidden">
                <?php if (count($notas) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($notas as $n): ?>
                        <div class="border border-gray-200 rounded p-4 hover:border-indigo-300 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-gray-800"><?= htmlspecialchars($n['titulo'] ?? 'Nota #' . $n['id']) ?></h4>
                                <span class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($n['created_at'] ?? 'now')) ?></span>
                            </div>
                            <p class="text-gray-600 text-sm line-clamp-3"><?= htmlspecialchars($n['conteudo'] ?? $n['descricao'] ?? '') ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic text-center py-6">Nenhuma nota/documento cadastrado</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarTab(tabName) {
    // Esconde todos os tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.add('hidden'));
    
    // Remove a classe 'active' de todos os botões
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => btn.classList.remove('border-indigo-600', 'text-indigo-600'));
    
    // Mostra o tab selecionado
    document.getElementById(tabName).classList.remove('hidden');
    
    // Adiciona a classe 'active' ao botão clicado
    event.target.closest('.tab-btn').classList.add('border-indigo-600', 'text-indigo-600');
}

// Ativa o primeiro tab por padrão
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.tab-btn').classList.add('border-indigo-600', 'text-indigo-600');
});
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
