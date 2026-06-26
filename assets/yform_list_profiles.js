/**
 * YForm-Listen-Profile: AJAX-Spaltenlader für die Settings-Seite.
 *
 * Beim Wechsel der Tabelle werden die Spalten via API geholt
 * und alle Spalten-Selects (#yfl-col-*) befüllt – ohne Page-Reload.
 */
(function () {
    'use strict';

    function buildOptions(select, columns) {
        var current = select.dataset.currentValue || '';
        var allowEmpty = select.dataset.allowEmpty === '1';
        var html = '';

        if (allowEmpty) {
            html += '<option value=""' + ('' === current ? ' selected' : '') + '>— optional —</option>';
        }

        var hasCurrent = false;
        for (var i = 0; i < columns.length; i++) {
            var col = columns[i];
            var sel = (col === current) ? ' selected' : '';
            if (col === current) {
                hasCurrent = true;
            }
            html += '<option value="' + escapeHtml(col) + '"' + sel + '>' + escapeHtml(col) + '</option>';
        }

        if (current && !hasCurrent && !allowEmpty) {
            html = '<option value="' + escapeHtml(current) + '" selected>'
                + escapeHtml(current) + ' (nicht in Tabelle)</option>' + html;
        } else if (current && !hasCurrent) {
            html += '<option value="' + escapeHtml(current) + '" selected>'
                + escapeHtml(current) + ' (nicht in Tabelle)</option>';
        }

        select.innerHTML = html;
        select.disabled = false;
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function loadColumns(table) {
        var selects = document.querySelectorAll('[data-yfl-column-select="1"]');
        if (!selects.length) {
            return;
        }

        if (!table) {
            selects.forEach(function (s) {
                s.innerHTML = '<option value="">— erst Tabelle wählen —</option>';
                s.disabled = true;
            });
            return;
        }

        selects.forEach(function (s) {
            s.disabled = true;
            s.innerHTML = '<option>… lade Spalten …</option>';
        });

        var apiUrl = (window.rex && window.rex.YFL_API_URL) || window.YFL_API_URL || '';
        var url = apiUrl + (apiUrl.indexOf('?') === -1 ? '?' : '&')
            + 'table=' + encodeURIComponent(table);

        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var cols = (data && Array.isArray(data.columns)) ? data.columns : [];
                selects.forEach(function (s) { buildOptions(s, cols); });
                var urlProfiles = (data && Array.isArray(data.url_profiles)) ? data.url_profiles : [];
                fillUrlProfiles(urlProfiles);
            })
            .catch(function () {
                selects.forEach(function (s) {
                    s.innerHTML = '<option value="">— Fehler beim Laden —</option>';
                });
            });
    }

    function fillUrlProfiles(profiles) {
        var sel = document.getElementById('yfl-url-profile');
        if (!sel) {
            return;
        }
        var current = sel.dataset.currentValue || '';
        var html = '<option value=""' + ('' === current ? ' selected' : '') + '>— kein URL-Profil —</option>';
        var hasCurrent = false;
        for (var i = 0; i < profiles.length; i++) {
            var p = profiles[i];
            if (!p || !p.namespace) { continue; }
            var s = (p.namespace === current) ? ' selected' : '';
            if (p.namespace === current) { hasCurrent = true; }
            html += '<option value="' + escapeHtml(p.namespace) + '"' + s + '>' + escapeHtml(p.label || p.namespace) + '</option>';
        }
        if (current && !hasCurrent) {
            html += '<option value="' + escapeHtml(current) + '" selected>' + escapeHtml(current) + ' (nicht für diese Tabelle)</option>';
        }
        sel.innerHTML = html;
        sel.disabled = false;
    }

    function init() {
        var tableSelect = document.getElementById('yfl-profile-table');
        if (!tableSelect) {
            return;
        }
        // Doppelte Initialisierung bei mehreren rex:ready-Events vermeiden
        if (tableSelect.dataset.yflInit === '1') {
            return;
        }
        tableSelect.dataset.yflInit = '1';

        tableSelect.addEventListener('change', function () {
            loadColumns(tableSelect.value);
        });
        // Initiale Auswahl: wenn Tabelle bereits gewählt aber Selects noch leer
        if (tableSelect.value) {
            var selects = document.querySelectorAll('[data-yfl-column-select="1"]');
            var needsLoad = false;
            selects.forEach(function (s) {
                if (s.options.length <= 1 && !s.dataset.serverFilled) {
                    needsLoad = true;
                }
            });
            if (needsLoad) {
                loadColumns(tableSelect.value);
            }
        }

        initProfileTypeUi();
    }

    function initProfileTypeUi() {
        var typeSelect = document.getElementById('yfl-profile-type');
        if (!typeSelect || typeSelect.dataset.yflTypeInit === '1') {
            return;
        }
        typeSelect.dataset.yflTypeInit = '1';

        function applyTypeVisibility() {
            var type = typeSelect.value || 'generic';
            var contactLabels = document.querySelectorAll('.yfl-type-contact');
            contactLabels.forEach(function (label) {
                var row = label.closest('.form-group');
                if (!row) {
                    return;
                }
                row.style.display = (type === 'contact') ? '' : 'none';
            });

            var layoutSelect = document.querySelector('select[name="default_layout"]');
            if (!layoutSelect) {
                return;
            }

            var current = layoutSelect.value;
            var preferred = {
                contact: 'contact',
                news: 'cards',
                event: 'list',
                product: 'cards',
                slides: 'slides',
                generic: 'cards'
            };

            if (current === '' || current === 'cards' || current === 'list' || current === 'contact') {
                if (preferred[type]) {
                    layoutSelect.value = preferred[type];
                }
            }
        }

        typeSelect.addEventListener('change', applyTypeVisibility);
        applyTypeVisibility();
    }

    // REDAXO-Standard: rex:ready (jQuery-Event) wird sowohl beim ersten Laden
    // als auch nach PJAX-Page-Reloads gefeuert. Fallback auf DOMContentLoaded
    // falls jQuery noch nicht verfügbar.
    if (window.jQuery) {
        jQuery(document).on('rex:ready', init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
