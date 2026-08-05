<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar Orçamento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <main class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="grid lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 space-y-6">
                <header class="bg-gradient-to-r from-blue-700 to-indigo-700 text-white rounded-2xl p-6 shadow-lg">
                    <p class="text-sm opacity-90">Orçamento #<?= $budget['id'] ?></p>
                    <h1 class="text-2xl sm:text-3xl font-bold mt-1">Autorização de Orçamento</h1>
                    <p class="text-blue-100 mt-2">Revise os itens e aprove ou rejeite sem login.</p>
                </header>

                <article class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500">Cliente</p>
                            <p class="text-lg font-semibold"><?= htmlspecialchars($budget['cliente_nome']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Emissão</p>
                            <p class="text-lg font-semibold"><?= date('d/m/Y', strtotime($budget['created_at'])) ?></p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h2 class="text-base font-semibold text-slate-700 mb-2">Descrição detalhada</h2>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 leading-relaxed">
                            <?= nl2br(htmlspecialchars($budget['descricao'])) ?>
                        </div>
                    </div>

                    <div class="mt-5 grid sm:grid-cols-3 gap-3">
                        <div class="rounded-xl border p-4 bg-slate-50">
                            <p class="text-xs uppercase text-slate-500">Peças</p>
                            <p class="text-xl font-bold">R$ <?= number_format($budget['valor_pecas'] ?? 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="rounded-xl border p-4 bg-slate-50">
                            <p class="text-xs uppercase text-slate-500">Mão de obra</p>
                            <p class="text-xl font-bold">R$ <?= number_format($budget['valor_mao_obra'] ?? 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="rounded-xl border p-4 bg-blue-50 border-blue-200">
                            <p class="text-xs uppercase text-blue-700">Valor total</p>
                            <p class="text-xl font-bold text-blue-700">R$ <?= number_format($budget['valor_total'] ?? 0, 2, ',', '.') ?></p>
                        </div>
                    </div>

                    <?php if (!empty($budget['data_validade'])): ?>
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm text-amber-900"><strong>Válido até:</strong> <?= date('d/m/Y', strtotime($budget['data_validade'])) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            </section>

            <aside class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sticky top-4">
                    <h3 class="text-lg font-bold mb-4">Ação rápida</h3>

                    <form action="<?= BASE_URL ?>/auth/aprovarOrcamento" method="POST" class="space-y-3">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($budget['token_autorizacao']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3">
                            Aprovar orçamento
                        </button>
                    </form>

                    <button type="button" onclick="document.getElementById('rejectBox').classList.toggle('hidden')" class="w-full mt-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3">
                        Rejeitar orçamento
                    </button>

                    <div id="rejectBox" class="hidden mt-4">
                        <form action="<?= BASE_URL ?>/auth/rejeitarOrcamento" method="POST" class="space-y-3">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($budget['token_autorizacao']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <textarea name="motivo" rows="4" class="w-full rounded-xl border border-slate-300 p-3" placeholder="Motivo da rejeição (opcional)"></textarea>
                            <button type="submit" class="w-full rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3">
                                Confirmar rejeição
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
