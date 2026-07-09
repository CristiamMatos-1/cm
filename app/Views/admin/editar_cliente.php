<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Cliente</h2>
        <a href="<?= BASE_URL ?>/admin/clientes" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoCliente/<?= $cliente['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Informações Básicas -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome/Razão Social *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
                
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Responsável (Empresas)</label>
                    <input type="text" name="responsavel_nome" value="<?= htmlspecialchars($cliente['responsavel_nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ (Apenas Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($cliente['cpf_cnpj'] ?? '') ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone/WhatsApp</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
            </div>

            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Endereço</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                    <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($cliente['cep'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" onblur="buscarCep(this.value)">
                    <span id="cep-error" class="text-xs text-red-500 hidden mt-1">CEP não encontrado</span>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                    <input type="text" name="logradouro" id="logradouro" value="<?= htmlspecialchars($cliente['logradouro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                    <input type="text" name="numero" value="<?= htmlspecialchars($cliente['numero'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                    <input type="text" name="complemento" value="<?= htmlspecialchars($cliente['complemento'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($cliente['bairro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                    <input type="text" name="estado" id="estado" maxlength="2" value="<?= htmlspecialchars($cliente['estado'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 uppercase">
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
