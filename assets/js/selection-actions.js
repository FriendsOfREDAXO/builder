/**
 * Builder: Aktionen für markierten Text in der Vorschau der Elemente.
 *
 * Markiert man in der Builder-Ansicht Text in einem Element (.slice-rendered), erscheint darüber eine
 * kleine Leiste mit den angemeldeten Aktionen. Der Builder bringt selbst keine Aktion mit – andere
 * AddOns melden ihre an (z. B. Knowledgebase „Ins Glossar“).
 *
 * Anmelden (Reihenfolge der Skripte egal):
 *
 *   window.BuilderSelectionActions = window.BuilderSelectionActions || [];
 *   window.BuilderSelectionActions.push({
 *       id: 'mein-addon-aktion',
 *       label: 'Ins Glossar',
 *       icon: 'fa-book',                       // Font-Awesome-Klasse, optional
 *       maxLength: 120,                        // optional, Standard 200 Zeichen
 *       isAvailable: function (ctx) { ... },   // optional, true = anzeigen
 *       run: function (ctx) { ... }            // Pflicht
 *   });
 *
 * ctx = { text, slice (HTMLElement), sliceType, sliceId, builder (.yform-content-builder), form, rect }
 */
(function () {
    'use strict';

    var queued = Array.isArray(window.BuilderSelectionActions) ? window.BuilderSelectionActions : [];
    var actions = [];
    var bar = null;
    var current = null;
    var hideTimer = null;

    function add(action) {
        if (!action || typeof action.run !== 'function' || !action.id) {
            return;
        }
        actions = actions.filter(function (a) { return a.id !== action.id; });
        actions.push(action);
    }

    // Öffentliche Schnittstelle; push() bleibt nutzbar, auch wenn das Skript später lädt
    window.BuilderSelectionActions = {
        push: add,
        register: add,
        unregister: function (id) { actions = actions.filter(function (a) { return a.id !== id; }); },
        list: function () { return actions.slice(); },
        hide: hide
    };
    queued.forEach(add);

    function cleanText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function contextFromSelection() {
        var selection = window.getSelection();
        if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
            return null;
        }
        var range = selection.getRangeAt(0);
        var node = range.commonAncestorContainer;
        var el = node.nodeType === 1 ? node : node.parentElement;
        if (!el || !el.closest) {
            return null;
        }
        var rendered = el.closest('.content-builder-slice .slice-rendered');
        if (!rendered || el.closest('input, textarea, select, [contenteditable="true"], .slice-toolbar')) {
            return null;
        }
        var text = cleanText(selection.toString());
        if (text === '') {
            return null;
        }
        var slice = rendered.closest('.content-builder-slice');
        var builder = slice.closest('.yform-content-builder');

        return {
            text: text,
            slice: slice,
            sliceType: slice.getAttribute('data-slice-type') || '',
            sliceId: slice.getAttribute('data-slice-id') || '',
            builder: builder,
            form: slice.closest('form'),
            rect: range.getBoundingClientRect()
        };
    }

    function ensureBar() {
        if (bar) {
            return bar;
        }
        bar = document.createElement('div');
        bar.className = 'builder-selection-bar';
        bar.setAttribute('role', 'toolbar');
        bar.setAttribute('aria-label', 'Aktionen für den markierten Text');
        bar.hidden = true;
        document.body.appendChild(bar);

        bar.addEventListener('mousedown', function (event) {
            // Markierung beim Klick auf die Leiste nicht verlieren
            event.preventDefault();
        });
        bar.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hide();
            }
        });

        return bar;
    }

    function hide() {
        if (bar) {
            bar.hidden = true;
            bar.innerHTML = '';
        }
        current = null;
    }

    function show(ctx) {
        var available = actions.filter(function (action) {
            var max = action.maxLength || 200;
            if (ctx.text.length > max) {
                return false;
            }
            try {
                return typeof action.isAvailable !== 'function' || action.isAvailable(ctx) !== false;
            } catch (e) {
                return false;
            }
        });
        if (available.length === 0) {
            hide();
            return;
        }

        var el = ensureBar();
        el.innerHTML = '';
        available.forEach(function (action) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'builder-selection-bar__button';
            if (action.icon) {
                var icon = document.createElement('i');
                icon.className = 'fa ' + action.icon;
                icon.setAttribute('aria-hidden', 'true');
                button.appendChild(icon);
            }
            button.appendChild(document.createTextNode(action.label || action.id));
            button.addEventListener('click', function () {
                var ctxForRun = current;
                hide();
                if (ctxForRun) {
                    action.run(ctxForRun);
                }
            });
            el.appendChild(button);
        });

        current = ctx;
        el.hidden = false;

        var rect = ctx.rect;
        var barRect = el.getBoundingClientRect();
        var top = window.scrollY + rect.top - barRect.height - 8;
        if (rect.top - barRect.height - 8 < 0) {
            top = window.scrollY + rect.bottom + 8;
        }
        var left = window.scrollX + rect.left + rect.width / 2 - barRect.width / 2;
        left = Math.max(window.scrollX + 8, Math.min(left, window.scrollX + document.documentElement.clientWidth - barRect.width - 8));
        el.style.top = Math.round(top) + 'px';
        el.style.left = Math.round(left) + 'px';
    }

    function update() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            if (actions.length === 0) {
                return;
            }
            var ctx = contextFromSelection();
            if (ctx) {
                show(ctx);
            } else if (!bar || !bar.contains(document.activeElement)) {
                hide();
            }
        }, 120);
    }

    document.addEventListener('mouseup', update);
    document.addEventListener('keyup', function (event) {
        if (event.key === 'Escape') {
            hide();
            return;
        }
        if (event.shiftKey || event.key === 'Shift') {
            update();
        }
    });
    document.addEventListener('selectionchange', function () {
        if (current && !contextFromSelection() && !(bar && bar.contains(document.activeElement))) {
            update();
        }
    });
    window.addEventListener('scroll', function () {
        if (current) {
            var ctx = contextFromSelection();
            if (ctx) {
                show(ctx);
            }
        }
    }, { passive: true });
})();
