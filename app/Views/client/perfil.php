<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-bold text-gray-800">Meu Perfil</h2>
    </div>

    <!-- Anotações Visíveis - Destacadas no topo se existirem -->
    <?php if (!empty($cliente['anotacoes_visivel'])): ?>
    <div class="mb-6">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg shadow-md p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-bell text-2xl text-green-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-green-800 mb-3">
                        📢 Mensagens da Nossa Equipe
                    </h3>
                    <div class="bg-white rounded p-4 border-l-4 border-green-400">
                        <div class="text-gray-700 text-base leading-8 space-y-3">
                            <?php
                                $anotacoes = htmlspecialchars($cliente['anotacoes_visivel']);
                                $linhas = explode("\n", $anotacoes);
                                foreach ($linhas as $linha):
                                    if (!empty(trim($linha))):
                            ?>
                                <div class="text-justify break-words">
                                    <?= htmlspecialchars($linha) ?>
                                </div>
                            <?php
                                    endif;
                                endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alterar Senha -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-indigo-500">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-lock mr-2 text-indigo-600"></i> Alterar Senha
            </h3>
            <form action="<?= BASE_URL ?>/client/atualizarSenha" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Senha Atual</label>
                    <input type="password" name="senha_atual" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nova Senha</label>
                    <input type="password" name="nova_senha" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded shadow hover:bg-indigo-700 transition-colors">
                        Salvar Nova Senha
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informações do Cliente -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-blue-500">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-blue-600"></i> Informações Cadastrais
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nome/Razão Social</label>
                        <p class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($cliente['nome']) ?></p>
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
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-600">Responsável</label>
                        <p class="text-gray-900 font-medium"><?= htmlspecialchars($cliente['responsavel_nome']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="my-6 border-gray-200">

                <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-map-marker-alt mr-2 text-red-500"></i> Endereço
                </h4>
                <div class="text-sm text-gray-700 bg-gray-50 rounded p-4">
                    <?php if (!empty($cliente['logradouro'])): ?>
                        <p class="font-medium"><?= htmlspecialchars($cliente['logradouro']) ?>, <?= htmlspecialchars($cliente['numero'] ?? '-') ?></p>
                        <?php if (!empty($cliente['complemento'])): ?>
                            <p class="text-gray-600"><?= htmlspecialchars($cliente['complemento']) ?></p>
                        <?php endif; ?>
                        <p class="text-gray-600"><?= htmlspecialchars($cliente['bairro'] ?? '') ?>, <?= htmlspecialchars($cliente['cidade'] ?? '') ?> - <?= htmlspecialchars($cliente['estado'] ?? '') ?></p>
                        <p class="font-mono text-gray-600"><?= htmlspecialchars($cliente['cep'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Nenhum endereço cadastrado</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-purple-500">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-2 text-purple-600"></i> Resumo
                </h3>
                
                <div class="space-y-3">
                    <div class="text-center p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500 hover:shadow-md transition">
                        <p class="text-3xl font-bold text-blue-600"><?= $totalOrcamentos ?? 0 ?></p>
                        <p class="text-sm text-gray-600 font-medium">Orçamentos</p>
                    </div>

                    <div class="text-center p-4 bg-orange-50 rounded-lg border-l-4 border-orange-500 hover:shadow-md transition">
                        <p class="text-3xl font-bold text-orange-600"><?= $totalChamados ?? 0 ?></p>
                        <p class="text-sm text-gray-600 font-medium">Chamados</p>
                    </div>

                    <div class="text-center p-4 bg-teal-50 rounded-lg border-l-4 border-teal-500 hover:shadow-md transition">
                        <p class="text-3xl font-bold text-teal-600"><?= $totalContratos ?? 0 ?></p>
                        <p class="text-sm text-gray-600 font-medium">Contratos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
