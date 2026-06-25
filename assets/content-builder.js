/**
 * YForm Content Builder JavaScript
 * Edit-on-Click & Drag-Funktionalität
 */

(function($) {
    'use strict';
    
    console.log('Content Builder JS loaded');
    
    // Flag um doppelte Initialisierung zu verhindern
    var eventsInitialized = false;
    var persistGuardInitialized = false;
    
    // API-URL für AJAX-Requests (rex_api_function)
    var apiUrl = '/redaxo/index.php?rex-api-call=content_builder';

    var ContentBuilder = {

        getAvailableElementsFromBuilder: function($builder) {
            var availableElementsRaw = ($builder && $builder.attr('data-available-elements')) || '{}';
            var availableElements = {};

            try {
                availableElements = JSON.parse(availableElementsRaw);
            } catch (e) {
                availableElements = {};
            }

            if (!availableElements || typeof availableElements !== 'object') {
                availableElements = {};
            }

            return availableElements;
        },
        
        // Getter für die API-URL (für AJAX-Requests)
        getAjaxUrl: function() {
            return apiUrl;
        },
        
        init: function() {
            // Events nur einmal binden (bei erstem init)
            if (!eventsInitialized) {
                this.bindEvents();
                this.fixTinyMCEInModals();
                this.initModalStacking();
                this.initDropdownZIndex();
                eventsInitialized = true;
            }
            this.initElementMenuTooltips();
            this.initMoveButtons();
            this.initGridViews();
            this.updateSectionClasses();
            this.updateInsertButtons();
            this.initPersistIndicators();
            
            // Paste-Buttons initial anzeigen, wenn Kopiertes vorhanden
            if (localStorage.getItem('yform_cb_copied_slice')) {
                $('.paste-slice-item').show();
            }

            this.syncAllLegacyHiddenFields();
        },

        initPersistIndicators: function() {
            var self = this;

            $('.yform-content-builder').each(function() {
                var $builder = $(this);
                var $hiddenField = $builder.find('.content-builder-data').first();

                if ($hiddenField.length === 0) {
                    return;
                }

                var baseline = $builder.attr('data-cb-persist-baseline');
                var currentValue = String($hiddenField.val() || '');

                if (typeof baseline === 'undefined') {
                    baseline = currentValue;
                    $builder.attr('data-cb-persist-baseline', baseline);
                    $builder.attr('data-cb-persist-dirty', '0');
                    self.setPersistState($builder, 'clean');
                    return;
                }

                if ($builder.attr('data-cb-persist-dirty') === '1' || String(baseline) !== currentValue) {
                    $builder.attr('data-cb-persist-dirty', '1');
                    self.setPersistState($builder, 'dirty');
                } else {
                    self.setPersistState($builder, 'clean');
                }
            });

            this.initPersistLeaveGuard();
        },

        setPersistState: function($builder, state) {
            var $status = $builder.find('.yform-cb-persist-status').first();
            if ($status.length === 0) {
                return;
            }

            var safeState = String(state || 'clean');
            var text = 'Alle Änderungen sind im Datensatz gespeichert.';

            $status.removeClass('is-clean is-dirty is-saving');

            if (safeState === 'dirty') {
                $status.addClass('is-dirty');
                text = 'Ungespeicherte Änderungen im Content Builder.';
            } else if (safeState === 'saving') {
                $status.addClass('is-saving');
                text = 'Datensatz wird gespeichert...';
            } else {
                $status.addClass('is-clean');
            }

            $status.attr('data-cb-persist-status', safeState);
            $status.find('.yform-cb-persist-text').text(text);
        },

        markPersistDirty: function($slice) {
            var $builder = $slice.closest('.yform-content-builder');
            if ($builder.length === 0) {
                return;
            }

            $builder.attr('data-cb-persist-dirty', '1');
            this.setPersistState($builder, 'dirty');
        },

        initPersistLeaveGuard: function() {
            if (persistGuardInitialized) {
                return;
            }
            persistGuardInitialized = true;

            $(window).on('beforeunload.yfcbPersistGuard', function(event) {
                var hasDirtyBuilder = $('.yform-content-builder[data-cb-persist-dirty="1"]').length > 0;
                if (!hasDirtyBuilder) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
                return '';
            });
        },

        syncLegacyHiddenField: function($textarea) {
            var $root = $textarea.closest('.yform-content-builder');
            var $hidden = $root.find('.content-builder-data').first();

            if ($root.length === 0 || $hidden.length === 0) {
                return;
            }

            $hidden.val($textarea.val() || '');
        },

        syncAllLegacyHiddenFields: function() {
            var self = this;
            $('.yform-cb-legacy-editor').each(function() {
                self.getLegacyEditorHtml($(this));
                self.syncLegacyHiddenField($(this));
            });
        },

        getLegacyEditorHtml: function($textarea) {
            if ($textarea.length === 0) {
                return '';
            }

            var textareaId = $textarea.attr('id') || '';

            if ($textarea.hasClass('tiny-editor') && typeof tinymce !== 'undefined' && textareaId !== '') {
                var tinyEditor = tinymce.get(textareaId);
                if (tinyEditor) {
                    $textarea.val(tinyEditor.getContent());
                }
            }

            var ckeEditor = $textarea.data('ycb-cke5-editor');
            if (ckeEditor && typeof ckeEditor.getData === 'function') {
                $textarea.val(ckeEditor.getData());
            }

            return $textarea.val() || '';
        },

        submitLegacyMigrationForm: function($button) {
            var $root = $button.closest('.yform-content-builder');
            var $textarea = $root.find('.yform-cb-legacy-editor').first();
            var $notice = $root.find('.yform-cb-legacy-notice').first();
            var $flag = $root.find('.yform-cb-legacy-migrate-flag').first();
            var $form = $root.closest('form');

            if ($root.length === 0 || $textarea.length === 0 || $flag.length === 0 || $form.length === 0) {
                return;
            }

            this.getLegacyEditorHtml($textarea);
            this.syncLegacyHiddenField($textarea);
            $flag.val('1');

            if ($notice.length) {
                $notice.removeClass('alert-info').addClass('alert-warning');
                $notice.find('span').first().text('Die Umwandlung wird jetzt gespeichert. Danach wird das Formular erneut mit modernem Content-Builder geöffnet.');
            }

            $button.prop('disabled', true).addClass('disabled');

            var submitter = $form.find('.btn-apply[type="submit"]').get(0)
                || $form.find('.btn-save[type="submit"]').get(0)
                || $form.find('button[type="submit"], input[type="submit"]').get(0);

            if (submitter && typeof $form.get(0).requestSubmit === 'function') {
                $form.get(0).requestSubmit(submitter);
                return;
            }

            if (submitter) {
                submitter.click();
                return;
            }

            $form.trigger('submit');
        },

        /**
         * Behebt z-index Problem beim Dropdown:
         * Wenn das Element-Dropdown geöffnet wird, erhöht sich der z-index
         * des parent .content-builder-slice damit es nicht von anderen Slices überdeckt wird
         */
        initDropdownZIndex: function() {
            $(document)
                .on('show.bs.dropdown', '.slice-toolbar .btn-group-insert, .column-add-slice, .content-builder-add', function() {
                    var $slice = $(this).closest('.content-builder-slice');
                    if ($slice.length > 0) {
                        // Keep dropdown above nearby slices, but below modals.
                        $slice.css('z-index', 1030);
                        $slice.find('> .slice-toolbar').css('z-index', 1031);
                    }
                    
                    var $menu = $(this).find('.dropdown-menu').first();
                    if ($menu.length > 0) {
                    }
                })
                .on('shown.bs.dropdown', '.slice-toolbar .btn-group-insert, .column-add-slice, .content-builder-add', function() {
                    var $menu = $(this).find('.dropdown-menu').first();
                    if ($menu.length > 0) {
                        var frozenWidth = $menu.outerWidth();
                        if (frozenWidth && frozenWidth > 0) {
                            $menu.css({
                                width: frozenWidth + 'px',
                                minWidth: frozenWidth + 'px'
                            });
                        }
                    }
                })
                .on('hidden.bs.dropdown', '.slice-toolbar .btn-group-insert, .column-add-slice, .content-builder-add', function() {
                    var $slice = $(this).closest('.content-builder-slice');
                    if ($slice.length > 0) {
                        $slice.css('z-index', 'auto');
                        $slice.find('> .slice-toolbar').css('z-index', '');
                    }

                    var $menu = $(this).find('.dropdown-menu').first();
                    if ($menu.length > 0) {
                        $menu.removeData('yfcbFrozenWidth');
                        $menu.css({
                            width: '',
                            minWidth: ''
                        });
                    }
                });
        },

        initElementMenuTooltips: function() {
            $(document)
                .off('shown.bs.dropdown.yfcbTooltips')
                .on('shown.bs.dropdown.yfcbTooltips', '.yform-content-builder .btn-group, .yform-content-builder .btn-group-insert', function() {
                    var $menu = $(this).find('.dropdown-menu').first();
                    if ($menu.length === 0 || typeof $.fn.tooltip !== 'function') {
                        return;
                    }

                    // Tooltips nur fuer Eintraege mit Beschreibung aktivieren.
                    $menu.find('[data-toggle="tooltip"]').tooltip({
                        trigger: 'hover',
                        placement: 'right',
                        container: 'body',
                        delay: {
                            show: 700,
                            hide: 120
                        }
                    });
                });
            
            // Element-Suche im Dropdown
            this.initElementSearch();
        },
        
        initElementSearch: function() {
            $(document).on('input keyup', '.yform-cb-element-search-input', function(e) {
                var $searchInput = $(this);
                var searchText = $searchInput.val().trim().toLowerCase();
                var $menu = $searchInput.closest('.dropdown-menu');
                
                if ($menu.length === 0) {
                    return;
                }
                
                var $elementLinks = $menu.find('.btn-add-slice, .btn-insert-slice');
                var $headers = $menu.find('.dropdown-header');
                var $dividers = $menu.find('[role="separator"]').not('.paste-slice-item');
                
                if (searchText === '') {
                    // Alle Items zeigen
                    $elementLinks.closest('li').show();
                    $headers.closest('li').show();
                    $dividers.closest('li').show();
                } else {
                    // Items filtern
                    $elementLinks.each(function() {
                        var $link = $(this);
                        var linkText = $link.text().toLowerCase();
                        var $li = $link.closest('li');
                        
                        if (linkText.indexOf(searchText) !== -1) {
                            $li.show();
                        } else {
                            $li.hide();
                        }
                    });
                    
                    // Headers basierend auf sichtbaren Items anzeigen/verstecken
                    $headers.each(function() {
                        var $header = $(this);
                        var $headerLi = $header.closest('li');
                        var $nextLis = $headerLi.nextUntil('[role="separator"]');
                        var visibleCount = 0;
                        
                        $nextLis.each(function() {
                            if ($(this).find('.btn-add-slice, .btn-insert-slice').length > 0 && $(this).is(':visible')) {
                                visibleCount++;
                            }
                        });
                        
                        if (visibleCount > 0) {
                            $headerLi.show();
                        } else {
                            $headerLi.hide();
                        }
                    });
                    
                    // Dividers anzeigen, wenn danach sichtbare Items kommen
                    $dividers.each(function() {
                        var $divider = $(this);
                        var $dividerLi = $divider.closest('li');
                        var $nextLis = $dividerLi.nextUntil('[role="separator"]');
                        var visibleAfter = false;
                        
                        $nextLis.each(function() {
                            if ($(this).is(':visible')) {
                                visibleAfter = true;
                                return false;
                            }
                        });
                        
                        if (visibleAfter) {
                            // Check if there's something visible before
                            var $prevLis = $dividerLi.prevUntil('[role="separator"]');
                            var visibleBefore = false;
                            
                            $prevLis.each(function() {
                                if ($(this).is(':visible')) {
                                    visibleBefore = true;
                                    return false;
                                }
                            });
                            
                            if (visibleBefore) {
                                $dividerLi.show();
                            } else {
                                $dividerLi.hide();
                            }
                        } else {
                            $dividerLi.hide();
                        }
                    });
                }
            });
        },
        
        /**
         * FIX: TinyMCE in Bootstrap-Modals erlauben
         * Bootstrap blockiert Focus-Events in Modals, aber TinyMCE braucht diese
         */
        fixTinyMCEInModals: function() {
            // Prevent Bootstrap modal from blocking TinyMCE focus events
            $(document).on('focusin', function(e) {
                if ($(e.target).closest('.tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root').length) {
                    e.stopImmediatePropagation();
                }
            });
        },

        initModalStacking: function() {
            // Bootstrap 3 nested modals: keep z-index/backdrop/body-state stable
            // and prevent the parent columns modal from being closed by child modal events.
            if (window.__yfcbModalStackingInitialized) {
                return;
            }
            window.__yfcbModalStackingInitialized = true;

            $(document).on('show.bs.modal', '.modal', function() {
                var $modal = $(this);

                // Defensive reset: dropdown interactions may leave elevated z-index
                // on slices/toolbars, which must never overlay modals.
                $('.content-builder-slice').css('z-index', '');
                $('.content-builder-slice > .slice-toolbar').css('z-index', '');

                // Repeater/Settings child modals are often rendered inside the
                // currently open nested editor modal. Move them to body so
                // Bootstrap's modal stacking works reliably.
                if (!$modal.parent().is('body')) {
                    $modal.appendTo('body');
                }

                var zIndex = 1040 + (10 * $('body > .modal.in').length);
                $modal.css('z-index', zIndex);

                setTimeout(function() {
                    $('.modal-backdrop').not('.yfcb-modal-stack').first()
                        .css('z-index', zIndex - 1)
                        .addClass('yfcb-modal-stack');
                }, 0);
            });

            $(document).on('hidden.bs.modal', 'body > .modal', function() {
                // Bootstrap removes modal-open on every hidden modal.
                // Re-add it while at least one modal is still visible.
                if ($('body > .modal.in').length > 0) {
                    $('body').addClass('modal-open');
                }
            });

            $(document).on('hide.bs.modal', '#nested-slice-edit-modal', function(e) {
                var hasOpenChildModal = $('body > .modal.in').not('#nested-slice-edit-modal').length > 0;
                if (hasOpenChildModal) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });
        },

        bindEvents: function() {
            var self = this;

            $(window)
                .off('rex:cke5IsInit.yfcbLegacy')
                .on('rex:cke5IsInit.yfcbLegacy', function(_event, editor, initializedEditorId) {
                    var $textarea = $('#' + initializedEditorId);
                    if ($textarea.length === 0 || !$textarea.hasClass('yform-cb-legacy-editor')) {
                        return;
                    }

                    $textarea.data('ycb-cke5-editor', editor);
                    editor.model.document.on('change:data', function() {
                        $textarea.val(editor.getData());
                        self.syncLegacyHiddenField($textarea);
                    });

                    $textarea.val(editor.getData());
                    self.syncLegacyHiddenField($textarea);
                });

            $(document).on('input change', '.yform-cb-legacy-editor', function() {
                self.syncLegacyHiddenField($(this));
            });

            $(document).on('click', '.yform-cb-legacy-migrate', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.submitLegacyMigrationForm($(this));
                return false;
            });

            $(document).on('submit', 'form', function() {
                var $form = $(this);
                $form.find('.yform-content-builder').each(function() {
                    ContentBuilder.setPersistState($(this), 'saving');
                });

                $(this).find('.yform-cb-legacy-editor').each(function() {
                    self.getLegacyEditorHtml($(this));
                    self.syncLegacyHiddenField($(this));
                });
            });

            $(document).on(
                'change',
                '.slice-edit-form :input',
                function() {
                    var $editForm = $(this).closest('.slice-edit-form');
                    self.applyConditionalFieldVisibility($editForm);
                }
            );

            // Bootstrap kann Modals in den body verschieben.
            // Dann liegt das Feld nicht mehr in .slice-edit-form und braucht
            // einen eigenen Conditional-Update-Handler.
            $(document).on(
                'change',
                'body > .modal :input',
                function() {
                    var $modal = $(this).closest('.modal');
                    if ($modal.length > 0) {
                        self.applyConditionalFieldVisibility($modal);
                    }
                }
            );

            $(document).on('shown.bs.modal', 'body > .modal', function() {
                var $modal = $(this);
                self.applyConditionalFieldVisibility($modal);
                self.ensureEditorsReady($modal);
            });

            $(document).on('shown.bs.tab', '.slice-edit-form a[data-toggle="tab"], #nested-slice-edit-modal a[data-toggle="tab"], .yform-content-builder a[data-toggle="tab"]', function() {
                var $tab = $(this);
                var $editForm = $tab.closest('.slice-edit-form');
                if ($editForm.length > 0) {
                    self.applyConditionalFieldVisibility($editForm);
                    self.ensureEditorsReady($editForm, $tab);
                    return;
                }

                var $modal = $tab.closest('.modal');
                if ($modal.length > 0) {
                    self.applyConditionalFieldVisibility($modal);
                    self.ensureEditorsReady($modal, $tab);
                    return;
                }

                self.ensureEditorsReady($tab.closest('.yform-content-builder'), $tab);
            });

            // Robust tab switching click fallback for dynamically loaded Bootstrap tabs
            $(document).on('click', '.slice-edit-form a[data-toggle="tab"], #nested-slice-edit-modal a[data-toggle="tab"], .yform-content-builder a[data-toggle="tab"]', function(e) {
                e.preventDefault();
                $(this).tab('show');
            });

            // Slice löschen - MUSS VOR edit kommen!
            $(document).on('click', '.btn-slice-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                self.deleteSlice($slice);
                return false;
            });
            
            // Move Up Button
            $(document).on('click', '.btn-slice-move-up', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                self.moveSliceUp($slice);
                return false;
            });
            
            // Move Down Button
            $(document).on('click', '.btn-slice-move-down', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                self.moveSliceDown($slice);
                return false;
            });
            
            // Slice online/offline umschalten
            $(document).on('click', '.btn-slice-toggle-online', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                self.toggleSliceOnline($slice);
                return false;
            });

            // Slice kopieren
            $(document).on('click', '.btn-slice-copy', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                var sliceType = $slice.data('slice-type');
                var sliceData = self.getSliceData($slice);
                var sliceToCopy = {
                    type: sliceType,
                    data: sliceData
                };
                
                try {
                    localStorage.setItem('yform_cb_copied_slice', JSON.stringify(sliceToCopy));
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(JSON.stringify(sliceToCopy));
                    }
                    alert('Element kopiert. Sie können es nun über die Plus-Menüs einfügen.');
                    $('.paste-slice-item').show();
                } catch (err) {
                    console.error('Kopieren fehlgeschlagen:', err);
                }
                return false;
            });

            // Kopiertes Slice einfügen
            $(document).on('click', '.btn-paste-slice', function(e) {
                e.preventDefault();
                var insertAfterVal = $(this).attr('data-insert-after');
                var $container = $(this).closest('.content-builder-slice').parent();
                if ($container.length === 0) {
                    $container = $(this).closest('.yform-content-builder').find('.content-builder-slices');
                }
                
                var copiedSliceRaw = localStorage.getItem('yform_cb_copied_slice');
                if (!copiedSliceRaw) {
                    alert('Kein kopiertes Element vorhanden.');
                    return false;
                }
                
                var copiedSlice = null;
                try {
                    copiedSlice = JSON.parse(copiedSliceRaw);
                } catch (err) {
                    alert('Fehler beim Lesen des kopierten Elements.');
                    return false;
                }
                
                if (copiedSlice && copiedSlice.type) {
                    var position;
                    if (insertAfterVal === undefined || insertAfterVal === 'end' || insertAfterVal === '') {
                        position = $container.children('.content-builder-slice').length;
                    } else {
                        var insertAfter = parseInt(insertAfterVal);
                        if (insertAfter === -1) {
                            position = $container.children('.content-builder-slice').length;
                        } else {
                            position = insertAfter + 1;
                        }
                    }
                    
                    self.insertCopiedSliceAt($container, copiedSlice, position);
                }
                return false;
            });

            // Old Move Button (deprecated)
            $(document).on('click', '.btn-slice-move', function(e) {
                e.stopPropagation();
            });

            // Enter in Slice-Form-Inputs: aktuelles Slice speichern statt
            // das aeussere YForm zu submitten (das wuerde alle ungespeicherten
            // Aenderungen verwerfen). Textareas und Buttons sind ausgenommen.
            $(document).on('keydown', '.slice-edit-form input, .slice-edit-form select', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    // In type="search" o.ae. Suchfeldern (z.B. selectpicker live-search)
                    // nicht speichern, sondern nur verhindern.
                    var $t = $(e.target);
                    if ($t.closest('.bootstrap-select').length || $t.is('[type="search"]')) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var $slice = $t.closest('.content-builder-slice');
                    if ($slice.length) {
                        self.saveSlice($slice);
                    }
                    return false;
                }
            });

            // Defensiv: falls das innere slice-form-Element direkt submittet wird,
            // stattdessen das Slice speichern.
            $(document).on('submit', '.slice-form', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                if ($slice.length === 0) {
                    var $modal = $(this).closest('#nested-slice-edit-modal');
                    if ($modal.length > 0) {
                        $slice = $modal.data('editing-slice');
                    }
                }
                if ($slice && $slice.length) {
                    self.saveSlice($slice);
                }
                return false;
            });

            // Vor dem eigentlichen YForm/REDAXO-Submit alle offenen Slice-Editoren
            // in data-slice-data + Hidden-Field synchronisieren.
            $(document).on('submit', 'form', function() {
                var $form = $(this);
                var $builders = $form.find('.yform-content-builder');

                if ($builders.length === 0) {
                    return true;
                }

                $builders.each(function() {
                    var $builder = $(this);

                    $builder.find('.content-builder-slice').each(function() {
                        var $slice = $(this);
                        var $editForm = $slice.find('.slice-edit-form:visible');

                        if ($editForm.length > 0) {
                            self.collectSliceDataFromForm($slice);
                        }
                    });

                    // Modales Formular synchronisieren, falls für dieses Builder-Feld geöffnet
                    var $modal = $('#nested-slice-edit-modal');
                    if ($modal.length > 0 && $modal.is(':visible')) {
                        var $editingSlice = $modal.data('editing-slice');
                        if ($editingSlice && $editingSlice.length > 0 && $editingSlice.closest('.yform-content-builder').is($builder)) {
                            self.collectSliceDataFromForm($editingSlice);
                        }
                    }
                });

                self.updateHiddenField();
                return true;
            });

            // Slice bearbeiten - Edit on Click
            $(document).on('click', '.btn-slice-edit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                self.editSlice($slice);
            });

            // Neues Slice hinzufügen (am Ende)
            $(document).on('click', '.btn-add-slice', function(e) {
                e.preventDefault();
                var elementType = $(this).data('element-type');
                var elementLabel = $(this).data('element-label');
                var $container = $(this).closest('.yform-content-builder').find('.content-builder-slices');
                self.addSlice($container, elementType, elementLabel);
            });
            
            // Neues nested Slice in Spalte hinzufügen
            $(document).on('click', '.btn-add-nested-slice', function(e) {
                e.preventDefault();
                var elementType = $(this).data('element-type');
                var elementLabel = $(this).data('element-label');
                var $container = $(this).closest('.content-builder-column').find('.content-builder-column-slices');
                self.addSlice($container, elementType, elementLabel);
            });
            
            // Slice an bestimmter Position einfügen (container-relativ)
            $(document).on('click', '.btn-insert-slice', function(e) {
                e.preventDefault();
                var elementType = $(this).data('element-type');
                var elementLabel = $(this).data('element-label');
                var insertAfter = parseInt($(this).data('insert-after'));
                var $slice = $(this).closest('.content-builder-slice');
                var $container = $slice.parent();
                self.insertSliceAt($container, elementType, elementLabel, insertAfter + 1);
            });

            // Formular speichern
            $(document).on('click', '.btn-slice-save', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                if ($slice.length === 0) {
                    var $modal = $(this).closest('#nested-slice-edit-modal');
                    if ($modal.length > 0) {
                        $slice = $modal.data('editing-slice');
                    }
                }
                if ($slice && $slice.length) {
                    self.saveSlice($slice);
                }
            });

            // Formular abbrechen
            $(document).on('click', '.btn-slice-cancel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $slice = $(this).closest('.content-builder-slice');
                if ($slice.length === 0) {
                    var $modal = $(this).closest('#nested-slice-edit-modal');
                    if ($modal.length > 0) {
                        $slice = $modal.data('editing-slice');
                    }
                }
                if ($slice && $slice.length) {
                    var confirmMsg = $(this).data('confirm');
                    if (confirmMsg && !confirm(confirmMsg)) {
                        return false;
                    }
                    self.cancelEdit($slice);
                }
            });
            
            // Media Browser Events
            $(document).on('click', '.btn-select-media', function(e) {
                e.preventDefault();
                var inputId = $(this).data('input-id');
                if (window.MediaBrowser) {
                    window.MediaBrowser.open(inputId);
                }
            });
            
            // Radio Image Selection Events
            $(document).on('change', '.radio-image-item input[type="radio"]', function() {
                var $container = $(this).closest('.radio-image-group');
                $container.find('.radio-image-item').removeClass('active');
                $(this).closest('.radio-image-item').addClass('active');
            });
            
            $(document).on('click', '.radio-image-item label', function(e) {
                var $item = $(this).closest('.radio-image-item');
                var $radio = $item.find('input[type="radio"]');
                if (!$radio.is(':checked')) {
                    $radio.prop('checked', true).trigger('change');
                }
            });
            
            // Color Swatches Selection Events
            $(document).on('change', '.color-swatch-item input[type="radio"]', function() {
                var $container = $(this).closest('.color-swatches-group');
                $container.find('.color-swatch-item').removeClass('active');
                $(this).closest('.color-swatch-item').addClass('active');
            });
            
            $(document).on('click', '.color-swatch-item label', function(e) {
                var $item = $(this).closest('.color-swatch-item');
                var $radio = $item.find('input[type="radio"]');
                if (!$radio.is(':checked')) {
                    $radio.prop('checked', true).trigger('change');
                }
            });
            
            $(document).on('click', '.btn-select-media-enhanced', function(e) {
                e.preventDefault();
                var inputId = $(this).data('input-id');
                var allowedTypes = $(this).data('allowed-types');
                if (window.MediaBrowser) {
                    window.MediaBrowser.openEnhanced(inputId, allowedTypes);
                }
            });
            
            // REDAXO Linkmap Widget Events (global für alle Kontexte: YForm + Struktur)
            $(document).on('click', '.rex-linkmap-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var linkId = $(this).data('id');
                var params = $(this).data('params') || '';
                
                if (typeof openLinkMap === 'function') {
                    // REDAXO's openLinkMap Funktion aufrufen
                    // Format: openLinkMap('REX_LINK_1', '&clang=1&category_id=1')
                    openLinkMap(linkId, params);
                } else {
                    console.error('openLinkMap function not found');
                }
                
                return false;
            });
            
            $(document).on('click', '.rex-linkmap-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                var linkId = $btn.data('id');
                var counter = $btn.data('counter');
                
                // 1. Core-Funktion aufrufen wenn möglich (für UI-Konstanz)
                if (typeof deleteREXLink === 'function' && counter) {
                    deleteREXLink(counter);
                } 
                
                // 2. Fallback/Zusatz: Manuell leeren (sicherer für AJAX/Modals)
                var $input = $('#' + linkId);
                var $nameInput = $('#' + linkId + '_NAME');
                
                $input.val('');
                $nameInput.val('');
                
                // 3. Trigger change damit Content Builder die Änderung bemerkt
                $input.trigger('change');
                
                return false;
            });
            
            // Enhanced Media Platzhalter und Preview klickbar machen
            $(document).on('click', '.media-preview-enhanced', function(e) {
                // Nur wenn nicht auf Delete-Button geklickt wurde
                if ($(e.target).closest('.btn-delete-preview').length > 0) {
                    return;
                }
                
                e.preventDefault();
                var $preview = $(this);
                var counter = $preview.data('counter');
                var types = $preview.data('types');
                
                if (counter) {
                    var typesParam = types ? '&types=' + types : '';
                    if (typeof openREXMedia === 'function') {
                        if (typesParam) {
                            openREXMedia(counter, typesParam);
                        } else {
                            openREXMedia(counter);
                        }
                    }
                }
            });
            
            // Delete Button für Enhanced Media Preview
            $(document).on('click', '.btn-delete-preview', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $preview = $(this).closest('.media-preview-enhanced');
                var inputId = $preview.data('input-id');
                var $input = $('#' + inputId);
                
                // Input leeren
                $input.val('');
                
                // Preview zurücksetzen auf Placeholder
                var types = $preview.data('types') || '';
                var allowedTypes = types ? types.split(',') : [];
                
                var placeholderText = 'Klicken Sie hier, um ein Medium auszuwählen';
                var placeholderIcon = 'fa-cloud-upload';
                
                if (allowedTypes.includes('image') && allowedTypes.includes('video')) {
                    placeholderText = 'Klicken Sie hier, um ein Bild oder Video auszuwählen';
                } else if (allowedTypes.includes('image')) {
                    placeholderText = 'Klicken Sie hier, um ein Bild auszuwählen';
                } else if (allowedTypes.includes('video')) {
                    placeholderText = 'Klicken Sie hier, um ein Video auszuwählen';
                }
                
                $preview.html(
                    '<div class="media-placeholder">' +
                        '<i class="fa ' + placeholderIcon + '"></i>' +
                        '<p>' + placeholderText + '</p>' +
                    '</div>'
                );
            });
            
            // Watch for media input changes and update enhanced preview
            $(document).on('change input', 'input[id^="REX_MEDIA_"]', function() {
                var $input = $(this);
                var inputId = $input.attr('id');
                var $preview = $('#preview_' + inputId);
                
                // Nur Enhanced Preview aktualisieren
                if ($preview.length && $preview.hasClass('media-preview-enhanced')) {
                    self.updateEnhancedMediaPreview($input, $preview);
                }
                
                // Trigger serialization für Module
                var $repeaterContainer = $input.closest('.repeater-container');
                if ($repeaterContainer.length > 0) {
                    // Verzögert, damit REDAXO's Logik fertig ist
                    setTimeout(function() {
                        self.serializeModuleData($repeaterContainer);
                    }, 500);
                }
            });
            
            // Watch for REX_LINK input changes (hidden fields)
            $(document).on('change', 'input[id^="REX_LINK_"]', function() {
                var $input = $(this);
                
                // Prüfe ob wir in einem Repeater sind
                var $repeaterContainer = $input.closest('.repeater-container');
                if ($repeaterContainer.length > 0) {
                    // Verzögert serialisieren, damit REDAXO's Linkmap-Callback fertig ist
                    setTimeout(function() {
                        self.serializeModuleData($repeaterContainer);
                    }, 500);
                }
            });
            
            // Polling für Enhanced Media Widgets (da openREXMedia nicht immer change-Event feuert)
            setInterval(function() {
                $('.media-enhanced-clickable').each(function() {
                    var $container = $(this);
                    var inputId = $container.data('input-id');
                    
                    if (inputId) {
                        var $input = $('#' + inputId);
                        
                        if ($input.length) {
                            var currentValue = $input.val();
                            var lastValue = $input.data('last-value') || '';
                            
                            if (currentValue !== lastValue) {
                                $input.data('last-value', currentValue);
                                self.updateEnhancedMediaWidget($container, $input);
                            }
                        }
                    }
                });
            }, 500);
            
            // Content Builder Media - Eigene Preview-Logik
            $(document).on('change', '.content-builder-media-input', function() {
                var $input = $(this);
                var inputId = $input.attr('id');
                var $preview = $('.content-builder-media-preview[data-input-id="' + inputId + '"]');
                var mediaFile = $input.val();
                
                if (mediaFile) {
                    // Update Preview
                    self.updateContentBuilderMediaPreview($preview, mediaFile);
                } else {
                    // Clear Preview
                    $preview.empty();
                }
            });
            
            // Delete Button für Content Builder Media
            $(document).on('click', '.btn-delete-cb-media', function(e) {
                e.preventDefault();
                var inputId = $(this).data('input-id');
                var $input = $('#' + inputId);
                var $preview = $('.content-builder-media-preview[data-input-id="' + inputId + '"]');
                
                $input.val('');
                $preview.empty();
                
                // Trigger change event
                $input.trigger('change');
            });
            
            // Video Controls
            $(document).on('click', '.btn-video-play', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $video = $(this).closest('.media-item-video').find('video')[0];
                var $button = $(this);
                
                if ($video.paused) {
                    $video.play();
                    $button.html('<i class="fa fa-pause"></i>');
                    $button.attr('title', 'Pausieren');
                } else {
                    $video.pause();
                    $button.html('<i class="fa fa-play"></i>');
                    $button.attr('title', 'Abspielen');
                }
            });
            
            $(document).on('click', '.btn-video-mute', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $video = $(this).closest('.media-item-video').find('video')[0];
                var $button = $(this);
                
                if ($video.muted) {
                    $video.muted = false;
                    $button.html('<i class="fa fa-volume-up"></i>');
                    $button.attr('title', 'Stumm schalten');
                } else {
                    $video.muted = true;
                    $button.html('<i class="fa fa-volume-off"></i>');
                    $button.attr('title', 'Ton an');
                }
            });
            
            $(document).on('click', '.btn-video-fullscreen', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $video = $(this).closest('.media-item-video').find('video')[0];
                
                if ($video.requestFullscreen) {
                    $video.requestFullscreen();
                } else if ($video.webkitRequestFullscreen) {
                    $video.webkitRequestFullscreen();
                } else if ($video.mozRequestFullScreen) {
                    $video.mozRequestFullScreen();
                } else if ($video.msRequestFullscreen) {
                    $video.msRequestFullscreen();
                }
            });
            
            // Video Click zum Play/Pause
            $(document).on('click', '.media-item-video video', function(e) {
                e.preventDefault();
                var $video = this;
                var $playButton = $(this).closest('.media-item-video').find('.btn-video-play');
                
                if ($video.paused) {
                    $video.play();
                    $playButton.html('<i class="fa fa-pause"></i>');
                    $playButton.attr('title', 'Pausieren');
                } else {
                    $video.pause();
                    $playButton.html('<i class="fa fa-play"></i>');
                    $playButton.attr('title', 'Abspielen');
                }
            });
            
            // Video Overlay Click
            $(document).on('click', '.media-item-video .media-overlay', function(e) {
                e.preventDefault();
                var $video = $(this).closest('.media-item-video').find('video')[0];
                var $playButton = $(this).closest('.media-item-video').find('.btn-video-play');
                
                if ($video.paused) {
                    $video.play();
                    $playButton.html('<i class="fa fa-pause"></i>');
                    $playButton.attr('title', 'Pausieren');
                } else {
                    $video.pause();
                    $playButton.html('<i class="fa fa-play"></i>');
                    $playButton.attr('title', 'Abspielen');
                }
            });
            
            // Video Loading Events
            $(document).on('loadedmetadata', '.media-item-video video', function() {
                $(this).closest('.media-item-video').find('.video-fallback').hide();
            });
            
            $(document).on('error', '.media-item-video video', function() {
                $(this).hide();
                $(this).closest('.media-item-video').find('.video-fallback').show();
            });
            
            // Video Hover Events für Controls
            $(document).on('mouseenter', '.media-item-video', function() {
                $(this).find('.media-controls').fadeIn(200);
            });
            
            $(document).on('mouseleave', '.media-item-video', function() {
                $(this).find('.media-controls').fadeOut(200);
            });
            
            $(document).on('click', '.btn-delete-media', function(e) {
                e.preventDefault();
                var inputId = $(this).data('input-id');
                $('#' + inputId).val('');
                $('#preview_' + inputId).hide().empty();
                
                // Enhanced preview zurücksetzen
                var $enhancedPreview = $('#preview_' + inputId);
                if ($enhancedPreview.hasClass('media-preview-enhanced')) {
                    var $input = $('#' + inputId);
                    var allowedTypes = $input.data('allowed-types');
                    if (allowedTypes) {
                        var allowedTypesArray = allowedTypes.split(',');
                        self.resetEnhancedPreview($enhancedPreview, allowedTypesArray);
                    }
                }
            });

            // Repeater: Item hinzufügen
            $(document).on('click', '.btn-add-repeater', function(e) {
                e.preventDefault();
                var $container = $(this).siblings('.repeater-container');
                self.addRepeaterItem($container);
            });

            // Repeater: Item entfernen
            $(document).on('click', '.btn-remove-repeater', function(e) {
                e.preventDefault();
                var $item = $(this).closest('.repeater-item');
                var $container = $item.closest('.repeater-container');
                
                // TinyMCE-Instanzen im Item entfernen
                self.destroyTinyMCEInContainer($item);
                
                $item.fadeOut(200, function() {
                    $item.remove();
                    // Update indices after removal
                    self.updateRepeaterIndices($container);
                    // Trigger custom event for module context to update form data
                    $container.trigger('repeater:item-removed');
                });
            });
        },

        /**
         * Aktualisiert die in-section Klassen basierend auf Section-Elementen
         */
        updateSectionClasses: function() {
            var $slices = $('.content-builder-slices');
            
            $slices.each(function() {
                var $container = $(this);
                var $allSlices = $container.find('.content-builder-slice');
                var inSection = false;
                
                // Erst alle in-section Klassen entfernen
                $allSlices.removeClass('in-section');
                
                // Dann neu zuweisen
                $allSlices.each(function() {
                    var $slice = $(this);
                    var isSection = $slice.hasClass('is-section');
                    
                    if (isSection) {
                        // Section-Element: Neue Section beginnt
                        inSection = true;
                    } else {
                        // Normales Element: Wenn wir in einer Section sind, Klasse setzen
                        if (inSection) {
                            $slice.addClass('in-section');
                        }
                    }
                });
            });
        },
        
        /**
         * Entfernt TinyMCE-Instanzen in einem Container
         */
        destroyTinyMCEInContainer: function($container) {
            if (typeof tinymce === 'undefined') {
                return;
            }
            
            $container.find('textarea.tiny-editor').each(function() {
                var textareaId = $(this).attr('id');
                if (textareaId && tinymce.get(textareaId)) {
                    try {
                        tinymce.get(textareaId).remove();
                    } catch(e) {
                        // Silent fail if editor already removed
                    }
                }
            });
        },

        initSortable: function() {
            var self = this;
            var slicesContainer = document.querySelector('.content-builder-slices');
            
            if (slicesContainer && typeof Sortable !== 'undefined') {
                new Sortable(slicesContainer, {
                    handle: '.btn-slice-move',
                    animation: 150,
                    ghostClass: 'slice-ghost',
                    onEnd: function(evt) {
                        self.updateIndices();
                        self.updateHiddenField();
                        self.updateSectionClasses(); // Nach Sortierung aktualisieren
                    }
                });
            }
        },

        editSlice: function($slice) {
            var self = this;
            var isNested = $slice.closest('.content-builder-column-slices').length > 0;
            
            if (isNested) {
                var $modal = $('#nested-slice-edit-modal');
                if ($modal.length === 0) {
                    $modal = $('<div class="modal fade" id="nested-slice-edit-modal" style="z-index: 1050;" tabindex="-1" role="dialog">' +
                        '<div class="modal-dialog modal-lg" role="document">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
                                    '<h4 class="modal-title">Element bearbeiten</h4>' +
                                '</div>' +
                                '<div class="modal-body"></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>');
                    $('body').append($modal);
                    
                    // Bind close event once
                    $modal.on('hidden.bs.modal', function (e) {
                        // Child modals inside the nested editor bubble hidden.bs.modal.
                        // Only react when the nested editor modal itself is closed.
                        if (e.target !== this) {
                            return;
                        }

                        var $form = $modal.find('.slice-edit-form');
                        if ($form.length > 0) {
                            var $editingSlice = $modal.data('editing-slice');
                            if ($editingSlice && $editingSlice.length > 0) {
                                self.cancelEdit($editingSlice);
                            }
                        }
                    });
                }
                
                $modal.data('editing-slice', $slice);
                var $editForm = $slice.children('.slice-edit-form');
                $modal.find('.modal-body').empty().append($editForm);
                $editForm.show();
                
                if ($editForm.children().length === 0) {
                    this.loadSliceForm($slice);
                } else {
                    this.applyConditionalFieldVisibility($editForm);
                    setTimeout(function() {
                        if (typeof tiny_init === 'function') {
                            try {
                                tiny_init($editForm);
                            } catch(e) {
                                console.error('tiny_init failed:', e);
                            }
                        }
                    }, 50);
                }
                
                $modal.modal('show');
            } else {
                // Gerenderte Ansicht ausblenden
                $slice.find('.slice-rendered').hide();
                $slice.find('.slice-toolbar').hide();
                
                // Edit-Form anzeigen
                var $editForm = $slice.children('.slice-edit-form');
                
                if ($editForm.children().length === 0) {
                    // Formular erstmal laden
                    this.loadSliceForm($slice);
                } else {
                    // Formular ist bereits geladen - TinyMCE neu initialisieren
                    this.applyConditionalFieldVisibility($editForm);
                    $editForm.show();
                    
                    setTimeout(function() {
                        if (typeof tiny_init === 'function') {
                            try {
                                tiny_init($editForm);
                            } catch(e) {
                                console.error('tiny_init failed:', e);
                            }
                        }
                    }, 50);
                }
                
                $editForm.show();
            }
        },

        loadSliceForm: function($slice) {
            var self = this;
            var sliceType = $slice.data('slice-type');
            var sliceData = this.getSliceData($slice);
            var isNested = $slice.closest('.content-builder-column-slices').length > 0;
            var $editForm = isNested ? $('#nested-slice-edit-modal .modal-body > .slice-edit-form') : $slice.children('.slice-edit-form');
            
            
            // YForm-Formular per AJAX laden
            $.ajax({
                url: ContentBuilder.getAjaxUrl(),
                method: 'POST',
                data: {
                    action: 'load_slice_form',
                    slice_type: sliceType,
                    slice_data: sliceData
                },
                dataType: 'html',
                success: function(response) {
                    // Script-Tags aus Response entfernen, um doppelte Variablen zu vermeiden
                    // (CKEditor, MBlock etc. sind bereits auf der Seite geladen)
                    var $response = $('<div>').html(response);
                    $response.find('script').remove();
                    var cleanedHtml = $response.html();
                    
                    $editForm.html(cleanedHtml);

                    $editForm.find('input[id^="REX_MEDIA_"]').each(function() {
                        var $input = $(this);
                        var oldId = $input.attr('id') || '';

                        if (oldId === '') {
                            return;
                        }

                        if (typeof window.rexMediaCounter === 'undefined') {
                            var maxCounter = 0;
                            $('input[id^="REX_MEDIA_"]').each(function() {
                                var match = $(this).attr('id').match(/REX_MEDIA_(\d+)/);
                                if (match) {
                                    maxCounter = Math.max(maxCounter, parseInt(match[1], 10));
                                }
                            });
                            window.rexMediaCounter = maxCounter;
                        }

                        window.rexMediaCounter++;
                        var newId = 'REX_MEDIA_' + window.rexMediaCounter;
                        var oldCounter = oldId.replace('REX_MEDIA_', '');

                        $input.attr('id', newId);
                        $input.closest('.rex-js-widget-media').find('[data-input-id="' + oldId + '"]').attr('data-input-id', newId);
                        $input.closest('.rex-js-widget-media').find('[data-target="#' + oldId + '"]').attr('data-target', '#' + newId);

                        var $widget = $input.closest('.rex-js-widget-media');
                        $widget.find('a[onclick*="openREXMedia"]').each(function() {
                            var $link = $(this);
                            var oldOnclick = $link.attr('onclick');
                            if (oldOnclick) {
                                $link.attr('onclick', oldOnclick.replace(
                                    'openREXMedia(' + oldCounter,
                                    'openREXMedia(' + window.rexMediaCounter
                                ));
                            }
                        });
                        $widget.find('a[onclick*="viewREXMedia"]').each(function() {
                            var $link = $(this);
                            var oldOnclick = $link.attr('onclick');
                            if (oldOnclick) {
                                $link.attr('onclick', oldOnclick.replace(
                                    'viewREXMedia(' + oldCounter,
                                    'viewREXMedia(' + window.rexMediaCounter
                                ));
                            }
                        });
                        $widget.find('a.btn-delete-cb-media').attr('data-input-id', newId);

                        var $preview = $widget.find('.content-builder-media-preview');
                        if ($preview.length) {
                            $preview.attr('data-input-id', newId);
                            $preview.attr('id', 'preview_' + newId);
                        }
                    });
                    
                    // Bootstrap Selectpicker initialisieren (für AJAX-geladene Inhalte)
                    // sanitize: false damit SVG/img src nicht entfernt wird
                    $editForm.find('.selectpicker').selectpicker({
                        sanitize: false
                    });

                    self.applyConditionalFieldVisibility($editForm);

                    // REX Linkmap-Buttons mit Event-Delegation initialisieren
                    $editForm.on('click', '.rex-linkmap-btn', function(e) {
                        e.preventDefault();
                        var $btn = $(this);
                        var inputId = $btn.data('id');
                        var params = $btn.data('params') || '';
                        
                        if (typeof openLinkMap === 'function') {
                            openLinkMap(inputId, params);
                        }
                        return false;
                    });
                    
                    // CKEditor 5 initialisieren - EINFACHER ANSATZ
                    setTimeout(function() {
                        $editForm.find('textarea.cke5-editor').each(function() {
                            var $textarea = $(this);
                            
                            if (typeof cke5_init === 'function') {
                                try {
                                    cke5_init($textarea);
                                } catch(e) {
                                    // CKE5 initialization failed
                                }
                            }
                        });
                        
                        // TinyMCE initialisieren
                        if (typeof tiny_init === 'function') {
                            try {
                                tiny_init($editForm);
                                
                                // Fix: Ensure TinyMCE editor containers are visible
                                setTimeout(function() {
                                    // First pass: show all visible TinyMCE containers
                                    $editForm.find('textarea.tiny-editor').each(function() {
                                        var $ta = $(this);
                                        var $editorContainer = $ta.siblings('.tox-tinymce');
                                        
                                        if ($editorContainer.length > 0 && !$editorContainer.is(':visible')) {
                                            $editorContainer.css({
                                                'display': 'block',
                                                'visibility': 'visible',
                                                'opacity': '1'
                                            });
                                        }
                                    });
                                    
                                    // Re-init TinyMCE for all existing repeater items
                                    // (when editing a saved slice with multiple repeater items)
                                    $editForm.find('.repeater-item:not(.repeater-item-template)').each(function() {
                                        var $item = $(this);
                                        // Only re-init if this item has textareas without initialized TinyMCE
                                        var $uninitializedTextareas = $item.find('textarea.tiny-editor').filter(function() {
                                            return !$(this).hasClass('mce-initialized');
                                        });
                                        
                                        if ($uninitializedTextareas.length > 0) {
                                            tiny_init($item);
                                        }
                                    });
                                    
                                    // Second pass: Force visibility for ALL TinyMCE containers after all inits
                                    setTimeout(function() {
                                        $editForm.find('textarea.tiny-editor').each(function() {
                                            var $ta = $(this);
                                            var $editorContainer = $ta.siblings('.tox-tinymce');
                                            
                                            if ($editorContainer.length > 0 && !$editorContainer.is(':visible')) {
                                                $editorContainer.show();
                                            }
                                        });
                                    }, 300);
                                }, 100);
                            } catch(e) {
                                console.error('TinyMCE initialization failed:', e);
                            }
                        }
                        
                        // Initialize move button states for repeater items
                        self.updateMoveButtonStates();
                    }, 300);
                }
            });
        },

        resolveTabPane: function($scope, $tab) {
            if (!$tab || $tab.length === 0) {
                return $();
            }

            var target = $tab.attr('data-target') || $tab.attr('href') || '';
            if (target === '' || target.charAt(0) !== '#') {
                return $();
            }

            var $pane = $();
            if ($scope && $scope.length > 0) {
                $pane = $scope.find(target).first();
            }

            if ($pane.length === 0) {
                $pane = $(target).first();
            }

            return $pane;
        },

        ensureEditorsReady: function($scope, $tab) {
            var self = this;
            var $context = ($scope && $scope.length > 0) ? $scope : $(document);
            var $editorScope = $context;
            var $tabPane = this.resolveTabPane($context, $tab);

            if ($tabPane.length > 0) {
                $editorScope = $tabPane;
            }

            setTimeout(function() {
                var needsTinyInit = false;

                $editorScope.find('textarea.tiny-editor:visible').each(function() {
                    var $textarea = $(this);
                    var textareaId = ($textarea.attr('id') || '').toString();
                    var hasEditor = false;

                    if (textareaId !== '' && typeof tinymce !== 'undefined' && tinymce.get(textareaId)) {
                        hasEditor = true;
                    }

                    var $tinyContainer = $textarea.siblings('.tox-tinymce');
                    if ($tinyContainer.length > 0) {
                        hasEditor = true;
                        $tinyContainer.css({
                            display: 'block',
                            visibility: 'visible',
                            opacity: '1'
                        });
                    }

                    if (!hasEditor) {
                        needsTinyInit = true;
                    }
                });

                if (needsTinyInit && typeof tiny_init === 'function') {
                    try {
                        tiny_init($editorScope);
                    } catch (e) {
                        console.error('tiny_init after tab switch failed:', e);
                    }
                }

                $editorScope.find('textarea.cke5-editor:visible').each(function() {
                    var $textarea = $(this);
                    var textareaId = ($textarea.attr('id') || '').toString();
                    var editor = null;

                    if (typeof ckeditors !== 'undefined' && textareaId !== '' && ckeditors[textareaId]) {
                        editor = ckeditors[textareaId];
                    }

                    if (!editor && typeof cke5_init === 'function') {
                        try {
                            cke5_init($textarea);
                        } catch (e) {
                            console.error('cke5_init after tab switch failed:', e);
                        }
                    } else if (editor && editor.ui && typeof editor.ui.update === 'function') {
                        try {
                            editor.ui.update();
                        } catch (e) {
                            // no-op: some editor builds don't expose ui.update reliably
                        }
                    }

                    var $ckeContainer = $textarea.siblings('.ck-editor');
                    if ($ckeContainer.length > 0) {
                        $ckeContainer.css({
                            display: 'block',
                            visibility: 'visible',
                            opacity: '1'
                        });
                    }
                });

                setTimeout(function() {
                    $editorScope.find('textarea.tiny-editor').each(function() {
                        var $tinyContainer = $(this).siblings('.tox-tinymce');
                        if ($tinyContainer.length > 0 && !$tinyContainer.is(':visible')) {
                            $tinyContainer.show();
                        }
                    });
                }, 120);

                if (typeof self.applyConditionalFieldVisibility === 'function') {
                    self.applyConditionalFieldVisibility($context);
                }

                try {
                    $(window).trigger('resize');
                } catch (e) {
                    // ignore
                }
            }, 40);
        },

        findConditionalSourceFields: function($scope, fieldName) {
            var source = String(fieldName || '').trim();
            if (!source.length) {
                return $();
            }

            return $scope.find(':input').filter(function() {
                var name = this.name || '';
                var id = this.id || '';

                if (source.charAt(0) === '#') {
                    return ('#' + id) === source;
                }

                if (name === source || id === source) {
                    return true;
                }

                return name.endsWith('[' + source + ']') || name.endsWith('[' + source + '][]');
            });
        },

        getConditionalSourceValue: function($scope, fieldName) {
            var $inputs = this.findConditionalSourceFields($scope, fieldName);
            if ($inputs.length === 0) {
                return null;
            }

            var $first = $inputs.first();

            if ($first.is(':checkbox')) {
                var anyChecked = $inputs.filter(':checkbox:checked').length > 0;
                return anyChecked ? '1' : '0';
            }

            if ($first.is(':radio')) {
                var $checked = $inputs.filter(':checked').first();
                return $checked.length > 0 ? String($checked.val()) : '';
            }

            if ($first.is('select') && $first.prop('multiple')) {
                var selectedValues = $first.val();
                if (Array.isArray(selectedValues)) {
                    return selectedValues.map(function(item) {
                        return String(item);
                    });
                }
                return [];
            }

            return String($first.val() || '');
        },

        updateConditionalTabs: function($scope) {
            // Tab-Sichtbarkeit nicht automatisch steuern, da dies in
            // verschachtelten/async Formularen zu falschem Ausblenden fuehren kann.
            // Conditionals werden weiterhin auf Feldebene ausgewertet.
            void $scope;
        },

        matchesConditionalRule: function(actualValue, expectedValue) {
            if (Array.isArray(expectedValue)) {
                if (Array.isArray(actualValue)) {
                    return expectedValue.some(function(expectedItem) {
                        return actualValue.indexOf(String(expectedItem)) !== -1;
                    });
                }

                return expectedValue.indexOf(String(actualValue)) !== -1;
            }

            return String(actualValue) === String(expectedValue);
        },

        getConditionalTargets: function($scope) {
            var $targets = $();

            if (!$scope || $scope.length === 0) {
                return $targets;
            }

            $targets = $targets.add($scope.find('.yfcb-conditional-field[data-yfcb-visible-if]'));

            // Modals können von Bootstrap außerhalb der Form in den Body verschoben werden.
            // Diese zugehörigen Felder müssen trotzdem im selben Slice-Kontext ausgewertet werden.
            $scope.find('[data-target], [data-bs-target]').each(function() {
                var modalSelector = $(this).attr('data-target') || $(this).attr('data-bs-target') || '';
                if (!modalSelector || modalSelector.charAt(0) !== '#') {
                    return;
                }

                var $modal = $(modalSelector);
                if ($modal.length > 0) {
                    $targets = $targets.add($modal.find('.yfcb-conditional-field[data-yfcb-visible-if]'));
                }
            });

            return $targets;
        },

        getConditionalFieldValue: function($conditionalField, $scope, sourceFieldName) {
            var localSelector = '.repeater-item, .modal, .slice-edit-form';
            var $localRoot = $conditionalField.closest(localSelector).first();
            var actualValue = null;

            if ($localRoot.length > 0) {
                actualValue = this.getConditionalSourceValue($localRoot, sourceFieldName);
            }

            if (actualValue === null) {
                actualValue = this.getConditionalSourceValue($scope, sourceFieldName);
            }

            return actualValue;
        },

        applyConditionalFieldVisibility: function($scope) {
            var self = this;

            if (!$scope || $scope.length === 0) {
                return;
            }

            self.getConditionalTargets($scope).each(function() {
                var $conditionalField = $(this);
                var rawCondition = $conditionalField.attr('data-yfcb-visible-if');

                if (!rawCondition) {
                    return;
                }

                var conditions;
                try {
                    conditions = JSON.parse(rawCondition);
                } catch (parseError) {
                    return;
                }

                if (!conditions || typeof conditions !== 'object') {
                    return;
                }

                var visible = true;

                Object.keys(conditions).forEach(function(sourceFieldName) {
                    if (!visible) {
                        return;
                    }

                    var expectedValue = conditions[sourceFieldName];
                    var actualValue = self.getConditionalFieldValue($conditionalField, $scope, sourceFieldName);

                    if (actualValue === null || !self.matchesConditionalRule(actualValue, expectedValue)) {
                        visible = false;
                    }
                });

                $conditionalField.toggle(visible);
            });

            this.updateConditionalTabs($scope);
        },

        saveSlice: function($slice) {
            var self = this;
            var sliceData = this.collectSliceDataFromForm($slice);
            
            // Slice neu rendern
            this.renderSlice($slice, sliceData);
            
            // Zur Ansicht zurück
            this.cancelEdit($slice);
            
            // Hidden Field updaten
            this.updateHiddenField();
            this.markPersistDirty($slice);
            
            // Section-Klassen aktualisieren (falls Section gespeichert wurde)
            this.updateSectionClasses();
            
            // Zur gespeicherten Slice scrollen und Glow-Effekt
            this.scrollToSlice($slice);
            this.glowEffect($slice);
        },

        collectSliceDataFromForm: function($slice) {
            var self = this;
            var isNested = $slice.closest('.content-builder-column-slices').length > 0;
            var $editForm = isNested ? $('#nested-slice-edit-modal .modal-body > .slice-edit-form') : $slice.children('.slice-edit-form');
            var sliceData = {};
            var sliceType = String($slice.data('slice-type') || '');

            // Preserve nested column-slice arrays only for the dedicated "columns" element.
            // Other elements (e.g. cards) legitimately use a scalar "columns" setting.
            var existingNestedColumns = null;
            var oldData = $slice.data('slice-data');
            if (oldData) {
                if (typeof oldData === 'string') {
                    try { oldData = JSON.parse(oldData); } catch(e) {}
                }
                if (sliceType === 'columns' && oldData && oldData.columns && typeof oldData.columns === 'object') {
                    existingNestedColumns = oldData.columns;
                }
            }

            // CKE5-Instanzen in Textareas zurückschreiben
            $editForm.find('textarea.cke5-editor').each(function() {
                var $textarea = $(this);
                var textareaId = $textarea.attr('id');

                if (typeof ckeditors !== 'undefined' && ckeditors[textareaId]) {
                    var editorData = ckeditors[textareaId].getData();
                    $textarea.val(editorData);
                }
            });

            // TinyMCE-Instanzen in Textareas zurückschreiben
            $editForm.find('textarea.tiny-editor').each(function() {
                var $textarea = $(this);
                var textareaId = $textarea.attr('id');

                if (typeof tinymce !== 'undefined' && tinymce.get(textareaId)) {
                    var editor = tinymce.get(textareaId);
                    var editorContent = editor.getContent();
                    $textarea.val(editorContent);
                }
            });

            var $allInputs = $editForm.find('input, textarea, select');

            $editForm.find('[data-toggle="modal"]').each(function() {
                var modalId = $(this).attr('data-target');
                if (modalId) {
                    var $modal = $(modalId);
                    if ($modal.length) {
                        $allInputs = $allInputs.add($modal.find('input, textarea, select'));
                    }
                }
            });

            $('body > .modal').each(function() {
                var $modal = $(this);
                var modalId = $modal.attr('id');
                if (modalId && $editForm.find('[data-target="#' + modalId + '"]').length > 0) {
                    $allInputs = $allInputs.add($modal.find('input, textarea, select'));
                }
            });

            $allInputs.each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();

                if ($field.closest('.repeater-item-template').length > 0) {
                    return;
                }

                var $modal = $field.closest('.modal');
                if ($modal.length > 0 && String($modal.attr('id') || '').indexOf('repeater_item_template_') === 0) {
                    return;
                }

                if ($field.is(':radio')) {
                    if ($field.is(':checked') && name) {
                        self.setNestedValue(sliceData, name, value);
                    }
                    return;
                }

                if ($field.is(':checkbox')) {
                    if (name) {
                        self.setNestedValue(sliceData, name, $field.is(':checked') ? (value || '1') : '0');
                    }
                    return;
                }

                if ($field.is('select') && $field.prop('multiple')) {
                    var selectedValues = $field.val();
                    if (selectedValues && selectedValues.length > 0) {
                        value = selectedValues.join(',');
                    } else {
                        value = '';
                    }
                }

                if ($field.hasClass('yform-dataset-real')) {
                    if (name) {
                        var pickerValue = $field.val();
                        self.setNestedValue(sliceData, name, pickerValue);
                    }
                } else if (name && value !== undefined) {
                    self.setNestedValue(sliceData, name, value);
                }
            });

            if (
                sliceType === 'columns' &&
                existingNestedColumns !== null &&
                (typeof sliceData.columns === 'undefined' || sliceData.columns === null)
            ) {
                sliceData.columns = existingNestedColumns;
            }

            $slice.attr('data-slice-data', JSON.stringify(sliceData));
            $slice.data('slice-data', sliceData);

            return sliceData;
        },
        
        /**
         * Glow-Effekt für visuelles Feedback
         */
        glowEffect: function($slice) {
            $slice.css({
                'box-shadow': '0 0 0 3px rgba(40, 167, 69, 0.6), 0 0 20px rgba(40, 167, 69, 0.4)',
                'transition': 'box-shadow 0.3s ease-in-out'
            });
            
            setTimeout(function() {
                $slice.css({
                    'box-shadow': '0 0 0 6px rgba(40, 167, 69, 0.8), 0 0 30px rgba(40, 167, 69, 0.5)'
                });
            }, 150);
            
            setTimeout(function() {
                $slice.css({
                    'box-shadow': ''
                });
            }, 1200);
        },
        
        /**
         * Set nested value from bracket notation (e.g. "items[0][title]" = "value")
         */
        setNestedValue: function(obj, path, value) {
            // Falls der Pfad keine Klammern hat, direkt setzen
            if (path.indexOf('[') === -1) {
                obj[path] = value;
                return;
            }

            // items[0][title] -> ['items', '0', 'title']
            var keys = path.split(/[\[\]]+/).filter(function(k) { return k !== ''; });
            
            var current = obj;
            for (var i = 0; i < keys.length - 1; i++) {
                var key = keys[i];
                var nextKey = keys[i + 1];
                
                // Wenn nächster Key eine Zahl ist, Array/Objekt vorbereiten
                if (!current[key]) {
                    // Falls der Key eine Zahl ist, nutzen wir ein Objekt (bessere Kompatibilität)
                    current[key] = (!isNaN(nextKey)) ? [] : {};
                }
                
                current = current[key];
            }
            
            current[keys[keys.length - 1]] = value;
        },

        renderSlice: function($slice, sliceData) {
            var sliceType = $slice.data('slice-type');
            var framework = $slice.closest('.yform-content-builder').data('framework') || 'bootstrap';
            
            // Section-Elemente im Backend speziell rendern
            if (sliceType === 'section') {
                var label = sliceData.label || 'Unbenannt';
                var bgColor = sliceData.background_color || '';
                var bgImage = sliceData.background_image || '';
                var customId = sliceData.custom_id || '';
                var bgThumbnailClass = 'bg-' + (bgColor || 'none');
                var bgThumbnailStyle = '';

                if (bgImage && !/^\d+$/.test(String(bgImage))) {
                    var sectionBgUrl = '/media/' + encodeURIComponent(String(bgImage));
                    bgThumbnailStyle = ' style="background-image: url(' + sectionBgUrl + ');"';
                } else {
                    var colorMap = {
                        'none': 'transparent',
                        'transparent': 'transparent',
                        'light': '#f5f5f5',
                        'dark': '#333333',
                        'muted': '#f8f8f8',
                        'primary': '#1e87f0',
                        'secondary': '#222222',
                        'white': '#ffffff',
                        'uk-section-default': '#ffffff',
                        'uk-section-muted': '#f8f8f8',
                        'uk-section-primary': '#1e87f0',
                        'uk-section-secondary': '#222222',
                        'uk-background-default': '#ffffff',
                        'uk-background-muted': '#f8f8f8',
                        'uk-background-primary': '#1e87f0',
                        'uk-background-secondary': '#222222',
                        'uk-background-transparent': 'transparent'
                    };
                    var previewColor = colorMap[bgColor] || 'transparent';
                    bgThumbnailStyle = ' style="background-color: ' + previewColor + ';"';
                }
                
                var html = '<div class="section-backend-label">' +
                    '<i class="fa fa-object-group"></i>' +
                    '<strong>Section:</strong> ' + $('<div>').text(label).html() +
                    '<span class="section-info">';
                
                if (bgColor && bgColor !== 'none') {
                    html += '<span class="label label-default">' + $('<div>').text(bgColor).html() + '</span>';
                }
                
                if (customId) {
                    html += '<span class="label label-info">#' + $('<div>').text(customId).html() + '</span>';
                }
                
                html += '</span>' +
                    '<span class="section-bg-thumbnail ' + $('<div>').text(bgThumbnailClass).html() + '"' + bgThumbnailStyle + '></span>' +
                    '</div>';
                
                $slice.find('.slice-rendered').html(html).show();
                return;
            }
            
            // Normale Elemente: Template per AJAX laden und rendern
            $.ajax({
                url: ContentBuilder.getAjaxUrl(),
                method: 'POST',
                data: {
                    action: 'render_slice',
                    slice_type: sliceType,
                    slice_data: sliceData,
                    framework: framework
                },
                success: function(response) {
                    $slice.find('.slice-rendered').html(response).show();
                },
                error: function(xhr, status, error) {
                    $slice.find('.slice-rendered').html('<div class="alert alert-danger">Fehler beim Laden des Templates</div>').show();
                }
            });
        },

        cancelEdit: function($slice) {
            var isNested = $slice.closest('.content-builder-column-slices').length > 0;
            
            if (isNested) {
                var $modal = $('#nested-slice-edit-modal');
                this.destroyTinyMCEInContainer($modal);
                var $editForm = $modal.find('.modal-body > .slice-edit-form');
                $slice.append($editForm.hide());
                $modal.modal('hide');
            } else {
                this.destroyTinyMCEInContainer($slice);
                $slice.children('.slice-edit-form').hide();
                $slice.find('.slice-rendered').show();
                $slice.find('.slice-toolbar').show();
            }
        },

        deleteSlice: function($slice) {
            var self = this;
            
            // Bootstrap Modal statt confirm()
            var modal = '<div class="modal fade" id="delete-slice-modal" tabindex="-1">' +
                '<div class="modal-dialog modal-sm">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                            '<h4 class="modal-title">Element löschen</h4>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<p>Element wirklich löschen?</p>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                            '<button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>' +
                            '<button type="button" class="btn btn-danger" id="confirm-delete-slice">Löschen</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            // Modal ins DOM einfügen
            $('body').append(modal);
            var $modal = $('#delete-slice-modal');
            
            // Confirm-Handler
            $('#confirm-delete-slice').on('click', function() {
                $modal.modal('hide');
                
                // TinyMCE-Instanzen entfernen
                self.destroyTinyMCEInContainer($slice);
                
                $slice.fadeOut(300, function() {
                    $(this).remove();
                    self.updateIndices();
                    self.updateHiddenField();
                    self.updateSectionClasses(); // Nach Löschen aktualisieren
                    self.updateInsertButtons();
                });
            });
            
            // Modal nach Schließen aufräumen
            $modal.on('hidden.bs.modal', function() {
                $(this).remove();
            });
            
            // Modal anzeigen (mit enforceFocus deaktiviert für TinyMCE)
            $modal.modal({
                show: true,
                backdrop: true,
                keyboard: true
            });
            
            // Bootstrap 3 enforceFocus deaktivieren für dieses Modal
            $modal.data('bs.modal').options.enforceFocus = false;
        },
        
        moveSliceUp: function($slice) {
            var $prev = $slice.prev('.content-builder-slice');
            if ($prev.length) {
                $slice.insertBefore($prev);
                this.updateIndices();
                this.updateHiddenField();
                this.updateSectionClasses();
                this.updateInsertButtons();
                
                // Kurzes Highlight
                $slice.css('background', '#d9edf7');
                setTimeout(function() {
                    $slice.css('background', '');
                }, 300);
            }
        },
        
        moveSliceDown: function($slice) {
            var $next = $slice.next('.content-builder-slice');
            if ($next.length) {
                $slice.insertAfter($next);
                this.updateIndices();
                this.updateHiddenField();
                this.updateSectionClasses();
                this.updateInsertButtons();
                
                // Kurzes Highlight
                $slice.css('background', '#d9edf7');
                setTimeout(function() {
                    $slice.css('background', '');
                }, 300);
            }
        },

        resolveElementDefaults: function($container, elementType) {
            // Defaults nur auf Top-Level-Slices anwenden. In verschachtelten
            // Spalten sollen bestehende/individuelle Werte nicht durch globale
            // Vorgaben beeinflusst werden.
            if ($container.hasClass('content-builder-column-slices')) {
                return {};
            }

            var elementDefaultsRaw = $container.closest('.yform-content-builder').attr('data-element-defaults') || '{}';
            var elementDefaults = {};
            try {
                elementDefaults = JSON.parse(elementDefaultsRaw);
            } catch (e) {
                elementDefaults = {};
            }

            if (!elementDefaults || typeof elementDefaults !== 'object') {
                return {};
            }

            var globalDefaults = elementDefaults['*'] || {};
            var typeDefaults = elementDefaults[elementType] || {};
            return Object.assign({}, globalDefaults, typeDefaults);
        },

        addSlice: function($container, elementType, elementLabel, initialData) {
            var sliceId = 'slice_' + Date.now();
            var index = $container.children('.content-builder-slice').length;
            
            // Section-Element?
            var isSectionClass = (elementType === 'section') ? ' is-section' : '';
            
            // Online/Offline-Toggle nur wenn aktiviert
            var onlineToggleEnabled = $container.closest('.yform-content-builder').data('online-toggle') == 1;
            var onlineBtnHtml = onlineToggleEnabled
                ? '<button type="button" class="btn btn-xs btn-default btn-slice-toggle-online" title="Offline/Online schalten"><i class="fa fa-eye"></i></button>'
                : '';

            var sliceDefaults = this.resolveElementDefaults($container, elementType);
            if (initialData && typeof initialData === 'object') {
                sliceDefaults = Object.assign({}, sliceDefaults, initialData);
            }

            // Icon aus available-elements lesen
            var availableElementsRaw = $container.closest('.yform-content-builder').attr('data-available-elements') || '{}';
            var availableElements = {};
            try { availableElements = JSON.parse(availableElementsRaw); } catch(e) {}
            var elementIcon = 'fa-cube';
            if (Array.isArray(availableElements)) {
                var found = availableElements.find(function(el) { return el && (el.type === elementType || el.key === elementType); });
                if (found && found.icon) {
                    elementIcon = found.icon;
                }
            } else if (availableElements[elementType] && availableElements[elementType].icon) {
                elementIcon = availableElements[elementType].icon;
            }
            var sliceLabelHtml = '<span class="slice-label"><i class="fa ' + elementIcon + '"></i>' + $('<span>').text(elementLabel || elementType).html() + '</span>';
            
            var $newSlice = $('<div class="content-builder-slice' + isSectionClass + '" data-slice-id="' + sliceId + '" data-slice-type="' + elementType + '" data-slice-index="' + index + '" data-slice-online="1">' +
                '<div class="slice-toolbar">' +
                    sliceLabelHtml +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-edit" title="Bearbeiten"><i class="fa fa-pencil"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-up" title="Nach oben"><i class="fa fa-arrow-up"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-down" title="Nach unten"><i class="fa fa-arrow-down"></i></button>' +
                    onlineBtnHtml +
                    '<button type="button" class="btn btn-xs btn-danger btn-slice-delete" title="Löschen"><i class="fa fa-trash"></i></button>' +
                '</div>' +
                '<div class="slice-rendered"><div class="alert alert-info">Neues Element: ' + (elementLabel || elementType) + ' - Klicken zum Bearbeiten</div></div>' +
                '<div class="slice-edit-form" style="display: none;"></div>' +
            '</div>');

            $newSlice.find('.slice-toolbar').attr('data-element-name', elementLabel || elementType || '');

            // Wenn Copy & Paste aktiviert ist, fügen wir den Copy-Button hinzu!
            var isCopyPasteEnabled = $container.closest('.yform-content-builder').data('copy-paste') == 1;
            if (isCopyPasteEnabled) {
                $newSlice.find('.slice-toolbar .btn-slice-edit').after('<button type="button" class="btn btn-xs btn-default btn-slice-copy" title="Kopieren"><i class="fa fa-copy"></i></button>');
            }

            if (Object.keys(sliceDefaults).length > 0) {
                $newSlice.attr('data-slice-data', JSON.stringify(sliceDefaults));
            }
            
            $container.append($newSlice);
            this.updateHiddenField();
            
            // Direkt bearbeiten
            this.editSlice($newSlice);
            
            this.updateIndices();
            this.updateSectionClasses(); // Nach Hinzufügen aktualisieren
            this.updateInsertButtons();
            
            this.scrollToSlice($newSlice);
            return $newSlice;
        },
        
        insertSliceAt: function($container, elementType, elementLabel, position) {
            var sliceId = 'slice_' + Date.now();
            
            // Section-Element?
            var isSectionClass = (elementType === 'section') ? ' is-section' : '';
            
            var onlineToggleEnabled2 = $container.closest('.yform-content-builder').data('online-toggle') == 1;
            var onlineBtnHtml2 = onlineToggleEnabled2
                ? '<button type="button" class="btn btn-xs btn-default btn-slice-toggle-online" title="Offline/Online schalten"><i class="fa fa-eye"></i></button>'
                : '';

            var sliceDefaults2 = this.resolveElementDefaults($container, elementType);

            // Icon aus available-elements lesen
            var availableElementsRaw2 = $container.closest('.yform-content-builder').attr('data-available-elements') || '{}';
            var availableElements2 = {};
            try { availableElements2 = JSON.parse(availableElementsRaw2); } catch(e) {}
            var elementIcon2 = 'fa-cube';
            if (Array.isArray(availableElements2)) {
                var found2 = availableElements2.find(function(el) { return el && (el.type === elementType || el.key === elementType); });
                if (found2 && found2.icon) {
                    elementIcon2 = found2.icon;
                }
            } else if (availableElements2[elementType] && availableElements2[elementType].icon) {
                elementIcon2 = availableElements2[elementType].icon;
            }
            var sliceLabelHtml2 = '<span class="slice-label"><i class="fa ' + elementIcon2 + '"></i>' + $('<span>').text(elementLabel || elementType).html() + '</span>';
            
            var $newSlice = $('<div class="content-builder-slice' + isSectionClass + '" data-slice-id="' + sliceId + '" data-slice-type="' + elementType + '" data-slice-index="' + position + '" data-slice-online="1">' +
                '<div class="slice-toolbar">' +
                    sliceLabelHtml2 +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-edit" title="Bearbeiten"><i class="fa fa-pencil"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-up" title="Nach oben"><i class="fa fa-arrow-up"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-down" title="Nach unten"><i class="fa fa-arrow-down"></i></button>' +
                    onlineBtnHtml2 +
                    '<button type="button" class="btn btn-xs btn-danger btn-slice-delete" title="Löschen"><i class="fa fa-trash"></i></button>' +
                '</div>' +
                '<div class="slice-rendered"><div class="alert alert-info">Neues Element: ' + (elementLabel || elementType) + ' - Klicken zum Bearbeiten</div></div>' +
                '<div class="slice-edit-form" style="display: none;"></div>' +
            '</div>');

            $newSlice.find('.slice-toolbar').attr('data-element-name', elementLabel || elementType || '');

            // Wenn Copy & Paste aktiviert ist, fügen wir den Copy-Button hinzu!
            var isCopyPasteEnabled2 = $container.closest('.yform-content-builder').data('copy-paste') == 1;
            if (isCopyPasteEnabled2) {
                $newSlice.find('.slice-toolbar .btn-slice-edit').after('<button type="button" class="btn btn-xs btn-default btn-slice-copy" title="Kopieren"><i class="fa fa-copy"></i></button>');
            }

            if (Object.keys(sliceDefaults2).length > 0) {
                $newSlice.attr('data-slice-data', JSON.stringify(sliceDefaults2));
            }
            
            var $slices = $container.children('.content-builder-slice');
            
            if (position >= $slices.length) {
                // Am Ende einfügen
                $container.append($newSlice);
            } else {
                // An Position einfügen
                $newSlice.insertBefore($slices.eq(position));
            }
            
            // Sofort zum Bearbeiten öffnen
            this.editSlice($newSlice);
            
            this.updateIndices();
            this.updateSectionClasses();
            this.updateInsertButtons();
            
            this.scrollToSlice($newSlice);
        },

        insertCopiedSliceAt: function($container, copiedSlice, position) {
            var self = this;
            var sliceId = 'slice_' + Date.now();
            var elementType = copiedSlice.type;
            
            // Section-Element?
            var isSectionClass = (elementType === 'section') ? ' is-section' : '';
            
            var onlineToggleEnabled = $container.closest('.yform-content-builder').data('online-toggle') == 1;
            var onlineBtnHtml = onlineToggleEnabled
                ? '<button type="button" class="btn btn-xs btn-default btn-slice-toggle-online" title="Offline/Online schalten"><i class="fa fa-eye"></i></button>'
                : '';

            // Icon aus available-elements lesen
            var availableElementsRaw = $container.closest('.yform-content-builder').attr('data-available-elements') || '{}';
            var availableElements = {};
            try { availableElements = JSON.parse(availableElementsRaw); } catch(e) {}
            var elementIcon = 'fa-cube';
            var elementLabel = elementType;
            if (Array.isArray(availableElements)) {
                var found = availableElements.find(function(el) { return el && (el.type === elementType || el.key === elementType); });
                if (found) {
                    if (found.icon) elementIcon = found.icon;
                    if (found.label) elementLabel = found.label;
                }
            } else if (availableElements[elementType]) {
                if (availableElements[elementType].icon) elementIcon = availableElements[elementType].icon;
                if (availableElements[elementType].label) elementLabel = availableElements[elementType].label;
            }
            var sliceLabelHtml = '<span class="slice-label"><i class="fa ' + elementIcon + '"></i>' + $('<span>').text(elementLabel || elementType).html() + '</span>';
            
            var $newSlice = $('<div class="content-builder-slice' + isSectionClass + '" data-slice-id="' + sliceId + '" data-slice-type="' + elementType + '" data-slice-index="' + position + '" data-slice-online="1">' +
                '<div class="slice-toolbar">' +
                    sliceLabelHtml +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-edit" title="Bearbeiten"><i class="fa fa-pencil"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-up" title="Nach oben"><i class="fa fa-arrow-up"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-default btn-slice-move-down" title="Nach unten"><i class="fa fa-arrow-down"></i></button>' +
                    onlineBtnHtml +
                    '<button type="button" class="btn btn-xs btn-danger btn-slice-delete" title="Löschen"><i class="fa fa-trash"></i></button>' +
                '</div>' +
                '<div class="slice-rendered"><div class="alert alert-info">Kopiertes Element: ' + (elementLabel || elementType) + ' - Wird geladen...</div></div>' +
                '<div class="slice-edit-form" style="display: none;"></div>' +
            '</div>');

            $newSlice.find('.slice-toolbar').attr('data-element-name', elementLabel || elementType || '');
            
            // Wenn Copy & Paste aktiviert ist, fügen wir den Copy-Button hinzu!
            var isCopyPasteEnabled = $container.closest('.yform-content-builder').data('copy-paste') == 1;
            if (isCopyPasteEnabled) {
                $newSlice.find('.slice-toolbar .btn-slice-edit').after('<button type="button" class="btn btn-xs btn-default btn-slice-copy" title="Kopieren"><i class="fa fa-copy"></i></button>');
            }

            if (copiedSlice.data) {
                $newSlice.attr('data-slice-data', JSON.stringify(copiedSlice.data));
            }
            
            var $slices = $container.children('.content-builder-slice');
            
            if (position >= $slices.length) {
                $container.append($newSlice);
            } else {
                $newSlice.insertBefore($slices.eq(position));
            }
            
            this.updateIndices();
            this.updateSectionClasses();
            this.updateInsertButtons();
            
            // Jetzt rendern wir das Element im Backend
            this.renderSlice($newSlice, copiedSlice.data || {});
            
            this.updateHiddenField();
            this.scrollToSlice($newSlice);
        },

        getSliceData: function($slice) {
            var self = this;
            var baseData = {};
            
            // Zuerst aus dem Attribut lesen (aktuellste Daten)
            var dataAttr = $slice.attr('data-slice-data');
            if (dataAttr) {
                try {
                    baseData = JSON.parse(dataAttr);
                } catch(e) {
                    // Error parsing slice data
                }
            } else {
                // Fallback auf jQuery data
                var dataStr = $slice.data('slice-data');
                if (dataStr && typeof dataStr === 'string') {
                    try {
                        baseData = JSON.parse(dataStr);
                    } catch(e) {}
                } else if (typeof dataStr === 'object') {
                    baseData = dataStr;
                }
            }

            if (!baseData) {
                baseData = {};
            }

            // Falls dieses Element Spalten (geschachtelte Elemente) hat, diese dynamisch sammeln
            var $cols = $slice.find('.content-builder-column-slices').filter(function() {
                // Nur Spalten-Container des aktuellen Slice erfassen, nicht die von verschachtelten Columns.
                return $(this).closest('.content-builder-slice').is($slice);
            });
            if ($cols.length > 0) {
                baseData.columns = [];
                $cols.each(function() {
                    var $column = $(this);
                    var colIndex = parseInt($column.attr('data-column-index') || 0);
                    baseData.columns[colIndex] = [];
                    
                    $column.children('.content-builder-slice').each(function() {
                        var $nestedSlice = $(this);
                        var online = $nestedSlice.attr('data-slice-online');
                        var isOnline = (online === undefined || online === '1');
                        
                        baseData.columns[colIndex].push({
                            id: $nestedSlice.data('slice-id'),
                            type: $nestedSlice.data('slice-type'),
                            online: isOnline,
                            data: self.getSliceData($nestedSlice) // Rekursion!
                        });
                    });
                });
            }

            return baseData;
        },

        getTopLevelSlicesContainer: function($builder) {
            var $wrappedContainer = $builder.find('> .content-builder-modern > .content-builder-slices').first();
            if ($wrappedContainer.length > 0) {
                return $wrappedContainer;
            }

            return $builder.children('.content-builder-slices').first();
        },

        updateIndices: function() {
            // Top-level slices indexen
            $('.yform-content-builder').each(function() {
                var $builder = $(this);
                var $topLevelSlices = ContentBuilder.getTopLevelSlicesContainer($builder);
                $topLevelSlices.children('.content-builder-slice').each(function(index) {
                    $(this).attr('data-slice-index', index);
                    $(this).find('> .slice-toolbar .btn-insert-slice').attr('data-insert-after', index);
                });
            });

            // Geschachtelte slices in Spalten indexen
            $('.content-builder-column-slices').each(function() {
                var $column = $(this);
                $column.children('.content-builder-slice').each(function(index) {
                    $(this).attr('data-slice-index', index);
                    $(this).find('> .slice-toolbar .btn-insert-slice').attr('data-insert-after', index);
                });
            });
        },
        
        updateInsertButtons: function() {
            var self = this;

            function resolveElementMeta(availableElements, elementType) {
                var meta = {
                    label: String(elementType || ''),
                    icon: 'fa-cube'
                };

                if (!availableElements) {
                    return meta;
                }

                if (Array.isArray(availableElements)) {
                    var found = availableElements.find(function(el) {
                        return el && (el.type === elementType || el.key === elementType);
                    });
                    if (found) {
                        if (found.label) {
                            meta.label = String(found.label);
                        }
                        if (found.icon) {
                            meta.icon = String(found.icon);
                        }
                    }
                    return meta;
                }

                if (availableElements[elementType]) {
                    if (availableElements[elementType].label) {
                        meta.label = String(availableElements[elementType].label);
                    }
                    if (availableElements[elementType].icon) {
                        meta.icon = String(availableElements[elementType].icon);
                    }
                }

                return meta;
            }

            function ensureToolbarLabel($toolbar, $slice, availableElements) {
                if (!$toolbar || $toolbar.length === 0) {
                    return;
                }

                var $existingLabel = $toolbar.children('.slice-label').first();
                if ($existingLabel.length > 0) {
                    return;
                }

                var elementType = String($slice.attr('data-slice-type') || $slice.data('slice-type') || '');
                var meta = resolveElementMeta(availableElements, elementType);
                var $label = $('<span class="slice-label"></span>');
                var $icon = $('<i></i>').addClass('fa ' + meta.icon);
                var $text = $('<span></span>').text(meta.label || elementType);

                $label.append($icon).append($text);
                $toolbar.attr('data-element-name', meta.label || elementType);

                var $firstButtonGroup = $toolbar.children('.btn-group').first();
                if ($firstButtonGroup.length > 0) {
                    $label.insertBefore($firstButtonGroup);
                } else {
                    $toolbar.prepend($label);
                }
            }
            
            $('.yform-content-builder').each(function() {
                var $builder = $(this);
                var availableElements = self.getAvailableElementsFromBuilder($builder);
                
                if (!availableElements) {
                    return;
                }
                
                // Remove old insert-between-buttons
                $builder.find('.content-builder-insert-between').remove();
                
                // Top-level slices
                var $topLevelSlices = self.getTopLevelSlicesContainer($builder);
                $topLevelSlices.children('.content-builder-slice').each(function(index) {
                    var $slice = $(this);
                    var $toolbar = $slice.find('> .slice-toolbar');
                    if ($toolbar.length === 0) {
                        $toolbar = $slice.children('.slice-toolbar');
                    }
                    ensureToolbarLabel($toolbar, $slice, availableElements);
                    var $insertGroup = $toolbar.find('.btn-group-insert');

                    if ($insertGroup.length > 0) {
                        $insertGroup.remove();
                    }

                    $insertGroup = self.createInsertButton(
                        availableElements,
                        index,
                        String($slice.data('slice-type') || ''),
                        $builder
                    );
                    var $sliceLabel = $toolbar.find('.slice-label');
                    if ($sliceLabel.length) {
                        $insertGroup.insertAfter($sliceLabel);
                    } else {
                        $toolbar.prepend($insertGroup);
                    }
                });

                // Geschachtelte slices
                $builder.find('.content-builder-column-slices').each(function() {
                    var $column = $(this);
                    $column.children('.content-builder-slice').each(function(index) {
                        var $slice = $(this);
                        var $toolbar = $slice.find('> .slice-toolbar');
                        if ($toolbar.length === 0) {
                            $toolbar = $slice.children('.slice-toolbar');
                        }
                        ensureToolbarLabel($toolbar, $slice, availableElements);
                        var $insertGroup = $toolbar.find('.btn-group-insert');

                        if ($insertGroup.length > 0) {
                            $insertGroup.remove();
                        }

                        $insertGroup = self.createInsertButton(
                            availableElements,
                            index,
                            String($slice.data('slice-type') || ''),
                            $builder
                        );
                        var $sliceLabel = $toolbar.find('.slice-label');
                        if ($sliceLabel.length) {
                            $insertGroup.insertAfter($sliceLabel);
                        } else {
                            $toolbar.prepend($insertGroup);
                        }
                    });
                });
            });
        },
        
        createInsertButton: function(availableElements, insertAfter, currentSliceType, $builder) {
            var dropdownItems = '';

            function esc(value) {
                return $('<div/>').text(value == null ? '' : String(value)).html();
            }

            function formatCategory(category) {
                var normalized = String(category == null ? '' : category).trim();
                if (normalized === '') {
                    normalized = 'sonstiges';
                }

                normalized = normalized.replace(/^\s*\d{1,4}\s*(?:::\s*|[:\-_.]{1,2}\s*)/, '');

                return normalized
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, function(ch) {
                        return ch.toUpperCase();
                    });
            }

            function getCategorySortKey(category) {
                var normalized = String(category == null ? '' : category).trim();
                if (normalized === '') {
                    return { priority: 9999, label: '' };
                }

                var match = normalized.match(/^\s*(\d{1,4})\s*(?:::\s*|[:\-_.]{1,2}\s*)?(.*)$/);
                if (match && match[2] && String(match[2]).trim() !== '') {
                    return {
                        priority: parseInt(match[1], 10),
                        label: String(match[2]).trim().toLowerCase()
                    };
                }

                return { priority: 9999, label: normalized.toLowerCase() };
            }

            function toBoolFlag(value, defaultValue) {
                if (typeof value === 'boolean') {
                    return value;
                }

                if (typeof value === 'number') {
                    return value === 1;
                }

                if (typeof value === 'string') {
                    var normalized = value.trim().toLowerCase();
                    if (normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on') {
                        return true;
                    }

                    if (normalized === '0' || normalized === 'false' || normalized === 'no' || normalized === 'off') {
                        return false;
                    }
                }

                return !!defaultValue;
            }

            function isSelfNestingBlocked(currentType, elementType, config) {
                if (!currentType || !elementType || String(currentType) !== String(elementType)) {
                    return false;
                }

                if (!config || typeof config !== 'object') {
                    return false;
                }

                if (Object.prototype.hasOwnProperty.call(config, 'prevent_self_nesting')) {
                    return toBoolFlag(config.prevent_self_nesting, false);
                }

                if (Object.prototype.hasOwnProperty.call(config, 'allow_self_nesting')) {
                    return !toBoolFlag(config.allow_self_nesting, true);
                }

                return false;
            }

            var groupedElements = {};
            var categoryOrder = [];
            
            var elementsList = [];
            if (Array.isArray(availableElements)) {
                availableElements.forEach(function(config, index) {
                    if (config) {
                        var key = config.type || config.key || index;
                        elementsList.push({
                            elementType: key,
                            config: config
                        });
                    }
                });
            } else {
                for (var elementType in availableElements) {
                    if (availableElements.hasOwnProperty(elementType)) {
                        elementsList.push({
                            elementType: elementType,
                            config: availableElements[elementType]
                        });
                    }
                }
            }

            elementsList.forEach(function(item) {
                var elementType = item.elementType;
                var config = item.config;

                if (isSelfNestingBlocked(currentSliceType, elementType, config)) {
                    return;
                }

                var category = (config && config.category) ? String(config.category) : '';
                if (category.trim() === '') {
                    category = 'sonstiges';
                }

                if (!groupedElements[category]) {
                    groupedElements[category] = [];
                    categoryOrder.push(category);
                }

                groupedElements[category].push({
                    elementType: elementType,
                    config: config
                });
            });

            categoryOrder.sort(function(leftCategory, rightCategory) {
                var leftKey = getCategorySortKey(leftCategory);
                var rightKey = getCategorySortKey(rightCategory);

                if (leftKey.priority !== rightKey.priority) {
                    return leftKey.priority - rightKey.priority;
                }

                return leftKey.label.localeCompare(rightKey.label);
            });

            categoryOrder.forEach(function(category, categoryIndex) {
                if (categoryIndex > 0) {
                    dropdownItems += '<li role="separator" class="divider"></li>';
                }

                dropdownItems += '<li class="dropdown-header">' + esc(formatCategory(category)) + '</li>';

                groupedElements[category].sort(function(leftEntry, rightEntry) {
                    var leftLabel = String((leftEntry.config && leftEntry.config.label) ? leftEntry.config.label : leftEntry.elementType).toLowerCase();
                    var rightLabel = String((rightEntry.config && rightEntry.config.label) ? rightEntry.config.label : rightEntry.elementType).toLowerCase();
                    return leftLabel.localeCompare(rightLabel);
                });

                groupedElements[category].forEach(function(entry) {
                    var elementType = entry.elementType;
                    var config = entry.config || {};
                    var label = config.label || elementType;
                    var icon = config.icon || 'fa-cube';
                    var description = config.description || '';
                    var tooltipAttributes = '';

                    if (description !== '') {
                        tooltipAttributes = ' data-toggle="tooltip" data-placement="right" data-container="body" data-delay="{&quot;show&quot;:700,&quot;hide&quot;:120}" title="' + esc(description) + '"';
                    }
                    
                    dropdownItems += '<li>' +
                        '<a href="#" class="btn-insert-slice" ' +
                        'data-element-type="' + esc(elementType) + '" ' +
                        'data-element-label="' + esc(label) + '" ' +
                        'data-insert-after="' + insertAfter + '"' + tooltipAttributes + '>' +
                        '<i class="fa ' + esc(icon) + '"></i> ' + esc(label) +
                        '</a>' +
                        '</li>';
                });
            });
            
            // Wenn Copy & Paste aktiviert ist, fügen wir eine Einfügen-Option hinzu
            var isCopyPasteEnabled = false;
            var isElementSearchEnabled = false;
            var $cb = ($builder && $builder.length) ? $builder : $('.yform-content-builder').first();
            if ($cb.length > 0 && $cb.attr('data-copy-paste') === '1') {
                isCopyPasteEnabled = true;
            }
            if ($cb.length > 0 && $cb.attr('data-element-search') === '1') {
                isElementSearchEnabled = true;
            }
            
            if (isCopyPasteEnabled) {
                var hasCopiedSlice = localStorage.getItem('yform_cb_copied_slice') !== null;
                var displayStyle = hasCopiedSlice ? '' : ' style="display: none;"';
                var pasteHtml = '<li class="paste-slice-item"' + displayStyle + '>' +
                    '<a href="#" class="btn-paste-slice" data-insert-after="' + insertAfter + '">' +
                    '<i class="fa fa-clipboard"></i> <strong>Element einfügen</strong>' +
                    '</a>' +
                    '</li>' +
                    '<li role="separator" class="divider paste-slice-item"' + displayStyle + '></li>';
                dropdownItems = pasteHtml + dropdownItems;
            }
            
            // Suchbox einfügen wenn aktiviert und genug Elemente vorhanden
            var elementCount = Array.isArray(elementsList) ? elementsList.length : 0;
            if (isElementSearchEnabled && elementCount >= 5) {
                var searchHtml = '<li class="yform-cb-search-item">' +
                    '<div class="yform-cb-search-wrapper">' +
                    '<input type="text" ' +
                    'class="yform-cb-element-search-input form-control input-sm" ' +
                    'placeholder="Element durchsuchen..." ' +
                    'style="margin: 0; width: 100%;">' +
                    '</div>' +
                    '</li>' +
                    '<li role="separator" class="divider"></li>';
                dropdownItems = searchHtml + dropdownItems;
            }
            
            var html = '<div class="btn-group btn-group-insert">' +
                '<button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" title="Element einfügen">' +
                '<i class="fa fa-plus"></i>' +
                '</button>' +
                '<ul class="dropdown-menu pull-right">' + dropdownItems + '</ul>' +
                '</div>';
            
            return $(html);
        },

        scrollToSlice: function($slice) {
            var offset = $slice.offset().top;
            // Ein bisschen Abstand nach oben lassen (z.B. für Fixed Header)
            offset = offset - 100;
            
            $('html, body').animate({
                scrollTop: offset
            }, 500);
        },

        updateHiddenField: function() {
            $('.yform-content-builder').each(function() {
                var $container = $(this);

                // Legacy-Editor-Instanzen behalten ihren HTML-String im Hidden-Field.
                if ($container.attr('data-legacy-mode') === '1') {
                    return;
                }

                var slices = [];

                var $topLevelSlices = ContentBuilder.getTopLevelSlicesContainer($container);

                $topLevelSlices.children('.content-builder-slice').each(function() {
                    var $slice = $(this);
                    var online = $slice.attr('data-slice-online');
                    // Default: online (true) wenn Attribut nicht gesetzt
                    var isOnline = (online === undefined || online === '1');
                    slices.push({
                        id: $slice.data('slice-id'),
                        type: $slice.data('slice-type'),
                        online: isOnline,
                        data: ContentBuilder.getSliceData($slice)
                    });
                });
                
                var newValue = JSON.stringify(slices);
                var $hiddenField = $container.find('.content-builder-data').first();
                var previousValue = String($hiddenField.val() || '');

                $hiddenField.val(newValue);

                if (newValue !== previousValue) {
                    $container.attr('data-cb-persist-dirty', '1');
                    ContentBuilder.setPersistState($container, 'dirty');
                }
            });
        },

        /**
         * Umschalten des Online/Offline-Status einer Slice
         */
        toggleSliceOnline: function($slice) {
            var currentlyOnline = $slice.attr('data-slice-online') !== '0';
            var newOnline = !currentlyOnline;
            
            $slice.attr('data-slice-online', newOnline ? '1' : '0');
            $slice.toggleClass('is-offline', !newOnline);
            
            // Icon im Toggle-Button tauschen
            var $btn = $slice.find('> .slice-toolbar .btn-slice-toggle-online').first();
            if ($btn.length === 0) {
                $btn = $slice.children('.slice-toolbar').find('.btn-slice-toggle-online').first();
            }
            var $icon = $btn.find('i');
            if (newOnline) {
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
            
            this.updateHiddenField();
        },

        addRepeaterItem: function($container) {
            var self = this;
            var fieldName = $container.data('field');
            var $items = $container.find('.repeater-item:not(.repeater-item-template)');
            var $templateItem = $container.find('.repeater-item-template');
            var newIndex = $items.length;
            
            
            // Prüfen ob ein verstecktes Template-Item existiert
            if ($templateItem.length > 0) {
                var $newItem = $templateItem.clone(false, false);
                $newItem.removeClass('repeater-item-template');
                $newItem.show();
                $newItem.attr('data-index', newIndex);
                
                // Move-Buttons enablen (werden vom Template als disabled geklont)
                $newItem.find('.btn-move').prop('disabled', false);
                
                // Neue eindeutige IDs für das Item und Modal generieren
                var newItemId = 'repeater_item_' + Math.random().toString(16).slice(2);
                $newItem.attr('id', newItemId);
                
                // ALLE Modals aktualisieren (es können mehrere sein: media_modal, item_modal, etc.)
                $newItem.find('.modal').each(function() {
                    var $modal = $(this);
                    var oldModalId = $modal.attr('id');
                    
                    // Modalname extrahieren (z.B. "_item_modal", "_media_modal")
                    var modalSuffix = oldModalId ? oldModalId.replace(/^repeater_item_[^_]+/, '') : '_modal';
                    var newModalId = newItemId + modalSuffix;
                    
                    $modal.attr('id', newModalId);
                    
                    // Den entsprechenden Button finden und aktualisieren
                    // Button sollte im gleichen Item sein (nicht in verschachtelten Items!)
                    $newItem.children().find('[data-target="#' + oldModalId + '"]').attr('data-target', '#' + newModalId);
                });
                
                // CKE5 Elemente entfernen
                $newItem.find('.ck-editor__editable').remove();
                $newItem.find('.ck-editor__top').remove();
                $newItem.find('.ck-editor__main').remove();
                $newItem.find('.ck-editor').remove();
                $newItem.find('.ck').remove();
                
                // TinyMCE Elemente entfernen
                $newItem.find('.tox-tinymce').remove();
                $newItem.find('textarea.tiny-editor').each(function() {
                    var $ta = $(this);
                    var oldId = $ta.attr('id');
                    // TinyMCE-Instanz entfernen falls vorhanden
                    if (oldId && typeof tinymce !== 'undefined' && tinymce.get(oldId)) {
                        try {
                            tinymce.get(oldId).remove();
                        } catch(e) {}
                    }
                    $ta.removeAttr('id');
                    $ta.removeClass('mce-initialized');
                    if (!$ta.attr('data-profile')) {
                        $ta.attr('data-profile', 'default');
                    }
                });
                
                // Textareas zurücksetzen
                $newItem.find('textarea.cke5-editor').each(function() {
                    var $ta = $(this);
                    $ta.removeAttr('id');
                    $ta.removeClass('ck-hidden');
                    $ta.removeAttr('data-cke-init');
                    if (!$ta.attr('data-profile')) {
                        $ta.attr('data-profile', 'default');
                    }
                });
                
                // Alle Media-Preview Container leeren (bevor IDs geändert werden)
                $newItem.find('.media-preview').each(function() {
                    $(this).empty().hide();
                });
                
                // Input-Namen und Werte aktualisieren
                $newItem.find('input, textarea, select').each(function() {
                    var $input = $(this);
                    var name = $input.attr('name');
                    var oldId = $input.attr('id');
                    
                    if (name) {
                        var newName = name.replace(/\[(\d+)\]/g, function(match, num) {
                            return '[' + newIndex + ']';
                        });
                        $input.attr('name', newName);
                        
                        if ($input.hasClass('cke5-editor')) {
                            var newId = 'cke5_' + Math.random().toString(16).slice(2);
                            $input.attr('id', newId);
                        }
                        
                        // TinyMCE: Neue ID generieren
                        if ($input.hasClass('tiny-editor')) {
                            var newId = 'tinymce_' + Math.random().toString(16).slice(2);
                            $input.attr('id', newId);
                        }
                        
                        // Media/Link-Inputs: Neue ID generieren
                        if (oldId && (oldId.indexOf('media_') === 0 || oldId.indexOf('REX_MEDIA_') === 0 || oldId.indexOf('REX_LINK_') === 0)) {
                            var newId;
                            
                            // Für REX_MEDIA_ müssen wir den Counter hochzählen
                            if (oldId.indexOf('REX_MEDIA_') === 0) {
                                // Globalen Counter verwenden oder hochzählen
                                if (typeof window.rexMediaCounter === 'undefined') {
                                    // Counter aus dem höchsten vorhandenen REX_MEDIA_X ermitteln
                                    var maxCounter = 0;
                                    $('input[id^="REX_MEDIA_"]').each(function() {
                                        var match = $(this).attr('id').match(/REX_MEDIA_(\d+)/);
                                        if (match) {
                                            maxCounter = Math.max(maxCounter, parseInt(match[1]));
                                        }
                                    });
                                    window.rexMediaCounter = maxCounter;
                                }
                                window.rexMediaCounter++;
                                newId = 'REX_MEDIA_' + window.rexMediaCounter;
                                
                                // onclick-Attribute für openREXMedia aktualisieren
                                $newItem.find('a[onclick*="openREXMedia"]').each(function() {
                                    var $link = $(this);
                                    var oldOnclick = $link.attr('onclick');
                                    if (oldOnclick) {
                                        var oldCounter = oldId.replace('REX_MEDIA_', '');
                                        var newOnclick = oldOnclick.replace(
                                            'openREXMedia(' + oldCounter,
                                            'openREXMedia(' + window.rexMediaCounter
                                        );
                                        $link.attr('onclick', newOnclick);
                                    }
                                });
                                
                                // deleteREXMedia und viewREXMedia auch updaten
                                $newItem.find('a[onclick*="deleteREXMedia"],a[onclick*="viewREXMedia"]').each(function() {
                                    var $link = $(this);
                                    var oldOnclick = $link.attr('onclick');
                                    if (oldOnclick) {
                                        var oldCounter = oldId.replace('REX_MEDIA_', '');
                                        var newOnclick = oldOnclick.replace(
                                            new RegExp('(deleteREXMedia|viewREXMedia)\\(' + oldCounter),
                                            '$1(' + window.rexMediaCounter
                                        );
                                        $link.attr('onclick', newOnclick);
                                    }
                                });
                            } else {
                                // Für media_ und REX_LINK_: Timestamp + Index
                                newId = oldId.replace(/_\w+$/, '_' + Date.now() + '_' + newIndex);
                            }
                            
                            $input.attr('id', newId);

                            // Zugehörige Buttons aktualisieren (data-id für Linkmap, data-input-id für Media)
                            $newItem.find('[data-id="' + oldId + '"]').attr('data-id', newId);
                            $newItem.find('[data-input-id="' + oldId + '"]').attr('data-input-id', newId);

                            // NAME-Input aktualisieren (für Linkmap)
                            var $nameInput = $newItem.find('#' + oldId + '_NAME');
                            if ($nameInput.length) {
                                $nameInput.attr('id', newId + '_NAME');
                            }

                            // Preview-Container aktualisieren (für Media)
                            var $preview = $newItem.find('#preview_' + oldId);
                            if ($preview.length) {
                                $preview.attr('id', 'preview_' + newId);
                                $preview.attr('data-input-id', newId);
                                
                                // Für be_media_enhanced: Counter auch aktualisieren
                                if ($preview.hasClass('media-preview-enhanced')) {
                                    if (oldId.indexOf('REX_MEDIA_') === 0) {
                                        var newCounter = window.rexMediaCounter;
                                        $preview.attr('data-counter', newCounter);
                                    }
                                }
                                
                                // Preview sollte bereits geleert sein (siehe oben)
                            }

                            // Zusätzlich: Alle anderen Referenzen auf die alte ID aktualisieren
                            $newItem.find('[data-target]').each(function() {
                                var $btn = $(this);
                                var target = $btn.attr('data-target');
                                if (target && target === '#' + oldId) {
                                    $btn.attr('data-target', '#' + newId);
                                }
                            });
                        }
                        
                        if ($input.is(':checkbox') || $input.is(':radio')) {
                            // Radio-Buttons: Neue eindeutige ID generieren und Label aktualisieren
                            if ($input.is(':radio') && oldId) {
                                var newRadioId = 'radio_' + Math.random().toString(16).slice(2);
                                $input.attr('id', newRadioId);
                                // Zugehöriges Label finden und for-Attribut aktualisieren
                                $newItem.find('label[for="' + oldId + '"]').attr('for', newRadioId);
                            }
                            // Default-Wert setzen (erstes Element als checked)
                            var $radioGroup = $input.closest('.radio-image-group, .color-swatches-group');
                            if ($radioGroup.length > 0) {
                                var $firstRadio = $radioGroup.find('input[type="radio"]:first');
                                if ($input.is($firstRadio)) {
                                    $input.prop('checked', true);
                                    $input.closest('.radio-image-item, .color-swatch-item').addClass('active');
                                } else {
                                    $input.prop('checked', false);
                                    $input.closest('.radio-image-item, .color-swatch-item').removeClass('active');
                                }
                            } else {
                                $input.prop('checked', false);
                            }
                        } else if ($input.is('select')) {
                            // For select fields, check if there's a default selected option
                            var $defaultOption = $input.find('option[selected]');
                            if ($defaultOption.length > 0) {
                                $input.val($defaultOption.val());
                            } else {
                                // If no default, select first option
                                var firstValue = $input.find('option:first').val();
                                $input.val(firstValue);
                            }
                        } else {
                            $input.val('');
                        }
                    }
                });
                
            } else if ($items.length === 0) {
                // Kein Item vorhanden - Template aus data-template holen oder Standard erstellen
                var template = $container.data('template');
                if (template) {
                    var $newItem = $(template);
                    
                    // Neue IDs für CKE5-Textareas generieren (auch im Template)
                    $newItem.find('textarea.cke5-editor').each(function() {
                        var $ta = $(this);
                        if (!$ta.attr('id')) {
                            var newId = 'cke5_' + Math.random().toString(16).slice(2);
                            $ta.attr('id', newId);
                        }
                    });
                } else {
                    // Standard-Template für text/textarea Felder
                    var newId = 'cke5_' + Math.random().toString(16).slice(2);
                    var $newItem = $('<div class="repeater-item" data-index="' + newIndex + '">' +
                        '<div class="move-buttons">' +
                            '<button type="button" class="btn-move btn-move-up" title="Nach oben"><i class="fa fa-chevron-up"></i></button>' +
                            '<button type="button" class="btn-move btn-move-down" title="Nach unten"><i class="fa fa-chevron-down"></i></button>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label>Titel</label>' +
                            '<input type="text" class="form-control" name="' + fieldName + '[' + newIndex + '][title]" />' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label>Inhalt</label>' +
                            '<textarea id="' + newId + '" class="form-control cke5-editor" data-profile="default" name="' + fieldName + '[' + newIndex + '][content]"></textarea>' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm btn-danger btn-remove-repeater"><i class="fa fa-trash"></i></button>' +
                    '</div>');
                }
            } else {
                // Item vorhanden - Clone vom letzten
                var $lastItem = $items.last();
                var $newItem = $lastItem.clone(false, false); // Shallow clone, keine Events, keine Data
                
                $newItem.attr('data-index', newIndex);
                
                // Move-Buttons enablen (werden vom letzten Item evtl. als disabled geklont)
                $newItem.find('.btn-move').prop('disabled', false);
                
                // Neue eindeutige IDs für das Item und Modal generieren
                var oldItemId = $lastItem.attr('id');
                var newItemId = 'repeater_item_' + Math.random().toString(16).slice(2);
                $newItem.attr('id', newItemId);
                
                // Modal ID aktualisieren (falls vorhanden)
                var $modal = $newItem.find('.modal');
                if ($modal.length > 0) {
                    var oldModalId = $modal.attr('id');
                    var newModalId = newItemId + '_modal';
                    $modal.attr('id', newModalId);
                    
                    // Modal-Button data-target aktualisieren
                    $newItem.find('[data-toggle="modal"]').attr('data-target', '#' + newModalId);
                    
                }
                
                // WICHTIG: Alle CKE5-DOM-Elemente komplett entfernen
                $newItem.find('.ck-editor__editable').remove();
                $newItem.find('.ck-editor__top').remove();
                $newItem.find('.ck-editor__main').remove();
                $newItem.find('.ck-editor').remove();
                $newItem.find('.ck').remove();
                
                // TinyMCE-DOM-Elemente entfernen
                $newItem.find('.tox-tinymce').remove();
                $newItem.find('textarea.tiny-editor').each(function() {
                    var $ta = $(this);
                    var oldId = $ta.attr('id');
                    // TinyMCE-Instanz entfernen
                    if (oldId && typeof tinymce !== 'undefined' && tinymce.get(oldId)) {
                        try {
                            tinymce.get(oldId).remove();
                        } catch(e) {}
                    }
                    $ta.removeAttr('id');
                    $ta.removeClass('mce-initialized');
                    if (!$ta.attr('data-profile')) {
                        $ta.attr('data-profile', 'default');
                    }
                });
                
                // Alle Textareas zurücksetzen und neue IDs geben
                $newItem.find('textarea.cke5-editor').each(function() {
                    var $ta = $(this);
                    // Alte ID entfernen
                    $ta.removeAttr('id');
                    // CKE5-Klassen/Attribute zurücksetzen
                    $ta.removeClass('ck-hidden');
                    $ta.removeAttr('data-cke-init');
                    // WICHTIG: data-profile BEHALTEN oder setzen
                    if (!$ta.attr('data-profile')) {
                        $ta.attr('data-profile', 'default');
                    }
                });
                
                // Input-Namen und Werte aktualisieren
                $newItem.find('input, textarea, select').each(function() {
                    var $input = $(this);
                    var name = $input.attr('name');
                    var oldId = $input.attr('id');
                    
                    if (name) {
                        // Index im Namen ersetzen (alle Vorkommen)
                        var newName = name.replace(/\[(\d+)\]/g, function(match, num) {
                            return '[' + newIndex + ']';
                        });
                        $input.attr('name', newName);
                        
                        // Neue eindeutige ID für CKE5-Textareas generieren
                        if ($input.hasClass('cke5-editor')) {
                            var newId = 'cke5_' + Math.random().toString(16).slice(2);
                            $input.attr('id', newId);
                            $input.removeAttr('repeater_cke'); // Damit cke5_init neue ID generiert
                        }
                        
                        // Neue eindeutige ID für TinyMCE-Textareas generieren
                        if ($input.hasClass('tiny-editor')) {
                            var newId = 'tinymce_' + Math.random().toString(16).slice(2);
                            $input.attr('id', newId);
                        }
                        
                        // Media/Link-Inputs: Neue ID generieren
                        if (oldId && (oldId.indexOf('media_') === 0 || oldId.indexOf('REX_MEDIA_') === 0 || oldId.indexOf('REX_LINK_') === 0)) {
                            var newId;
                            
                            // Für REX_MEDIA_ müssen wir den Counter hochzählen
                            if (oldId.indexOf('REX_MEDIA_') === 0) {
                                // Globalen Counter verwenden oder hochzählen
                                if (typeof window.rexMediaCounter === 'undefined') {
                                    // Counter aus dem höchsten vorhandenen REX_MEDIA_X ermitteln
                                    var maxCounter = 0;
                                    $('input[id^="REX_MEDIA_"]').each(function() {
                                        var match = $(this).attr('id').match(/REX_MEDIA_(\d+)/);
                                        if (match) {
                                            maxCounter = Math.max(maxCounter, parseInt(match[1]));
                                        }
                                    });
                                    window.rexMediaCounter = maxCounter;
                                }
                                window.rexMediaCounter++;
                                newId = 'REX_MEDIA_' + window.rexMediaCounter;
                                
                                // onclick-Attribute für openREXMedia aktualisieren
                                $newItem.find('a[onclick*="openREXMedia"]').each(function() {
                                    var $link = $(this);
                                    var oldOnclick = $link.attr('onclick');
                                    if (oldOnclick) {
                                        var oldCounter = oldId.replace('REX_MEDIA_', '');
                                        var newOnclick = oldOnclick.replace(
                                            'openREXMedia(' + oldCounter,
                                            'openREXMedia(' + window.rexMediaCounter
                                        );
                                        $link.attr('onclick', newOnclick);
                                    }
                                });
                                
                                // deleteREXMedia und viewREXMedia auch updaten
                                $newItem.find('a[onclick*="deleteREXMedia"],a[onclick*="viewREXMedia"]').each(function() {
                                    var $link = $(this);
                                    var oldOnclick = $link.attr('onclick');
                                    if (oldOnclick) {
                                        var oldCounter = oldId.replace('REX_MEDIA_', '');
                                        var newOnclick = oldOnclick.replace(
                                            new RegExp('(deleteREXMedia|viewREXMedia)\\(' + oldCounter),
                                            '$1(' + window.rexMediaCounter
                                        );
                                        $link.attr('onclick', newOnclick);
                                    }
                                });
                            } else {
                                // Für media_ und REX_LINK_: Timestamp + Index
                                newId = oldId.replace(/_\w+$/, '_' + Date.now() + '_' + newIndex);
                            }
                            
                            $input.attr('id', newId);
                            
                            // Zugehörige Buttons aktualisieren (data-id für Linkmap, data-input-id für Media)
                            $newItem.find('[data-id="' + oldId + '"]').attr('data-id', newId);
                            $newItem.find('[data-input-id="' + oldId + '"]').attr('data-input-id', newId);
                            
                            // NAME-Input aktualisieren (für Linkmap)
                            var $nameInput = $newItem.find('#' + oldId + '_NAME');
                            if ($nameInput.length) {
                                $nameInput.attr('id', newId + '_NAME');
                            }
                            
                            // Preview-Container aktualisieren (für Media)
                            var $preview = $newItem.find('#preview_' + oldId);
                            if ($preview.length) {
                                $preview.attr('id', 'preview_' + newId);
                            }
                            
                            // Zusätzlich: Alle anderen Referenzen auf die alte ID aktualisieren
                            $newItem.find('[data-target]').each(function() {
                                var $btn = $(this);
                                var target = $btn.attr('data-target');
                                if (target && target === '#' + oldId) {
                                    $btn.attr('data-target', '#' + newId);
                                }
                            });
                        }
                        
                        // Wert leeren
                        if ($input.is(':checkbox') || $input.is(':radio')) {
                            $input.prop('checked', false);
                        } else {
                            $input.val('');
                        }
                    }
                });
            }
            
            // Insert new item BEFORE the template item (not at the end)
            var $template = $container.find('.repeater-item-template');
            if ($template.length > 0) {
                $template.before($newItem);
            } else {
                $container.append($newItem);
            }
            $newItem.hide().fadeIn(200);

            // Bootstrap Selectpicker: Geklonte Instanz wiederherstellen und neu initialisieren.
            // Beim Klonen wird der Bootstrap-Select-Wrapper mitkopiert, der Dropdown ist aber leer.
            // Lösung: Wrapper entfernen, natives Select wiederherstellen, Selectpicker neu init.
            if (typeof $.fn.selectpicker !== 'undefined') {
                $newItem.find('div.bootstrap-select').each(function() {
                    var $wrapper = $(this);
                    var $select = $wrapper.find('> select');
                    if ($select.length) {
                        // Select ohne Wrapper wiederherstellen
                        $wrapper.replaceWith($select.detach());
                        // selectpicker-Klasse sicherstellen (Bootstrap Select entfernt sie beim Init)
                        if (!$select.hasClass('selectpicker')) {
                            $select.addClass('selectpicker');
                        }
                        $select.removeAttr('tabindex');
                    }
                });
                // Neu initialisieren – sanitize: false ist erforderlich für SVG/img in data-content
                $newItem.find('select.selectpicker').selectpicker({
                    sanitize: false
                });
            }

            // Enhanced Media Previews zurücksetzen
            $newItem.find('.media-preview-enhanced').each(function() {
                var $preview = $(this);
                var types = $preview.attr('data-types') || '';
                var allowedTypes = types ? types.split(',') : ['image', 'video'];
                self.resetEnhancedPreview($preview, allowedTypes);
            });
            
            // Initial last-value für Polling setzen
            $newItem.find('input[id^="REX_MEDIA_"]').each(function() {
                $(this).data('last-value', $(this).val() || '');
            });

            // YForm Picker Widgets in neuem Item initialisieren
            $newItem.find('.yform-dataset-widget').each(function() {
                var $widget = $(this);
                var oldId = $widget.attr('id');
                var newId = 'yform-dataset-' + Math.random().toString(16).slice(2);
                $widget.attr('id', newId);
                
                // Name-Input (für Display) leeren
                $widget.find('.yform-dataset-view').val('');
                // Real-Input (Hidden ID) leeren
                $widget.find('.yform-dataset-real').val('');
            });
            
            // REDO REX:READY for everything in this item (YForm, etc.)
            $(document).trigger('rex:ready', [$newItem]);
            
            // Move Button States aktualisieren
            this.updateMoveButtonStates();
            
            // CKE5 in neuem Item initialisieren - EINFACHER ANSATZ
            setTimeout(function() {
                $newItem.find('textarea.cke5-editor').each(function() {
                    var $textarea = $(this);
                    
                    if (typeof cke5_init === 'function') {
                        try {
                            cke5_init($textarea);
                        } catch(e) {
                            // CKE5 initialization failed
                        }
                    }
                });
                
                // TinyMCE in neuem Item initialisieren
                if (typeof tiny_init === 'function') {
                    setTimeout(function() {
                        try {
                            tiny_init($newItem);
                            
                            // Fix: TinyMCE editor container visibility in repeater items
                            setTimeout(function() {
                                $newItem.find('textarea.tiny-editor').each(function() {
                                    var $ta = $(this);
                                    var id = $ta.attr('id');
                                    var editor = (typeof tinymce !== 'undefined' && id) ? tinymce.get(id) : null;
                                    var $editorContainer = $ta.siblings('.tox-tinymce');
                                    
                                    // Force editor container to be visible
                                    if ($editorContainer.length > 0 && !$editorContainer.is(':visible')) {
                                        $editorContainer.show();
                                    }
                                    
                                    // Trigger layout refresh
                                    if (editor && typeof editor.dispatch === 'function') {
                                        try {
                                            editor.dispatch('ResizeContent');
                                        } catch(e) {}
                                    }
                                });
                                
                                // Update button states again after all initializations
                                self.updateMoveButtonStates();
                            }, 100);
                        } catch(e) {
                            console.error('TinyMCE initialization in repeater failed:', e);
                        }
                    }, 500);
                }
            }, 500); // Länger warten
            
        },
        
        resetEnhancedPreview: function($preview, allowedTypes) {
            // Platzhalter je nach erlaubten Typen
            var placeholderText = '';
            var placeholderIcon = 'fa-file';
            
            if (allowedTypes.includes('image') && allowedTypes.includes('video')) {
                placeholderText = 'Bild oder Video auswählen';
                placeholderIcon = 'fa-file-image-o';
            } else if (allowedTypes.includes('image')) {
                placeholderText = 'Bild auswählen';
                placeholderIcon = 'fa-image';
            } else if (allowedTypes.includes('video')) {
                placeholderText = 'Video auswählen';
                placeholderIcon = 'fa-video-camera';
            }
            
            $preview.html(
                '<div class="media-placeholder">' +
                    '<i class="fa ' + placeholderIcon + '"></i>' +
                    '<p>' + placeholderText + '</p>' +
                '</div>'
            ).show();
        },
        
        /**
         * Update Enhanced Media Preview when media is selected
         */
        updateEnhancedMediaPreview: function($input, $preview) {
            var filename = $input.val();
            
            if (!filename) {
                // Kein Medium: Placeholder anzeigen
                var allowedTypes = $input.data('allowed-types') || [];
                this.resetEnhancedPreview($preview, allowedTypes);
                return;
            }
            
            // Dateityp bestimmen
            var ext = filename.split('.').pop().toLowerCase();
            var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
            var videoExts = ['mp4', 'webm', 'ogv', 'mov'];
            
            var html = '';
            
            if (imageExts.indexOf(ext) !== -1) {
                // Bild-Preview
                var mediaUrl = '';
                if (typeof rex !== 'undefined' && rex.media_manager_url) {
                    mediaUrl = rex.media_manager_url + 'rex_mediapool_preview/' + encodeURIComponent(filename);
                } else {
                    mediaUrl = '../media/' + encodeURIComponent(filename);
                }
                
                html = '<div class="media-item media-item-image">' +
                       '<img src="' + mediaUrl + '" alt="' + filename + '" />' +
                       '</div>';
                       
            } else if (videoExts.indexOf(ext) !== -1) {
                // Video-Preview
                var mediaUrl = '../media/' + encodeURIComponent(filename);
                html = '<div class="media-item media-item-video" style="position: relative; padding-bottom: 56.25%; height: 0;">' +
                       '<video preload="metadata" muted style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">' +
                       '<source src="' + mediaUrl + '" type="video/' + ext + '" />' +
                       '</video>' +
                       '<div class="media-overlay"><i class="fa fa-play-circle"></i></div>' +
                       '</div>';
                       
            } else {
                // Unbekannter Dateityp
                html = '<div class="media-placeholder">' +
                       '<i class="fa fa-file"></i>' +
                       '<p>' + filename + '</p>' +
                       '</div>';
            }
            
            $preview.html(html).show();
        },
        
        /**
         * Initialize Grid Views
         */
        initGridViews: function() {
            $('.repeater-grid-view').each(function() {
                var $container = $(this);
                var columns = $container.data('grid-columns') || 3;
                $container.css('grid-template-columns', 'repeat(' + columns + ', 1fr)');
            });
        },
        
        /**
         * Initialize Move Buttons for all Repeaters
         */
        initMoveButtons: function() {
            var self = this;
            
            // Move Up Button
            $(document).on('click', '.btn-move-up', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Ignore clicks on disabled buttons
                if ($(this).prop('disabled')) {
                    return;
                }
                
                var $item = $(this).closest('.repeater-item');
                var $container = $item.closest('.repeater-container');
                var $prevItem = $item.prev('.repeater-item:not(.repeater-item-template)');
                
                if ($prevItem.length > 0) {
                    // Destroy TinyMCE instances before moving (like mblock does)
                    $container.find('.tiny-editor').each(function() {
                        var editorId = $(this).attr('id');
                        if (editorId && typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                            try {
                                tinymce.get(editorId).save(); // Save content first
                                tinymce.get(editorId).remove(); // Remove instance
                            } catch(e) {
                                console.warn('TinyMCE remove error:', e);
                            }
                        }
                    });
                    
                    // Move item
                    $item.insertBefore($prevItem);
                    self.updateRepeaterIndices($container);
                    self.updateMoveButtonStates();
                    
                    // Re-initialize widgets (like mblock does)
                    setTimeout(function() {
                        $container.find('.repeater-item:not(.repeater-item-template)').each(function() {
                            $(this).trigger('rex:ready', [$(this)]);
                        });
                        
                        // Force TinyMCE editor containers to be visible after re-init
                        setTimeout(function() {
                            $container.find('textarea.tiny-editor').each(function() {
                                var $ta = $(this);
                                var $editorContainer = $ta.siblings('.tox-tinymce');
                                
                                if ($editorContainer.length > 0 && !$editorContainer.is(':visible')) {
                                    $editorContainer.show();
                                }
                            });
                        }, 200);
                        
                        // Visual feedback
                        $item.css('background', '#d9edf7');
                        setTimeout(function() {
                            $item.css('background', '');
                        }, 300);
                    }, 100);
                }
            });
            
            // Move Down Button
            $(document).on('click', '.btn-move-down', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Ignore clicks on disabled buttons
                if ($(this).prop('disabled')) {
                    return;
                }
                
                var $item = $(this).closest('.repeater-item');
                var $container = $item.closest('.repeater-container');
                var $nextItem = $item.next('.repeater-item:not(.repeater-item-template)');
                
                if ($nextItem.length > 0) {
                    // Destroy TinyMCE instances before moving (like mblock does)
                    $container.find('.tiny-editor').each(function() {
                        var editorId = $(this).attr('id');
                        if (editorId && typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                            try {
                                tinymce.get(editorId).save(); // Save content first
                                tinymce.get(editorId).remove(); // Remove instance
                            } catch(e) {
                                console.warn('TinyMCE remove error:', e);
                            }
                        }
                    });
                    
                    // Move item
                    $item.insertAfter($nextItem);
                    self.updateRepeaterIndices($container);
                    self.updateMoveButtonStates();
                    
                    // Re-initialize widgets (like mblock does)
                    setTimeout(function() {
                        $container.find('.repeater-item:not(.repeater-item-template)').each(function() {
                            $(this).trigger('rex:ready', [$(this)]);
                        });
                        
                        // Force TinyMCE editor containers to be visible after re-init
                        setTimeout(function() {
                            $container.find('textarea.tiny-editor').each(function() {
                                var $ta = $(this);
                                var $editorContainer = $ta.siblings('.tox-tinymce');
                                
                                if ($editorContainer.length > 0 && !$editorContainer.is(':visible')) {
                                    $editorContainer.show();
                                }
                            });
                        }, 200);
                        
                        // Visual feedback
                        $item.css('background', '#d9edf7');
                        setTimeout(function() {
                            $item.css('background', '');
                        }, 300);
                    }, 100);
                }
            });
            
            // Update button states after initialization
            this.updateMoveButtonStates();
        },
        
        /**
         * Update repeater indices after reordering
         */
        updateRepeaterIndices: function($container) {
            $container.find('.repeater-item:not(.repeater-item-template)').each(function(index) {
                var $item = $(this);
                $item.attr('data-index', index);
                
                // Input-Namen aktualisieren - nur den ersten numerischen Index ersetzen
                $item.find('input, textarea, select').each(function() {
                    var $input = $(this);
                    var name = $input.attr('name');
                    
                    if (name && name.indexOf('[') !== -1) {
                        // Ersetze nur den ERSTEN numerischen Index: items[2][title] -> items[0][title]
                        // Lässt verschachtelte Indizes unberührt: items[2][nested][1] bleibt bei [1] für nested
                        var newName = name.replace(/\[(\d+)\]/, '[' + index + ']');
                        $input.attr('name', newName);
                    }
                });
            });
            
            // Button states aktualisieren
            this.updateMoveButtonStates();
            
            // Für Module: Serialisiere Daten zurück ins JSON hidden field
            this.serializeModuleData($container);
        },
        
        /**
         * Serialisiert Repeater-Daten zurück ins Module hidden field (yform_cb_data_storage)
         */
        serializeModuleData: function($container) {
            // Prüfe ob wir in einem Modul sind (hat hidden field yform_cb_data_storage)
            var $hiddenField = $('#yform_cb_data_storage');
            if ($hiddenField.length === 0) {
                return; // Nicht in einem Modul
            }
            
            try {
                // Parse aktuelles JSON
                var data = JSON.parse($hiddenField.val() || '{}');
                
                // Hole field name aus Container (z.B. "items")
                var fieldName = $container.data('field');
                if (!fieldName) {
                    return;
                }
                
                // Sammle Items in korrekter Reihenfolge
                var items = [];
                $container.find('.repeater-item:not(.repeater-item-template)').each(function() {
                    var $item = $(this);
                    var itemData = {};
                    
                    // Sammle alle Inputs für dieses Item
                    $item.find('input, textarea, select').each(function() {
                        var $input = $(this);
                        var name = $input.attr('name');
                        
                        // Nur Inputs für diesen Repeater (z.B. items[0][title])
                        if (name && name.indexOf(fieldName + '[') === 0) {
                            // Extrahiere Feldname: items[0][title] -> title
                            var match = name.match(/\[\d+\]\[([^\]]+)\]/);
                            if (match) {
                                var key = match[1];
                                var value = $input.val();
                                
                                // Handle Checkboxen
                                if ($input.attr('type') === 'checkbox') {
                                    value = $input.is(':checked') ? $input.val() || '1' : '';
                                }
                                
                                itemData[key] = value;
                            }
                        }
                    });
                    
                    // Nur hinzufügen wenn Item Daten hat
                    if (Object.keys(itemData).length > 0) {
                        items.push(itemData);
                    }
                });
                
                // Update data object
                data[fieldName] = items;
                
                // Schreibe zurück ins hidden field
                $hiddenField.val(JSON.stringify(data));
                
            } catch(e) {
                console.error('Fehler beim Serialisieren der Module-Daten:', e);
            }
        },
        
        /**
         * Update move button states (disable if first/last)
         */
        updateMoveButtonStates: function() {
            $('.repeater-container').each(function() {
                var $container = $(this);
                var $items = $container.find('.repeater-item:not(.repeater-item-template)');
                
                $items.each(function(index) {
                    var $item = $(this);
                    var $upBtn = $item.find('.btn-move-up');
                    var $downBtn = $item.find('.btn-move-down');
                    
                    // Erster Item: Up-Button deaktivieren
                    $upBtn.prop('disabled', index === 0);
                    
                    // Letzter Item: Down-Button deaktivieren
                    $downBtn.prop('disabled', index === $items.length - 1);
                });
            });
        },

        /**
         * Update Content Builder Media Preview
         */
        updateContentBuilderMediaPreview: function($preview, mediaFile) {
            var ext = mediaFile.split('.').pop().toLowerCase();
            var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1;
            var isVideo = ['mp4', 'webm', 'ogg', 'mov'].indexOf(ext) !== -1;
            
            var html = '';
            
            if (isImage) {
                var mediaUrl = '/media/' + mediaFile;
                // Versuche Media Manager URL wenn verfügbar
                if (typeof rex !== 'undefined' && rex.media_manager) {
                    mediaUrl = rex.media_manager.getUrl('builder_preview', mediaFile);
                }
                html = '<div class="cb-media-preview-item">';
                html += '<div class="cb-media-container">';
                html += '<img src="' + mediaUrl + '" alt="' + mediaFile + '" />';
                html += '</div>';
                html += '<span class="cb-media-filename">' + mediaFile + '</span>';
                html += '</div>';
            } else if (isVideo) {
                var mediaUrl = '/media/' + mediaFile;
                html = '<div class="cb-media-preview-item cb-media-video">';
                html += '<div class="cb-media-container">';
                html += '<video controls preload="metadata">';
                html += '<source src="' + mediaUrl + '" />';
                html += '</video>';
                html += '</div>';
                html += '<span class="cb-media-filename">' + mediaFile + '</span>';
                html += '</div>';
            } else {
                html = '<div class="cb-media-preview-item cb-media-file">';
                html += '<i class="fa fa-file"></i>';
                html += '<span class="cb-media-filename">' + mediaFile + '</span>';
                html += '</div>';
            }
            
            $preview.html(html);
        },
        
        /**
         * Initialisiere Enhanced Media Widgets
         */
        initEnhancedMediaWidgets: function() {
            // Setze last-value für alle enhanced media inputs
            $('.media-enhanced-clickable').each(function() {
                var $container = $(this);
                var inputId = $container.data('input-id');
                
                if (inputId) {
                    var $input = $('#' + inputId);
                    
                    if ($input.length) {
                        $input.data('last-value', $input.val() || '');
                    }
                }
            });
        },
        
        /**
         * Update Enhanced Media Widget nach Medienauswahl
         */
        updateEnhancedMediaWidget: function($container, $input) {
            var mediaValue = $input.val();
            var types = $input.data('types') || '';
            
            if (mediaValue) {
                // Medium wurde ausgewählt - Preview anzeigen
                var self = this;
                $.ajax({
                    url: ContentBuilder.getAjaxUrl(),
                    type: 'POST',
                    data: {
                        ajax_action: 'get_media_preview',
                        media_file: mediaValue,
                        types: types
                    },
                    success: function(response) {
                        if (response.success && response.html) {
                            $container.html(response.html);
                        } else {
                            // Fallback: Einfache Preview
                            self.createSimpleMediaPreview($container, mediaValue, types);
                        }
                    },
                    error: function() {
                        // Fallback: Einfache Preview
                        self.createSimpleMediaPreview($container, mediaValue, types);
                    }
                });
            } else {
                // Kein Medium - Placeholder anzeigen
                this.showMediaPlaceholder($container, types);
            }
        },
        
        /**
         * Erstelle einfache Media Preview (Fallback)
         */
        createSimpleMediaPreview: function($container, mediaFile, types) {
            var ext = mediaFile.split('.').pop().toLowerCase();
            var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1;
            var isVideo = ['mp4', 'webm', 'ogg', 'mov'].indexOf(ext) !== -1;
            
            var html = '<div class="media-enhanced-preview">';
            
            if (isImage) {
                var mediaUrl = '/media/' + mediaFile;
                html += '<img src="' + mediaUrl + '" alt="' + mediaFile + '" />';
            } else if (isVideo) {
                var mediaUrl = '/media/' + mediaFile;
                html += '<video controls><source src="' + mediaUrl + '" /></video>';
            } else {
                html += '<div class="media-enhanced-file">';
                html += '<i class="fa fa-file"></i>';
                html += '<span>' + mediaFile + '</span>';
                html += '</div>';
            }
            
            html += '</div>';
            $container.html(html);
        },
        
        /**
         * Zeige Media Placeholder
         */
        showMediaPlaceholder: function($container, types) {
            var placeholderIcon = 'fa-cloud-upload';
            var placeholderText = 'Medium auswählen';
            
            if (types) {
                var typeArray = types.split(',');
                if (typeArray.indexOf('image') !== -1 && typeArray.indexOf('video') !== -1) {
                    placeholderIcon = 'fa-file-image-o';
                    placeholderText = 'Bild oder Video auswählen';
                } else if (typeArray.indexOf('image') !== -1) {
                    placeholderIcon = 'fa-image';
                    placeholderText = 'Bild auswählen';
                } else if (typeArray.indexOf('video') !== -1) {
                    placeholderIcon = 'fa-video-camera';
                    placeholderText = 'Video auswählen';
                }
            }
            
            var html = '<div class="media-placeholder-box">';
            html += '<i class="fa ' + placeholderIcon + '"></i>';
            html += '<p>' + placeholderText + '</p>';
            html += '</div>';
            
            $container.html(html);
        }
    };

    // Initialisierungsfunktion
    function initContentBuilder() {
        ContentBuilder.init();
        
        // Media Browser initialisieren, falls vorhanden
        if (window.MediaBrowser) {
            window.MediaBrowser.init();
        } else if (window.ContentBuilderMediaBrowser) {
            window.MediaBrowser = window.ContentBuilderMediaBrowser;
            window.MediaBrowser.init();
        }
    }

    window.ContentBuilder = ContentBuilder;

    // REDAXO Backend-Lifecycle: immer ueber rex:ready initialisieren
    $(document).on('rex:ready', function(event, container) {
        // Nur initialisieren, wenn Content Builder im geladenen Container vorhanden ist
        var $container = container ? $(container) : $(document);
        if ($container.find('.yform-content-builder').length > 0 || 
            $container.is('.yform-content-builder')) {
            initContentBuilder();
        }
    });

    $(function() {
        if ($('.yform-content-builder').length > 0) {
            initContentBuilder();
        }
    });

})(jQuery);
