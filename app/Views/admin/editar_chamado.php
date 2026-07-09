<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Gerenciar Chamado #<?= htmlspecialchars($chamado['id']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/chamados" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoChamado/<?= $chamado['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
            
            <!-- Bloco 1: Informações do Cliente e Serviço -->
            <div class="mb-6 p-4 bg-gray-50 rounded border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Informações Iniciais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-bold">Cliente:</p>
                        <p class="text-gray-800"><?= htmlspecialchars($chamado['cliente_nome']) ?> (<?= htmlspecialchars($chamado['cliente_telefone']) ?>)</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-bold">Tipo de Serviço Solicitado:</p>
                        <p class="text-gray-800"><?= htmlspecialchars($chamado['tipo_servico']) ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 font-bold">Descrição do Cliente:</p>
                        <p class="text-gray-800 mt-1"><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Bloco 2: Atribuições -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Técnico Responsável</label>
                    <select name="tecnico_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Não Atribuído</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>" <?= ($chamado['tecnico_id'] == $func['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['nome']) ?> (<?= ucfirst($func['perfil']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Programador Responsável</label>
                    <select name="programador_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Não Atribuído</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>" <?= ($chamado['programador_id'] == $func['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['nome']) ?> (<?= ucfirst($func['perfil']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Engenheiro Responsável</label>
                    <select name="engenheiro_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Não Atribuído</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>" <?= ($chamado['engenheiro_id'] == $func['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['nome']) ?> (<?= ucfirst($func['perfil']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Bloco 3: Execução e Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status do Chamado</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 font-bold">
                        <?php
                            $status_options = [
                                'aberto' => 'Aberto',
                                'andamento' => 'Andamento',
                                'em_analise' => 'Em Análise',
                                'em_execucao' => 'Em Execução',
                                'esperando_peca' => 'Esperando Peça',
                                'finalizado' => 'Finalizado'
                            ];
                            foreach ($status_options as $key => $label):
                        ?>
                            <option value="<?= $key ?>" <?= ($chamado['status'] === $key) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do Serviço Executado / Relatório Técnico</label>
                    <textarea name="relatorio_final" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500"><?= htmlspecialchars($chamado['relatorio_final'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Financeiro e Fechamento</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Serviço (R$)</label>
                    <input type="text" name="valor_servico" value="<?= number_format($chamado['valor_servico'] ?? 0, 2, ',', '.') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0,00">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                    <select name="forma_pagamento" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Selecione (se aplicável)...</option>
                        <option value="pix" <?= ($chamado['forma_pagamento'] === 'pix') ? 'selected' : '' ?>>À Vista - PIX</option>
                        <option value="debito" <?= ($chamado['forma_pagamento'] === 'debito') ? 'selected' : '' ?>>À Vista - Débito</option>
                        <option value="credito_vista" <?= ($chamado['forma_pagamento'] === 'credito_vista') ? 'selected' : '' ?>>À Vista - Cartão de Crédito</option>
                        <option value="credito_parcelado" <?= ($chamado['forma_pagamento'] === 'credito_parcelado') ? 'selected' : '' ?>>A Prazo - Cartão Parcelado</option>
                        <option value="boleto" <?= ($chamado['forma_pagamento'] === 'boleto') ? 'selected' : '' ?>>A Prazo - Boleto</option>
                        <option value="faturamento_mensal" <?= ($chamado['forma_pagamento'] === 'faturamento_mensal') ? 'selected' : '' ?>>A Prazo - Faturamento Mensal</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Salvar Chamado
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
