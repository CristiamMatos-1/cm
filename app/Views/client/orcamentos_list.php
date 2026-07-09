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
                <div class="border rounded-lg p-5 hover:shadow-md transition-shadow <?= $o['status'] === 'pendente' ? 'border-indigo-200 bg-indigo-50' : '' ?>">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($o['titulo']) ?></h4>
                            <p class="text-sm text-gray-500">Enviado em: <?= date('d/m/Y', strtotime($o['created_at'])) ?></p>
                            <?php if ($o['ticket_id']): ?>
                                <p class="text-xs text-indigo-600 mt-1"><i class="fas fa-link"></i> Referente ao chamado #<?= $o['ticket_id'] ?> (<?= htmlspecialchars($o['tipo_servico']) ?>)</p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($o['valor'], 2, ',', '.') ?></p>
                            <?php
                                $cor = 'bg-yellow-100 text-yellow-800';
                                if ($o['status'] === 'aprovado') $cor = 'bg-green-100 text-green-800';
                                if ($o['status'] === 'rejeitado') $cor = 'bg-red-100 text-red-800';
                                if ($o['status'] === 'executado') $cor = 'bg-blue-100 text-blue-800';
                            ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full mt-1 <?= $cor ?>">
                                Status: <?= strtoupper($o['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap mb-4">
                        <strong class="block mb-2 text-gray-800">Descrição do Serviço / Peças:</strong>
                        <?= htmlspecialchars($o['descricao']) ?>
                    </div>

                    <?php if ($o['status'] === 'pendente'): ?>
                        <div class="flex gap-3 justify-end border-t pt-4">
                            <form action="<?= BASE_URL ?>/client/responderOrcamento/<?= $o['id'] ?>" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                <input type="hidden" name="acao" value="rejeitar">
                                <button type="submit" class="bg-white border border-red-500 text-red-500 hover:bg-red-50 px-4 py-2 rounded text-sm font-medium transition-colors" onclick="return confirm('Tem certeza que deseja recusar este orçamento?')">
                                    Recusar
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