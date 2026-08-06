<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-truck mr-2 text-corpBlue-600"></i>
            <?= isset($fornecedor) ? 'Editar Fornecedor' : 'Novo Fornecedor' ?>
        </h2>
        <a href="<?= BASE_URL ?>/admin/fornecedores"
           class="text-gray-500 hover:text-gray-700 transition-colors text-sm flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Notificações -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 rounded-lg px-4 py-3">
        <i class="fas fa-exclamation-circle mr-1"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <form method="POST" action="<?= isset($fornecedor)
        ? BASE_URL . '/admin/salvarEdicaoFornecedor/' . $fornecedor['id']
        : BASE_URL . '/admin/salvarFornecedor' ?>" class="space-y-6">

        <!-- Dados da Empresa -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-building text-corpBlue-500"></i> Dados da Empresa
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- CNPJ com busca automática -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                    <div class="relative">
                        <input type="text" name="cnpj" id="cnpj"
                               value="<?= htmlspecialchars($fornecedor['cnpj'] ?? '') ?>"
                               placeholder="00.000.000/0000-00" maxlength="18"
                               oninput="mascararCNPJ(this); buscarCNPJ(this.value)"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500 pr-10">
                        <span id="cnpj-loading" class="hidden absolute right-3 top-2.5 text-corpBlue-500">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </div>
                    <p id="cnpj-status" class="mt-1 text-xs hidden"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social *</label>
                    <input type="text" name="razao_social" id="razao_social"
                           value="<?= htmlspecialchars($fornecedor['razao_social'] ?? '') ?>"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Fantasia</label>
                    <input type="text" name="nome_fantasia" id="nome_fantasia"
                           value="<?= htmlspecialchars($fornecedor['nome_fantasia'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual (IE)</label>
                    <input type="text" name="ie" id="ie"
                           value="<?= htmlspecialchars($fornecedor['ie'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal (IM)</label>
                    <input type="text" name="im" id="im"
                           value="<?= htmlspecialchars($fornecedor['im'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
            </div>
        </div>

        <!-- Contato -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-phone text-green-500"></i> Contato
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" id="email"
                           value="<?= htmlspecialchars($fornecedor['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                    <input type="text" name="whatsapp" id="whatsapp"
                           value="<?= htmlspecialchars($fornecedor['whatsapp'] ?? '') ?>"
                           placeholder="(00) 90000-0000"
                           oninput="mascararTelefone(this)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" id="telefone"
                           value="<?= htmlspecialchars($fornecedor['telefone'] ?? '') ?>"
                           placeholder="(00) 0000-0000"
                           oninput="mascararTelefone(this)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-red-500"></i> Endereço
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                    <input type="text" name="cep" id="cep"
                           value="<?= htmlspecialchars($fornecedor['cep'] ?? '') ?>"
                           placeholder="00000-000" maxlength="9"
                           oninput="mascararCEP(this); buscarCEP(this.value)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                    <input type="text" name="logradouro" id="logradouro"
                           value="<?= htmlspecialchars($fornecedor['logradouro'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                    <input type="text" name="numero" id="numero"
                           value="<?= htmlspecialchars($fornecedor['numero'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                    <input type="text" name="complemento" id="complemento"
                           value="<?= htmlspecialchars($fornecedor['complemento'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                    <input type="text" name="bairro" id="bairro"
                           value="<?= htmlspecialchars($fornecedor['bairro'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                    <input type="text" name="cidade" id="cidade"
                           value="<?= htmlspecialchars($fornecedor['cidade'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                    <input type="text" name="uf" id="uf"
                           value="<?= htmlspecialchars($fornecedor['uf'] ?? '') ?>"
                           maxlength="2"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código IBGE</label>
                    <input type="text" name="codigo_ibge" id="codigo_ibge"
                           value="<?= htmlspecialchars($fornecedor['codigo_ibge'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
            </div>
        </div>

        <!-- Dados Fiscais -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-yellow-500"></i> Dados Fiscais
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário (CRT)</label>
                    <select name="crt" id="crt"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                        <option value="1" <?= ($fornecedor['crt'] ?? '1') == '1' ? 'selected' : '' ?>>1 – Simples Nacional</option>
                        <option value="2" <?= ($fornecedor['crt'] ?? '') == '2' ? 'selected' : '' ?>>2 – Simples Nacional – Excesso</option>
                        <option value="3" <?= ($fornecedor['crt'] ?? '') == '3' ? 'selected' : '' ?>>3 – Lucro Presumido / Real</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Indicador IE</label>
                    <select name="indicador_ie" id="indicador_ie"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                        <option value="1" <?= ($fornecedor['indicador_ie'] ?? '') == '1' ? 'selected' : '' ?>>1 – Contribuinte ICMS</option>
                        <option value="2" <?= ($fornecedor['indicador_ie'] ?? '') == '2' ? 'selected' : '' ?>>2 – Contribuinte Isento</option>
                        <option value="9" <?= ($fornecedor['indicador_ie'] ?? '9') == '9' ? 'selected' : '' ?>>9 – Não Contribuinte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNAE Principal</label>
                    <input type="text" name="cnae_principal" id="cnae_principal"
                           value="<?= htmlspecialchars($fornecedor['cnae_principal'] ?? '') ?>"
                           placeholder="0000-0/00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500">
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-sticky-note text-gray-400"></i> Observações
            </h3>
            <textarea name="observacoes" rows="4"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-corpBlue-500 resize-none"
                      placeholder="Observações internas sobre o fornecedor..."><?= htmlspecialchars($fornecedor['observacoes'] ?? '') ?></textarea>
        </div>

        <!-- Botões -->
        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="<?= BASE_URL ?>/admin/fornecedores"
               class="px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="px-8 py-2 rounded-lg bg-corpBlue-600 text-white font-semibold hover:bg-corpBlue-700 transition-colors shadow">
                <i class="fas fa-save mr-2"></i>
                <?= isset($fornecedor) ? 'Salvar Alterações' : 'Cadastrar Fornecedor' ?>
            </button>
        </div>
    </form>
</div>

<script>
function mascararCNPJ(el) {
    let v = el.value.replace(/\D/g, '').substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    el.value = v;
}

function mascararCEP(el) {
    let v = el.value.replace(/\D/g, '').substring(0, 8);
    el.value = v.length > 5 ? v.replace(/(\d{5})(\d)/, '$1-$2') : v;
}

function mascararTelefone(el) {
    let v = el.value.replace(/\D/g, '').substring(0, 11);
    if (v.length === 11) v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    else if (v.length === 10) v = v.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    el.value = v;
}

let cnpjTimer = null;
function buscarCNPJ(valor) {
    const cnpjNumeros = valor.replace(/\D/g, '');
    if (cnpjNumeros.length !== 14) return;

    clearTimeout(cnpjTimer);
    cnpjTimer = setTimeout(async () => {
        const loading = document.getElementById('cnpj-loading');
        const status  = document.getElementById('cnpj-status');
        loading.classList.remove('hidden');
        status.classList.add('hidden');

        try {
            const res = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjNumeros}`);
            if (!res.ok) throw new Error('CNPJ não encontrado');
            const d = await res.json();

            setField('razao_social',   d.razao_social || '');
            setField('nome_fantasia',  d.nome_fantasia || '');
            setField('email',          d.email || '');
            setField('telefone',       formatarTel(d.ddd_telefone_1 || ''));
            setField('cep',            (d.cep || '').replace(/\D/g,'').replace(/(\d{5})(\d)/,'$1-$2'));
            setField('logradouro',     d.logradouro || '');
            setField('numero',         d.numero || '');
            setField('complemento',    d.complemento || '');
            setField('bairro',         d.bairro || '');
            setField('cidade',         d.municipio || '');
            setField('uf',             d.uf || '');
            setField('cnae_principal', d.cnae_fiscal_descricao ? String(d.cnae_fiscal) : '');

            status.textContent = '✔ Dados preenchidos automaticamente via BrasilAPI';
            status.className = 'mt-1 text-xs text-green-600';
            status.classList.remove('hidden');
        } catch (e) {
            status.textContent = '⚠ CNPJ não encontrado. Preencha os campos manualmente.';
            status.className = 'mt-1 text-xs text-red-500';
            status.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }, 600);
}

function setField(id, val) {
    const el = document.getElementById(id);
    if (el && !el.value) el.value = val;
}

function formatarTel(tel) {
    const n = tel.replace(/\D/g, '');
    if (n.length >= 10) return n.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
    return tel;
}

let cepTimer = null;
function buscarCEP(val) {
    const cep = val.replace(/\D/g, '');
    if (cep.length !== 8) return;
    clearTimeout(cepTimer);
    cepTimer = setTimeout(async () => {
        try {
            const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const d = await r.json();
            if (d.erro) return;
            setField('logradouro', d.logradouro || '');
            setField('bairro',     d.bairro || '');
            setField('cidade',     d.localidade || '');
            setField('uf',         d.uf || '');
            setField('codigo_ibge',d.ibge || '');
        } catch(e) {}
    }, 500);
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
