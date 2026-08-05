<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="container-fluid px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Painel de Controle</h1>
        <p class="text-gray-600 mt-2">Resumo dos dados e estatísticas do sistema</p>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total de Chamados -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total de Chamados</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?= $ticketStats['total'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-green-600">Abertos: <?= $ticketStats['aberto'] ?? 0 ?></span> • 
                        <span class="text-yellow-600">Em análise: <?= $ticketStats['em_analise'] ?? 0 ?></span>
                    </p>
                </div>
                <i class="fas fa-ticket-alt text-blue-500 text-3xl opacity-20"></i>
            </div>
        </div>

        <!-- Total de Orçamentos -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total de Orçamentos</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?= $budgetStats['total'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-orange-600">Pendentes: <?= $budgetStats['pendente'] ?? 0 ?></span> • 
                        <span class="text-green-600">Aprovados: <?= $budgetStats['aprovado'] ?? 0 ?></span>
                    </p>
                </div>
                <i class="fas fa-file-invoice-dollar text-green-500 text-3xl opacity-20"></i>
            </div>
        </div>

        <!-- Serviços Avulsos -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Serviços Avulsos</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?= $avulsoStats['total'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500 mt-2">
                        Concluídos: <span class="text-green-600">R$ <?= number_format($avulsoStats['valor_concluido'] ?? 0, 2, ',', '.') ?></span>
                    </p>
                </div>
                <i class="fas fa-tools text-purple-500 text-3xl opacity-20"></i>
            </div>
        </div>

        <!-- Clientes Cadastrados -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Clientes Cadastrados</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?= $clientCount ?></p>
                    <p class="text-xs text-gray-500 mt-2">Ativos no sistema</p>
                </div>
                <i class="fas fa-users text-orange-500 text-3xl opacity-20"></i>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Resumo Financeiro</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Orçamentos Aprovados</span>
                    <span class="text-2xl font-bold text-green-600">R$ <?= number_format($financialSummary['budgets_aprovados'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Notas Fiscais Emitidas</span>
                    <span class="text-2xl font-bold text-blue-600">R$ <?= number_format($financialSummary['notas_fiscais_total'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Serviços Avulsos Concluídos</span>
                    <span class="text-2xl font-bold text-purple-600">R$ <?= number_format($financialSummary['servicos_avulsos_concluidos'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t-2">
                    <span class="text-gray-800 font-bold">Receita Mensal (Contratos)</span>
                    <span class="text-2xl font-bold text-orange-600">R$ <?= number_format($financialSummary['contratos_mensais'] ?? 0, 2, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Status dos Orçamentos -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Status dos Orçamentos</h3>
            <canvas id="budgetChart" height="250"></canvas>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Chamados por Cliente (Top 10)</h3>
            <canvas id="ticketsChart" height="300"></canvas>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Contratos Ativos</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center pb-2 border-b">
                    <span class="text-gray-600">Total de Contratos</span>
                    <span class="font-bold text-gray-800"><?= $contractStats['total'] ?? 0 ?></span>
                </div>
                <div class="flex justify-between items-center pb-2 border-b">
                    <span class="text-gray-600">Valor Mensal Total</span>
                    <span class="font-bold text-green-600">R$ <?= number_format($contractStats['valor_total_mensal'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-gray-600">Valor Anual (Estimado)</span>
                    <span class="font-bold text-blue-600">R$ <?= number_format(($contractStats['valor_total_mensal'] ?? 0) * 12, 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Aguardando Aprovação</h3>
                <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full">
                    <?= count($pendingApprovals) ?>
                </span>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php if (count($pendingApprovals) > 0): ?>
                    <?php foreach ($pendingApprovals as $budget): ?>
                        <div class="p-3 bg-gray-50 rounded border-l-2 border-orange-500">
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($budget['titulo']) ?></p>
                            <p class="text-xs text-gray-600 mt-1">Cliente: <?= htmlspecialchars($budget['cliente_nome']) ?></p>
                            <p class="text-xs text-gray-600">Valor: R$ <?= number_format($budget['valor_total'], 2, ',', '.') ?></p>
                            <?php if ($budget['data_validade']): ?>
                                <p class="text-xs mt-1 <?= strtotime($budget['data_validade']) < time() ? 'text-red-600' : 'text-gray-600' ?>">
                                    Válido até: <?= date('d/m/Y', strtotime($budget['data_validade'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm text-center py-8">Nenhum orçamento aguardando aprovação</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Chamados Recentes</h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">
                    <?= count($recentTickets) ?>
                </span>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach ($recentTickets as $ticket): ?>
                    <div class="p-3 bg-gray-50 rounded border-l-2 border-blue-500">
                        <p class="text-sm font-medium text-gray-800">#<?= $ticket['id'] ?></p>
                        <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($ticket['cliente_nome']) ?></p>
                        <p class="text-xs text-gray-600">Status: 
                            <span class="font-semibold">
                                <?php 
                                $statusColors = [
                                    'aberto' => 'text-blue-600',
                                    'andamento' => 'text-yellow-600',
                                    'em_analise' => 'text-purple-600',
                                    'finalizado' => 'text-green-600',
                                    'rejeitado' => 'text-red-600',
                                ];
                                echo $statusColors[$ticket['status']] ?? 'text-gray-600';
                                echo ucfirst(str_replace('_', ' ', $ticket['status']));
                                ?>
                            </span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Budgets -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Orçamentos Recentes</h3>
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                    <?= count($recentBudgets) ?>
                </span>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach ($recentBudgets as $budget): ?>
                    <div class="p-3 bg-gray-50 rounded border-l-2 border-green-500">
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($budget['titulo']) ?></p>
                        <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($budget['cliente_nome']) ?></p>
                        <p class="text-xs text-gray-600">Status: 
                            <span class="font-semibold">
                                <?php 
                                $budgetStatusColors = [
                                    'pendente' => 'text-orange-600',
                                    'aprovado' => 'text-green-600',
                                    'rejeitado' => 'text-red-600',
                                    'expirado' => 'text-gray-600',
                                ];
                                echo $budgetStatusColors[$budget['status']] ?? 'text-gray-600';
                                echo ucfirst(str_replace('_', ' ', $budget['status']));
                                ?>
                            </span>
                        </p>
                        <p class="text-xs text-gray-600 mt-1">R$ <?= number_format($budget['valor_total'], 2, ',', '.') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Budget Status Chart
    const budgetData = <?= json_encode($budgetsByStatus) ?>;
    const budgetCtx = document.getElementById('budgetChart').getContext('2d');
    new Chart(budgetCtx, {
        type: 'doughnut',
        data: {
            labels: budgetData.map(b => {
                const statusLabels = {
                    'pendente': 'Pendentes',
                    'aprovado': 'Aprovados',
                    'rejeitado': 'Rejeitados',
                    'expirado': 'Expirados'
                };
                return statusLabels[b.status] || b.status;
            }),
            datasets: [{
                data: budgetData.map(b => b.quantidade),
                backgroundColor: [
                    '#FFA500',
                    '#10B981',
                    '#EF4444',
                    '#9CA3AF'
                ],
                borderColor: '#FFFFFF',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Tickets by Client Chart
    const ticketsData = <?= json_encode($ticketsByClient) ?>;
    if (ticketsData.length > 0) {
        const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
        new Chart(ticketsCtx, {
            type: 'bar',
            data: {
                labels: ticketsData.map(t => t.nome),
                datasets: [{
                    label: 'Quantidade de Chamados',
                    data: ticketsData.map(t => t.quantidade),
                    backgroundColor: '#3B82F6',
                    borderColor: '#1E40AF',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
