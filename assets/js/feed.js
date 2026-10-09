/* Feed Rápido: carrega postagens via fetch e faz rolagem infinita.
 *
 * Uso (qualquer página):
 *   <section id="feed-rapido" data-endpoint="/cm/feed/carregar" data-limit="5"></section>
 *   <script src="/cm/assets/js/feed.js" defer></script>
 */
(function (window, document) {
    'use strict';

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function initFeed(root) {
        var endpoint = root.getAttribute('data-endpoint');
        if (!endpoint || root.dataset.feedReady === '1') return;
        root.dataset.feedReady = '1';

        var limit = parseInt(root.getAttribute('data-limit'), 10) || 5;
        var offset = parseInt(root.getAttribute('data-offset'), 10) || 0;
        var loading = false;
        var finished = false;
        var total = 0;

        var list = el('div', 'space-y-4');
        var status = el('div', 'py-6 text-center text-sm text-gray-500');
        status.setAttribute('aria-live', 'polite');
        var retry = el('button', 'hidden mt-3 px-4 py-2 rounded-lg bg-corpBlue-600 text-white text-sm font-medium hover:bg-corpBlue-700', 'Tentar novamente');
        retry.type = 'button';
        var sentinel = el('div', 'h-px');
        sentinel.setAttribute('aria-hidden', 'true');

        root.appendChild(list);
        root.appendChild(status);
        status.appendChild(retry);
        root.appendChild(sentinel);

        var observer = null;

        function setStatus(message, showSpinner) {
            while (status.firstChild && status.firstChild !== retry) status.removeChild(status.firstChild);
            if (showSpinner) {
                var spinner = el('i', 'fas fa-spinner animate-spin mr-2');
                spinner.setAttribute('aria-hidden', 'true');
                status.insertBefore(spinner, retry);
            }
            status.insertBefore(document.createTextNode(message || ''), retry);
        }

        function appendHtml(html) {
            var tpl = document.createElement('template');
            tpl.innerHTML = html;
            var added = 0;
            Array.prototype.slice.call(tpl.content.children).forEach(function (card) {
                var id = card.getAttribute('data-post-id');
                // Novas publicações deslocam o offset; evita mostrar o mesmo card duas vezes.
                if (id && list.querySelector('[data-post-id="' + id + '"]')) return;
                list.appendChild(card);
                added++;
            });
            return added;
        }

        function finish() {
            finished = true;
            if (observer) observer.disconnect();
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);
            setStatus(total === 0 ? 'Nenhuma postagem por enquanto.' : 'Você chegou ao fim.', false);
        }

        function loadMore() {
            if (loading || finished) return;
            loading = true;
            retry.classList.add('hidden');
            setStatus('Carregando...', true);

            var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') +
                'offset=' + encodeURIComponent(offset) + '&limit=' + encodeURIComponent(limit);

            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data || !data.success) {
                            throw new Error((data && data.message) || 'Falha ao carregar as postagens.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    total += appendHtml(data.html || '');
                    offset = data.next_offset;
                    loading = false;

                    if (!data.has_more) {
                        finish();
                        return;
                    }

                    setStatus('', false);
                    // Se o sentinel continuar visível (tela alta), o observer precisa ser rearmado.
                    if (observer) {
                        observer.unobserve(sentinel);
                        observer.observe(sentinel);
                    } else {
                        onScroll();
                    }
                })
                .catch(function (error) {
                    loading = false;
                    setStatus(error && error.message ? error.message : 'Falha de conexão.', false);
                    retry.classList.remove('hidden');
                });
        }

        function onScroll() {
            if (loading || finished) return;
            var rect = sentinel.getBoundingClientRect();
            if (rect.top - 300 <= window.innerHeight) loadMore();
        }

        retry.addEventListener('click', loadMore);

        // Só um vídeo toca por vez.
        root.addEventListener('play', function (event) {
            if (!event.target || event.target.tagName !== 'VIDEO') return;
            Array.prototype.forEach.call(root.querySelectorAll('video'), function (video) {
                if (video !== event.target && !video.paused) video.pause();
            });
        }, true);

        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function (entries) {
                if (entries.some(function (entry) { return entry.isIntersecting; })) loadMore();
            }, { rootMargin: '300px 0px' });
            observer.observe(sentinel);
        } else {
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll);
            onScroll();
        }
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('#feed-rapido, [data-feed]'), initFeed);
    }

    window.FeedRapido = { init: initFeed };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})(window, document);
