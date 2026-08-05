<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Meu Perfil</h2>
    </div>

    <!-- Informações do Cliente -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Informações Cadastrais</h3>
                
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
                        <label class="block text-sm font-medium text-gray-600">Responsável</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cliente['responsavel_nome']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="my-4 border-gray-200">

                <h4 class="font-bold text-gray-700 mb-3">Endereço</h4>
                <div class="text-sm text-gray-600">
                    <?php if (!empty($cliente['logradouro'])): ?>
                        <p><?= htmlspecialchars($cliente['logradouro']) ?>, <?= htmlspecialchars($cliente['numero'] ?? '') ?></p>
                        <?php if (!empty($cliente['complemento'])): ?>
                            <p><?= htmlspecialchars($cliente['complemento']) ?></p>
                        <?php endif; ?>
                        <p><?= htmlspecialchars($cliente['bairro'] ?? '') ?>, <?= htmlspecialchars($cliente['cidade'] ?? '') ?> - <?= htmlspecialchars($cliente['estado'] ?? '') ?></p>
                        <p class="font-mono"><?= htmlspecialchars($cliente['cep'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Nenhum endereço cadastrado</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Resumo</h3>
            
            <div class="space-y-4">
                <div class="text-center p-3 bg-blue-50 rounded">
                    <p class="text-2xl font-bold text-blue-600"><?= $totalOrcamentos ?? 0 ?></p>
                    <p class="text-sm text-gray-600">Orçamentos</p>
                </div>

                <div class="text-center p-3 bg-orange-50 rounded">
                    <p class="text-2xl font-bold text-orange-600"><?= $totalChamados ?? 0 ?></p>
                    <p class="text-sm text-gray-600">Chamados</p>
                </div>

                <div class="text-center p-3 bg-green-50 rounded">
                    <p class="text-2xl font-bold text-green-600"><?= $totalContratos ?? 0 ?></p>
                    <p class="text-sm text-gray-600">Contratos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Anotações Visíveis -->
    <?php if (!empty($cliente['anotacoes_visivel'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-bold text-green-700 mb-3">
            <i class="fas fa-sticky-note mr-2"></i> Recados da Nossa Equipe
        </h3>
        <div class="text-gray-700 whitespace-pre-wrap">
            <?= htmlspecialchars($cliente['anotacoes_visivel']) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
