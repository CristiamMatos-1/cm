<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Meu Patrimônio e Equipamentos</h2>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden border-t-4 border-corpBlue-500">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <p class="text-sm text-gray-600">Abaixo estão listados os equipamentos de sua propriedade que são monitorados ou geridos por nossa assistência técnica.</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipamento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº de Série</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data de Compra</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Garantia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($ativos)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-desktop text-4xl mb-3 text-gray-300"></i>
                            <p>Você ainda não possui equipamentos registrados em nosso sistema.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ativos as $a): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <i class="fas fa-microchip text-gray-400 mr-2"></i>
                            <?= htmlspecialchars($a['nome_equipamento']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 font-mono"><?= htmlspecialchars($a['numero_serie']) ?: '-' ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= $a['data_compra'] ? date('d/m/Y', strtotime($a['data_compra'])) : '-' ?></td>
                        <td class="px-6 py-4 text-sm">
                            <?php
                            if ($a['data_compra']) {
                                $compra = strtotime($a['data_compra']);
                                $vencimento = strtotime("+" . $a['garantia_meses'] . " months", $compra);
                                if (time() > $vencimento) {
                                    echo '<span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">Expirada</span>';
                                } else {
                                    echo '<span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">Até ' . date('m/Y', $vencimento) . '</span>';
                                }
                            } else {
                                echo '<span class="text-gray-400">-</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>