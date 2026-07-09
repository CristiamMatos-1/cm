<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/client/chamados" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Abertura de Novo Chamado</h2>

    <form action="<?= BASE_URL ?>/client/salvarChamado" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="mb-5">
            <label for="tipo_servico" class="block text-sm font-medium text-gray-700 mb-1">Qual o tipo de serviço que você precisa?</label>
            <select id="tipo_servico" name="tipo_servico" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 outline-none bg-white">
                <option value="" disabled selected>Selecione uma opção...</option>
                <option value="Manutencao em Computador">Manutenção em Computador / Notebook</option>
                <option value="Criacao de Sistema">Criação de Sistema / Aplicativo</option>
                <option value="Manutencao em Sistema">Manutenção em Sistema Existente</option>
                <option value="Envio para Analise">Envio para Análise Técnica</option>
                <option value="Execucao">Execução de Serviço Específico</option>
            </select>
        </div>

        <div class="mb-5">
            <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descreva o seu problema detalhadamente</label>
            <textarea id="descricao" name="descricao" rows="5" required placeholder="Ex: Meu notebook não está ligando, a tela fica preta e faz um bipe contínuo..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-corpBlue-500 outline-none"></textarea>
        </div>

        <!-- Seção de Upload Mobile-First -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Enviar Fotos ou Vídeos (Opcional)</label>
            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                <div class="space-y-1 text-center">
                    <i class="fas fa-camera text-4xl text-gray-400 mb-2"></i>
                    <div class="flex text-sm text-gray-600 justify-center">
                        <label for="midias" class="relative cursor-pointer bg-white rounded-md font-medium text-corpBlue-600 hover:text-corpBlue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-corpBlue-500 px-2 py-1 shadow-sm">
                            <span>Tirar Foto / Gravar Vídeo</span>
                            <!-- accept="image/*,video/*" permite abrir a câmera no celular -->
                            <input id="midias" name="midias[]" type="file" class="sr-only" multiple accept="image/jpeg, image/png, image/webp, video/mp4, video/webm" onchange="updateFileList(this)">
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Você pode selecionar múltiplos arquivos (PNG, JPG, MP4 até 20MB)</p>
                </div>
            </div>
            <!-- Div para preview dos nomes dos arquivos selecionados -->
            <ul id="file-list" class="mt-3 text-sm text-gray-600 space-y-1"></ul>
        </div>

        <div class="pt-4 border-t border-gray-200 flex justify-end">
            <button type="submit" class="bg-corpBlue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-corpBlue-700 focus:ring-4 focus:ring-corpBlue-200 transition-all w-full sm:w-auto">
                <i class="fas fa-paper-plane mr-2"></i> Enviar Chamado
            </button>
        </div>
    </form>
</div>

<script>
function updateFileList(input) {
    const list = document.getElementById('file-list');
    list.innerHTML = '';
    
    if (input.files.length > 0) {
        list.innerHTML = '<li class="font-semibold text-gray-800">Arquivos Selecionados:</li>';
        Array.from(input.files).forEach(file => {
            const li = document.createElement('li');
            li.innerHTML = `<i class="fas fa-file-alt text-gray-400 mr-2"></i> ${file.name} <span class="text-xs text-gray-400">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>`;
            list.appendChild(li);
        });
    }
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>