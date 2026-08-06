<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Criar Novo Cliente</h2>
        <a href="<?= BASE_URL ?>/admin/clientes" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <?php if (isset($erro) && !empty($erro)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarNovoCliente" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Informações Básicas -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome/Razão Social *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="Nome ou razão social">
                </div>
                
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ *</label>
                    <div class="relative">
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="<?= htmlspecialchars($dados['cpf_cnpj'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 pr-9" placeholder="XXX.XXX.XXX-XX ou XX.XXX.XXX/XXXX-XX"
                               oninput="verificarCNPJCliente(this.value)">
                        <span id="cpf-loading" class="hidden absolute right-2 top-2.5 text-corpBlue-500 text-sm"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                    <p id="cpf-status" class="mt-1 text-xs hidden"></p>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Responsável (Empresas)</label>
                    <input type="text" name="responsavel_nome" id="responsavel_nome" value="<?= htmlspecialchars($dados['responsavel_nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="Deixe em branco para pessoas físicas">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" id="email_cliente" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="email@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                    <input type="text" name="whatsapp" id="whatsapp_cliente" value="<?= htmlspecialchars($dados['whatsapp'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="(XX) 9XXXX-XXXX">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" id="telefone_cliente" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="(XX) XXXXX-XXXX">
                </div>
            </div>

            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Dados Fiscais</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual (IE)</label>
                    <input type="text" name="ie" value="<?= htmlspecialchars($dados['ie'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal (IM)</label>
                    <input type="text" name="im" value="<?= htmlspecialchars($dados['im'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNAE Principal</label>
                    <input type="text" name="cnae_principal" id="cnae_cliente" value="<?= htmlspecialchars($dados['cnae_principal'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0000-0/00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário (CRT)</label>
                    <select name="crt" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="1" <?= ($dados['crt'] ?? '1') == '1' ? 'selected' : '' ?>>1 – Simples Nacional</option>
                        <option value="2" <?= ($dados['crt'] ?? '') == '2' ? 'selected' : '' ?>>2 – Simples Nacional – Excesso</option>
                        <option value="3" <?= ($dados['crt'] ?? '') == '3' ? 'selected' : '' ?>>3 – Lucro Presumido / Real</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Indicador IE</label>
                    <select name="indicador_ie" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="9" <?= ($dados['indicador_ie'] ?? '9') == '9' ? 'selected' : '' ?>>9 – Não Contribuinte</option>
                        <option value="1" <?= ($dados['indicador_ie'] ?? '') == '1' ? 'selected' : '' ?>>1 – Contribuinte ICMS</option>
                        <option value="2" <?= ($dados['indicador_ie'] ?? '') == '2' ? 'selected' : '' ?>>2 – Contribuinte Isento</option>
                    </select>
                </div>
            </div>

            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Endereço</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                    <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($dados['cep'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" onblur="buscarCep(this.value)" placeholder="XXXXX-XXX">
                    <span id="cep-error" class="text-xs text-red-500 hidden mt-1">CEP não encontrado</span>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                    <input type="text" name="logradouro" id="logradouro" value="<?= htmlspecialchars($dados['logradouro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                    <input type="text" name="numero" value="<?= htmlspecialchars($dados['numero'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                    <input type="text" name="complemento" value="<?= htmlspecialchars($dados['complemento'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="Apto, sala, etc">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($dados['bairro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($dados['cidade'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                    <input type="text" name="estado" id="estado" maxlength="2" value="<?= htmlspecialchars($dados['estado'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500 uppercase" placeholder="SP">
                </div>
            </div>

            <hr class="my-6 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Anotações</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Anotações Visíveis para o Cliente
                        <span class="text-xs text-gray-500">(O cliente pode visualizar)</span>
                    </label>
                    <textarea id="anotacoes_visivel" name="anotacoes_visivel" rows="10" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500 font-mono text-sm bg-gray-50" placeholder="# Título&#10;&#10;Texto normal&#10;&#10;**Negrito**&#10;_Itálico_&#10;- Lista"><?= htmlspecialchars($dados['anotacoes_visivel'] ?? '') ?></textarea>
                </div>

                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Anotações Internas
                        <span class="text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded px-2 py-1">(Apenas sua empresa)</span>
                    </label>
                    <textarea id="anotacoes_interna" name="anotacoes_interna" rows="10" class="w-full px-4 py-3 border border-yellow-300 bg-yellow-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 font-mono text-sm" placeholder="# Título&#10;&#10;Texto normal&#10;&#10;**Negrito**&#10;_Itálico_&#10;- Lista"><?= htmlspecialchars($dados['anotacoes_interna'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-bold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i> Informações Importantes
                </h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><i class="fas fa-check mr-2"></i>Uma senha aleatória será gerada e o cliente poderá alterar após o primeiro login</li>
                    <li><i class="fas fa-check mr-2"></i>O cliente receberá um email com suas credenciais de acesso</li>
                    <li><i class="fas fa-check mr-2"></i>CPF/CNPJ e E-mail devem ser únicos no sistema</li>
                </ul>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="<?= BASE_URL ?>/admin/clientes" class="bg-gray-300 text-gray-700 px-6 py-2 rounded shadow hover:bg-gray-400 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i> Criar Cliente
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

const attachDraft = (textareaId) => {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    const key = `client-note-${textareaId}`;
    textarea.value = localStorage.getItem(key) ?? textarea.value;
    textarea.addEventListener('input', () => localStorage.setItem(key, textarea.value));
    localStorage.setItem(key, textarea.value);
};
attachDraft('anotacoes_visivel');
attachDraft('anotacoes_interna');

// ---- Busca automática por CNPJ ----
let cnpjClienteTimer = null;
function verificarCNPJCliente(valor) {
    const n = valor.replace(/\D/g, '');
    if (n.length !== 14) return;
    clearTimeout(cnpjClienteTimer);
    cnpjClienteTimer = setTimeout(async () => {
        const loading = document.getElementById('cpf-loading');
        const status  = document.getElementById('cpf-status');
        loading.classList.remove('hidden');
        status.classList.add('hidden');
        try {
            const res = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${n}`);
            if (!res.ok) throw new Error('não encontrado');
            const d = await res.json();
            setValIfEmpty('nome',              d.razao_social || '');
            setValIfEmpty('responsavel_nome',  d.nome_fantasia || '');
            setValIfEmpty('email_cliente',     d.email || '');
            setValIfEmpty('telefone_cliente',  formatarTelNC(d.ddd_telefone_1 || ''));
            setValIfEmpty('cnae_cliente',      d.cnae_fiscal ? String(d.cnae_fiscal) : '');
            const cepN = (d.cep||'').replace(/\D/g,'');
            setValIfEmpty('cep',       cepN.replace(/(\d{5})(\d)/,'$1-$2'));
            setValIfEmpty('logradouro',d.logradouro||'');
            setValIfEmpty('bairro',    d.bairro||'');
            setValIfEmpty('cidade',    d.municipio||'');
            setValIfEmpty('estado',    d.uf||'');
            status.textContent = '✔ Dados preenchidos via BrasilAPI';
            status.className = 'mt-1 text-xs text-green-600';
            status.classList.remove('hidden');
        } catch(e) {
            status.textContent = '⚠ CNPJ não encontrado. Preencha manualmente.';
            status.className = 'mt-1 text-xs text-red-500';
            status.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }, 600);
}
function setValIfEmpty(id, val) {
    const el = document.getElementById(id);
    if (el && !el.value) el.value = val;
}
function formatarTelNC(tel) {
    const n = tel.replace(/\D/g,'');
    if (n.length >= 10) return n.replace(/(\d{2})(\d{4,5})(\d{4})/,'($1) $2-$3');
    return tel;
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
