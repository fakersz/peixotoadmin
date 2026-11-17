/* Peixoto Admin — JS (vanilla) */
(function () {
    'use strict';

    // ---- Sidebar mobile ----
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggle  = document.getElementById('menuToggle');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', closeSidebar);
    }

    // ---- Confirmação em formulários destrutivos ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---- Auto-hide de alertas ----
    document.querySelectorAll('[data-autohide]').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity .4s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 400);
        }, 5000);
    });

    // ---- Cálculo automático do total da OS ----
    var calcInputs = document.querySelectorAll('input[data-calc]');
    var totalPreview = document.getElementById('totalPreview');

    function formatBRL(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function updateTotal() {
        var total = 0;
        calcInputs.forEach(function (input) {
            total += parseFloat(input.value) || 0;
        });
        if (totalPreview) totalPreview.textContent = formatBRL(total);
    }

    if (calcInputs.length && totalPreview) {
        calcInputs.forEach(function (input) {
            input.addEventListener('input', updateTotal);
        });
        updateTotal();
    }

    // ---- Máscara leve de telefone BR; o backend salva apenas dígitos normalizados. ----
    document.querySelectorAll('[data-phone-mask]').forEach(function (input) {
        function formatPhone() {
            var digits = input.value.replace(/\D+/g, '').slice(0, 11);
            if (digits.length > 10) {
                input.value = digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
            } else if (digits.length > 6) {
                input.value = digits.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
            } else if (digits.length > 2) {
                input.value = digits.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            } else {
                input.value = digits;
            }
        }
        input.addEventListener('input', formatPhone);
        formatPhone();
    });

    // ---- Filtro de equipamentos pelo cliente selecionado (form de OS) ----
    var clientSelect = document.getElementById('client_id');
    var deviceSelect = document.getElementById('device_id');

    if (clientSelect && deviceSelect && deviceSelect.querySelector('option[data-client]')) {
        var allOptions = Array.prototype.slice.call(deviceSelect.options);

        function filterDevices() {
            var clientId = clientSelect.value;
            var selected = deviceSelect.value;

            allOptions.forEach(function (option) {
                if (!option.value) return; // placeholder
                var match = !clientId || option.getAttribute('data-client') === clientId;
                option.hidden = !match;
                if (!match && option.value === selected) {
                    deviceSelect.value = '';
                }
            });
        }

        clientSelect.addEventListener('change', filterDevices);
        filterDevices();
    }

})();

/* Peixoto Admin — Centro de Conhecimento */
(function () {
    'use strict';

    var modal = document.getElementById('knowledgeFinalizeModal');
    if (!modal) return;

    var pendingForm = null;

    document.querySelectorAll('form[data-knowledge-finalize]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            pendingForm = form;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        });
    });

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal || event.target.closest('[data-knowledge-no]')) {
            if (pendingForm && event.target.closest('[data-knowledge-no]')) {
                pendingForm.querySelector('input[name="create_knowledge"]').value = '0';
                var form = pendingForm;
                pendingForm = null;
                closeModal();
                form.submit();
                return;
            }
            closeModal();
        }
        if (event.target.closest('[data-knowledge-yes]') && pendingForm) {
            pendingForm.querySelector('input[name="create_knowledge"]').value = '1';
            var articleBox = document.getElementById('knowledgeCreateArticle');
            var articleInput = pendingForm.querySelector('input[name="create_article"]');
            if (articleInput) {
                articleInput.value = articleBox && articleBox.checked ? '1' : '0';
            }
            var submitForm = pendingForm;
            pendingForm = null;
            closeModal();
            submitForm.submit();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();

/* Peixoto Admin — Centro de Conhecimento: formulários e editor Markdown */
(function () {
    'use strict';

    function insertAtSelection(textarea, before, after, placeholder) {
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        var selected = textarea.value.slice(start, end) || placeholder || '';
        textarea.value = textarea.value.slice(0, start) + before + selected + after + textarea.value.slice(end);
        textarea.focus();
        textarea.selectionStart = start + before.length;
        textarea.selectionEnd = start + before.length + selected.length;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderMarkdownLite(source) {
        var html = escapeHtml(source || '');
        html = html.replace(/^### (.*)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.*)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.*)$/gm, '<h1>$1</h1>');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
        html = html.replace(/^&gt; ?(.*)$/gm, '<blockquote>$1</blockquote>');
        html = html.replace(/\n{2,}/g, '</p><p>');
        html = '<p>' + html.replace(/\n/g, '<br>') + '</p>';
        return html;
    }

    document.querySelectorAll('[data-md-editor]').forEach(function (editor) {
        var textarea = editor.querySelector('[data-md-source]');
        var preview = editor.querySelector('[data-md-target]');
        if (!textarea || !preview) return;

        function updatePreview() {
            preview.innerHTML = renderMarkdownLite(textarea.value);
        }

        editor.addEventListener('click', function (event) {
            var button = event.target.closest('[data-md], [data-md-preview]');
            if (!button) return;
            if (button.hasAttribute('data-md-preview')) {
                editor.classList.toggle('preview-only');
                updatePreview();
                return;
            }
            var cmd = button.getAttribute('data-md');
            var map = {
                bold: ['**', '**', 'texto'],
                italic: ['*', '*', 'texto'],
                h2: ['\n## ', '\n', 'Título'],
                list: ['\n- ', '', 'item'],
                check: ['\n- [ ] ', '', 'tarefa'],
                table: ['\n| Coluna | Valor |\n| --- | --- |\n| ', ' |  |\n', 'item'],
                code: ['\n```\n', '\n```\n', 'código'],
                quote: ['\n> ', '', 'observação'],
                link: ['[', '](https://)', 'texto'],
                image: ['![', '](https://)', 'descrição'],
                warning: ['\n> [!AVISO] ', '', 'atenção']
            };
            if (map[cmd]) insertAtSelection(textarea, map[cmd][0], map[cmd][1], map[cmd][2]);
        });
        textarea.addEventListener('input', updatePreview);
        updatePreview();
    });

    ['manufacturer_id'].forEach(function (id) {
        var manufacturer = document.getElementById(id);
        var model = document.getElementById('model_id');
        if (!manufacturer || !model || !model.querySelector('option[data-manufacturer]')) return;
        var options = Array.prototype.slice.call(model.options);
        function filterModels() {
            var selectedManufacturer = manufacturer.value;
            options.forEach(function (option) {
                if (!option.value) return;
                var match = !selectedManufacturer || option.getAttribute('data-manufacturer') === selectedManufacturer;
                option.hidden = !match;
                if (!match && option.selected) model.value = '';
            });
        }
        manufacturer.addEventListener('change', filterModels);
        filterModels();
    });
})();

/* Peixoto Admin — Trello kanban (drag & drop, composers e modal) */
(function () {
    'use strict';

    var board = document.getElementById('kanban');
    if (!board) return;

    var csrf = board.getAttribute('data-csrf');
    var dragging = null;
    var justDragged = false;

    // ---------- Composers (adicionar cartão / lista) ----------
    function setupComposer(wrap) {
        var btn  = wrap.querySelector('.bcol-add-btn, .bcol-new-btn');
        var form = wrap.querySelector('.bcol-add-form');
        if (!btn || !form) return;

        var field  = form.querySelector('textarea, input[type="text"]');
        var cancel = form.querySelector('.bcol-add-cancel');

        function open() {
            btn.hidden = true;
            form.hidden = false;
            field.focus();
        }
        function close() {
            form.hidden = true;
            btn.hidden = false;
            field.value = '';
        }

        btn.addEventListener('click', open);
        cancel.addEventListener('click', close);
        field.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && field.tagName === 'TEXTAREA') {
                e.preventDefault();
                if (field.value.trim()) form.submit();
            }
            if (e.key === 'Escape') close();
        });
    }

    board.querySelectorAll('.bcol-add').forEach(setupComposer);
    var newList = board.querySelector('.bcol-new');
    if (newList) setupComposer(newList);

    // ---------- Modal de detalhe do cartão ----------
    var overlay = document.getElementById('cardModal');
    var panel   = document.getElementById('cardModalPanel');

    function openModal(cardId) {
        fetch('/trello/card_modal?id=' + encodeURIComponent(cardId))
            .then(function (r) {
                if (!r.ok) throw new Error('http ' + r.status);
                return r.text();
            })
            .then(function (html) {
                panel.innerHTML = html;
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
            })
            .catch(function () {
                // fallback: abre a página completa do cartão
                window.location.href = '/trello/card?id=' + cardId;
            });
    }

    function closeModal() {
        overlay.hidden = true;
        panel.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (overlay && panel) {
        board.addEventListener('click', function (e) {
            var card = e.target.closest('.bcard');
            if (!card) return;
            e.preventDefault();
            if (justDragged) return;
            openModal(card.getAttribute('data-card'));
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
            if (e.target.closest('[data-close]')) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.hidden) closeModal();
        });

        // Confirmação para forms destrutivos injetados no modal.
        panel.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-confirm]');
            if (form && !window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    }

    // ---------- Drag & drop ----------
    function afterElement(container, y) {
        var cards = Array.prototype.slice.call(
            container.querySelectorAll('.bcard:not(.dragging)')
        );
        var closest = { offset: -Infinity, el: null };
        cards.forEach(function (card) {
            var box = card.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                closest = { offset: offset, el: card };
            }
        });
        return closest.el;
    }

    function persist(container) {
        var columnId = container.getAttribute('data-column');
        var ids = Array.prototype.slice
            .call(container.querySelectorAll('.bcard'))
            .map(function (c) { return c.getAttribute('data-card'); });

        var body = new URLSearchParams();
        body.append('_csrf', csrf);
        body.append('card_id', dragging ? dragging.getAttribute('data-card') : '');
        body.append('column_id', columnId);
        ids.forEach(function (id) { body.append('order[]', id); });

        fetch('/trello/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
    }

    function updateCounts() {
        board.querySelectorAll('.bcol[data-column]').forEach(function (col) {
            var count = col.querySelectorAll('.bcard').length;
            var pill = col.querySelector('.bcol-count');
            if (pill) pill.textContent = count;
        });
    }

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.bcard');
        if (!card) return;
        dragging = card;
        setTimeout(function () { card.classList.add('dragging'); }, 0);
    });

    board.addEventListener('dragend', function () {
        if (dragging) {
            dragging.classList.remove('dragging');
            // Persiste onde o card terminou, mesmo se o drop caiu fora
            // da área de cards (header, composer…) — comum no touch.
            var container = dragging.closest('.bcol-cards');
            if (container) {
                persist(container);
                updateCounts();
            }
        }
        dragging = null;
        justDragged = true;
        setTimeout(function () { justDragged = false; }, 80);
        board.querySelectorAll('.drag-over').forEach(function (el) {
            el.classList.remove('drag-over');
        });
    });

    // Auto-scroll horizontal do quadro ao arrastar perto das bordas
    // (Trello faz isso; essencial no iPad e em telas menores).
    board.addEventListener('dragover', function (e) {
        if (!dragging) return;
        e.preventDefault();
        var rect = board.getBoundingClientRect();
        var edge = 70, speed = 16;
        if (e.clientX < rect.left + edge) {
            board.scrollLeft -= speed;
        } else if (e.clientX > rect.right - edge) {
            board.scrollLeft += speed;
        }
    });

    board.querySelectorAll('.bcol-cards').forEach(function (container) {
        container.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragging) return;
            container.classList.add('drag-over');

            // Auto-scroll vertical dentro da lista
            var crect = container.getBoundingClientRect();
            if (e.clientY < crect.top + 48) {
                container.scrollTop -= 12;
            } else if (e.clientY > crect.bottom - 48) {
                container.scrollTop += 12;
            }

            var ref = afterElement(container, e.clientY);
            if (ref == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, ref);
            }
        });

        container.addEventListener('dragleave', function (e) {
            if (!container.contains(e.relatedTarget)) {
                container.classList.remove('drag-over');
            }
        });

        container.addEventListener('drop', function (e) {
            // A persistência acontece no dragend (cobre qualquer alvo de drop).
            e.preventDefault();
            container.classList.remove('drag-over');
        });
    });
})();
