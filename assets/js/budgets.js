/* Fluxo de aprovação/rejeição de orçamentos (painel admin, painel do cliente e link público). */
(function (window, document) {
    'use strict';

    var UI = window.UI;
    if (!UI) return;

    var DECISIONS = {
        aprovar: {
            variant: 'success',
            title: 'Aprovar orçamento?',
            confirmText: 'Sim, aprovar',
            loading: 'Aprovando...',
            message: function (title) { return 'Você está prestes a aprovar "' + title + '". Esta decisão não poderá ser alterada depois.'; }
        },
        rejeitar: {
            variant: 'danger',
            title: 'Rejeitar orçamento?',
            confirmText: 'Sim, rejeitar',
            loading: 'Rejeitando...',
            message: function (title) { return 'Você está prestes a rejeitar "' + title + '". Esta decisão não poderá ser alterada depois.'; },
            reason: { label: 'Justificativa (opcional)', placeholder: 'Explique o motivo da rejeição...', required: false, maxLength: 1000 }
        }
    };

    function escapeSelector(value) {
        return String(value).replace(/"/g, '\\"');
    }

    function refreshCounters() {
        var rows = document.querySelectorAll('[data-budget-row]');
        if (!rows.length) return;
        var counts = { todos: rows.length, pendente: 0, aprovado: 0, rejeitado: 0, expirado: 0 };
        rows.forEach(function (row) {
            var status = row.getAttribute('data-status');
            if (counts[status] !== undefined) counts[status]++;
        });
        document.querySelectorAll('[data-budget-count]').forEach(function (node) {
            var key = node.getAttribute('data-budget-count');
            node.textContent = counts[key] !== undefined ? counts[key] : 0;
        });
    }

    function applyFilter() {
        var active = document.querySelector('[data-budget-filter].is-active');
        var filter = active ? active.getAttribute('data-budget-filter') : 'todos';
        var visible = 0;
        document.querySelectorAll('[data-budget-row]').forEach(function (row) {
            var show = filter === 'todos' || row.getAttribute('data-status') === filter;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        var empty = document.getElementById('budget-filter-empty');
        if (empty) empty.classList.toggle('hidden', visible > 0);
    }

    /** Atualiza, sem recarregar a página, tudo que representa o orçamento na tela. */
    function updateBudgetUI(budget) {
        if (!budget) return;
        var id = escapeSelector(budget.id);
        var scope = document.querySelectorAll('[data-budget-id="' + id + '"]');

        scope.forEach(function (node) {
            if (node.hasAttribute('data-budget-row') || node.hasAttribute('data-budget-card')) {
                node.setAttribute('data-status', budget.status);
                node.classList.remove('border-indigo-200', 'bg-indigo-50', 'bg-indigo-50/40');
            }

            node.querySelectorAll('[data-budget-status-badge]').forEach(function (badge) {
                badge.innerHTML = budget.badge_html;
            });

            node.querySelectorAll('[data-budget-decision-form]').forEach(function (form) {
                form.remove();
            });

            node.querySelectorAll('[data-budget-decision-text]').forEach(function (text) {
                text.textContent = budget.decision_text || '';
                text.classList.toggle('hidden', !budget.decision_text);
            });

            node.querySelectorAll('fieldset[data-budget-lockable]').forEach(function (fieldset) {
                fieldset.disabled = budget.status === 'aprovado' || budget.status === 'rejeitado';
            });

            node.querySelectorAll('[data-hide-when-approved]').forEach(function (target) {
                target.classList.toggle('hidden', budget.status === 'aprovado');
            });

            node.querySelectorAll('[data-budget-pending-only]').forEach(function (pendingOnly) {
                pendingOnly.classList.toggle('hidden', budget.status !== 'pendente');
            });

            node.querySelectorAll('[data-budget-decided-only]').forEach(function (decidedOnly) {
                var kind = decidedOnly.getAttribute('data-for');
                var show = kind === 'expirado'
                    ? budget.status === 'expirado'
                    : (budget.status === 'aprovado' || budget.status === 'rejeitado');
                decidedOnly.classList.toggle('hidden', !show);
            });
        });

        document.querySelectorAll('[data-budget-actions-empty]').forEach(function (hint) {
            if (hint.closest('[data-budget-id="' + id + '"]')) hint.classList.remove('hidden');
        });

        refreshCounters();
        applyFilter();
    }

    function handleDecision(form, submitter) {
        var key = form.getAttribute('data-decision');
        var config = DECISIONS[key];
        if (!config || form.dataset.busy === '1') return;

        var title = form.getAttribute('data-budget-title') || ('#' + form.getAttribute('data-budget-ref'));

        UI.confirm({
            title: config.title,
            message: config.message(title),
            confirmText: config.confirmText,
            variant: config.variant,
            reason: config.reason
        }).then(function (result) {
            if (!result.confirmed) return;

            form.dataset.busy = '1';
            var buttons = form.parentNode.querySelectorAll('button[type="submit"]');
            buttons.forEach(function (button) { button.disabled = true; });
            UI.setLoading(submitter, true, config.loading);

            UI.postForm(form, result.reason ? { motivo: result.reason } : {}).then(function (response) {
                var data = response.data || {};

                if (response.status === 401 && data.redirect) {
                    UI.toast('warning', data.message || 'Sessão expirada.');
                    setTimeout(function () { window.location.href = data.redirect; }, 1500);
                    return;
                }

                if (response.status === 419) {
                    UI.toast('error', data.message || 'Sessão de segurança expirada. Recarregue a página.');
                    UI.setLoading(submitter, false);
                    buttons.forEach(function (button) { button.disabled = false; });
                    form.dataset.busy = '0';
                    return;
                }

                if (data.success) {
                    UI.toast('success', data.message);
                    updateBudgetUI(data.budget);
                    document.dispatchEvent(new CustomEvent('budget:decided', { detail: data.budget }));
                    return;
                }

                // Falhas "esperadas" (já decidido/expirado) também sincronizam a tela com o estado real.
                var type = (data.code === 'already_decided' || data.code === 'expired') ? 'warning' : 'error';
                UI.toast(type, data.message || 'Não foi possível concluir a ação.');
                if (data.budget && (data.code === 'already_decided' || data.code === 'expired')) {
                    updateBudgetUI(data.budget);
                } else {
                    UI.setLoading(submitter, false);
                    buttons.forEach(function (button) { button.disabled = false; });
                    form.dataset.busy = '0';
                }
            });
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.hasAttribute || !form.hasAttribute('data-budget-decision-form')) return;
        event.preventDefault();
        handleDecision(form, event.submitter || form.querySelector('button[type="submit"]'));
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-budget-filter]');
        if (!button) return;
        document.querySelectorAll('[data-budget-filter]').forEach(function (other) {
            var isActive = other === button;
            other.classList.toggle('is-active', isActive);
            other.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        applyFilter();
    });

    document.addEventListener('DOMContentLoaded', function () {
        refreshCounters();
        applyFilter();
    });

    window.BudgetUI = { update: updateBudgetUI };
})(window, document);
