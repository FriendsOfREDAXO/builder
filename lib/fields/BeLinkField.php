<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_article;
use rex_clang;
use rex_escape;
use rex_i18n;
use rex_response;

/**
 * REDAXO Backend Link Widget
 */
class BeLinkField extends FieldAbstract
{
    private static bool $jsIncluded = false;
    public static function getType(): string
    {
        return 'be_link';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $categoryId = $fieldConfig['category'] ?? 1;

        $linkCounter = self::getNextLinkCounter();
        $inputId = 'REX_LINK_' . $linkCounter;

        // Artikel-Name für Anzeige ermitteln
        $artName = '';
        if ($value) {
            $article = rex_article::get($value);
            if ($article) {
                $artName = $article->getName();
            }
        }

        $openParams = '&clang=' . rex_clang::getCurrentId() . '&category_id=' . $categoryId;

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="input-group cb-link-widget-wrapper" data-widget-id="' . $linkCounter . '" data-clang="' . rex_clang::getCurrentId() . '">';
        
        // Sichtbares Textfeld mit Artikel-Name
        echo '<input class="form-control" type="text" ';
        echo 'value="' . rex_escape($artName) . '" ';
        echo 'id="' . $inputId . '_NAME" ';
        echo 'readonly />';
        
        // Hidden Field mit Artikel-ID
        echo '<input type="hidden" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'id="' . $inputId . '" ';
        echo 'value="' . rex_escape($value) . '" />';
        
        echo '<span class="input-group-btn">';
        echo '<a href="#" class="btn btn-popup rex-linkmap-btn" ';
        echo 'data-id="' . $inputId . '" ';
        echo 'data-params="' . rex_escape($openParams) . '" ';
        echo 'title="Seite auswählen">';
        echo '<i class="rex-icon rex-icon-open-linkmap"></i>';
        echo '</a>';
        echo '<a href="#" class="btn btn-popup rex-linkmap-delete-btn" ';
        echo 'data-id="' . $inputId . '" ';
        echo 'data-counter="' . $linkCounter . '" ';
        echo 'title="Link entfernen">';
        echo '<i class="rex-icon rex-icon-delete-link"></i>';
        echo '</a>';
        echo '</span>';
        echo '</div>';

        $this->closeFormGroup($notice);
        
        // JavaScript nur einmal einfügen
        if (!self::$jsIncluded) {
            self::$jsIncluded = true;
            echo $this->getEditScript();
        }
    }
    
    /**
     * JavaScript für Edit-Button
     */
    private function getEditScript(): string
    {
        $editTitle = rex_i18n::msg('content_editarticle');
        $nonce = rex_response::getNonce();

        return <<<JS
            <script nonce="{$nonce}">
            (function() {
                function initLinkEditButtons() {
                    document.querySelectorAll('.cb-link-widget-wrapper').forEach(function(wrapper) {
                        const widgetId = wrapper.dataset.widgetId;
                        const clang = wrapper.dataset.clang || 1;
                        const btnGroup = wrapper.querySelector('.input-group-btn');
                        
                        if (btnGroup && !btnGroup.querySelector('.cb-link-edit')) {
                            const editBtn = document.createElement('a');
                            editBtn.href = '#';
                            editBtn.className = 'btn btn-popup cb-link-edit';
                            editBtn.title = '{$editTitle}';
                            editBtn.dataset.widgetId = widgetId;
                            editBtn.dataset.clang = clang;
                            editBtn.innerHTML = '<i class="rex-icon fa-pencil"></i>';
                            btnGroup.appendChild(editBtn);
                        }
                    });
                }
                
                // Initial bei rex:ready
                jQuery(document).on('rex:ready', function() {
                    initLinkEditButtons();
                });
                
                // Bei Modal-Öffnung
                jQuery(document).on('shown.bs.modal', '.modal', function() {
                    initLinkEditButtons();
                });
                
                // Nach Modal-Schließen (für Haupt-Seite)
                jQuery(document).on('hidden.bs.modal', '.modal', function() {
                    initLinkEditButtons();
                });
                
                // Bei Pjax-Reload
                jQuery(document).on('pjax:end', function() {
                    initLinkEditButtons();
                });
                
                // Bei Content Builder Element-Hinzufügen/Änderungen
                jQuery(document).on('content-builder:element-added content-builder:element-updated', function() {
                    initLinkEditButtons();
                });
                
                // Mutation Observer für dynamisch hinzugefügte Widgets
                const observer = new MutationObserver(function(mutations) {
                    let shouldInit = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1 && (node.classList.contains('cb-link-widget-wrapper') || node.querySelector('.cb-link-widget-wrapper'))) {
                                    shouldInit = true;
                                }
                            });
                        }
                    });
                    if (shouldInit) {
                        initLinkEditButtons();
                    }
                });
                
                // Observer starten
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Click-Handler für Edit-Buttons
                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('.cb-link-edit');
                    if (!btn) return;
                    
                    e.preventDefault();
                    
                    const widgetId = btn.dataset.widgetId;
                    const clang = btn.dataset.clang || 1;
                    const input = document.getElementById('REX_LINK_' + widgetId);
                    
                    // Wenn leer: Zur Struktur springen um neue Seite anzulegen
                    if (!input || !input.value || input.value === '') {
                        const url = 'index.php?page=structure&clang=' + clang;
                        window.open(url, '_blank');
                        return;
                    }
                    
                    const articleId = parseInt(input.value, 10);
                    if (isNaN(articleId) || articleId < 1) {
                        // Fallback zur Struktur wenn ungültige ID
                        const url = 'index.php?page=structure&clang=' + clang;
                        window.open(url, '_blank');
                        return;
                    }
                    
                    // Artikel im Backend öffnen
                    const url = 'index.php?page=content/edit&article_id=' + articleId + '&clang=' + clang + '&mode=edit';
                    window.open(url, '_blank');
                });
            })();
            </script>
            JS;
    }
}
