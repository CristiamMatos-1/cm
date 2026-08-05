<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Meus Orçamentos</h2>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden border-t-4 border-indigo-500">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <p class="text-sm text-gray-600">Confira abaixo as propostas e orçamentos enviados pela nossa equipe técnica para aprovação.</p>
    </div>
    
    <div class="p-6">
        <?php if (empty($orcamentos)): ?>
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-file-invoice text-4xl mb-3 text-gray-300"></i>
                <p>Nenhum orçamento pendente ou aprovado no momento.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orcamentos as $o): ?>
                <div class="border rounded-lg p-6 hover:shadow-lg transition-shadow <?= $o['status'] === 'pendente' ? 'border-indigo-200 bg-indigo-50' : 'border-gray-300' ?>">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($o['titulo']) ?></h4>
                            <p class="text-sm text-gray-500">Enviado em: <?= date('d/m/Y', strtotime($o['created_at'])) ?></p>
                            <?php if (!empty($o['ticket_id'])): ?>
                                <?php $tipo = htmlspecialchars($o['tipo_servico'] ?? $o['tipo'] ?? 'Serviço'); ?>
                                <p class="text-xs text-indigo-600 mt-1"><i class="fas fa-link"></i> Referente ao chamado #<?= $o['ticket_id'] ?> (<?= $tipo ?>)</p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-green-600">R$ <?= number_format($o['valor_total'] ?? $o['valor'] ?? 0, 2, ',', '.') ?></p>
                            <?php
                                $cor = 'bg-yellow-100 text-yellow-800';
                                if ($o['status'] === 'aprovado') $cor = 'bg-green-100 text-green-800';
                                if ($o['status'] === 'rejeitado') $cor = 'bg-red-100 text-red-800';
                                if ($o['status'] === 'executado') $cor = 'bg-blue-100 text-blue-800';
                            ?>
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full mt-2 <?= $cor ?>">
                                Status: <?= strtoupper($o['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Descrição do Serviço -->
                    <div class="bg-gray-50 p-4 rounded border border-gray-300 mb-4">
                        <strong class="block mb-3 text-gray-800 text-sm uppercase tracking-wide">📋 Descrição do Serviço / Peças:</strong>
                        <div class="text-gray-700 text-sm leading-relaxed space-y-2">
                            <?php
                                $descricao = htmlspecialchars($o['descricao']);
                                $linhas = explode("\n", $descricao);
                                foreach ($linhas as $linha):
                                    if (!empty(trim($linha))):
                            ?>
                                <div class="flex gap-3">
                                    <span class="text-indigo-500 font-bold">•</span>
                                    <span><?= htmlspecialchars($linha) ?></span>
                                </div>
                            <?php
                                    endif;
                                endforeach;
                            ?>
                        </div>
                    </div>

                    <!-- Resumo do Valor -->
                    <?php if (!empty($o['valor_pecas']) || !empty($o['valor_mao_obra']) || !empty($o['valor_servico'])): ?>
                    <div class="bg-blue-50 p-4 rounded border border-blue-200 mb-4">
                        <strong class="block mb-3 text-gray-800 text-sm">💰 Detalhes do Valor:</strong>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <?php if (!empty($o['valor_pecas'])): ?>
                            <div class="text-center">
                                <p class="text-gray-600">Peças</p>
                                <p class="text-lg font-bold text-gray-800">R$ <?= number_format($o['valor_pecas'], 2, ',', '.') ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($o['valor_mao_obra'])): ?>
                            <div class="text-center">
                                <p class="text-gray-600">Mão de Obra</p>
                                <p class="text-lg font-bold text-gray-800">R$ <?= number_format($o['valor_mao_obra'], 2, ',', '.') ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($o['valor_servico'])): ?>
                            <div class="text-center">
                                <p class="text-gray-600">Serviço</p>
                                <p class="text-lg font-bold text-gray-800">R$ <?= number_format($o['valor_servico'], 2, ',', '.') ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($o['status'] === 'pendente'): ?>
                        <div class="flex gap-3 justify-end border-t pt-4">
                            <form action="<?= BASE_URL ?>/client/responderOrcamento/<?= $o['id'] ?>" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                <input type="hidden" name="acao" value="rejeitar">
                                <button type="submit" class="bg-white border border-red-500 text-red-500 hover:bg-red-50 px-4 py-2 rounded text-sm font-medium transition-colors" onclick="return confirm('Tem certeza que deseja recusar este orçamento?')">
                                    <i class="fas fa-times mr-1"></i> Recusar
                                </button>
                            </form>
                            
                            <form action="<?= BASE_URL ?>/client/responderOrcamento/<?= $o['id'] ?>" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                <input type="hidden" name="acao" value="aprovar">
                                <button type="submit" class="bg-green-600 text-white hover:bg-green-700 px-6 py-2 rounded text-sm font-bold shadow-sm transition-colors" onclick="return confirm('Ao aprovar, nossa equipe iniciará a execução do serviço. Confirmar aprovação?')">
                                    <i class="fas fa-check mr-1"></i> Aprovar Orçamento
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>