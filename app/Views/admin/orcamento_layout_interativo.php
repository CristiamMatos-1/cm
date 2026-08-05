<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Interativo de Orçamento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 space-y-6">
            <header class="bg-white rounded-2xl shadow-sm border p-6">
                <h1 class="text-2xl sm:text-3xl font-bold">Montador de Orçamento</h1>
                <p class="text-slate-500 mt-1">Selecione itens por categoria e acompanhe o total em tempo real.</p>
            </header>

            <div class="space-y-4">
                <article class="bg-white rounded-2xl shadow-sm border p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Sala</h2>
                            <p class="text-sm text-slate-500">Itens do ambiente principal</p>
                        </div>
                        <span class="text-xs bg-slate-100 px-3 py-1 rounded-full">Card</span>
                    </div>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl border hover:bg-slate-50">
                            <input type="checkbox" class="mt-1 item-check" data-group="Sala" data-name="Instalação elétrica" data-price="250">
                            <div class="flex-1">
                                <div class="flex justify-between gap-4">
                                    <span class="font-medium">Instalação elétrica</span>
                                    <span class="font-semibold">R$ 250,00</span>
                                </div>
                                <p class="text-sm text-slate-500">Ponto extra com materiais inclusos.</p>
                            </div>
                        </label>
                        <div class="flex items-center justify-between p-3 rounded-xl border hover:bg-slate-50">
                            <label class="flex items-center gap-3">
                                <input type="checkbox" class="item-check" data-group="Sala" data-name="Painel LED" data-price="180">
                                <span class="font-medium">Painel LED</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" class="px-3 py-1 rounded border qty-minus" data-target="qty-led">-</button>
                                <input id="qty-led" type="text" value="1" class="w-14 text-center border rounded py-1" readonly>
                                <button type="button" class="px-3 py-1 rounded border qty-plus" data-target="qty-led">+</button>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="bg-white rounded-2xl shadow-sm border p-5">
                    <h2 class="text-lg font-semibold mb-4">Quarto</h2>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl border hover:bg-slate-50">
                            <input type="checkbox" class="mt-1 item-check" data-group="Quarto" data-name="Tomada USB" data-price="95">
                            <div class="flex-1">
                                <div class="flex justify-between gap-4">
                                    <span class="font-medium">Tomada USB</span>
                                    <span class="font-semibold">R$ 95,00</span>
                                </div>
                                <p class="text-sm text-slate-500">Instalação com acabamento.</p>
                            </div>
                        </label>
                    </div>
                </article>
            </div>
        </section>

        <aside class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border p-5 lg:sticky lg:top-6">
                <h3 class="text-lg font-semibold mb-4">Resumo do orçamento</h3>
                <div id="summary" class="space-y-3 text-sm text-slate-600 min-h-48"></div>
                <div class="mt-4 pt-4 border-t flex items-center justify-between">
                    <span class="font-semibold">Total</span>
                    <span id="total" class="text-2xl font-bold text-blue-700">R$ 0,00</span>
                </div>
                <button class="w-full mt-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3">
                    Solicitar orçamento
                </button>
            </div>
        </aside>
    </div>
</main>

<script>
const checks = document.querySelectorAll('.item-check');
const summary = document.getElementById('summary');
const total = document.getElementById('total');

function renderSummary() {
  const groups = {};
  let sum = 0;
  checks.forEach((check) => {
    if (!check.checked) return;
    const group = check.dataset.group;
    const price = Number(check.dataset.price || 0);
    const qtyInput = document.getElementById('qty-led');
    const qty = qtyInput ? Number(qtyInput.value || 1) : 1;
    const itemTotal = price * qty;
    sum += itemTotal;
    if (!groups[group]) groups[group] = [];
    groups[group].push(`${check.dataset.name} — R$ ${itemTotal.toFixed(2).replace('.', ',')}`);
  });

  summary.innerHTML = Object.keys(groups).length
    ? Object.entries(groups).map(([group, items]) => `
        <div class="rounded-xl border p-3">
          <div class="font-semibold text-slate-800 mb-2">${group}</div>
          <ul class="space-y-1">${items.map(item => `<li>${item}</li>`).join('')}</ul>
        </div>`).join('')
    : '<p class="text-slate-400">Nenhum item selecionado.</p>';

  total.textContent = `R$ ${sum.toFixed(2).replace('.', ',')}`;
}

checks.forEach((check) => check.addEventListener('change', renderSummary));
document.querySelectorAll('.qty-plus,.qty-minus').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    let value = Number(input.value || 1);
    value = btn.classList.contains('qty-plus') ? value + 1 : Math.max(1, value - 1);
    input.value = value;
    renderSummary();
  });
});

renderSummary();
</script>
</body>
</html>
