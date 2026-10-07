<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;
use app\Helpers\UI;
use app\Helpers\Lgpd;

$campos = [
    'Nome' => $usuario['nome'] ?? '',
    'CPF/CNPJ' => $usuario['cpf_cnpj'] ?? '',
    'E-mail' => $usuario['email'] ?? '',
    'Telefone' => $usuario['telefone'] ?? '',
    'Responsável' => $usuario['responsavel_nome'] ?? '',
    'Endereço' => trim(implode(', ', array_filter([
        $usuario['logradouro'] ?? '', $usuario['numero'] ?? '', $usuario['complemento'] ?? '',
        $usuario['bairro'] ?? '', $usuario['cidade'] ?? '', $usuario['estado'] ?? '', $usuario['cep'] ?? ''
    ]))),
];
$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-corpBlue-500';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-user-shield mr-2 text-corpBlue-500"></i> Privacidade e meus dados (LGPD)</h2>
        <p class="text-sm text-gray-500 mt-1">Aqui você consulta os dados que mantemos sobre você, baixa uma cópia e registra pedidos previstos no art. 18 da LGPD.
            Leia a <a href="<?= BASE_URL ?>/auth/privacidade" target="_blank" rel="noopener" class="text-corpBlue-600 underline">Política de Privacidade</a>.</p>
    </div>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" aria-labelledby="dados-titulo">
        <h3 id="dados-titulo" class="font-semibold text-gray-800 mb-4">Dados do seu cadastro</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <?php foreach ($campos as $rotulo => $valor): ?>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500"><?= Security::esc($rotulo) ?></dt>
                    <dd class="text-gray-800 break-words"><?= $valor !== '' ? Security::esc($valor) : '<span class="text-gray-400">Não informado</span>' ?></dd>
                </div>
            <?php endforeach; ?>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Consentimento à Política de Privacidade</dt>
                <dd class="text-gray-800">
                    <?php if (!empty($usuario['consentimento_em'])): ?>
                        Aceito em <?= UI::date($usuario['consentimento_em'], true) ?> (versão <?= Security::esc($usuario['consentimento_versao'] ?? '-') ?>)
                    <?php else: ?>
                        <span class="text-gray-500">Cadastro realizado pela empresa (base legal: execução de contrato)</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <p class="text-xs text-gray-500 mt-4">Para corrigir algum dado, abra uma solicitação de "Correção" abaixo.</p>

        <form action="<?= BASE_URL ?>/client/exportarMeusDados" method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
            <button type="submit" data-no-loading class="inline-flex items-center px-4 py-2 rounded-lg bg-corpBlue-600 text-white text-sm font-semibold hover:bg-corpBlue-700">
                <i class="fas fa-download mr-2"></i> Baixar meus dados (JSON)
            </button>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" aria-labelledby="solicitar-titulo">
        <h3 id="solicitar-titulo" class="font-semibold text-gray-800 mb-4">Nova solicitação</h3>
        <form action="<?= BASE_URL ?>/client/solicitarPrivacidade" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">O que você deseja? *</label>
                <select id="tipo" name="tipo" required class="<?= $inputClass ?>">
                    <option value="" disabled selected>Selecione...</option>
                    <?php foreach (Lgpd::TIPOS_SOLICITACAO as $valor => $rotulo): ?>
                        <option value="<?= Security::esc($valor) ?>"><?= Security::esc($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="mensagem" class="block text-sm font-medium text-gray-700 mb-1">Detalhes (opcional)</label>
                <textarea id="mensagem" name="mensagem" rows="3" maxlength="2000" class="<?= $inputClass ?>" placeholder="Explique o que precisa ser feito..."></textarea>
            </div>
            <p class="text-xs text-gray-500">Pedidos de anonimização/eliminação respeitam os prazos legais de guarda (ex.: notas fiscais): esses registros são mantidos sem identificação pessoal quando possível.</p>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold hover:bg-gray-900">
                <i class="fas fa-paper-plane mr-2"></i> Enviar solicitação
            </button>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" aria-labelledby="hist-titulo">
        <h3 id="hist-titulo" class="font-semibold text-gray-800 mb-4">Minhas solicitações</h3>
        <?php if (empty($solicitacoes)): ?>
            <p class="text-sm text-gray-500">Você ainda não abriu nenhuma solicitação.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($solicitacoes as $s): ?>
                    <li class="py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">Protocolo #<?= (int)$s['id'] ?></span>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= Lgpd::statusClass($s['status']) ?>"><?= Security::esc(Lgpd::statusLabel($s['status'])) ?></span>
                            <span class="text-xs text-gray-500"><?= UI::date($s['created_at'], true) ?></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1"><?= Security::esc(Lgpd::tipoLabel($s['tipo'])) ?></p>
                        <?php if (!empty($s['mensagem'])): ?><p class="text-sm text-gray-500 mt-1 break-words"><?= nl2br(Security::esc($s['mensagem'])) ?></p><?php endif; ?>
                        <?php if (!empty($s['resposta'])): ?>
                            <p class="text-sm text-gray-800 mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100 break-words"><strong>Resposta<?= !empty($s['atendido_em']) ? ' (' . UI::date($s['atendido_em']) . ')' : '' ?>:</strong> <?= nl2br(Security::esc($s['resposta'])) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
