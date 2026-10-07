/* Componentes de interface compartilhados: toasts, modal de confirmação e estado de loading. */
(function (window, document) {
    'use strict';

    var UI = {};

    var TOAST_STYLES = {
        success: { box: 'bg-green-50 border-green-400 text-green-800', icon: 'fa-check-circle text-green-500' },
        error: { box: 'bg-red-50 border-red-400 text-red-800', icon: 'fa-times-circle text-red-500' },
        warning: { box: 'bg-yellow-50 border-yellow-400 text-yellow-800', icon: 'fa-exclamation-triangle text-yellow-500' },
        info: { box: 'bg-blue-50 border-blue-400 text-blue-800', icon: 'fa-info-circle text-blue-500' }
    };

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function ensureToastContainer() {
        var container = document.getElementById('toast-container');
        if (!container) {
            container = el('div', 'fixed top-4 right-4 left-4 sm:left-auto sm:w-96 z-[100] space-y-2 pointer-events-none');
            container.id = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    UI.toast = function (type, message, duration) {
        if (!message) return;
        var style = TOAST_STYLES[type] || TOAST_STYLES.info;
        var toast = el('div', 'ui-toast pointer-events-auto flex items-start gap-3 border-l-4 rounded-lg shadow-lg p-4 ' + style.box);
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        toast.appendChild(el('i', 'fas ' + style.icon + ' mt-0.5'));
        toast.appendChild(el('p', 'text-sm font-medium flex-1', message));

        var close = el('button', 'text-current opacity-60 hover:opacity-100');
        close.type = 'button';
        close.setAttribute('aria-label', 'Fechar');
        close.appendChild(el('i', 'fas fa-times'));
        toast.appendChild(close);

        var remove = function () {
            toast.classList.add('ui-toast-leaving');
            setTimeout(function () { toast.remove(); }, 200);
        };
        close.addEventListener('click', remove);
        ensureToastContainer().appendChild(toast);
        setTimeout(remove, duration || (type === 'error' ? 7000 : 4500));
    };

    /**
     * Modal de confirmação acessível.
     * options: title, message, confirmText, cancelText, variant ('success'|'danger'|'primary'),
     *          reason ({label, placeholder, required, maxLength}) para coletar justificativa.
     * Retorna Promise<{confirmed: boolean, reason: string}>.
     */
    UI.confirm = function (options) {
        options = options || {};
        var variant = options.variant || 'primary';
        var colors = {
            success: { btn: 'bg-green-600 hover:bg-green-700 focus:ring-green-500', icon: 'bg-green-100 text-green-600', glyph: 'fa-check' },
            danger: { btn: 'bg-red-600 hover:bg-red-700 focus:ring-red-500', icon: 'bg-red-100 text-red-600', glyph: 'fa-exclamation-triangle' },
            primary: { btn: 'bg-corpBlue-600 hover:bg-corpBlue-700 focus:ring-corpBlue-500', icon: 'bg-blue-100 text-blue-600', glyph: 'fa-question' }
        }[variant] || {};

        return new Promise(function (resolve) {
            var previouslyFocused = document.activeElement;
            var backdrop = el('div', 'ui-modal-backdrop fixed inset-0 z-[90] bg-gray-900/60 flex items-end sm:items-center justify-center p-4');
            var panel = el('div', 'ui-modal-panel bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden');
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');

            var body = el('div', 'p-6');
            var head = el('div', 'flex items-start gap-4');
            var icon = el('div', 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full ' + colors.icon);
            icon.appendChild(el('i', 'fas ' + colors.glyph));
            var texts = el('div', 'min-w-0');
            var titleId = 'ui-modal-title-' + Date.now();
            var title = el('h3', 'text-lg font-semibold text-gray-900', options.title || 'Confirmar ação');
            title.id = titleId;
            panel.setAttribute('aria-labelledby', titleId);
            texts.appendChild(title);
            if (options.message) texts.appendChild(el('p', 'mt-1 text-sm text-gray-600 break-words', options.message));
            head.appendChild(icon);
            head.appendChild(texts);
            body.appendChild(head);

            var textarea = null;
            var error = null;
            if (options.reason) {
                var wrap = el('div', 'mt-4');
                var label = el('label', 'block text-sm font-medium text-gray-700 mb-1', options.reason.label || 'Justificativa');
                textarea = el('textarea', 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500');
                textarea.rows = 3;
                textarea.maxLength = options.reason.maxLength || 1000;
                textarea.placeholder = options.reason.placeholder || '';
                label.htmlFor = textarea.id = 'ui-modal-reason';
                error = el('p', 'hidden mt-1 text-xs text-red-600', 'Informe uma justificativa para continuar.');
                wrap.appendChild(label);
                wrap.appendChild(textarea);
                wrap.appendChild(error);
                body.appendChild(wrap);
            }

            var footer = el('div', 'bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-2');
            var cancel = el('button', 'w-full sm:w-auto px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300', options.cancelText || 'Cancelar');
            cancel.type = 'button';
            var ok = el('button', 'w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 ' + colors.btn, options.confirmText || 'Confirmar');
            ok.type = 'button';
            footer.appendChild(cancel);
            footer.appendChild(ok);

            panel.appendChild(body);
            panel.appendChild(footer);
            backdrop.appendChild(panel);
            document.body.appendChild(backdrop);
            document.body.classList.add('overflow-hidden');

            var finish = function (confirmed) {
                var reason = textarea ? textarea.value.trim() : '';
                document.removeEventListener('keydown', onKey, true);
                backdrop.remove();
                document.body.classList.remove('overflow-hidden');
                if (previouslyFocused && previouslyFocused.focus) previouslyFocused.focus();
                resolve({ confirmed: confirmed, reason: reason });
            };

            var submit = function () {
                if (options.reason && options.reason.required && textarea.value.trim() === '') {
                    error.classList.remove('hidden');
                    textarea.focus();
                    return;
                }
                finish(true);
            };

            var onKey = function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    finish(false);
                } else if (event.key === 'Tab') {
                    var focusable = panel.querySelectorAll('button, textarea');
                    var first = focusable[0];
                    var last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            };

            document.addEventListener('keydown', onKey, true);
            cancel.addEventListener('click', function () { finish(false); });
            ok.addEventListener('click', submit);
            backdrop.addEventListener('mousedown', function (event) {
                if (event.target === backdrop) finish(false);
            });

            (textarea || ok).focus();
        });
    };

    /** Liga/desliga o estado de carregamento de um botão. */
    UI.setLoading = function (button, loading, loadingText) {
        if (!button) return;
        if (loading) {
            if (button.dataset.originalHtml === undefined) button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('btn-loading');
            button.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i>' + (loadingText || 'Processando...');
        } else {
            if (button.dataset.originalHtml !== undefined) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.classList.remove('btn-loading');
        }
    };

    /** Envia um formulário via fetch esperando JSON. Sempre resolve com {ok, status, data}. */
    UI.postForm = function (form, extra) {
        var body = new FormData(form);
        Object.keys(extra || {}).forEach(function (key) { body.set(key, extra[key]); });

        return fetch(form.action, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            return response.json().catch(function () { return null; }).then(function (data) {
                return { ok: response.ok, status: response.status, data: data };
            });
        }).catch(function () {
            return { ok: false, status: 0, data: { success: false, message: 'Falha de conexão. Verifique sua internet e tente novamente.' } };
        });
    };

    window.UI = UI;

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        document.querySelectorAll('.btn-loading').forEach(function (button) { UI.setLoading(button, false); });
    });

    document.addEventListener('DOMContentLoaded', function () {
        (window.__FLASH__ || []).forEach(function (item) { UI.toast(item.type, item.message); });
        window.__FLASH__ = [];

        // Botões de submit comuns também ganham feedback de loading (evita duplo clique).
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (form.hasAttribute('data-budget-decision-form') || form.hasAttribute('data-confirm') || event.defaultPrevented) return;
            var submitter = event.submitter || form.querySelector('button[type="submit"]');
            if (submitter && !submitter.hasAttribute('data-no-loading')) {
                setTimeout(function () { UI.setLoading(submitter, true, 'Salvando...'); }, 0);
            }
        });

        // Formulários com data-confirm exibem o modal antes de enviar (ex.: exclusões).
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.hasAttribute || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;
            event.preventDefault();
            var submitter = event.submitter;
            var reasonLabel = form.getAttribute('data-confirm-reason');
            UI.confirm({
                title: form.getAttribute('data-confirm-title') || 'Confirmar ação',
                message: form.getAttribute('data-confirm'),
                confirmText: form.getAttribute('data-confirm-button') || 'Confirmar',
                variant: form.getAttribute('data-confirm-variant') || 'danger',
                reason: reasonLabel ? { label: reasonLabel, placeholder: form.getAttribute('data-confirm-reason-placeholder') || '', required: true, maxLength: 1000 } : undefined
            }).then(function (result) {
                if (!result.confirmed) return;
                if (reasonLabel) {
                    var field = form.querySelector('input[name="motivo"]');
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'motivo';
                        form.appendChild(field);
                    }
                    field.value = result.reason;
                }
                form.dataset.confirmed = '1';
                if (submitter) UI.setLoading(submitter, true);
                form.submit();
            });
        });
    });
})(window, document);
