<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/usuarios" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Cadastrar Novo Usuário</h2>

    <form action="<?= BASE_URL ?>/admin/salvarUsuario" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome / Razão Social *</label>
                <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CPF ou CNPJ *</label>
                <input type="text" name="cpf_cnpj" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text" name="telefone" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cadastro (Perfil) *</label>
                <select name="perfil" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50">
                    <optgroup label="Funcionários da Empresa">
                        <option value="admin">Administrador (Acesso Total)</option>
                        <option value="tecnico">Técnico / Programador / Engenheiro</option>
                    </optgroup>
                    <optgroup label="Clientes (Acesso Restrito)">
                        <option value="cliente">Cliente</option>
                    </optgroup>
                </select>
                <p class="text-xs text-gray-500 mt-1">Dica: Clientes criados aqui irão para a tela de "Meus Clientes".</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha de Acesso *</label>
                <input type="password" name="senha" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <label class="block text-sm font-bold text-gray-800 mb-2">Permissões Especiais Iniciais</label>
            <div class="space-y-2">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissoes[]" value="abrir_chamado_admin" class="form-checkbox h-4 w-4 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Pode abrir chamados (Se for admin)</span>
                </label>
                <br>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissoes[]" value="criar_orcamento" class="form-checkbox h-4 w-4 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Pode gerenciar Orçamentos e Serviços Avulsos</span>
                </label>
                <br>
                <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="gerar_relatorios" class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode emitir Relatórios (Tempo, Clientes, Financeiro)</span>
                    </label>
                    <br>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="acesso_financeiro" class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode acessar e gerenciar a Área Contábil / Financeira</span>
                    </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700 transition-colors">
                <i class="fas fa-save mr-2"></i> Cadastrar Usuário
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>