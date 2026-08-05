<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar Orçamento</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8">
                <h1 class="text-3xl font-bold text-white mb-2">Autorização de Orçamento</h1>
                <p class="text-blue-100">Orçamento #<?= $budget['id'] ?></p>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Budget Info -->
                <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                    <h2 class="text-xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($budget['titulo']) ?></h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Cliente</p>
                            <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($budget['cliente_nome']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Data de Emissão</p>
                            <p class="text-lg font-semibold text-gray-800"><?= date('d/m/Y', strtotime($budget['created_at'])) ?></p>
                        </div>
                    </div>

                    <?php if ($budget['descricao']): ?>
                        <div class="mb-4 p-4 bg-white rounded border-l-4 border-blue-500">
                            <p class="text-sm text-gray-600 mb-2">Descrição</p>
                            <p class="text-gray-800"><?= nl2br(htmlspecialchars($budget['descricao'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Values -->
                    <div class="space-y-2 pt-4 border-t border-gray-200">
                        <?php if ($budget['valor_pecas'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Peças:</span>
                                <span class="font-semibold text-gray-800">R$ <?= number_format($budget['valor_pecas'], 2, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($budget['valor_mao_obra'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Mão de Obra:</span>
                                <span class="font-semibold text-gray-800">R$ <?= number_format($budget['valor_mao_obra'], 2, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between pt-2 border-t border-gray-200 text-lg">
                            <span class="font-bold text-gray-800">VALOR TOTAL:</span>
                            <span class="font-bold text-blue-600">R$ <?= number_format($budget['valor_total'], 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <?php if ($budget['data_validade']): ?>
                        <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                            <p class="text-sm text-yellow-800">
                                <strong>Válido até:</strong> <?= date('d/m/Y', strtotime($budget['data_validade'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <!-- Approve Form -->
                    <form action="<?= BASE_URL ?>/auth/aprovarOrcamento" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($budget['token_autorizacao']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-check mr-2"></i> Aprovar Orçamento
                        </button>
                    </form>

                    <!-- Reject Form -->
                    <button onclick="document.getElementById('rejectForm').classList.remove('hidden')" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i> Rejeitar Orçamento
                    </button>
                </div>

                <!-- Reject Form (Hidden) -->
                <div id="rejectForm" class="hidden mt-6 p-6 bg-red-50 rounded-lg border border-red-200">
                    <form action="<?= BASE_URL ?>/auth/rejeitarOrcamento" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($budget['token_autorizacao']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <h3 class="text-lg font-bold text-red-800 mb-4">Rejeitar Orçamento</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição (opcional)</label>
                            <textarea name="motivo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" rows="4" placeholder="Explique por que está rejeitando o orçamento..."></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-times mr-2"></i> Confirmar Rejeição
                            </button>
                            <button type="button" onclick="document.getElementById('rejectForm').classList.add('hidden')" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-ban mr-2"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-100 px-6 py-4 text-center text-sm text-gray-600">
                <p>Suas respostas serão registradas automaticamente e comunicadas ao responsável.</p>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
