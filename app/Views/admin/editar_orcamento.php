<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Orçamento #<?= htmlspecialchars($orcamento['id']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/orcamentos" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoOrcamento/<?= $orcamento['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($orcamento['cliente_nome']) ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a Chamado Existente (Opcional)</label>
                    <select name="ticket_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Nenhum chamado vinculado</option>
                        <?php foreach ($chamados_abertos as $chamado): ?>
                            <option value="<?= $chamado['id'] ?>" <?= ($orcamento['ticket_id'] == $chamado['id']) ? 'selected' : '' ?>>
                                #<?= $chamado['id'] ?> - <?= htmlspecialchars($chamado['cliente_nome']) ?> (<?= ucfirst($chamado['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status do Orçamento</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 font-bold">
                        <?php
                            $status_options = [
                                'pendente' => 'Pendente (Aguardando Cliente)',
                                'aprovado' => 'Aprovado',
                                'rejeitado' => 'Rejeitado',
                                'em_andamento' => 'Em Andamento',
                                'em_analise' => 'Em Análise',
                                'esperando_peca' => 'Esperando Peça',
                                'executado' => 'Executado',
                                'finalizado' => 'Finalizado'
                            ];
                            foreach ($status_options as $key => $label):
                        ?>
                            <option value="<?= $key ?>" <?= ($orcamento['status'] === $key) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título do Orçamento *</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($orcamento['titulo']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição Detalhada *</label>
                    <textarea name="descricao" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500"><?= htmlspecialchars($orcamento['descricao']) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor de Peças (R$)</label>
                    <input type="text" name="valor_pecas" id="valor_pecas" value="<?= number_format($orcamento['valor_pecas'] ?? 0, 2, ',', '.') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" onkeyup="calcularTotal()">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor de Mão de Obra (R$)</label>
                    <input type="text" name="valor_mao_obra" id="valor_mao_obra" value="<?= number_format($orcamento['valor_mao_obra'] ?? 0, 2, ',', '.') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" onkeyup="calcularTotal()">
                </div>
                
                <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center">
                    <span class="text-lg font-medium text-corpBlue-800">Valor Total Estimado:</span>
                    <span class="text-2xl font-bold text-corpBlue-900">R$ <span id="valor_total_display"><?= number_format($orcamento['valor'], 2, ',', '.') ?></span></span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calcularTotal() {
    let pecasStr = document.getElementById('valor_pecas').value.replace(',', '.') || 0;
    let maoObraStr = document.getElementById('valor_mao_obra').value.replace(',', '.') || 0;
    
    let total = parseFloat(pecasStr) + parseFloat(maoObraStr);
    
    if(!isNaN(total)) {
        document.getElementById('valor_total_display').innerText = total.toFixed(2).replace('.', ',');
    }
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
