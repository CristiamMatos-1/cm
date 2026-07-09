<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Detalhes do Chamado #<?= htmlspecialchars($chamado['id']) ?></h2>
        <a href="<?= BASE_URL ?>/client/chamados" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">Status Atual</p>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                    <?php 
                        if ($chamado['status'] === 'aberto') echo 'bg-yellow-100 text-yellow-800';
                        elseif ($chamado['status'] === 'finalizado') echo 'bg-green-100 text-green-800';
                        else echo 'bg-blue-100 text-blue-800';
                    ?>">
                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($chamado['status']))) ?>
                </span>
            </div>
            
            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">Data de Abertura</p>
                <p class="text-gray-800"><?= date('d/m/Y H:i', strtotime($chamado['created_at'])) ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">Tipo de Serviço Solicitado</p>
                <p class="text-gray-800"><?= htmlspecialchars($chamado['tipo_servico']) ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">Profissional Responsável</p>
                <p class="text-gray-800">
                    <?php if(!empty($chamado['tecnico_nome'])) echo "Técnico: " . htmlspecialchars($chamado['tecnico_nome']) . "<br>"; ?>
                    <?php if(!empty($chamado['programador_nome'])) echo "Programador: " . htmlspecialchars($chamado['programador_nome']) . "<br>"; ?>
                    <?php if(!empty($chamado['engenheiro_nome'])) echo "Engenheiro: " . htmlspecialchars($chamado['engenheiro_nome']) . "<br>"; ?>
                    <?php if(empty($chamado['tecnico_nome']) && empty($chamado['programador_nome']) && empty($chamado['engenheiro_nome'])) echo "Ainda não atribuído."; ?>
                </p>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 font-bold mb-2">Sua Descrição Inicial:</p>
            <div class="bg-gray-50 p-4 rounded text-gray-800 whitespace-pre-wrap"><?= htmlspecialchars($chamado['descricao']) ?></div>
        </div>

        <?php if (!empty($chamado['relatorio_final'])): ?>
        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 font-bold mb-2 text-corpBlue-600">Relatório / Parecer Técnico:</p>
            <div class="bg-blue-50 p-4 rounded text-gray-800 whitespace-pre-wrap border border-blue-100"><?= htmlspecialchars($chamado['relatorio_final']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($chamado['valor_servico']) && $chamado['valor_servico'] > 0): ?>
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center p-4 bg-green-50 rounded border border-green-100">
                <span class="font-bold text-green-800">Valor do Serviço:</span>
                <span class="text-xl font-bold text-green-900">R$ <?= number_format($chamado['valor_servico'], 2, ',', '.') ?></span>
            </div>
            <?php if (!empty($chamado['forma_pagamento'])): ?>
                <p class="text-sm text-gray-600 mt-2 text-right">Forma de Pagamento: <strong><?= ucfirst(str_replace('_', ' ', htmlspecialchars($chamado['forma_pagamento']))) ?></strong></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
