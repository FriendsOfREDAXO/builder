/*
 * YForm Content Builder - Field Widgets
 * - SmartLink (combined target field)
 */
(function($) {
    'use strict';

    var checkboxToggleInitialized = false;

    function initCheckboxToggleActions() {
        if (checkboxToggleInitialized) {
            return;
        }

        checkboxToggleInitialized = true;

        $(document).on('click.builderCheckboxToggle', '[data-builder-checkbox-toggle]', function(e) {
            e.preventDefault();

            var $trigger = $(this);
            var targetSelector = String($trigger.attr('data-builder-checkbox-target') || '').trim();
            var action = String($trigger.attr('data-builder-checkbox-toggle') || '').trim();
            var scopeSelector = String($trigger.attr('data-builder-checkbox-scope') || '').trim();

            if (targetSelector === '' || (action !== 'check' && action !== 'uncheck')) {
                return;
            }

            var $scope = scopeSelector !== '' ? $(scopeSelector) : $(document);
            if ($scope.length === 0) {
                $scope = $(document);
            }

            $scope.find(targetSelector).prop('checked', action === 'check');
        });
    }

    function updateColorFieldUi($root) {
        var $scope = $root && $root.length ? $root : $(document);
        $scope.find('[data-cb-color-field="1"]').each(function() {
            var $field = $(this);
            var $input = $field.find('.cb-color-input');
            var $dot = $field.find('.cb-color-dot');
            var $code = $field.find('.cb-color-code');

            if ($input.length === 0) {
                return;
            }

            var val = ($input.val() || '').toString().trim().toLowerCase();
            if (!/^#[0-9a-f]{6}$/.test(val)) {
                val = '#3b82f6';
                $input.val(val);
            }

            if ($dot.length) {
                $dot.css('background-color', val);
            }
            if ($code.length) {
                $code.val(val);
            }
        });
    }

    function detectLinkType(value) {
        var v = (value || '').trim();
        if (v === '') {
            return 'url';
        }
        if (/^mailto:/i.test(v)) {
            return 'mail';
        }
        if (/^tel:/i.test(v)) {
            return 'tel';
        }
        if (/^#/i.test(v)) {
            return 'url';
        }
        if (/^\d+$/.test(v)) {
            return 'intern';
        }
        if (/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)) {
            return 'mail';
        }
        if (/^\+?[0-9\s\-()\/]{5,}$/.test(v)) {
            return 'tel';
        }
        if (/^[a-z0-9_]+:\d+$/i.test(v)) {
            return 'yform';
        }
        if (/^yform:\/\/[a-z0-9_]+\/\d+$/i.test(v)) {
            return 'yform';
        }
        if (/\.(jpg|jpeg|png|gif|webp|svg|pdf|mp4|webm|mov|avi)$/i.test(v)) {
            return 'media';
        }
        return 'url';
    }

    function normalizeTargetByType(type, value) {
        var v = (value || '').toString().trim();
        if (type === 'mail' && v !== '' && !/^mailto:/i.test(v)) {
            return 'mailto:' + v;
        }
        if (type === 'tel' && v !== '' && !/^tel:/i.test(v)) {
            return 'tel:' + v;
        }
        return v;
    }

    var labelCache = {};

    function displayTextForTarget(value, callback, preferredLabel) {
        var v = (value || '').toString().trim();
        var display = (preferredLabel || '').toString().trim();

        if (display === '') {
            display = v;
        }
        
        if (!callback) {
            return display;
        }

        labelCache[v] = display;
        callback(display);
    }

    function updateAutoLabel($row, label) {
        var $labelInput = $row.find('.cb-smart-link-label');
        var current = ($labelInput.val() || '').toString().trim();
        var autoLabel = ($labelInput.data('auto-label') || '').toString().trim();
        var nextLabel = (label || '').toString().trim();

        if ($labelInput.length === 0) {
            return;
        }

        if (current === '' || current === autoLabel) {
            $labelInput.val(nextLabel);
        }

        $labelInput.data('auto-label', nextLabel);
    }

    function applySelection($widget, $row, type, value, displayLabel, autoLabel) {
        var normalizedValue = (value || '').toString().trim();
        var display = displayTextForTarget(normalizedValue, null, displayLabel);

        $row.find('.cb-smart-link-target').val(normalizedValue);
        $row.find('.cb-smart-link-target-display').val(display);
        refreshSmartLinkSelect($row.find('.cb-smart-link-type'), type);

        updateAutoLabel($row, autoLabel || display);
        smartLinkToggleTypeUi($row);
        smartLinkSerialize($widget);

        // Success flash
        $row.removeClass('is-success');
        // Force reflow so re-adding the class retriggers the animation
        void $row[0].offsetWidth;
        $row.addClass('is-success');
        setTimeout(function() { $row.removeClass('is-success'); }, 600);
    }

    function renderSmartLinkPreview($row) {
        var type = $row.find('.cb-smart-link-type').val();
        var value = ($row.find('.cb-smart-link-target').val() || '').toString().trim();
        var $preview = $row.find('.cb-smart-link-preview');

        if (value === '') {
            $preview.text('');
            return;
        }

        var effectiveType = type === 'auto' ? detectLinkType(value) : type;
        if (effectiveType === 'media') {
            var safe = $('<div>').text(value).html();
            $preview.html('Media: <strong>' + safe + '</strong>');
            return;
        }

        $preview.text('Erkannt: ' + effectiveType + ' → ' + value);
    }

    function initSmartLinkSelects($scope) {
        if (!$scope || $scope.length === 0) {
            return;
        }

        $scope.find('.cb-smart-link-select').each(function() {
            $(this).removeAttr('tabindex');
        });
    }

    function refreshSmartLinkSelect($select, value) {
        if (!$select || $select.length === 0) {
            return;
        }

        $select.val(value);
    }

    function smartLinkSerialize($widget) {
        var multiple = $widget.data('multiple') == 1;
        var items = [];

        $widget.find('.cb-smart-link-row').each(function() {
            var $row = $(this);
            var type = ($row.find('.cb-smart-link-type').val() || 'auto').toString();
            var value = ($row.find('.cb-smart-link-target').val() || '').toString().trim();
            var label = ($row.find('.cb-smart-link-label').val() || '').toString().trim();
            var pdfjs = $row.find('.cb-smart-link-pdfjs').is(':checked');

            if (type === 'yform') {
                var yformVal = ($row.find('.cb-smart-link-yform').val() || '').toString().trim();
                if (yformVal !== '') {
                    value = yformVal;
                }
            }

            value = normalizeTargetByType(type, value);

            if (value === '') {
                return;
            }

            items.push({
                type: type,
                value: value,
                label: label,
                pdfjs: pdfjs
            });
        });

        if (!multiple && items.length > 1) {
            items = [items[0]];
        }

        $widget.find('.cb-smart-link-value').val(JSON.stringify({
            multiple: multiple,
            items: items
        }));
    }

    function smartLinkToggleTypeUi($row) {
        var type = $row.find('.cb-smart-link-type').val();
        var targetValue = ($row.find('.cb-smart-link-target').val() || '').toString();

        if (type === 'yform') {
            $row.find('.cb-smart-link-yform-wrap').show();
        } else {
            $row.find('.cb-smart-link-yform-wrap').hide();
        }

        if (type === 'media' || type === 'auto') {
            $row.find('.cb-smart-link-pdfjs').closest('label').show();
        } else {
            $row.find('.cb-smart-link-pdfjs').closest('label').hide();
            $row.find('.cb-smart-link-pdfjs').prop('checked', false);
        }

        // NOTE: DO NOT update display input here - let PHP render initial value
        // Only update when picker closes (via displayTextForTarget callback)

        renderSmartLinkPreview($row);
    }

    function bindCustomLinkButtons($widget) {
        $widget.on('click', '.cb-smart-link-target-wrap .intern_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            var $wrap = $row.find('.cb-smart-link-target-wrap');
            var widgetId = ($wrap.data('link-widget-id') || '').toString();
            if (widgetId === '' || typeof openLinkMap !== 'function') {
                return;
            }

            var $group = $row.find('.cb-smart-link-target-wrap');
            var clang = ($group.data('clang') || '').toString();
            var category = ($group.data('category') || '').toString();
            var args = '';
            if (clang !== '') {
                args += '&clang=' + clang;
            }
            if (category !== '') {
                args += '&category_id=' + category;
            }

            var linkMap = openLinkMap('REX_LINK_' + widgetId, args);
            if (linkMap && typeof $ === 'function') {
                $(linkMap).off('rex:selectLink.cbSmartLink').on('rex:selectLink.cbSmartLink', function(event, link, name) {
                    event.preventDefault();
                    if (typeof linkMap.close === 'function') {
                        linkMap.close();
                    }

                    applySelection(
                        $widget,
                        $row,
                        'intern',
                        (link || '').toString().replace(/^redaxo:\/\//, ''),
                        (name || '').toString().trim(),
                        (name || '').toString().trim()
                    );
                });
            }
        });

        $widget.on('click', '.cb-smart-link-target-wrap .media_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            var $wrap = $row.find('.cb-smart-link-target-wrap');
            var widgetId = ($wrap.data('link-widget-id') || '').toString();
            if (widgetId === '') {
                return;
            }

            // MediaPlace-Weiche (siehe assets/js/mediaplace-bridge.js): bei aktivem
            // Overlay direkt darueber picken statt das klassische Popup zu oeffnen.
            if (typeof window.rex5MediaplaceBridge !== 'undefined' && window.rex5MediaplaceBridge.isActive()) {
                window.rex5MediaplaceBridge.pick(function(filename) {
                    if (!filename) {
                        return;
                    }
                    applySelection($widget, $row, 'media', filename, filename, filename);
                });
                return;
            }

            if (typeof openREXMedia !== 'function') {
                return;
            }

            var $target = $row.find('.cb-smart-link-target');
            var originalId = $target.attr('id');
            $target.attr('id', 'REX_MEDIA_' + widgetId);

            var mediaMap = openREXMedia(widgetId, '');
            if (mediaMap && typeof $ === 'function') {
                $(mediaMap).off('rex:selectMedia.cbSmartLink').on('rex:selectMedia.cbSmartLink', function(event, mediaName) {
                    event.preventDefault();
                    if (typeof mediaMap.close === 'function') {
                        mediaMap.close();
                    }

                    $target.attr('id', originalId);
                    applySelection(
                        $widget,
                        $row,
                        'media',
                        mediaName,
                        mediaName,
                        mediaName
                    );
                });
            }
        });

        $widget.on('click', '.cb-smart-link-target-wrap .external_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            var $input = $row.find('.cb-smart-link-target');
            var current = ($input.val() || '').toString();
            if (current === '' || current.indexOf('http') !== 0) {
                current = 'https://';
            }
            var val = window.prompt('Link', current);
            if (val !== null) {
                applySelection($widget, $row, 'url', val, val, val);
            }
        });

        $widget.on('click', '.cb-smart-link-target-wrap .email_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            var $input = $row.find('.cb-smart-link-target');
            var current = ($input.val() || '').toString();
            if (current === '' || !/^mailto:/i.test(current)) {
                current = 'mailto:';
            }
            var val = window.prompt('Mail', current);
            if (val !== null) {
                applySelection($widget, $row, 'mail', val, val, val);
            }
        });

        $widget.on('click', '.cb-smart-link-target-wrap .phone_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            var $input = $row.find('.cb-smart-link-target');
            var current = ($input.val() || '').toString();
            if (current === '' || !/^tel:/i.test(current)) {
                current = 'tel:';
            }
            var val = window.prompt('Telefon', current);
            if (val !== null) {
                applySelection($widget, $row, 'tel', val, val, val);
            }
        });

        $widget.on('click', '.cb-smart-link-target-wrap .delete_link', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.cb-smart-link-row');
            $row.find('.cb-smart-link-target').val('');
            var $nameInput = $row.find('.cb-smart-link-target-display');
            if ($nameInput.length) {
                $nameInput.val('');
            }
            updateAutoLabel($row, '');
            smartLinkToggleTypeUi($row);
            smartLinkSerialize($widget);
        });
    }

    function startSmartLinkSync($widget) {
        if (!$widget.data('smartlink-sync')) {
            $widget.data('smartlink-sync', 1);
        }
    }

    function initSmartLinkWidget($widget) {
        if ($widget.data('smartlink-init')) {
            return;
        }
        $widget.data('smartlink-init', 1);

        bindCustomLinkButtons($widget);

        $widget.on('change input', '.cb-smart-link-type, .cb-smart-link-target, .cb-smart-link-label, .cb-smart-link-pdfjs, .cb-smart-link-yform', function() {
            var $row = $(this).closest('.cb-smart-link-row');
            if ($(this).hasClass('cb-smart-link-yform')) {
                var yv = ($(this).val() || '').toString();
                if (yv !== '') {
                    applySelection($widget, $row, 'yform', yv, yv, yv);
                    return;
                }
            }
            smartLinkToggleTypeUi($row);
            smartLinkSerialize($widget);
        });

        $widget.on('click', '.cb-smart-link-detect', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('.cb-smart-link-row');
            var value = ($row.find('.cb-smart-link-target').val() || '').toString();
            refreshSmartLinkSelect($row.find('.cb-smart-link-type'), detectLinkType(value));
            smartLinkToggleTypeUi($row);
            smartLinkSerialize($widget);

            // Spin feedback on the icon
            $btn.addClass('is-spinning');
            setTimeout(function() { $btn.removeClass('is-spinning'); }, 520);
        });

        $widget.on('click', '.cb-smart-link-remove', function(e) {
            e.preventDefault();
            var $rows = $widget.find('.cb-smart-link-row');
            var $row = $(this).closest('.cb-smart-link-row');
            if ($rows.length > 1 && $widget.data('multiple') == 1) {
                $row.addClass('is-removing');
                setTimeout(function() { $row.remove(); smartLinkSerialize($widget); }, 300);
            } else {
                $row.addClass('is-removing');
                setTimeout(function() {
                    $row.removeClass('is-removing');
                    $row.find('.cb-smart-link-target, .cb-smart-link-label').val('');
                    $row.find('.cb-smart-link-target-display').val('');
                    $row.find('.cb-smart-link-yform').val('');
                    updateAutoLabel($row, '');
                    smartLinkToggleTypeUi($row);
                    smartLinkSerialize($widget);
                }, 310);
            }
        });

        $widget.on('click', '.cb-smart-link-add', function(e) {
            e.preventDefault();
            var $first = $widget.find('.cb-smart-link-row').first();
            var $clone = $first.clone();
            $clone.find('input[type="text"]').val('');
            $clone.find('input[type="hidden"].cb-smart-link-target').val('');
            $clone.find('.cb-smart-link-type').val('auto');
            $clone.find('.cb-smart-link-yform').val('');
            $clone.find('.cb-smart-link-pdfjs').prop('checked', false);
            $clone.find('.cb-smart-link-preview').text('');

            var newWidgetId = 'cbsl_' + Math.floor(Math.random() * 900000 + 100000);
            $clone.find('.cb-smart-link-target-wrap [id]').each(function() {
                var oldId = $(this).attr('id');
                if (!oldId) {
                    return;
                }
                var replaced = oldId.replace(/REX_LINK_[^"\s]+/g, function(match) {
                    if (/_NAME$/.test(match)) {
                        return 'REX_LINK_' + newWidgetId + '_NAME';
                    }
                    return 'REX_LINK_' + newWidgetId;
                }).replace(/mform_[a-z_]+_[^"\s]+/g, function(match) {
                    var prefix = match.replace(/_[^_]+$/, '');
                    return prefix + '_' + newWidgetId;
                });
                $(this).attr('id', replaced);
            });
            $clone.find('.cb-smart-link-target-wrap [name]').each(function() {
                var oldName = $(this).attr('name');
                if (!oldName) {
                    return;
                }
                if (oldName.indexOf('cb_smart_link_target_') === 0) {
                    $(this).attr('name', 'cb_smart_link_target_' + newWidgetId);
                }
            });
            $clone.find('.cb-smart-link-target-wrap').attr('data-link-widget-id', newWidgetId);
            $clone.find('.cb-smart-link-target').data('last-val', '');

            $widget.find('.cb-smart-link-rows').append($clone);
            initSmartLinkSelects($clone);
            smartLinkToggleTypeUi($clone);
            smartLinkSerialize($widget);
        });

        initSmartLinkSelects($widget);

        $widget.find('.cb-smart-link-row').each(function() {
            var $row = $(this);
            var initialLabel = ($row.find('.cb-smart-link-label').val() || '').toString().trim();
            var initialDisplay = ($row.find('.cb-smart-link-target-display').val() || '').toString().trim();
            var initialValue = ($row.find('.cb-smart-link-target').val() || '').toString().trim();
            if (initialLabel !== '' && (initialLabel === initialDisplay || initialLabel === initialValue)) {
                $row.find('.cb-smart-link-label').data('auto-label', initialLabel);
            }
            smartLinkToggleTypeUi($row);
        });
        startSmartLinkSync($widget);
        smartLinkSerialize($widget);
    }

    $(document).on('input change', '.cb-color-input', function() {
        updateColorFieldUi($(this).closest('[data-cb-color-field="1"]'));
    });

    $(document).on('rex:ready', function(e) {
        var $root = e && e.target ? $(e.target) : $(document);
        initCheckboxToggleActions();
        updateColorFieldUi($root);
    });

    $(function() {
        initCheckboxToggleActions();
        updateColorFieldUi($(document));
    });

    function parseTableEditorConfig($widget) {
        var defaults = {
            minCols: 1,
            maxCols: 999,
            minRows: 1,
            maxRows: 999,
            headerRowPolicy: 'user',
            headerColPolicy: 'user',
            enableMedia: false,
            enableLink: false,
            enableTextarea: true
        };

        var raw = $widget.attr('data-config') || '{}';
        try {
            var parsed = JSON.parse(raw);
            return $.extend({}, defaults, parsed || {});
        } catch (e) {
            return defaults;
        }
    }

    function tableEditorGetBoolConfig($widget, key) {
        var $el = $widget.find('[data-config="' + key + '"]');
        if ($el.length === 0) {
            return false;
        }
        if ($el.is(':checkbox')) {
            return $el.is(':checked');
        }
        return ($el.val() || '').toString() === '1';
    }

    function normalizeTableEditorState(state, config) {
        state.rows = Array.isArray(state.rows) ? state.rows : [];
        state.cols = Array.isArray(state.cols) ? state.cols : [];

        if (state.rows.length === 0) {
            state.rows = [['']];
        }

        var colCount = 1;
        state.rows.forEach(function(row) {
            if (Array.isArray(row)) {
                colCount = Math.max(colCount, row.length);
            }
        });
        colCount = Math.max(colCount, config.minCols);

        while (state.cols.length < colCount) {
            state.cols.push({ type: 'text', header_type: 'text' });
        }
        if (state.cols.length > colCount) {
            state.cols = state.cols.slice(0, colCount);
        }

        state.cols = state.cols.map(function(col) {
            var type = (col && col.type) ? col.type : 'text';
            var headerType = (col && col.header_type) ? col.header_type : 'text';
            return {
                type: type,
                header_type: headerType
            };
        });

        state.rows = state.rows.map(function(row) {
            var out = Array.isArray(row) ? row.slice(0, colCount) : [];
            while (out.length < colCount) {
                out.push('');
            }
            return out.map(function(cell) {
                return (cell == null) ? '' : String(cell);
            });
        });

        while (state.rows.length < config.minRows) {
            state.rows.push(new Array(colCount).fill(''));
        }

        if (config.headerRowPolicy === 'yes') {
            state.has_header_row = true;
        }
        if (config.headerRowPolicy === 'no') {
            state.has_header_row = false;
        }
        if (config.headerColPolicy === 'yes') {
            state.has_header_col = true;
        }
        if (config.headerColPolicy === 'no') {
            state.has_header_col = false;
        }
    }

    function tableEditorTypes(config) {
        var types = ['text', 'number', 'center'];
        if (config.enableTextarea) {
            types.push('textarea');
        }
        if (config.enableMedia) {
            types.push('media');
        }
        if (config.enableLink) {
            types.push('link');
        }
        return types;
    }

    function tableEditorTypeLabel(type) {
        if (type === 'number') {
            return 'Zahl';
        }
        if (type === 'center') {
            return 'Zentriert';
        }
        if (type === 'textarea') {
            return 'Mehrzeilig';
        }
        if (type === 'media') {
            return 'Medien';
        }
        if (type === 'link') {
            return 'Link';
        }
        return 'Text';
    }

    function tableEditorTypeIcon(type) {
        if (type === 'number') {
            return 'fa-hashtag';
        }
        if (type === 'center') {
            return 'fa-align-center';
        }
        if (type === 'textarea') {
            return 'fa-paragraph';
        }
        if (type === 'media') {
            return 'fa-file-o';
        }
        if (type === 'link') {
            return 'rex-icon-open-linkmap';
        }
        return 'fa-font';
    }

    function tableEditorHeaderIcon(type) {
        if (type === 'number') {
            return 'fa-align-right';
        }
        if (type === 'center') {
            return 'fa-align-center';
        }
        return 'fa-align-left';
    }

    function tableEditorApplyAlign($input, type) {
        if (type === 'number') {
            $input.css('text-align', 'right');
            return;
        }
        if (type === 'center') {
            $input.css('text-align', 'center');
            return;
        }
        $input.css('text-align', 'left');
    }

    function initTableEditorWidget($widget) {
        if ($widget.data('table-editor-init')) {
            return;
        }
        $widget.data('table-editor-init', 1);

        var config = parseTableEditorConfig($widget);
        var $hidden = $widget.find('.cb-table-editor-value');
        var $caption = $widget.find('.cb-table-editor-caption');
        var $table = $widget.find('.cb-table-editor-table');

        var initialState = {};
        try {
            initialState = JSON.parse($hidden.val() || '{}') || {};
        } catch (e) {
            initialState = {};
        }

        var state = {
            caption: (initialState.caption || $caption.val() || '').toString(),
            has_header_row: tableEditorGetBoolConfig($widget, 'has_header_row'),
            has_header_col: tableEditorGetBoolConfig($widget, 'has_header_col'),
            cols: initialState.cols || [],
            rows: initialState.rows || []
        };

        normalizeTableEditorState(state, config);

        function persist() {
            $hidden.val(JSON.stringify({
                caption: state.caption,
                has_header_row: state.has_header_row,
                has_header_col: state.has_header_col,
                cols: state.cols,
                rows: state.rows
            }));
        }

        function render() {
            normalizeTableEditorState(state, config);

            var $thead = $table.find('thead').empty();
            var $tbody = $table.find('tbody').empty();

            var canAddRow = state.rows.length < config.maxRows;
            var canAddCol = state.cols.length < config.maxCols;
            var canDelRow = state.rows.length > config.minRows;
            var canDelCol = state.cols.length > config.minCols;

            $widget.find('.cb-table-editor-add-row').toggle(canAddRow);
            $widget.find('.cb-table-editor-add-col').toggle(canAddCol);

            var $metaRow = $('<tr class="cb-table-editor-meta-row"></tr>');
            state.cols.forEach(function(col, colIndex) {
                var $th = $('<th class="text-center" style="padding:5px;"></th>');
                var $btnGroup = $('<div style="display:flex;justify-content:center;gap:2px;"></div>');

                var $headerBtn = $('<button type="button" class="btn btn-default btn-xs cb-table-editor-toggle-header" data-col="' + colIndex + '"></button>');
                $headerBtn.attr('title', 'Kopf-Ausrichtung');
                $headerBtn.html('<i class="rex-icon ' + tableEditorHeaderIcon(col.header_type || 'text') + '"></i>');
                $btnGroup.append($headerBtn);

                var $typeBtn = $('<button type="button" class="btn btn-default btn-xs cb-table-editor-toggle-type" data-col="' + colIndex + '"></button>');
                $typeBtn.attr('title', tableEditorTypeLabel(col.type || 'text'));
                $typeBtn.html('<i class="rex-icon ' + tableEditorTypeIcon(col.type || 'text') + '"></i>');
                $btnGroup.append($typeBtn);

                if (canAddCol) {
                    $btnGroup.append('<button type="button" class="btn btn-default btn-xs cb-table-editor-add-col-inline" data-col="' + colIndex + '" title="Spalte rechts einfügen"><i class="rex-icon fa-plus"></i></button>');
                }
                if (canDelCol) {
                    $btnGroup.append('<button type="button" class="btn btn-default btn-xs cb-table-editor-del-col" data-col="' + colIndex + '" title="Spalte löschen" style="color:#d9534f;"><i class="rex-icon fa-times"></i></button>');
                }

                $th.append($btnGroup);
                $metaRow.append($th);
            });
            $thead.append($metaRow);

            state.rows.forEach(function(row, rowIndex) {
                var isHeaderRow = rowIndex === 0 && state.has_header_row;
                var $tr = $('<tr></tr>');
                if (isHeaderRow) {
                    $tr.addClass('info');
                }

                row.forEach(function(cell, colIndex) {
                    var col = state.cols[colIndex] || { type: 'text', header_type: 'text' };
                    var isHeaderCol = colIndex === 0 && state.has_header_col;
                    var isHeaderCell = isHeaderRow || isHeaderCol;
                    var $cell = $('<' + (isHeaderCell ? 'th' : 'td') + '></' + (isHeaderCell ? 'th' : 'td') + '>');
                    if (isHeaderCol && !isHeaderRow) {
                        $cell.attr('scope', 'row');
                    }

                    var inputType = (!isHeaderRow && col.type === 'textarea') ? 'textarea' : 'input';
                    var $input = inputType === 'textarea'
                        ? $('<textarea class="form-control" rows="1" style="resize:vertical;min-height:30px;"></textarea>')
                        : $('<input type="text" class="form-control input-sm">');

                    $input.attr('data-row', rowIndex);
                    $input.attr('data-col', colIndex);
                    $input.val(cell);

                    var alignType = isHeaderRow ? (col.header_type || 'text') : (col.type || 'text');
                    tableEditorApplyAlign($input, alignType);

                    var $wrap = $('<div class="cb-table-editor-cell-wrap" style="display:flex;gap:4px;align-items:flex-start;"></div>');
                    $wrap.append($input);

                    if (colIndex === row.length - 1) {
                        var $actions = $('<div class="cb-table-editor-actions"></div>');
                        if (canAddRow) {
                            $actions.append('<button type="button" class="btn btn-default btn-xs cb-table-editor-add-row-inline" data-row="' + rowIndex + '" title="Zeile darunter einfügen" tabindex="-1"><i class="rex-icon fa-plus"></i></button>');
                        }
                        if (canDelRow) {
                            $actions.append('<button type="button" class="btn btn-default btn-xs cb-table-editor-del-row" data-row="' + rowIndex + '" title="Zeile löschen" tabindex="-1"><i class="rex-icon fa-times"></i></button>');
                        }
                        $wrap.append($actions);
                    }

                    $cell.append($wrap);
                    $tr.append($cell);
                });

                $tbody.append($tr);
            });

            persist();
        }

        $widget.on('input change', '.cb-table-editor-caption', function() {
            state.caption = ($(this).val() || '').toString();
            persist();
        });

        $widget.on('change', '.cb-table-editor-config', function() {
            var key = ($(this).data('config') || '').toString();
            if (key !== 'has_header_row' && key !== 'has_header_col') {
                return;
            }
            state[key] = $(this).is(':checkbox') ? $(this).is(':checked') : ($(this).val() === '1');
            render();
        });

        $widget.on('input change', '[data-row][data-col]', function() {
            var row = parseInt($(this).attr('data-row'), 10);
            var col = parseInt($(this).attr('data-col'), 10);
            if (Number.isNaN(row) || Number.isNaN(col) || !state.rows[row]) {
                return;
            }
            state.rows[row][col] = ($(this).val() || '').toString();
            persist();
        });

        $widget.on('click', '.cb-table-editor-add-row', function(e) {
            e.preventDefault();
            if (state.rows.length >= config.maxRows) {
                return;
            }
            state.rows.push(new Array(state.cols.length).fill(''));
            render();
        });

        $widget.on('click', '.cb-table-editor-add-col', function(e) {
            e.preventDefault();
            if (state.cols.length >= config.maxCols) {
                return;
            }
            state.cols.push({ type: 'text', header_type: 'text' });
            state.rows.forEach(function(row) {
                row.push('');
            });
            render();
        });

        $widget.on('click', '.cb-table-editor-add-col-inline', function(e) {
            e.preventDefault();
            if (state.cols.length >= config.maxCols) {
                return;
            }
            var col = parseInt($(this).attr('data-col'), 10);
            if (Number.isNaN(col)) {
                return;
            }
            state.cols.splice(col + 1, 0, { type: 'text', header_type: 'text' });
            state.rows.forEach(function(row) {
                row.splice(col + 1, 0, '');
            });
            render();
        });

        $widget.on('click', '.cb-table-editor-del-col', function(e) {
            e.preventDefault();
            if (state.cols.length <= config.minCols) {
                return;
            }
            var col = parseInt($(this).attr('data-col'), 10);
            if (Number.isNaN(col)) {
                return;
            }
            state.cols.splice(col, 1);
            state.rows.forEach(function(row) {
                row.splice(col, 1);
            });
            render();
        });

        $widget.on('click', '.cb-table-editor-add-row-inline', function(e) {
            e.preventDefault();
            if (state.rows.length >= config.maxRows) {
                return;
            }
            var row = parseInt($(this).attr('data-row'), 10);
            if (Number.isNaN(row)) {
                return;
            }
            state.rows.splice(row + 1, 0, new Array(state.cols.length).fill(''));
            render();
        });

        $widget.on('click', '.cb-table-editor-del-row', function(e) {
            e.preventDefault();
            if (state.rows.length <= config.minRows) {
                return;
            }
            var row = parseInt($(this).attr('data-row'), 10);
            if (Number.isNaN(row)) {
                return;
            }
            state.rows.splice(row, 1);
            render();
        });

        $widget.on('click', '.cb-table-editor-toggle-header', function(e) {
            e.preventDefault();
            var col = parseInt($(this).attr('data-col'), 10);
            if (Number.isNaN(col) || !state.cols[col]) {
                return;
            }
            var current = state.cols[col].header_type || 'text';
            state.cols[col].header_type = current === 'text' ? 'center' : (current === 'center' ? 'number' : 'text');
            render();
        });

        $widget.on('click', '.cb-table-editor-toggle-type', function(e) {
            e.preventDefault();
            var col = parseInt($(this).attr('data-col'), 10);
            if (Number.isNaN(col) || !state.cols[col]) {
                return;
            }
            var types = tableEditorTypes(config);
            var current = state.cols[col].type || 'text';
            var index = types.indexOf(current);
            if (index < 0) {
                index = 0;
            }
            state.cols[col].type = types[(index + 1) % types.length];
            render();
        });

        render();
    }


    function initAll(root) {
        var $root = root ? $(root) : $(document);
        var smartLinks = $root.find('.cb-smart-link-widget');
        var tableEditors = $root.find('.cb-table-editor');

        if (smartLinks.length > 0) {
            console.log('[CB-Widgets] Initializing:', smartLinks.length, 'SmartLink(s)');
        }

        if (tableEditors.length > 0) {
            console.log('[CB-Widgets] Initializing:', tableEditors.length, 'TableEditor(s)');
        }

        smartLinks.each(function() {
            initSmartLinkWidget($(this));
        });

        tableEditors.each(function() {
            initTableEditorWidget($(this));
        });
    }

    function initPublic(root) {
        initAll(root || document);
        ensureAutoInitObserver();
    }

    window.YCBFieldWidgets = window.YCBFieldWidgets || {};
    window.YCBFieldWidgets.init = initPublic;

    var cbWidgetsObserverInitialized = false;
    function ensureAutoInitObserver() {
        if (cbWidgetsObserverInitialized) {
            return;
        }
        cbWidgetsObserverInitialized = true;

        if (typeof MutationObserver === 'undefined' || !document.body) {
            return;
        }

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (!m.addedNodes || m.addedNodes.length === 0) {
                    return;
                }
                for (var i = 0; i < m.addedNodes.length; i++) {
                    var node = m.addedNodes[i];
                    if (!node || node.nodeType !== 1) {
                        continue;
                    }
                    initAll(node);
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    $(document).ready(function() {
        console.log('[CB-Widgets] Document ready, initializing...');
        initPublic(document);
    });

    $(document).on('rex:ready', function(e, container) {
        console.log('[CB-Widgets] rex:ready event, initializing container...');
        initAll(container || document);
    });

    $(document).on('shown.bs.modal', '.modal', function() {
        console.log('[CB-Widgets] Modal shown, initializing modal contents...');
        initAll(this);
    });

})(jQuery);
