<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<!-- Adiciona estilo CSS apenas para a impressão (Oculta botões e menus) -->
<style>
    @media print {
        #sidebar, header, .no-print { display: none !important; }
        main { background-color: white !important; padding: 0 !important; }
        .print-break { page-break-before: always; }
        .shadow-sm { box-shadow: none !important; }
        .bg-gray-50 { background-color: transparent !important; }
        .border { border: 1px solid #ddd !important; }
    }
</style>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4 no-print">
    <h2 class="text-2xl font-bold text-gray-800">Central de Relatórios</h2>
    <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-gray-900 transition-colors text-sm font-medium">
        <i class="fas fa-print mr-1"></i> Imprimir Relatórios
    </button>
</div>

<!-- Relatório 1: Balanço de Clientes -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="font-bold text-gray-800 uppercase tracking-wider text-sm"><i class="fas fa-users mr-2 text-indigo-500"></i> Relatório Analítico de Clientes</h3>
    </div>
    <div class="p-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Cliente / Razão Social</th>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Documento</th>
                    <th class="px-4 py-2 text-center font-bold text-gray-700">Chamados Abertos</th>
                    <th class="px-4 py-2 text-center font-bold text-gray-700">Contratos SLA Ativos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($c['nome']) ?></td>
                    <td class="px-4 py-3 text-gray-600 font-mono"><?= htmlspecialchars($c['cpf_cnpj']) ?></td>
                    <td class="px-4 py-3 text-center text-gray-800 font-bold"><?= $c['total_chamados'] ?></td>
                    <td class="px-4 py-3 text-center text-gray-800 font-bold"><?= $c['total_contratos'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Relatório 2: Tempo de Serviços e Horas Trabalhadas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 print-break">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="font-bold text-gray-800 uppercase tracking-wider text-sm"><i class="fas fa-clock mr-2 text-green-500"></i> Relatório de Serviços Executados e Tempo Gasto</h3>
    </div>
    <div class="p-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Ref.</th>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Cliente / Serviço</th>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Técnico</th>
                    <th class="px-4 py-2 text-center font-bold text-gray-700">Tempo de Execução</th>
                    <th class="px-4 py-2 text-left font-bold text-gray-700">Data Fechamento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($servicos)): ?>
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhum serviço finalizado no sistema.</td></tr>
                <?php else: ?>
                    <?php 
                        $totalMinutosGlobais = 0;
                        foreach ($servicos as $s): 
                            $minutos = (int)$s['tempo_minutos'];
                            $totalMinutosGlobais += $minutos;
                            
                            $horas = floor($minutos / 60);
                            $minutos_restantes = $minutos % 60;
                            $tempoFormatado = sprintf("%02d:%02d h", $horas, $minutos_restantes);
                    ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 font-mono">#<?= $s['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($s['cliente_nome']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($s['tipo_servico']) ?> (<?= ucfirst($s['atendimento']) ?>)</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($s['tecnico_nome'] ?? 'Admin') ?></td>
                        <td class="px-4 py-3 text-center font-bold text-indigo-600 bg-indigo-50 rounded"><?= $tempoFormatado ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($s['closed_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Linha de Totalização -->
                    <tr class="bg-gray-50 border-t-2 border-gray-300">
                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-800 uppercase text-xs">Total de Horas Trabalhadas:</td>
                        <td class="px-4 py-3 text-center font-bold text-green-700 text-lg">
                            <?php 
                                $hTotal = floor($totalMinutosGlobais / 60);
                                $mTotal = $totalMinutosGlobais % 60;
                                echo sprintf("%02d:%02d h", $hTotal, $mTotal);
                            ?>
                        </td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>