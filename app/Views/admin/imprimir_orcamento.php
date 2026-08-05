<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= $budget['id'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .document-title {
            text-align: right;
            flex: 1;
        }
        
        .document-title h1 {
            font-size: 32px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        
        .document-info {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .client-info, .budget-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .info-field {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 13px;
        }
        
        .description-box {
            background: #f9fafb;
            padding: 15px;
            border-left: 4px solid #3b82f6;
            margin: 15px 0;
            font-size: 13px;
            line-height: 1.6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #e5e7eb;
            color: #1f2937;
        }
        
        td {
            padding: 12px;
            border: 1px solid #e5e7eb;
        }
        
        tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .subtotal-row {
            background: #f3f4f6;
            font-weight: bold;
        }
        
        .total-row {
            background: #1f2937;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .value {
            font-weight: bold;
            color: #1f2937;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        
        .validity-notice {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .container {
                max-width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
        }
        
        .print-button {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .print-button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="print-button" onclick="window.print()">Imprimir / Salvar como PDF</button>
        
        <div class="header">
            <div class="company-info">
                <?php if ($logo_url): ?>
                    <img src="<?= $logo_url ?>" alt="Logo" class="company-logo">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-building" style="font-size: 40px; color: #9ca3af;"></i>
                    </div>
                <?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
                <div style="font-size: 12px; color: #666; line-height: 1.6;">
                    <?php if ($company_cnpj): ?>
                        <div>CNPJ: <?= htmlspecialchars($company_cnpj) ?></div>
                    <?php endif; ?>
                    <?php if ($company_phone): ?>
                        <div>Telefone: <?= htmlspecialchars($company_phone) ?></div>
                    <?php endif; ?>
                    <?php if ($company_email): ?>
                        <div>Email: <?= htmlspecialchars($company_email) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="document-title">
                <h1>ORÇAMENTO</h1>
                <div class="document-info">
                    <div>Número: <strong>#<?= $budget['id'] ?></strong></div>
                    <div>Emitido em: <strong><?= date('d/m/Y', strtotime($budget['created_at'])) ?></strong></div>
                    <div style="margin-top: 10px;">
                        Status: <span class="status-badge status-<?= $budget['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $budget['status'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <!-- Informações do Cliente -->
            <div class="section">
                <div class="section-title">Dados do Cliente</div>
                <div class="client-info">
                    <div class="info-field">
                        <div class="info-label">Nome/Empresa</div>
                        <div class="info-value"><?= htmlspecialchars($budget['cliente_nome']) ?></div>
                    </div>
                    <div class="info-field">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?= htmlspecialchars($budget['cliente_telefone']) ?></div>
                    </div>
                    <div class="info-field">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($budget['cliente_email']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Informações do Orçamento -->
            <div class="section">
                <div class="section-title">Orçamento</div>
                <div class="info-field">
                    <div class="info-label">Título</div>
                    <div class="info-value"><?= htmlspecialchars($budget['titulo']) ?></div>
                </div>
            </div>
            
            <!-- Descrição -->
            <?php if ($budget['descricao']): ?>
                <div class="description-box">
                    <strong>Descrição Detalhada:</strong><br>
                    <?= nl2br(htmlspecialchars($budget['descricao'])) ?>
                </div>
            <?php endif; ?>
            
            <!-- Itens do Orçamento -->
            <?php if (count($budget_items) > 0): ?>
                <div class="section">
                    <div class="section-title">Itens do Orçamento</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-right">Valor Unit.</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pecas_total = 0;
                            $mao_obra_total = 0;
                            foreach ($budget_items as $item): 
                                $item_value = floatval($item['subtotal']);
                                if ($item['tipo'] === 'peca') {
                                    $pecas_total += $item_value;
                                } elseif ($item['tipo'] === 'mao_obra') {
                                    $mao_obra_total += $item_value;
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['descricao']) ?></td>
                                    <td class="text-center">
                                        <?php
                                        $tipo_labels = [
                                            'peca' => 'Peça',
                                            'mao_obra' => 'Mão de Obra',
                                            'servico' => 'Serviço'
                                        ];
                                        echo $tipo_labels[$item['tipo']] ?? $item['tipo'];
                                        ?>
                                    </td>
                                    <td class="text-center"><?= $item['quantidade'] ?></td>
                                    <td class="text-right">R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                                    <td class="text-right">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Resumo de Valores -->
            <table style="width: 50%; margin-left: auto; margin-bottom: 20px;">
                <tbody>
                    <?php if ($budget['valor_pecas'] > 0): ?>
                        <tr class="subtotal-row">
                            <td>Total Peças:</td>
                            <td class="text-right">R$ <?= number_format($budget['valor_pecas'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($budget['valor_mao_obra'] > 0): ?>
                        <tr class="subtotal-row">
                            <td>Total Mão de Obra:</td>
                            <td class="text-right">R$ <?= number_format($budget['valor_mao_obra'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td style="color: white;">VALOR TOTAL:</td>
                        <td class="text-right">R$ <?= number_format($budget['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Validade -->
            <?php if ($budget['data_validade']): ?>
                <div class="validity-notice">
                    <strong>Validade do Orçamento:</strong> Este orçamento é válido até 
                    <strong><?= date('d/m/Y', strtotime($budget['data_validade'])) ?></strong>.
                    Após esta data, o orçamento expira automaticamente.
                </div>
            <?php endif; ?>
            
            <!-- Observações finais -->
            <div class="section">
                <div class="section-title">Observações</div>
                <div style="font-size: 12px; color: #666; line-height: 1.8;">
                    <p>• Pagamento: Conforme acordo entre as partes</p>
                    <p>• Prazo de execução: A ser confirmado</p>
                    <p>• Válido apenas com a assinatura/aprovação do cliente</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Este documento foi gerado automaticamente. Para aprovação, acesse o link disponibilizado via WhatsApp ou email.</p>
            <p style="margin-top: 10px; font-size: 10px;">Gerado em <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
