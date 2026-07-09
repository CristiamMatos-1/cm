<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Funcionário</h2>
        <a href="<?= BASE_URL ?>/admin/usuarios" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoUsuario/<?= $usuario['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Informações Básicas -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ (Apenas Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['cpf_cnpj'] ?? '') ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone/WhatsApp</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
            </div>

            <!-- Nível de Acesso e Senha -->
            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Acesso e Segurança</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Perfil de Acesso *</label>
                    <select name="perfil" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 font-bold bg-gray-50">
                        <option value="tecnico" <?= $usuario['perfil'] === 'tecnico' ? 'selected' : '' ?>>Técnico / Programador / Engenheiro</option>
                        <option value="admin" <?= $usuario['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador (Acesso Total)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha (Deixe em branco para manter a atual)</label>
                    <input type="password" name="senha" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
            </div>

            <!-- Permissões Granulares -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                <label class="block text-sm font-bold text-corpBlue-800 mb-2">Permissões Especiais (Módulos)</label>
                <?php $perms = json_decode($usuario['permissoes'] ?? '[]', true) ?: []; ?>
                <div class="space-y-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="abrir_chamado_admin" <?= in_array('abrir_chamado_admin', $perms) ? 'checked' : '' ?> class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode abrir chamados para os clientes</span>
                    </label>
                    <br>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="criar_orcamento" <?= in_array('criar_orcamento', $perms) ? 'checked' : '' ?> class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode gerenciar Orçamentos e Serviços Avulsos</span>
                    </label>
                    <br>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="gerar_relatorios" <?= in_array('gerar_relatorios', $perms) ? 'checked' : '' ?> class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode emitir Relatórios (Tempo, Clientes, Financeiro)</span>
                    </label>
                    <br>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissoes[]" value="acesso_financeiro" <?= in_array('acesso_financeiro', $perms) ? 'checked' : '' ?> class="form-checkbox h-4 w-4 text-corpBlue-600">
                        <span class="ml-2 text-sm text-gray-800">Pode acessar e gerenciar a Área Contábil / Financeira</span>
                    </label>
                </div>
            </div>

            <!-- Endereço -->
            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Endereço Residencial/Comercial</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                    <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($usuario['cep'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" onblur="buscarCep(this.value)">
                    <span id="cep-error" class="text-xs text-red-500 hidden mt-1">CEP não encontrado</span>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                    <input type="text" name="logradouro" id="logradouro" value="<?= htmlspecialchars($usuario['logradouro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                    <input type="text" name="numero" value="<?= htmlspecialchars($usuario['numero'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                    <input type="text" name="complemento" value="<?= htmlspecialchars($usuario['complemento'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($usuario['bairro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                    <input type="text" name="estado" id="estado" maxlength="2" value="<?= htmlspecialchars($usuario['estado'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 uppercase">
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function buscarCep(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep !== "") {
        var validacep = /^[0-9]{8}$/;
        if(validacep.test(cep)) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('cep-error').classList.add('hidden');
                    } else {
                        document.getElementById('cep-error').classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Erro:', error));
        }
    }
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
