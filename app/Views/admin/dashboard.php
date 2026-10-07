<?php
/**
 * Visão Geral (Dashboard Admin).
 *
 * Variáveis recebidas de DashboardController::index() -> DashboardModel:
 *   $ticketKpis, $budgetKpis, $financeKpis, $userKpis, $systemStatus   (cards)
 *   $ticketFlow, $cashFlow                                              (gráficos)
 *   $recentTickets, $upcomingDues, $adminLogs                           (tabelas)
 *
 * Onde injetar dados dinâmicos: procure os comentários "DADOS DINÂMICOS" abaixo. Cada bloco indica
 * a variável do controller e o método do model que a gera.
 */

use app\Helpers\Security;
use app\Helpers\UI;

require_once APP_PATH . '/Views/layout/header.php';

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$base = BASE_URL . '/admin';
$int = fn($v) => number_format((int)$v, 0, ',', '.');

// Classes fixas por cor (o Tailwind CDN precisa enxergar a classe completa).
$palette = [
    'blue'   => ['border' => 'border-blue-500',   'icon' => 'bg-blue-50 text-blue-600'],
    'yellow' => ['border' => 'border-yellow-500', 'icon' => 'bg-yellow-50 text-yellow-600'],
    'indigo' => ['border' => 'border-indigo-500', 'icon' => 'bg-indigo-50 text-indigo-600'],
    'green'  => ['border' => 'border-green-500',  'icon' => 'bg-green-50 text-green-600'],
    'red'    => ['border' => 'border-red-500',    'icon' => 'bg-red-50 text-red-600'],
    'purple' => ['border' => 'border-purple-500', 'icon' => 'bg-purple-50 text-purple-600'],
    'orange' => ['border' => 'border-orange-500', 'icon' => 'bg-orange-50 text-orange-600'],
    'gray'   => ['border' => 'border-gray-400',   'icon' => 'bg-gray-100 text-gray-600'],
];

/**
 * Card de KPI. $c: label, value (já formatado), icon, color, sub (HTML já escapado, opcional), href (opcional).
 */
$kpiCard = function (array $c) use ($palette) {
    $p = $palette[$c['color'] ?? 'blue'];
    $tag = !empty($c['href']) ? 'a' : 'div';
    $href = !empty($c['href']) ? ' href="' . Security::esc($c['href']) . '"' : '';
    $hover = !empty($c['href']) ? ' hover:shadow-md transition-shadow' : '';
    ob_start(); ?>
    <<?= $tag . $href ?> class="block bg-white rounded-lg shadow-sm p-5 border-l-4 <?= $p['border'] . $hover ?>">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide"><?= Security::esc($c['label']) ?></p>
                <p class="text-2xl font-bold text-gray-800 mt-1 truncate"><?= $c['value'] ?></p>
            </div>
            <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center <?= $p['icon'] ?>">
                <i class="fas <?= Security::esc($c['icon']) ?>"></i>
            </div>
        </div>
        <?php if (!empty($c['sub'])): ?>
            <p class="text-xs text-gray-500 mt-3"><?= $c['sub'] ?></p>
        <?php endif; ?>
    </<?= $tag ?>>
    <?php
    return ob_get_clean();
};

$sectionTitle = fn($icon, $text) => '<h3 class="flex items-center text-sm font-bold text-gray-600 uppercase tracking-wider mb-3">'
    . '<i class="fas ' . $icon . ' mr-2 text-corpBlue-600"></i>' . Security::esc($text) . '</h3>';

$saldoPositivo = $financeKpis['saldo_geral'] >= 0;
$statusSistema = [
    'operacional' => ['label' => 'Operacional', 'badge' => 'bg-green-100 text-green-800 ring-green-200', 'dot' => 'bg-green-500', 'color' => 'green', 'icon' => 'fa-heartbeat'],
    'atencao'     => ['label' => 'Atenção',     'badge' => 'bg-yellow-100 text-yellow-800 ring-yellow-200', 'dot' => 'bg-yellow-500', 'color' => 'yellow', 'icon' => 'fa-exclamation-triangle'],
    'critico'     => ['label' => 'Indisponível', 'badge' => 'bg-red-100 text-red-800 ring-red-200', 'dot' => 'bg-red-500', 'color' => 'red', 'icon' => 'fa-times-circle'],
][$systemStatus['nivel']];

$decididos = $budgetKpis['aprovado'] + $budgetKpis['rejeitado'];
$totalVencidoReceber = $financeKpis['receber']['vencido'];
$totalVencidoPagar = $financeKpis['pagar']['vencido'];
?>

<div class="max-w-screen-2xl mx-auto space-y-8">

    <!-- ===== Cabeçalho ===== -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Visão Geral</h2>
            <p class="text-sm text-gray-500">Resumo operacional e financeiro &middot; atualizado em <?= date('d/m/Y \à\s H:i') ?></p>
        </div>
        <a href="<?= $base ?>" class="inline-flex items-center text-sm text-corpBlue-600 hover:text-corpBlue-800 font-medium">
            <i class="fas fa-sync-alt mr-2"></i> Atualizar dados
        </a>
    </div>

    <!-- ===== Alertas críticos (só aparecem quando há algo a tratar) ===== -->
    <?php if ($totalVencidoReceber > 0 || $totalVencidoPagar > 0 || $systemStatus['nivel'] !== 'operacional'): ?>
    <div class="space-y-2" role="alert">
        <?php if ($totalVencidoReceber > 0): ?>
            <a href="<?= $base ?>/contabil" class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm hover:bg-red-100">
                <i class="fas fa-exclamation-circle"></i>
                <span><strong><?= $int($financeKpis['receber']['vencido_qtd']) ?></strong> recebimento(s) em atraso somando <strong><?= UI::money($totalVencidoReceber) ?></strong>.</span>
            </a>
        <?php endif; ?>
        <?php if ($totalVencidoPagar > 0): ?>
            <a href="<?= $base ?>/contabil" class="flex items-center gap-3 bg-orange-50 border border-orange-200 text-orange-800 rounded-lg px-4 py-3 text-sm hover:bg-orange-100">
                <i class="fas fa-file-invoice-dollar"></i>
                <span><strong><?= $int($financeKpis['pagar']['vencido_qtd']) ?></strong> conta(s) a pagar vencida(s) somando <strong><?= UI::money($totalVencidoPagar) ?></strong>.</span>
            </a>
        <?php endif; ?>
        <?php if ($systemStatus['nivel'] !== 'operacional'): ?>
            <div class="flex items-center gap-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3 text-sm">
                <i class="fas fa-server"></i>
                <span>Status do sistema: <strong><?= $statusSistema['label'] ?></strong>
                    <?php if (!$systemStatus['db_ok']): ?>&mdash; banco de dados sem resposta.<?php endif; ?>
                    <?php if (!$systemStatus['uploads_ok']): ?>&mdash; pasta <code>uploads</code> sem permissão de escrita.<?php endif; ?>
                    <?php if ($systemStatus['disco_livre_pct'] !== null && $systemStatus['disco_livre_pct'] < 10): ?>&mdash; pouco espaço em disco (<?= $systemStatus['disco_livre_pct'] ?>% livre).<?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- =====================================================================
         1. KPIs
         ===================================================================== -->

    <!-- Chamados -->
    <section aria-label="Resumo de chamados">
        <?= $sectionTitle('fa-ticket-alt', 'Chamados') ?>
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- DADOS DINÂMICOS: $ticketKpis (DashboardModel::getTicketKpis) -->
            <?= $kpiCard(['label' => 'Total de chamados', 'value' => $int($ticketKpis['total']), 'icon' => 'fa-ticket-alt', 'color' => 'blue', 'href' => "$base/chamados"]) ?>
            <?= $kpiCard(['label' => 'Em aberto', 'value' => $int($ticketKpis['aberto']), 'icon' => 'fa-folder-open', 'color' => 'yellow', 'href' => "$base/chamados",
                'sub' => 'Aguardando primeiro atendimento']) ?>
            <?= $kpiCard(['label' => 'Em andamento', 'value' => $int($ticketKpis['andamento']), 'icon' => 'fa-spinner', 'color' => 'indigo', 'href' => "$base/chamados",
                'sub' => 'Análise, execução ou aguardando peça']) ?>
            <?= $kpiCard(['label' => 'Encerrados', 'value' => $int($ticketKpis['encerrado']), 'icon' => 'fa-check-circle', 'color' => 'green', 'href' => "$base/chamados",
                'sub' => $ticketKpis['total'] > 0 ? Security::esc(number_format($ticketKpis['encerrado'] / $ticketKpis['total'] * 100, 0)) . '% do total' : 'Sem chamados']) ?>
        </div>
    </section>

    <!-- Orçamentos -->
    <section aria-label="Resumo de orçamentos">
        <?= $sectionTitle('fa-hand-holding-usd', 'Orçamentos') ?>
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- DADOS DINÂMICOS: $budgetKpis (DashboardModel::getBudgetKpis) -->
            <?= $kpiCard(['label' => 'Total emitidos', 'value' => $int($budgetKpis['total']), 'icon' => 'fa-file-invoice-dollar', 'color' => 'blue', 'href' => "$base/orcamentos"]) ?>
            <?= $kpiCard(['label' => 'Pendentes', 'value' => $int($budgetKpis['pendente']), 'icon' => 'fa-clock', 'color' => 'yellow', 'href' => "$base/orcamentos",
                'sub' => UI::money($budgetKpis['valor_pendente']) . ' em negociação' . ($budgetKpis['expirado'] > 0 ? ' &middot; ' . $int($budgetKpis['expirado']) . ' expirado(s)' : '')]) ?>
            <?= $kpiCard(['label' => 'Aceitos', 'value' => $int($budgetKpis['aprovado']), 'icon' => 'fa-check-circle', 'color' => 'green', 'href' => "$base/orcamentos",
                'sub' => UI::money($budgetKpis['valor_aprovado']) . ' aprovados']) ?>
            <?= $kpiCard(['label' => 'Rejeitados', 'value' => $int($budgetKpis['rejeitado']), 'icon' => 'fa-times-circle', 'color' => 'red', 'href' => "$base/orcamentos",
                'sub' => $budgetKpis['taxa_conversao'] !== null ? 'Conversão geral: ' . number_format($budgetKpis['taxa_conversao'], 1, ',', '.') . '%' : 'Sem orçamentos decididos']) ?>
        </div>
    </section>

    <!-- Financeiro -->
    <section aria-label="Resumo financeiro">
        <?= $sectionTitle('fa-coins', 'Financeiro') ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- DADOS DINÂMICOS: $financeKpis (DashboardModel::getFinanceKpis) - base: tabela financeiro_contabil -->
            <?= $kpiCard(['label' => 'A receber', 'value' => UI::money($financeKpis['receber']['total']), 'icon' => 'fa-arrow-circle-down', 'color' => 'green', 'href' => "$base/contabil",
                'sub' => '<span class="text-red-600 font-medium">Inadimplência: ' . UI::money($financeKpis['receber']['vencido']) . '</span>'
                    . ' &middot; <span class="text-green-700 font-medium">A vencer: ' . UI::money($financeKpis['receber']['a_vencer']) . '</span>']) ?>
            <?= $kpiCard(['label' => 'A pagar', 'value' => UI::money($financeKpis['pagar']['total']), 'icon' => 'fa-arrow-circle-up', 'color' => 'red', 'href' => "$base/contabil",
                'sub' => '<span class="text-red-600 font-medium">Vencidas: ' . UI::money($financeKpis['pagar']['vencido']) . '</span>'
                    . ' &middot; <span class="text-gray-700 font-medium">A vencer: ' . UI::money($financeKpis['pagar']['a_vencer']) . '</span>']) ?>
            <?= $kpiCard(['label' => 'Saldo geral', 'value' => UI::money($financeKpis['saldo_geral']), 'icon' => 'fa-wallet', 'color' => $saldoPositivo ? 'blue' : 'red', 'href' => "$base/contabil",
                'sub' => 'Faturamento do mês: <span class="text-green-700 font-medium">' . UI::money($financeKpis['receita_mes']) . '</span>'
                    . ' &middot; Despesas: <span class="text-red-600 font-medium">' . UI::money($financeKpis['despesa_mes']) . '</span>'
                    . ' &middot; Resultado: <span class="font-medium ' . ($financeKpis['saldo_mes'] >= 0 ? 'text-blue-700' : 'text-red-600') . '">' . UI::money($financeKpis['saldo_mes']) . '</span>']) ?>
        </div>
    </section>

    <!-- Administração do sistema -->
    <section aria-label="Administração do sistema">
        <?= $sectionTitle('fa-user-shield', 'Administração do sistema') ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- DADOS DINÂMICOS: $userKpis (DashboardModel::getUserKpis) -->
            <?= $kpiCard(['label' => 'Clientes cadastrados', 'value' => $int($userKpis['clientes']), 'icon' => 'fa-users', 'color' => 'purple', 'href' => "$base/clientes",
                'sub' => '<strong>' . $int($userKpis['clientes_ativos']) . '</strong> com chamados nos últimos ' . $userKpis['periodo_dias'] . ' dias &middot; Equipe: ' . $int($userKpis['equipe'])]) ?>
            <?= $kpiCard(['label' => 'Novos registros (' . $userKpis['periodo_dias'] . ' dias)', 'value' => $int($userKpis['novos_clientes'] + $userKpis['novos_equipe']), 'icon' => 'fa-user-plus', 'color' => 'orange', 'href' => "$base/usuarios",
                'sub' => $int($userKpis['novos_clientes']) . ' cliente(s) &middot; ' . $int($userKpis['novos_equipe']) . ' funcionário(s)']) ?>

            <!-- DADOS DINÂMICOS: $systemStatus (DashboardModel::getSystemStatus) -->
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 <?= $palette[$statusSistema['color']]['border'] ?>">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status do sistema</p>
                        <p class="mt-2">
                            <span class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-full ring-1 ring-inset <?= $statusSistema['badge'] ?>">
                                <span class="w-2 h-2 rounded-full <?= $statusSistema['dot'] ?>"></span> <?= $statusSistema['label'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center <?= $palette[$statusSistema['color']]['icon'] ?>">
                        <i class="fas <?= $statusSistema['icon'] ?>"></i>
                    </div>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-500">
                    <dt>Uptime do servidor</dt>
                    <dd class="text-right font-medium text-gray-700"><?= $systemStatus['uptime_segundos'] !== null ? UI::duration($systemStatus['uptime_segundos']) : 'Indisponível' ?></dd>
                    <dt>Banco de dados</dt>
                    <dd class="text-right font-medium <?= $systemStatus['db_ok'] ? 'text-gray-700' : 'text-red-600' ?>"><?= $systemStatus['db_ok'] ? $systemStatus['db_ms'] . ' ms' : 'Sem resposta' ?></dd>
                    <dt>Disco livre</dt>
                    <dd class="text-right font-medium text-gray-700"><?= $systemStatus['disco_livre_pct'] !== null ? number_format($systemStatus['disco_livre_pct'], 1, ',', '.') . '%' : 'Indisponível' ?></dd>
                    <dt>PHP</dt>
                    <dd class="text-right font-medium text-gray-700"><?= Security::esc($systemStatus['php_version']) ?></dd>
                </dl>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         2. GRÁFICOS (Chart.js - os dados são injetados no <script> ao final da página)
         ===================================================================== -->
    <section aria-label="Gráficos" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Chamados abertos x encerrados (30 dias) -->
        <div class="lg:col-span-8 bg-white rounded-lg shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Chamados abertos x encerrados</h3>
                    <p class="text-xs text-gray-500">Últimos <?= count($ticketFlow['labels']) ?> dias &middot;
                        <span class="text-blue-600 font-medium"><?= $int($ticketFlow['total_abertos']) ?> abertos</span> &middot;
                        <span class="text-green-600 font-medium"><?= $int($ticketFlow['total_encerrados']) ?> encerrados</span>
                    </p>
                </div>
                <div class="inline-flex rounded-md shadow-sm text-xs" role="group" aria-label="Tipo de gráfico">
                    <button type="button" data-flow-type="line" class="flow-type-btn px-3 py-1.5 border border-gray-300 rounded-l-md bg-corpBlue-900 text-white">Linhas</button>
                    <button type="button" data-flow-type="bar" class="flow-type-btn px-3 py-1.5 border border-gray-300 rounded-r-md bg-white text-gray-700 hover:bg-gray-50">Barras</button>
                </div>
            </div>
            <!-- DADOS DINÂMICOS: $ticketFlow (DashboardModel::getTicketFlow) -->
            <div class="relative h-72"><canvas id="chartTicketFlow" aria-label="Gráfico de chamados abertos e encerrados por dia" role="img"></canvas></div>
        </div>

        <!-- Conversão de orçamentos -->
        <div class="lg:col-span-4 bg-white rounded-lg shadow-sm p-5 flex flex-col">
            <h3 class="text-base font-bold text-gray-800">Taxa de conversão de orçamentos</h3>
            <p class="text-xs text-gray-500 mb-4">Aceitos x rejeitados</p>
            <!-- DADOS DINÂMICOS: $budgetKpis['aprovado'] / ['rejeitado'] (DashboardModel::getBudgetKpis) -->
            <div class="relative h-56 flex-1">
                <canvas id="chartBudgetConversion" aria-label="Gráfico de conversão de orçamentos" role="img"></canvas>
                <?php if ($decididos > 0): ?>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-3xl font-bold text-gray-800"><?= number_format($budgetKpis['taxa_conversao'], 1, ',', '.') ?>%</span>
                    <span class="text-xs text-gray-500">de conversão</span>
                </div>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-4 text-center">
                <?= $int($budgetKpis['pendente']) ?> pendente(s) e <?= $int($budgetKpis['expirado']) ?> expirado(s) não entram no cálculo.
            </p>
        </div>

        <!-- Fluxo de caixa -->
        <div class="lg:col-span-5 bg-white rounded-lg shadow-sm p-5">
            <h3 class="text-base font-bold text-gray-800">Fluxo de caixa</h3>
            <p class="text-xs text-gray-500 mb-4">Receitas x despesas pagas &middot; últimos <?= count($cashFlow['labels']) ?> meses</p>
            <!-- DADOS DINÂMICOS: $cashFlow (DashboardModel::getCashFlow) -->
            <div class="relative h-72"><canvas id="chartCashFlow" aria-label="Gráfico de fluxo de caixa" role="img"></canvas></div>
        </div>

        <!-- Próximos vencimentos (7 dias) -->
        <div class="lg:col-span-7 bg-white rounded-lg shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Próximos vencimentos financeiros</h3>
                    <p class="text-xs text-gray-500">Contas a pagar e a receber nos próximos <?= (int)$diasProximosVencimentos ?> dias</p>
                </div>
                <a href="<?= $base ?>/contabil" class="text-xs text-corpBlue-600 hover:underline font-medium">Ver financeiro</a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="responsive-table min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Vencimento</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Descrição</th>
                            <th class="px-4 py-3 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($upcomingDues)): ?>
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-calendar-check text-2xl text-green-300 block mb-2"></i>Nenhum vencimento nos próximos <?= (int)$diasProximosVencimentos ?> dias.</td></tr>
                        <?php endif; ?>
                        <!-- DADOS DINÂMICOS: $upcomingDues (DashboardModel::getUpcomingDues) -->
                        <?php foreach ($upcomingDues as $due): $isReceita = $due['tipo'] === 'receita'; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap" data-label="Vencimento">
                                    <span class="font-medium text-gray-800"><?= UI::date($due['data_vencimento']) ?></span>
                                    <span class="block text-xs text-gray-500"><?= UI::dueLabel($due['data_vencimento']) ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" data-label="Tipo">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full ring-1 ring-inset <?= $isReceita ? 'bg-green-100 text-green-800 ring-green-200' : 'bg-red-100 text-red-800 ring-red-200' ?>">
                                        <i class="fas <?= $isReceita ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i> <?= $isReceita ? 'A receber' : 'A pagar' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3" data-label="Descrição">
                                    <span class="text-gray-800"><?= Security::esc($due['descricao']) ?></span>
                                    <?php if (!empty($due['parte_nome'])): ?><span class="block text-xs text-gray-500"><?= Security::esc($due['parte_nome']) ?></span><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-semibold <?= $isReceita ? 'text-green-700' : 'text-red-600' ?>" data-label="Valor"><?= UI::money($due['valor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         3. ATIVIDADE RECENTE
         ===================================================================== -->
    <section aria-label="Atividade recente" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Últimos chamados -->
        <div class="lg:col-span-8 bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-800">Últimos chamados</h3>
                <a href="<?= $base ?>/chamados" class="text-xs text-corpBlue-600 hover:underline font-medium">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="responsive-table min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Assunto</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($recentTickets)): ?>
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-inbox text-2xl text-gray-300 block mb-2"></i>Nenhum chamado registrado.</td></tr>
                        <?php endif; ?>
                        <!-- DADOS DINÂMICOS: $recentTickets (DashboardModel::getRecentTickets) -->
                        <?php foreach ($recentTickets as $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium" data-label="ID">
                                    <a href="<?= $base ?>/editarChamado/<?= (int)$t['id'] ?>" class="text-corpBlue-600 hover:underline">#<?= (int)$t['id'] ?></a>
                                </td>
                                <td class="px-4 py-3 text-gray-800" data-label="Cliente"><?= Security::esc($t['cliente_nome']) ?></td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs" data-label="Assunto">
                                    <span class="font-medium text-gray-800"><?= Security::esc($t['tipo_servico']) ?></span>
                                    <?php if (trim((string)$t['descricao_resumo']) !== ''): ?>
                                        <span class="block text-xs text-gray-500 truncate"><?= Security::esc($t['descricao_resumo']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" data-label="Status"><?= UI::ticketStatusBadge($t['status']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500" data-label="Data"><?= UI::date($t['created_at'], true) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Logs administrativos -->
        <div class="lg:col-span-4 bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b">
                <h3 class="text-base font-bold text-gray-800">Logs administrativos</h3>
                <p class="text-xs text-gray-500">Últimas ações críticas no sistema</p>
            </div>
            <!-- DADOS DINÂMICOS: $adminLogs (DashboardModel::getRecentAdminLogs, gravados por AuditLogModel::record) -->
            <?php if (!$adminLogs['disponivel']): ?>
                <div class="p-6 text-sm text-gray-600 text-center">
                    <i class="fas fa-database text-2xl text-yellow-400 block mb-2"></i>
                    A tabela <code>admin_logs</code> ainda não existe. Execute <code>database/migracao_correcao_schema.sql</code> para ativar a auditoria.
                </div>
            <?php elseif (empty($adminLogs['registros'])): ?>
                <div class="p-6 text-sm text-gray-500 text-center">
                    <i class="fas fa-clipboard-list text-2xl text-gray-300 block mb-2"></i>Nenhuma ação registrada ainda.
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto">
                    <?php foreach ($adminLogs['registros'] as $log): $lvl = UI::logLevel($log['nivel']); ?>
                        <li class="px-5 py-3 flex gap-3">
                            <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 <?= $lvl['dot'] ?>" title="<?= Security::esc($lvl['label']) ?>"></span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 break-words"><?= Security::esc($log['descricao']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <span class="font-medium <?= $lvl['text'] ?>"><?= Security::esc($log['user_name']) ?></span>
                                    &middot; <?= UI::date($log['created_at'], true) ?>
                                    <?php if (!empty($log['ip'])): ?>&middot; <?= Security::esc($log['ip']) ?><?php endif; ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Chart.js (mesma CDN usada pelo restante do projeto, versão fixada) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ===== DADOS DINÂMICOS: tudo abaixo vem do controller via json_encode (já escapado contra XSS) =====
    const ticketFlow = <?= json_encode($ticketFlow, $jsonFlags) ?>;
    const cashFlow = <?= json_encode($cashFlow, $jsonFlags) ?>;
    const budgetConversion = <?= json_encode(['aprovados' => (int)$budgetKpis['aprovado'], 'rejeitados' => (int)$budgetKpis['rejeitado']], $jsonFlags) ?>;

    const money = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const showEmpty = (canvasId, text) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const note = document.createElement('div');
        note.className = 'absolute inset-0 flex items-center justify-center text-sm text-gray-500 text-center px-4';
        note.textContent = text;
        canvas.style.visibility = 'hidden';
        canvas.parentNode.appendChild(note);
    };

    if (typeof Chart === 'undefined') {
        ['chartTicketFlow', 'chartBudgetConversion', 'chartCashFlow'].forEach((id) => showEmpty(id, 'Não foi possível carregar a biblioteca de gráficos.'));
        return;
    }

    Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.color = '#6b7280';

    // ----- Chamados abertos x encerrados -----
    const flowCanvas = document.getElementById('chartTicketFlow');
    let flowChart = null;
    const buildFlowChart = (type) => {
        if (flowChart) flowChart.destroy();
        const isLine = type === 'line';
        flowChart = new Chart(flowCanvas, {
            type: type,
            data: {
                labels: ticketFlow.labels,
                datasets: [
                    { label: 'Abertos', data: ticketFlow.abertos, borderColor: '#3b82f6', backgroundColor: isLine ? 'rgba(59,130,246,.12)' : '#3b82f6', fill: isLine, tension: .3, pointRadius: isLine ? 2 : 0, borderWidth: 2 },
                    { label: 'Encerrados', data: ticketFlow.encerrados, borderColor: '#10b981', backgroundColor: isLine ? 'rgba(16,185,129,.12)' : '#10b981', fill: isLine, tension: .3, pointRadius: isLine ? 2 : 0, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10, autoSkip: true } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    };
    if (ticketFlow.total_abertos + ticketFlow.total_encerrados === 0) {
        showEmpty('chartTicketFlow', 'Sem movimentação de chamados no período.');
        document.querySelectorAll('.flow-type-btn').forEach((b) => { b.disabled = true; b.classList.add('opacity-50', 'cursor-not-allowed'); });
    } else {
        buildFlowChart('line');
        document.querySelectorAll('.flow-type-btn').forEach((btn) => btn.addEventListener('click', () => {
            document.querySelectorAll('.flow-type-btn').forEach((b) => {
                const active = b === btn;
                b.classList.toggle('bg-corpBlue-900', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('bg-white', !active);
                b.classList.toggle('text-gray-700', !active);
            });
            buildFlowChart(btn.dataset.flowType);
        }));
    }

    // ----- Conversão de orçamentos (donut) -----
    if (budgetConversion.aprovados + budgetConversion.rejeitados === 0) {
        showEmpty('chartBudgetConversion', 'Nenhum orçamento aceito ou rejeitado ainda.');
    } else {
        new Chart(document.getElementById('chartBudgetConversion'), {
            type: 'doughnut',
            data: {
                labels: ['Aceitos', 'Rejeitados'],
                datasets: [{ data: [budgetConversion.aprovados, budgetConversion.rejeitados], backgroundColor: ['#10b981', '#ef4444'], borderColor: '#fff', borderWidth: 3 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '68%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // ----- Fluxo de caixa (receitas x despesas + saldo) -----
    const hasCash = cashFlow.receitas.concat(cashFlow.despesas).some((v) => v > 0);
    if (!hasCash) {
        showEmpty('chartCashFlow', 'Nenhuma receita ou despesa paga no período.');
    } else {
        new Chart(document.getElementById('chartCashFlow'), {
            data: {
                labels: cashFlow.labels,
                datasets: [
                    { type: 'bar', label: 'Receitas', data: cashFlow.receitas, backgroundColor: '#10b981', borderRadius: 4, order: 2 },
                    { type: 'bar', label: 'Despesas', data: cashFlow.despesas, backgroundColor: '#ef4444', borderRadius: 4, order: 2 },
                    { type: 'line', label: 'Saldo', data: cashFlow.saldo, borderColor: '#1e3a8a', backgroundColor: '#1e3a8a', tension: .3, pointRadius: 3, borderWidth: 2, order: 1 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + money(ctx.parsed.y) } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { callback: (v) => 'R$ ' + Number(v).toLocaleString('pt-BR') } }
                }
            }
        });
    }
})();
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
