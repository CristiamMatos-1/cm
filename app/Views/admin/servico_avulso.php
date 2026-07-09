<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/chamados" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para Chamados
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto border-t-4 border-corpBlue-500">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Abertura de Serviço Avulso</h2>
    <p class="text-sm text-gray-600 mb-6">Utilize este formulário quando o cliente entrar em contato por telefone/WhatsApp e você precisar registrar o chamado por ele.</p>

    <form action="<?= BASE_URL ?>/admin/salvarServicoAvulso" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Selecione o Cliente *</label>
            <select name="cliente_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none bg-gray-50">
                <option value="">Buscar cliente...</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do Serviço Solicitado *</label>
            <textarea name="descricao" rows="5" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none" placeholder="Descreva o problema relatado ou o serviço solicitado pelo cliente..."></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-corpBlue-600 text-white font-bold py-2 px-6 rounded hover:bg-corpBlue-700 transition-colors">
                <i class="fas fa-plus-circle mr-2"></i> Criar Chamado / Serviço
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>