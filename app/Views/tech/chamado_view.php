<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/tech/chamados" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para Fila
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna Esquerda: Informações do Chamado -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Detalhes do Chamado -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between border-b pb-4 mb-4">
                <h2 class="text-xl font-bold text-gray-800">Chamado #<?= htmlspecialchars($chamado['id']) ?></h2>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                    <?= $chamado['status'] === 'aberto' ? 'bg-yellow-100 text-yellow-800' : ($chamado['status'] === 'andamento' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                    <?= ucfirst(htmlspecialchars($chamado['status'])) ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Cliente</p>
                    <p class="text-gray-800"><?= htmlspecialchars($chamado['cliente_nome']) ?></p>
                    <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($chamado['cliente_telefone']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tipo de Serviço</p>
                    <p class="text-gray-800"><?= htmlspecialchars($chamado['tipo_servico']) ?></p>
                    <p class="text-sm text-gray-500 font-medium mt-2">Aberto em</p>
                    <p class="text-gray-800"><?= date('d/m/Y H:i', strtotime($chamado['created_at'])) ?></p>
                </div>
            </div>

            <div>
                <p class="text-sm text-gray-500 font-medium mb-2">Descrição do Problema relatado pelo Cliente:</p>
                <div class="bg-gray-50 p-4 rounded-lg text-gray-700 whitespace-pre-wrap border border-gray-200"><?= htmlspecialchars($chamado['descricao']) ?></div>
            </div>
        </div>

        <!-- Mídias e Anexos -->
        <?php if (!empty($midias)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-paperclip text-gray-400 mr-2"></i> Anexos e Mídias</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($midias as $media): ?>
                    <?php if ($media['tipo'] === 'imagem'): ?>
                        <a href="<?= BASE_URL ?>/<?= $media['file_url'] ?>" target="_blank" class="block border rounded p-1 hover:border-corpBlue-500 transition-colors">
                            <img src="<?= BASE_URL ?>/<?= $media['file_url'] ?>" alt="Anexo" class="w-full h-32 object-cover rounded">
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/<?= $media['file_url'] ?>" target="_blank" class="flex flex-col items-center justify-center border rounded p-4 h-32 hover:border-corpBlue-500 transition-colors bg-gray-50">
                            <i class="fas fa-video text-3xl text-gray-400 mb-2"></i>
                            <span class="text-xs text-gray-500 text-center">Ver Vídeo</span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resposta da IA Gemini -->
        <?php if (!empty($iaResponse)): ?>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm p-6 border border-blue-100">
            <h3 class="text-lg font-bold text-corpBlue-900 mb-4 flex items-center">
                <i class="fas fa-robot mr-2 text-corpBlue-600"></i> Análise do Consultor IA (Gemini)
            </h3>
            <div class="prose max-w-none text-gray-800 text-sm">
                <?= nl2br($iaResponse) ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Coluna Direita: Ações do Técnico -->
    <div class="space-y-6">
        
        <!-- Assumir Chamado -->
        <?php if (empty($chamado['tecnico_id'])): ?>
            <div class="bg-white rounded-lg shadow-sm p-6 border-t-4 border-yellow-400">
                <h3 class="font-bold text-gray-800 mb-2">Chamado não atribuído</h3>
                <p class="text-sm text-gray-600 mb-4">Você precisa assumir este chamado para poder realizar triagem e emitir relatório.</p>
                <form action="<?= BASE_URL ?>/tech/assumirChamado/<?= $chamado['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="w-full bg-corpBlue-600 text-white font-semibold py-2 px-4 rounded hover:bg-corpBlue-700 transition-colors">
                        Assumir Atendimento
                    </button>
                </form>
            </div>
        <?php elseif ($chamado['tecnico_id'] == $_SESSION['user_id']): ?>
            <!-- Ações IA -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-2">Assistente Virtual</h3>
                <p class="text-sm text-gray-600 mb-4">Utilize a inteligência artificial do Google Gemini para analisar o problema e sugerir possíveis causas raízes.</p>
                <a href="<?= BASE_URL ?>/tech/analisarIA/<?= $chamado['id'] ?>" class="block w-full text-center bg-indigo-600 text-white font-semibold py-2 px-4 rounded hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-magic mr-2"></i> Analisar com IA
                </a>
            </div>

            <!-- Formulário de Triagem / Fechamento -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold text-gray-800 mb-4">Gestão do Atendimento</h3>
                <form action="<?= BASE_URL ?>/tech/atualizarChamado/<?= $chamado['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento</label>
                        <select name="atendimento" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none text-sm">
                            <option value="">Selecione...</option>
                            <option value="remoto" <?= $chamado['atendimento'] === 'remoto' ? 'selected' : '' ?>>Acesso Remoto</option>
                            <option value="presencial" <?= $chamado['atendimento'] === 'presencial' ? 'selected' : '' ?>>Visita Presencial</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none text-sm">
                            <option value="andamento" <?= $chamado['status'] === 'andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="finalizado" <?= $chamado['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relatório Final</label>
                        <textarea name="relatorio" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-corpBlue-500 focus:border-corpBlue-500 outline-none text-sm" placeholder="Descreva o serviço executado..."><?= htmlspecialchars($chamado['relatorio_final'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white font-semibold py-2 px-4 rounded hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-gray-100 rounded-lg p-6 text-center text-gray-500">
                <i class="fas fa-lock text-3xl mb-2"></i>
                <p class="text-sm">Este chamado está atribuído a outro técnico.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>