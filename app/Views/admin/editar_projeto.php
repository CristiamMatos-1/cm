<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Gerenciar Projeto: <?= htmlspecialchars($projeto['nome_projeto']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/projetos" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoProjeto/<?= $projeto['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente Solicitante (Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($projeto['cliente_nome']) ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fase / Status do Projeto</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 font-bold">
                        <option value="planejamento" <?= $projeto['status'] === 'planejamento' ? 'selected' : '' ?>>1. Planejamento (Requisitos)</option>
                        <option value="desenvolvimento" <?= $projeto['status'] === 'desenvolvimento' ? 'selected' : '' ?>>2. Desenvolvimento</option>
                        <option value="testes" <?= $projeto['status'] === 'testes' ? 'selected' : '' ?>>3. Testes / QA</option>
                        <option value="homologacao" <?= $projeto['status'] === 'homologacao' ? 'selected' : '' ?>>4. Homologação (Cliente)</option>
                        <option value="producao" <?= $projeto['status'] === 'producao' ? 'selected' : '' ?>>5. Produção (Lançado)</option>
                        <option value="concluido" <?= $projeto['status'] === 'concluido' ? 'selected' : '' ?>>6. Concluído / Encerrado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Engenheiro de Software Responsável</label>
                    <select name="engenheiro_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Ainda não definido</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>" <?= $projeto['engenheiro_id'] == $func['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['nome']) ?> (<?= ucfirst($func['perfil']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Projeto / Sistema *</label>
                    <input type="text" name="nome_projeto" value="<?= htmlspecialchars($projeto['nome_projeto']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição / Requisitos (Visão Geral)</label>
                    <textarea name="descricao" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500"><?= htmlspecialchars($projeto['descricao']) ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link da Documentação Técnica (Eng. de Software)</label>
                    <input type="url" name="documentacao" value="<?= htmlspecialchars($projeto['documentacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="https://...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link do Repositório (Git)</label>
                    <input type="url" name="link_repositorio" value="<?= htmlspecialchars($projeto['link_repositorio'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="https://github.com/...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL de Produção / Homologação</label>
                    <input type="url" name="link_producao" value="<?= htmlspecialchars($projeto['link_producao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="https://...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Previsão de Início</label>
                    <input type="date" name="data_inicio" value="<?= $projeto['data_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Previsão de Conclusão</label>
                    <input type="date" name="data_previsao_fim" value="<?= $projeto['data_previsao_fim'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Salvar Progresso do Projeto
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
