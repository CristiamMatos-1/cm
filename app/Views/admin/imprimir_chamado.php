<?php
use app\Helpers\UI;
use app\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #<?= (int)$chamado['id'] ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #1f2937; background: #f3f4f6; margin: 0; padding: 24px; }
        .sheet { max-width: 800px; margin: 0 auto; background: #fff; padding: 32px; box-shadow: 0 0 10px rgba(0,0,0,.1); }
        h1 { margin: 0 0 4px; color: #1e3a8a; }
        .muted { color: #6b7280; font-size: 13px; }
        .row { display: flex; flex-wrap: wrap; gap: 24px; margin: 20px 0; }
        .row > div { flex: 1 1 200px; }
        .label { font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; white-space: pre-wrap; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .btn { display: inline-block; margin-bottom: 16px; padding: 8px 16px; background: #1e3a8a; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        @media print { .btn { display: none; } body { background: #fff; padding: 0; } .sheet { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <button class="btn" onclick="window.print()">Imprimir / Salvar como PDF</button>
        <h1>Ordem de Serviço #<?= (int)$chamado['id'] ?></h1>
        <p class="muted">Aberta em <?= UI::date($chamado['created_at'], true) ?> &middot; Status: <?= Security::esc(ucfirst(str_replace('_', ' ', $chamado['status']))) ?></p>

        <div class="row">
            <div><div class="label">Cliente</div><?= Security::esc($chamado['cliente_nome']) ?></div>
            <div><div class="label">Tipo de serviço</div><?= Security::esc($chamado['tipo_servico']) ?></div>
            <div><div class="label">Técnico</div><?= Security::esc($chamado['tecnico_nome'] ?? 'Não atribuído') ?></div>
        </div>

        <div class="label">Descrição</div>
        <div class="box"><?= Security::esc($chamado['descricao']) ?></div>

        <?php if (!empty($chamado['relatorio_final'])): ?>
            <p class="label" style="margin-top:16px">Relatório</p>
            <div class="box"><?= Security::esc($chamado['relatorio_final']) ?></div>
        <?php endif; ?>

        <table>
            <tr><td>Peças</td><td style="text-align:right"><?= UI::money($chamado['valor_pecas'] ?? 0) ?></td></tr>
            <tr><td>Mão de obra</td><td style="text-align:right"><?= UI::money($chamado['valor_mao_obra'] ?? 0) ?></td></tr>
            <tr><td>Serviço</td><td style="text-align:right"><?= UI::money($chamado['valor_servico'] ?? 0) ?></td></tr>
            <tr><td><strong>Total</strong></td><td style="text-align:right"><strong><?= UI::money(($chamado['valor_pecas'] ?? 0) + ($chamado['valor_mao_obra'] ?? 0) + ($chamado['valor_servico'] ?? 0)) ?></strong></td></tr>
        </table>
    </div>
</body>
</html>
