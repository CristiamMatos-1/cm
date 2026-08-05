<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= $budget['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<main class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden">
        <header class="bg-gradient-to-r from-slate-900 to-blue-900 text-white p-6 sm:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-sm opacity-80">Orçamento #<?= $budget['id'] ?></p>
                    <h1 class="text-2xl sm:text-3xl font-bold mt-1">Proposta comercial</h1>
                    <p class="text-blue-100 mt-2"><?= htmlspecialchars($budget['cliente_nome']) ?></p>
                </div>
                <button onclick="window.print()" class="bg-white text-slate-900 px-4 py-2 rounded-xl font-semibold">
                    Imprimir / PDF
                </button>
            </div>
        </header>

        <section class="p-6 sm:p-8 grid gap-6">
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border p-4 bg-slate-50">
                    <p class="text-xs uppercase text-slate-500">Cliente</p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($budget['cliente_nome']) ?></p>
                </div>
                <div class="rounded-2xl border p-4 bg-slate-50">
                    <p class="text-xs uppercase text-slate-500">Emissão</p>
                    <p class="text-lg font-semibold"><?= date('d/m/Y', strtotime($budget['created_at'])) ?></p>
                </div>
            </div>

            <?php if (!empty($budget['data_validade'])): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm text-amber-900"><strong>Validade:</strong> <?= date('d/m/Y', strtotime($budget['data_validade'])) ?></p>
                </div>
            <?php endif; ?>

            <article class="rounded-2xl border p-5">
                <h2 class="font-semibold mb-3">Descrição detalhada</h2>
                <p class="text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($budget['descricao'])) ?></p>
            </article>

            <section class="grid md:grid-cols-3 gap-4">
                <div class="rounded-2xl border p-5 bg-slate-50">
                    <p class="text-xs uppercase text-slate-500">Peças</p>
                    <p class="text-2xl font-bold">R$ <?= number_format($budget['valor_pecas'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border p-5 bg-slate-50">
                    <p class="text-xs uppercase text-slate-500">Mão de obra</p>
                    <p class="text-2xl font-bold">R$ <?= number_format($budget['valor_mao_obra'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border p-5 bg-blue-50 border-blue-200">
                    <p class="text-xs uppercase text-blue-700">Total</p>
                    <p class="text-2xl font-bold text-blue-700">R$ <?= number_format($budget['valor_total'] ?? 0, 2, ',', '.') ?></p>
                </div>
            </section>
        </section>
    </div>
</main>
</body>
</html>
