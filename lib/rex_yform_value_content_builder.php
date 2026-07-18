<?php

use FriendsOfREDAXO\Builder\Helper;
use FriendsOfREDAXO\Builder\ModalHelper;
use FriendsOfREDAXO\Builder\Config\ElementModeResolver;
use FriendsOfREDAXO\Builder\Config\ThemeProviderBridge;
use FriendsOfREDAXO\Builder\Starter\StarterConfig as Config;

/**
 * YForm Content Builder Field
 * Slice-based content management für YForm
 */
class rex_yform_value_content_builder extends rex_yform_value_abstract
{
    private static $widgetCounters = [
        'media' => 0,
        'link' => 0,
    ];

    private const LEGACY_DEFAULT_PROFILE = 'default';
    private const LEGACY_DEFAULT_LANG = 'de';
    private const LEGACY_DEFAULT_TARGET = 'starter_text';
    private const LEGACY_DEFAULT_EDITOR_CLASSES = 'form-control cke5-editor yform-cb-legacy-editor';
    private const MAX_WIDTH_PATTERN = '#^[a-zA-Z0-9.%()\-\s+/]+$#';
    
    /**
     * Get next unique media counter (global über alle Instanzen)
     */
    private static function getNextMediaCounter()
    {
        if (!isset($GLOBALS['yform_cb_media_counter'])) {
            $GLOBALS['yform_cb_media_counter'] = 0;
        }
        return ++$GLOBALS['yform_cb_media_counter'];
    }
    
    public function enterObject()
    {
        $this->applyThemeContext();

        // AJAX-Anfragen behandeln - wenn AJAX, wird hier beendet
        if ($this->handleAjaxRequests()) {
            return; // AJAX wurde behandelt, normal processing stoppen
        }
        
        // Wert normalisieren
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        // Default-Wert wenn leer
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        // Werte für E-Mail und Datenbank setzen
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        // Template-Ausgabe
        if ($this->needsOutput() && $this->isViewable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                'value.content_builder.tpl.php',
                $this->getTemplateVars()
            );
        }

        // POST-Daten verarbeiten
        if (isset($_POST['FORM']) && isset($_POST['FORM'][$this->params['form_name']]['send'])) {
            $this->processFormData();
        }
    }

    private function applyThemeContext(): void
    {
        $addon = rex_addon::get('builder');

        $tableTheme = $this->resolveThemeForCurrentTable($addon);
        $fallbackTheme = trim((string) $addon->getConfig('theme', ''));
        $theme = $tableTheme !== '' ? $tableTheme : $fallbackTheme;

        ThemeProviderBridge::resetThemeContext();
        if ($theme !== '') {
            ThemeProviderBridge::setTheme($theme);
        }
    }

    private function resolveThemeForCurrentTable(rex_addon $addon): string
    {
        $tableName = trim((string) $this->getParam('main_table', ''));
        if ($tableName === '') {
            $tableName = trim((string) $this->getParam('table_name', ''));
        }

        if ($tableName === '') {
            return '';
        }

        $rawMapping = $addon->getConfig('table_themes', []);
        if (!is_array($rawMapping)) {
            return '';
        }

        if (!array_key_exists($tableName, $rawMapping)) {
            return '';
        }

        return trim((string) $rawMapping[$tableName]);
    }

    private function handleAjaxRequests(): bool
    {
        if (rex_request::isXmlHttpRequest()) {
            $action = rex_request::request('action', 'string', '');
            
            if ($action === 'load_slice_form') {
                $this->ajaxLoadSliceForm();
                return true;
            }
            
            if ($action === 'render_slice') {
                $this->ajaxRenderSlice();
                return true;
            }
        }
        
        return false;
    }

    protected function ajaxLoadSliceForm()
    {
        rex_response::cleanOutputBuffers();
        
        $sliceType = rex_request::post('slice_type', 'string');
        $sliceData = rex_request::post('slice_data', 'array', []);
        
        // Element-Pfad via getElementPath (unterstützt Overrides)
        $elementPath = $this->getElementPath($sliceType);
        Helper::loadElementI18n($elementPath);
        $configFile = $elementPath . '/config.php';
        
        if (!file_exists($configFile)) {
            echo '<div class="alert alert-danger">Element-Konfiguration nicht gefunden: ' . rex_escape($sliceType) . '</div>';
            return;
        }
        
        $config = include $configFile;
        
        if (!isset($config['fields']) || !is_array($config['fields'])) {
            echo '<div class="alert alert-danger">Element-Konfiguration fehlerhaft: ' . rex_escape($sliceType) . '</div>';
            return;
        }
        
        // YForm-Formular generieren
        echo '<form class="slice-form">';

        $hasSettingsModal = isset($config['settings_modal']) && is_array($config['settings_modal']);
        $helpModalConfig = ModalHelper::buildConfigForElementDir($elementPath . '/');

        if ($hasSettingsModal || $helpModalConfig !== null) {
            echo '<div class="clearfix" style="margin-bottom: 15px; display: flex; justify-content: flex-end; gap: 6px;">';

            if ($hasSettingsModal) {
                $this->renderSettingsModalButton($config, $sliceData, true);
            }

            if ($helpModalConfig !== null) {
                $helpModalConfig['_modal_id'] = ModalHelper::createModalId();
                ModalHelper::renderButton($helpModalConfig, true);
            }

            echo '</div>';

            // Modal-HTML ausserhalb des text-align:right Wrappers rendern
            if ($helpModalConfig !== null) {
                ModalHelper::renderModal($helpModalConfig);
            }
        }
        
        // Prüfen ob Tabs definiert sind
        if (isset($config['field_groups']) && is_array($config['field_groups'])) {
            $this->renderFormWithTabs($config, $sliceData);
        } else {
            // Standard: Alle Felder ohne Tabs (außer die im Modal)
            $modalFields = [];
            if (isset($config['settings_modal']['fieldsets']) && is_array($config['settings_modal']['fieldsets'])) {
                foreach ($config['settings_modal']['fieldsets'] as $fieldset) {
                    if (!is_array($fieldset) || !isset($fieldset['fields']) || !is_array($fieldset['fields'])) {
                        continue;
                    }
                    $modalFields = array_merge($modalFields, $fieldset['fields']);
                }
            } elseif (isset($config['settings_modal']['fields'])) {
                $modalFields = $config['settings_modal']['fields'];
            }
            
            foreach ($config['fields'] as $fieldName => $fieldConfig) {
                // Felder die im Modal sind überspringen
                if (!in_array($fieldName, $modalFields)) {
                    $this->renderFormField($fieldName, $fieldConfig, $sliceData);
                }
            }
        }
        
        echo '<div class="form-group slice-form-actions">';
        echo '<div class="slice-form-actions-note"><i class="fa fa-info-circle"></i> Diese Aktion übernimmt nur dieses Element in den Builder.</div>';
        echo '<div class="btn-group pull-right cb-slice-action-group">';
        echo '<button type="button" class="btn btn-success btn-slice-save cb-btn-apply"><i class="fa fa-check"></i> Element übernehmen</button>';
        echo '<button type="button" class="btn btn-danger btn-slice-cancel cb-btn-cancel"><i class="fa fa-times"></i> Bearbeitung verwerfen</button>';
        echo '</div>';
        echo '<div class="clearfix"></div>';
        echo '</div>';
        echo '</form>';
        exit;
    }
    
    protected function renderFormWithTabs(array $config, array $sliceData)
    {
        $tabId = 'tab_' . uniqid();
        
        echo '<ul class="nav nav-tabs" role="tablist">';
        $firstTab = true;
        foreach ($config['field_groups'] as $groupKey => $group) {
            $active = $firstTab ? ' class="active"' : '';
            echo '<li role="presentation"' . $active . '>';
            echo '<a href="#' . $tabId . '_' . $groupKey . '" role="tab" data-toggle="tab">';
            
            // Icon anzeigen falls vorhanden
            if (!empty($group['icon'])) {
                echo '<i class="fa ' . rex_escape($group['icon']) . '"></i> ';
            }
            
            echo rex_escape($group['label'] ?? $groupKey);
            echo '</a>';
            echo '</li>';
            $firstTab = false;
        }
        echo '</ul>';
        
        echo '<div class="tab-content" style="padding-top: 20px;">';
        $firstTab = true;
        foreach ($config['field_groups'] as $groupKey => $group) {
            $active = $firstTab ? ' active' : '';
            echo '<div role="tabpanel" class="tab-pane' . $active . '" id="' . $tabId . '_' . $groupKey . '">';
            
            // Felder dieser Gruppe rendern
            if (isset($group['fields']) && is_array($group['fields'])) {
                foreach ($group['fields'] as $fieldName) {
                    if (isset($config['fields'][$fieldName])) {
                        $this->renderFormField($fieldName, $config['fields'][$fieldName], $sliceData);
                    }
                }
            }
            
            echo '</div>';
            $firstTab = false;
        }
        echo '</div>';
    }
    
    protected function renderSettingsModalButton(array $config, array $sliceData, bool $toolbarButton = false)
    {
        $modalId = 'settings_modal_' . uniqid();
        $modalConfig = $config['settings_modal'];
        $label = $modalConfig['label'] ?? 'Einstellungen';
        $icon = $modalConfig['icon'] ?? 'fa-cog';

        if ($toolbarButton) {
            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#' . $modalId . '">';
        } else {
            echo '<div class="form-group">';
            echo '<button type="button" class="btn btn-default btn-block" data-toggle="modal" data-target="#' . $modalId . '">';
        }

        echo '<i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label);
        echo '</button>';

        if (!$toolbarButton) {
            echo '</div>';
        }
        
        // Modal HTML
        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog">';
        echo '<div class="modal-dialog modal-lg" role="document">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>';
        echo '<h4 class="modal-title"><i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label) . '</h4>';
        echo '</div>';
        echo '<div class="modal-body" style="text-align: left;">';
        
        // Fieldsets (mit Akkordeon) oder einfache Felder rendern
        if (isset($modalConfig['fieldsets']) && is_array($modalConfig['fieldsets'])) {
            $this->renderModalFieldsets($modalConfig['fieldsets'], $config, $sliceData);
        } elseif (isset($modalConfig['fields']) && is_array($modalConfig['fields'])) {
            // Legacy: einfache Felderliste
            foreach ($modalConfig['fields'] as $fieldName) {
                if (isset($config['fields'][$fieldName])) {
                    $this->renderFormField($fieldName, $config['fields'][$fieldName], $sliceData);
                }
            }
        }
        
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-primary" data-dismiss="modal">Übernehmen</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Modal mit echten <fieldset> Elementen rendern (mit optionalem Akkordeon)
     *
     * @param array<string, array<string, mixed>> $fieldsets
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sliceData
     */
    protected function renderModalFieldsets(array $fieldsets, array $config, array $sliceData)
    {
        $accordionConfig = [];
        if (isset($config['settings_modal']['accordion']) && is_array($config['settings_modal']['accordion'])) {
            $accordionConfig = $config['settings_modal']['accordion'];
        }

        $initialOpen = (string) ($accordionConfig['initial'] ?? 'none');
        if (!in_array($initialOpen, ['first', 'all', 'none'], true)) {
            $initialOpen = 'none';
        }

        $singleOpen = array_key_exists('single_open', $accordionConfig)
            ? (bool) $accordionConfig['single_open']
            : true;
        $collapsible = array_key_exists('collapsible', $accordionConfig)
            ? (bool) $accordionConfig['collapsible']
            : true;

        echo '<div class="builder-fieldsets" data-accordion-initial="' . rex_escape($initialOpen) . '" data-accordion-single="' . ($singleOpen ? '1' : '0') . '" data-accordion-collapsible="' . ($collapsible ? '1' : '0') . '">';
        
        $fieldsetIndex = 0;
        foreach ($fieldsets as $fieldsetKey => $fieldsetDef) {
            $fsLabel = $fieldsetDef['label'] ?? $fieldsetKey;
            $fsIcon = $fieldsetDef['icon'] ?? 'fa-folder';
            $fsFields = $fieldsetDef['fields'] ?? [];
            $isOpen = match ($initialOpen) {
                'all' => true,
                'none' => false,
                default => $fieldsetIndex === 0,
            };
            
            echo '<div class="builder-fieldset' . ($isOpen ? ' accordion-open' : '') . '">';
            echo '<button type="button" class="builder-fieldset-legend" aria-expanded="' . ($isOpen ? 'true' : 'false') . '">';
            echo '<span class="builder-fieldset-legend-main">';
            if (!empty($fsIcon)) {
                echo '<span class="builder-fieldset-legend-icon"><i class="fa ' . rex_escape($fsIcon) . '"></i></span>';
            }
            echo '<span class="builder-fieldset-legend-label">' . rex_escape($fsLabel) . '</span>';
            echo '</span>';
            echo '<span class="builder-fieldset-legend-toggle" aria-hidden="true">▼</span>';
            echo '</button>';
            
            // Body mit Feldern
            echo '<div class="builder-fieldset-body' . ($isOpen ? '' : ' accordion-collapsed') . '">';
            foreach ($fsFields as $fieldName) {
                if (isset($config['fields'][$fieldName])) {
                    $this->renderFormField($fieldName, $config['fields'][$fieldName], $sliceData);
                }
            }
            echo '</div>';
            echo '</div>';
            $fieldsetIndex++;
        }
        
        echo '</div>';
    }


    /**
     * Rendert ein generisches Modal für ein Feld im Repeater
     * Kann für verschiedene Modal-Typen verwendet werden (item_modal, media_modal, etc.)
     */
    protected function renderRepeaterFieldModal(string $itemId, int $index, string $fieldName, array $fieldConfig, array $item, string $baseFieldName, string $modalKey)
    {
        $modalConfig = $fieldConfig[$modalKey];
        $modalId = $itemId . '_' . $modalKey;
        $label = $modalConfig['label'] ?? 'Optionen';
        $icon = $modalConfig['icon'] ?? 'fa-cog';
        
        echo '<div class="form-group">';
        echo '<button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#' . $modalId . '">';
        echo '<i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label);
        echo '</button>';
        echo '</div>';
        
        // Modal HTML
        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog">';
        echo '<div class="modal-dialog" role="document">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>';
        echo '<h4 class="modal-title"><i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label) . '</h4>';
        echo '</div>';
        echo '<div class="modal-body" style="text-align: left;">';
        
        // Modal-Felder rendern
        if (isset($modalConfig['fields']) && is_array($modalConfig['fields'])) {
            foreach ($modalConfig['fields'] as $subFieldName) {
                if (isset($fieldConfig['fields'][$subFieldName])) {
                    $subValue = $item[$subFieldName] ?? '';
                    $fullFieldName = $fieldName . '[' . $index . '][' . $subFieldName . ']';
                    
                    // Für Subfelder müssen wir die Daten anders übergeben
                    $subData = [$baseFieldName => [$index => $item]];
                    $this->renderFormField($fullFieldName, $fieldConfig['fields'][$subFieldName], $subData);
                }
            }
        }
        
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-primary" data-dismiss="modal">Übernehmen</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    protected function renderRepeaterItemModal(string $itemId, int $index, string $fieldName, array $fieldConfig, array $item, string $baseFieldName)
    {
        $modalId = $itemId . '_modal';
        $modalConfig = $fieldConfig['item_modal'];
        $label = $modalConfig['label'] ?? 'Erweiterte Optionen';
        $icon = $modalConfig['icon'] ?? 'fa-cog';
        
        echo '<div class="form-group">';
        echo '<button type="button" class="btn btn-default btn-sm btn-block" data-toggle="modal" data-target="#' . $modalId . '">';
        echo '<i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label);
        echo '</button>';
        echo '</div>';
        
        // Modal HTML
        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog">';
        echo '<div class="modal-dialog" role="document">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>';
        echo '<h4 class="modal-title"><i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label) . '</h4>';
        echo '</div>';
        echo '<div class="modal-body" style="text-align: left;">';
        
        // Modal-Felder rendern
        if (isset($modalConfig['fields']) && is_array($modalConfig['fields'])) {
            foreach ($modalConfig['fields'] as $subFieldName) {
                if (isset($fieldConfig['fields'][$subFieldName])) {
                    $subValue = $item[$subFieldName] ?? '';
                    $fullFieldName = $fieldName . '[' . $index . '][' . $subFieldName . ']';
                    
                    // Für Subfelder müssen wir die Daten anders übergeben
                    $subData = [$baseFieldName => [$index => $item]];
                    $this->renderFormField($fullFieldName, $fieldConfig['fields'][$subFieldName], $subData);
                }
            }
        }
        
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-primary" data-dismiss="modal">Übernehmen</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function renderFormField(string $fieldName, array $fieldConfig, array $sliceData)
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (isset($fieldConfig['perm'])) {
            // 'admin' => nur für Admins
            if ($fieldConfig['perm'] === 'admin' && !rex::getUser()?->isAdmin()) {
                return;
            }
        }
        
        // Wert aus verschachtelten Arrays extrahieren (z.B. "items[0][title]")
        $value = $this->getNestedValue($fieldName, $sliceData);
        if ($value === null) {
            $value = $fieldConfig['default'] ?? '';
        }
        $label = $fieldConfig['label'] ?? $fieldName;
        $type = $fieldConfig['type'] ?? 'text';
        
        // Checkbox hat eigenes Label-Handling
        if ($type !== 'checkbox') {
            echo '<div class="form-group">';
            echo '<label>' . rex_escape($label) . '</label>';
        }
        
        switch ($type) {
            case 'text':
                echo '<input type="text" class="form-control" name="' . rex_escape($fieldName) . '" value="' . rex_escape($value) . '">';
                break;
                
            case 'checkbox':
                $checked = !empty($value) ? ' checked' : '';
                echo '<div class="form-group">';
                echo '<div class="checkbox">';
                echo '<label>';
                echo '<input type="checkbox" name="' . rex_escape($fieldName) . '" value="1"' . $checked . '> ';
                echo rex_escape($label);
                echo '</label>';
                echo '</div>';
                break;
                
            case 'textarea':
                echo '<textarea class="form-control" name="' . rex_escape($fieldName) . '" rows="5">' . rex_escape($value) . '</textarea>';
                break;
                
            case 'cke5':
                $editorId = 'ck' . uniqid();
                echo '<textarea id="' . $editorId . '" ';
                echo 'class="form-control cke5-editor" ';
                echo 'name="' . rex_escape($fieldName) . '" ';
                echo 'data-profile="default" ';
                echo 'rows="10">';
                echo rex_escape($value);
                echo '</textarea>';
                break;
                
            case 'be_media':
                // REDAXO Standard Media Widget
                $mediaCounter = self::getNextMediaCounter();
                
                $inputId = 'REX_MEDIA_' . $mediaCounter;
                
                // Robuste Behandlung von allowed_types
                $types = '';
                $allowedTypes = [];
                if (isset($fieldConfig['allowed_types'])) {
                    if (is_array($fieldConfig['allowed_types'])) {
                        $allowedTypes = $fieldConfig['allowed_types'];
                    } else {
                        $allowedTypes = array_map('trim', explode(',', $fieldConfig['allowed_types']));
                    }
                    $types = implode(',', $allowedTypes);
                }
                
                $wdgtClass = 'rex-js-widget rex-js-widget-media';
                
                echo '<div class="' . $wdgtClass . '">';
                echo '<div class="input-group">';
                echo '<input class="form-control content-builder-media-input" type="text" ';
                echo 'name="' . rex_escape($fieldName) . '" ';
                echo 'id="' . $inputId . '" ';
                echo 'value="' . rex_escape($value) . '" ';
                echo 'data-media-id="' . $mediaCounter . '" />';
                echo '<span class="input-group-btn">';
                
                $openMediaParams = $types ? ", '&types=" . rex_escape($types) . "'" : '';
                echo '<a href="#" class="btn btn-popup" ';
                echo 'onclick="openREXMedia(' . $mediaCounter . $openMediaParams . '); return false;" ';
                echo 'title="' . rex_i18n::msg('var_media_open') . '">';
                echo '<i class="rex-icon fa fa-folder-open"></i></a>';
                
                echo '<a href="#" class="btn btn-popup" ';
                echo 'onclick="viewREXMedia(' . $mediaCounter . $openMediaParams . '); return false;" ';
                echo 'title="' . rex_i18n::msg('var_media_view') . '">';
                echo '<i class="rex-icon fa fa-eye"></i></a>';
                
                echo '<a href="#" class="btn btn-popup btn-delete-cb-media" ';
                echo 'data-input-id="' . $inputId . '" ';
                echo 'onclick="return false;" ';
                echo 'title="' . rex_i18n::msg('var_media_remove') . '">';
                echo '<i class="rex-icon fa fa-trash"></i></a>';
                
                echo '</span></div>';
                
                // Eigene Preview Implementation
                echo '<div class="content-builder-media-preview" data-input-id="' . $inputId . '">';
                if ($value) {
                    $mediaPath = rex_path::media($value);
                    $isImage = Helper::isImage($value);
                    $isVideo = Helper::isVideo($value);
                    
                    if ($isImage && file_exists($mediaPath)) {
                        $mediaUrl = rex_url::media($value);
                        if (rex_addon::get('media_manager')->isAvailable()) {
                            $mediaUrl = rex_media_manager::getUrl('builder_preview', $value);
                        }
                        echo '<div class="cb-media-preview-item">';
                        echo '<div class="cb-media-container">';
                        echo '<img src="' . $mediaUrl . '" alt="' . rex_escape($value) . '" />';
                        echo '</div>';
                        echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
                        echo '</div>';
                    } elseif ($isVideo && file_exists($mediaPath)) {
                        $mediaUrl = rex_url::media($value);
                        echo '<div class="cb-media-preview-item cb-media-video">';
                        echo '<div class="cb-media-container">';
                        echo '<video controls preload="metadata">';
                        echo '<source src="' . $mediaUrl . '" />';
                        echo '</video>';
                        echo '</div>';
                        echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="cb-media-preview-item cb-media-file">';
                        echo '<i class="fa fa-file"></i>';
                        echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                echo '</div>';
                break;
                
            case 'be_link':
                // REDAXO Linkmap Widget
                self::$widgetCounters['link']++;
                $linkCounter = self::$widgetCounters['link'];
                
                $inputId = 'REX_LINK_' . $linkCounter;
                $categoryId = $fieldConfig['category'] ?? 1;
                
                // Artikel-Name für Anzeige ermitteln
                $artName = '';
                if ($value) {
                    $article = rex_article::get($value);
                    if ($article) {
                        $artName = $article->getName();
                    }
                }
                
                $openParams = '&clang=' . rex_clang::getCurrentId() . '&category_id=' . $categoryId;
                
                echo '<div class="input-group">';
                // Sichtbares Textfeld mit Artikel-Name
                echo '<input class="form-control" type="text" ';
                echo 'value="' . rex_escape($artName) . '" ';
                echo 'id="' . $inputId . '_NAME" ';
                echo 'readonly />';
                // Hidden Field mit Artikel-ID (wird vom Formular submitted)
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
                break;
                
            case 'radio_image':
                // Radio-Buttons mit Bildern/SVGs für visuelle Layout-Auswahl
                $options = $fieldConfig['options'] ?? [];
                $default = $fieldConfig['default'] ?? '';
                
                if (($value === null || $value === '') && !empty($default)) {
                    $value = $default;
                }
                
                // Eindeutiger Prefix für diese Radio-Gruppe (inkl. uniqid für Repeater)
                $groupId = 'radio_' . uniqid() . '_';
                
                echo '<div class="radio-image-group">';
                foreach ($options as $optValue => $optData) {
                    $checked = ($value == $optValue) ? ' checked' : '';
                    $inputId = $groupId . md5($optValue);
                    
                    // Label und Image aus optData extrahieren
                    $label = $optData;
                    $image = '';
                    if (is_array($optData)) {
                        $label = $optData['label'] ?? $optValue;
                        $image = $optData['image'] ?? '';
                    }
                    
                    echo '<div class="radio-image-item' . ($checked ? ' active' : '') . '">';
                    echo '<input type="radio" name="' . rex_escape($fieldName) . '" ';
                    echo 'id="' . $inputId . '" ';
                    echo 'value="' . rex_escape($optValue) . '"' . $checked . '>';
                    echo '<label for="' . $inputId . '">';
                    
                    if (!empty($image)) {
                        // Bild oder SVG Base64
                        if (strpos($image, 'data:image/svg+xml;base64,') === 0) {
                            echo '<img src="' . $image . '" alt="' . rex_escape($label) . '">';
                        } else {
                            echo '<img src="' . rex_escape($image) . '" alt="' . rex_escape($label) . '">';
                        }
                    }
                    
                    echo '<span class="radio-image-label">' . rex_escape($label) . '</span>';
                    echo '</label>';
                    echo '</div>';
                }
                echo '</div>';
                break;
            
            case 'color_swatches':
                // Farbauswahl mit visuellen Farbfeldern (wie MForm RadioColorField)
                $options = $fieldConfig['options'] ?? [];
                $default = $fieldConfig['default'] ?? '';
                
                if (($value === null || $value === '') && !empty($default)) {
                    $value = $default;
                }
                
                // Eindeutiger Prefix für diese Radio-Gruppe
                $groupId = 'color_' . uniqid() . '_';
                
                echo '<div class="color-swatches-group">';
                foreach ($options as $optValue => $optData) {
                    $checked = ($value == $optValue) ? ' checked' : '';
                    $inputId = $groupId . md5($optValue);
                    
                    // Label und Color extrahieren
                    $label = $optData;
                    $color = '#cccccc';
                    if (is_array($optData)) {
                        $label = $optData['label'] ?? $optValue;
                        $color = $optData['color'] ?? '#cccccc';
                    }
                    
                    // Transparenz-Style für spezielle Farben
                    $bgStyle = '';
                    if ($color === 'transparent' || $color === '') {
                        $bgStyle = 'background: linear-gradient(135deg, #fff 0%, #fff 45%, #ff0000 50%, #fff 55%, #fff 100%);';
                    } else {
                        $bgStyle = 'background-color: ' . $color . ';';
                    }
                    
                    // Border für helle Farben
                    $borderStyle = '';
                    if ($color === '#ffffff' || $color === '#fff' || $color === 'white') {
                        $borderStyle = 'border: 1px solid #ccc;';
                    }
                    
                    echo '<div class="color-swatch-item' . ($checked ? ' active' : '') . '">';
                    echo '<input type="radio" name="' . rex_escape($fieldName) . '" ';
                    echo 'id="' . $inputId . '" ';
                    echo 'value="' . rex_escape($optValue) . '"' . $checked . '>';
                    echo '<label for="' . $inputId . '" title="' . rex_escape($label) . '">';
                    echo '<span class="color-swatch" style="' . $bgStyle . $borderStyle . '"></span>';
                    echo '</label>';
                    echo '</div>';
                }
                echo '</div>';
                break;
                
            case 'choice':
                $choices = $fieldConfig['choices'] ?? [];
                $default = $fieldConfig['default'] ?? '';
                // Selectpicker ist standardmäßig aktiviert für einheitliches Styling
                // Kann mit 'selectpicker' => false explizit deaktiviert werden
                $useSelectpicker = $fieldConfig['selectpicker'] ?? true;
                $isMultiple = !empty($fieldConfig['multiple']);
                
                // Falls choices als String übergeben wurde (legacy)
                if (is_string($choices)) {
                    $parsed = [];
                    foreach (explode(',', $choices) as $choice) {
                        if (strpos($choice, '=') !== false) {
                            [$key, $val] = explode('=', $choice, 2);
                            $parsed[trim($key)] = trim($val);
                        } else {
                            $parsed[trim($choice)] = trim($choice);
                        }
                    }
                    $choices = $parsed;
                }
                
                // Multi-select: Wert immer als Array verarbeiten
                if ($isMultiple) {
                    if (is_string($value) && '' !== $value) {
                        // Legacy-Speicherung als kommagetrennte Liste tolerieren
                        $decoded = json_decode($value, true);
                        $value = is_array($decoded) ? $decoded : array_map('trim', explode(',', $value));
                    }
                    if (!is_array($value)) {
                        $value = [];
                    }
                    if ([] === $value && !empty($default)) {
                        $value = is_array($default) ? $default : [$default];
                    }
                } else {
                    // Default verwenden wenn kein Wert gesetzt
                    if (($value === null || $value === '') && !empty($default)) {
                        $value = $default;
                    }
                }
                
                // Farbdaten für Selectpicker mit Farbvorschau
                $choiceColors = $fieldConfig['choice_colors'] ?? [];
                // Icons/Piktogramme für Selectpicker (z.B. Layout-Auswahl)
                $choiceIcons = $fieldConfig['choice_icons'] ?? [];
                
                $selectClass = 'form-control';
                if ($useSelectpicker) {
                    $selectClass .= ' selectpicker';
                }

                $multiAttrs = '';
                $nameAttr = rex_escape($fieldName);
                if ($isMultiple) {
                    $nameAttr .= '[]';
                    $multiAttrs = ' multiple';
                    if ($useSelectpicker) {
                        $multiAttrs .= ' data-actions-box="true" data-live-search="true"';
                    }
                }

                echo '<select class="' . $selectClass . '" name="' . $nameAttr . '"' . $multiAttrs . '>';
                foreach ($choices as $choiceValue => $choiceLabel) {
                    if (is_array($choiceLabel)) {
                        echo '<optgroup label="' . rex_escape($choiceValue) . '">';
                        foreach ($choiceLabel as $subValue => $subLabel) {
                            if ($isMultiple) {
                                $selected = in_array((string) $subValue, array_map('strval', (array) $value), true) ? ' selected' : '';
                            } else {
                                $selected = ($value == $subValue) ? ' selected' : '';
                            }
                            $dataContent = '';
                            if ($useSelectpicker && isset($choiceIcons[$subValue])) {
                                $iconHtml = $choiceIcons[$subValue];
                                $escapedIcon = str_replace('"', '&quot;', $iconHtml);
                                $dataContent = ' data-content="' . $escapedIcon . ' ' . rex_escape($subLabel) . '"';
                            } elseif ($useSelectpicker && isset($choiceColors[$subValue])) {
                                $colorData = $choiceColors[$subValue];
                                $color = is_array($colorData) ? ($colorData['color'] ?? '') : $colorData;
                                if (!empty($color)) {
                                    $borderStyle = ($color === '#ffffff' || $color === 'transparent' || $color === '#fff') ? 'border: 1px solid #ccc;' : '';
                                    $bgColor = $color === 'transparent' ? 'background: repeating-linear-gradient(45deg, #f0f0f0, #f0f0f0 5px, #fff 5px, #fff 10px);' : 'background-color: ' . rex_escape($color) . ';';
                                    $dataContent = ' data-content="<span style=\'display:inline-block;width:16px;height:16px;margin-right:8px;vertical-align:middle;border-radius:3px;' . $bgColor . $borderStyle . '\'></span>' . rex_escape($subLabel) . '"';
                                }
                            }
                            echo '<option value="' . rex_escape($subValue) . '"' . $selected . $dataContent . '>' . rex_escape($subLabel) . '</option>';
                        }
                        echo '</optgroup>';
                    } else {
                        if ($isMultiple) {
                            $selected = in_array((string) $choiceValue, array_map('strval', (array) $value), true) ? ' selected' : '';
                        } else {
                            $selected = ($value == $choiceValue) ? ' selected' : '';
                        }
                        $dataContent = '';
                        if ($useSelectpicker && isset($choiceIcons[$choiceValue])) {
                            $iconHtml = $choiceIcons[$choiceValue];
                            $escapedIcon = str_replace('"', '&quot;', $iconHtml);
                            $dataContent = ' data-content="' . $escapedIcon . ' ' . rex_escape($choiceLabel) . '"';
                        } elseif ($useSelectpicker && isset($choiceColors[$choiceValue])) {
                            $colorData = $choiceColors[$choiceValue];
                            $color = is_array($colorData) ? ($colorData['color'] ?? '') : $colorData;
                            if (!empty($color)) {
                                $borderStyle = ($color === '#ffffff' || $color === 'transparent' || $color === '#fff') ? 'border: 1px solid #ccc;' : '';
                                $bgColor = $color === 'transparent' ? 'background: repeating-linear-gradient(45deg, #f0f0f0, #f0f0f0 5px, #fff 5px, #fff 10px);' : 'background-color: ' . rex_escape($color) . ';';
                                $dataContent = ' data-content="<span style=\'display:inline-block;width:16px;height:16px;margin-right:8px;vertical-align:middle;border-radius:3px;' . $bgColor . $borderStyle . '\'></span>' . rex_escape($choiceLabel) . '"';
                            }
                        }
                        echo '<option value="' . rex_escape($choiceValue) . '"' . $selected . $dataContent . '>' . rex_escape($choiceLabel) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'repeater':
                // Repeater-Felder
                // Wert aus sliceData extrahieren - nur der erste Teil des Feldnamens
                $baseFieldName = preg_replace('/\[.*$/', '', $fieldName);
                $items = $sliceData[$baseFieldName] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                
                // Item Modal Config prüfen
                $hasItemModal = isset($fieldConfig['item_modal']) && is_array($fieldConfig['item_modal']);
                $itemModalFields = $hasItemModal ? $fieldConfig['item_modal']['fields'] : [];
                
                // Alle Modals mit trigger_after sammeln (z.B. media_modal)
                $triggerModals = [];
                foreach ($fieldConfig as $configKey => $configValue) {
                    if (str_ends_with($configKey, '_modal') && $configKey !== 'item_modal' && is_array($configValue)) {
                        if (isset($configValue['trigger_after']) && isset($configValue['fields'])) {
                            $triggerModals[$configValue['trigger_after']] = $configKey;
                            // Diese Felder auch aus der Hauptansicht ausblenden
                            $itemModalFields = array_merge($itemModalFields, $configValue['fields']);
                        }
                    }
                }
                
                // View Config prüfen - kann auch dynamisch aus Slice-Daten kommen
                $view = $fieldConfig['view'] ?? 'list';
                $gridColumns = intval($fieldConfig['grid_columns'] ?? 3);
                
                // Prüfen ob view_mode in den Slice-Daten gesetzt ist (für dynamische Umschaltung)
                if (isset($sliceData['view_mode'])) {
                    $view = $sliceData['view_mode'];
                }
                
                $containerClass = 'repeater-container';
                
                if ($view === 'grid') {
                    $containerClass .= ' repeater-grid-view';
                }
                
                echo '<div class="repeater-wrapper">';
                echo '<div class="' . $containerClass . '" data-field="' . htmlspecialchars($fieldName) . '" data-view="' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8') . '" data-grid-columns="' . htmlspecialchars($gridColumns, ENT_QUOTES, 'UTF-8') . '">';
                
                // Template-Item erstellen wenn keine Items vorhanden sind
                if (empty($items) && $hasItemModal) {
                    $templateId = 'repeater_item_template_' . uniqid();
                    echo '<div class="repeater-item repeater-item-template" data-index="0" id="' . $templateId . '" style="display:none;">';
                    
                    // Move Buttons für Template
                    echo '<div class="move-buttons">';
                    echo '<button type="button" class="btn-move btn-move-up" title="Nach oben"><i class="fa fa-chevron-up"></i></button>';
                    echo '<button type="button" class="btn-move btn-move-down" title="Nach unten"><i class="fa fa-chevron-down"></i></button>';
                    echo '</div>';
                    
                    // Template-Felder rendern
                    $templateFieldCount = 0;
                    foreach ($fieldConfig['fields'] as $subFieldName => $subFieldConfig) {
                        if (in_array($subFieldName, $itemModalFields)) {
                            continue;
                        }
                        
                        $fullFieldName = $fieldName . '[0][' . $subFieldName . ']';
                        $subData = [$baseFieldName => [0 => []]];
                        $this->renderFormField($fullFieldName, $subFieldConfig, $subData);
                        
                        $templateFieldCount++;
                        
                        // Trigger-Modal nach diesem Feld anzeigen
                        if (isset($triggerModals[$subFieldName])) {
                            $modalKey = $triggerModals[$subFieldName];
                            $this->renderRepeaterFieldModal($templateId, 0, $fieldName, $fieldConfig, [], $baseFieldName, $modalKey);
                        }
                        
                        // Item-Modal Button nach 2. Feld
                        if ($hasItemModal && $templateFieldCount === 2) {
                            $this->renderRepeaterItemModal($templateId, 0, $fieldName, $fieldConfig, [], $baseFieldName);
                        }
                    }
                    
                    echo '<button type="button" class="btn btn-sm btn-danger btn-remove-repeater"><i class="fa fa-trash"></i></button>';
                    echo '</div>';
                }
                
                foreach ($items as $index => $item) {
                    $itemId = 'repeater_item_' . uniqid();
                    echo '<div class="repeater-item" data-index="' . $index . '" id="' . $itemId . '">';
                    
                    // Move Buttons für alle Repeater
                    echo '<div class="move-buttons">';
                    echo '<button type="button" class="btn-move btn-move-up" title="Nach oben"><i class="fa fa-chevron-up"></i></button>';
                    echo '<button type="button" class="btn-move btn-move-down" title="Nach unten"><i class="fa fa-chevron-down"></i></button>';
                    echo '</div>';
                    
                    // Haupt-Felder (nicht im Modal)
                    $fieldsRendered = 0;
                    foreach ($fieldConfig['fields'] as $subFieldName => $subFieldConfig) {
                        // Überspringen wenn Feld im Modal ist
                        if (in_array($subFieldName, $itemModalFields)) {
                            continue;
                        }
                        
                        $subValue = $item[$subFieldName] ?? '';
                        $fullFieldName = $fieldName . '[' . $index . '][' . $subFieldName . ']';
                        
                        // Für Subfelder müssen wir die Daten anders übergeben
                        $subData = [$baseFieldName => [$index => $item]];
                        $this->renderFormField($fullFieldName, $subFieldConfig, $subData);
                        
                        $fieldsRendered++;
                        
                        // Trigger-Modal nach diesem Feld anzeigen (z.B. media_modal nach image)
                        if (isset($triggerModals[$subFieldName])) {
                            $modalKey = $triggerModals[$subFieldName];
                            $this->renderRepeaterFieldModal($itemId, $index, $fieldName, $fieldConfig, $item, $baseFieldName, $modalKey);
                        }
                        
                        // Item-Modal Button nach dem 2. Feld einfügen
                        if ($hasItemModal && $fieldsRendered === 2) {
                            $this->renderRepeaterItemModal($itemId, $index, $fieldName, $fieldConfig, $item, $baseFieldName);
                        }
                    }
                    
                    echo '<button type="button" class="btn btn-sm btn-danger btn-remove-repeater"><i class="fa fa-trash"></i></button>';
                    echo '</div>';
                }
                
                echo '</div>'; // .repeater-container
                echo '<button type="button" class="btn btn-sm btn-primary btn-add-repeater"><i class="fa fa-plus"></i> ' . ($fieldConfig['add_label'] ?? 'Hinzufügen') . '</button>';
                echo '</div>'; // .repeater-wrapper
                break;
        }
        
        if (isset($fieldConfig['notice'])) {
            echo '<p class="help-block">' . rex_escape($fieldConfig['notice']) . '</p>';
        }
        
        echo '</div>';
    }

    protected function ajaxRenderSlice()
    {
        rex_response::cleanOutputBuffers();
        
        $sliceType = rex_request::post('slice_type', 'string');
        $sliceData = rex_request::post('slice_data', 'array', []);
        $framework = rex_request::post('framework', 'string', 'uikit');
        $framework = ThemeProviderBridge::normalizeFramework($framework);
        
        // Element-Pfad via getElementPath (unterstützt Overrides)
        $elementPath = $this->getElementPath($sliceType);
        Helper::loadElementI18n($elementPath);
        
        $templateCandidates = [
            $framework,
            'plain',
            'uikit',
            'bootstrap',
        ];

        $templateFile = '';
        foreach ($templateCandidates as $templateName) {
            $candidate = $elementPath . '/templates/' . $templateName . '.php';
            if (file_exists($candidate)) {
                $templateFile = $candidate;
                break;
            }
        }
        
        if ($templateFile !== '') {
            $elementData = $sliceData;
            $available_elements = $this->getAvailableElements();
            $groupedAvailableElements = [];
            foreach ($available_elements as $elementType => $config) {
                $category = trim((string) ($config['category'] ?? ''));
                if ('' === $category) {
                    $category = 'sonstiges';
                }
                $groupedAvailableElements[$category][$elementType] = $config;
            }
            include $templateFile;
        } else {
            echo '<div class="alert alert-danger">Template nicht gefunden</div>';
        }
        exit;
    }
    

    
    /**
     * Check if file is an image
     */
    protected function isImage(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
    }
    
    /**
     * Check if file is a video
     */
    protected function isVideo(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'ogg']);
    }
    
    /**
     * Get value from nested array using bracket notation (e.g. "items[0][title]")
     */
    protected function getNestedValue(string $key, array $data)
    {
        // Einfacher Key ohne Brackets
        if (strpos($key, '[') === false) {
            return $data[$key] ?? null;
        }
        
        // Bracket-Notation parsen: items[0][title] -> ['items', '0', 'title']
        preg_match_all('/([^\[\]]+)/', $key, $matches);
        $keys = $matches[1];
        
        $value = $data;
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    protected function processFormData()
    {
        $formName = $this->params['form_name'];
        $fieldId = $this->getId();
        $migrationFlagField = $fieldId . '__legacy_migrate';
        
        if (isset($_POST['FORM'][$formName][$fieldId])) {
            $postValue = $_POST['FORM'][$formName][$fieldId];
            $migrateLegacyOnSubmit = isset($_POST['FORM'][$formName][$migrationFlagField])
                && $this->asBool($_POST['FORM'][$formName][$migrationFlagField]);
            
            // Wenn bereits JSON-String (aus Hidden Field), direkt verwenden
            if (is_string($postValue)) {
                // Validiere dass es gültiges JSON ist
                $decoded = json_decode($postValue, true);
                if (json_last_error() === JSON_ERROR_NONE && $this->isValidBuilderPayload($postValue, $decoded)) {
                    $existingValue = trim((string) $this->getValue());
                    $hasMeaningfulExistingContent = $this->hasMeaningfulExistingContent($existingValue);

                    // Sicherheitsnetz: Leeres Slice-Array darf einen bestehenden Inhalt
                    // nicht unbeabsichtigt überschreiben (Race/Sync-Fehler im Backend).
                    if (is_array($decoded) && $decoded === [] && $hasMeaningfulExistingContent && !$migrateLegacyOnSubmit) {
                        $this->params['warning'][] = $fieldId;
                        $this->params['warning_messages'][$fieldId] = 'Leerer Content-Builder-Stand wurde erkannt. Der bestehende Inhalt wurde aus Sicherheitsgründen beibehalten.';
                    } else {
                        $this->setValue($postValue);
                    }
                } elseif ($migrateLegacyOnSubmit && $this->isLegacyModeEnabled() && $this->isLegacyHtmlString($postValue)) {
                    $migrationJson = $this->buildLegacyMigrationJson($postValue);
                    if (is_string($migrationJson)) {
                        $this->setValue($migrationJson);
                    } else {
                        $configuredTarget = trim((string) $this->getElement('legacy_migration_target', self::LEGACY_DEFAULT_TARGET));
                        if ($configuredTarget === '') {
                            $configuredTarget = self::LEGACY_DEFAULT_TARGET;
                        }

                        $this->setValue($postValue);
                        $this->params['warning'][] = $fieldId;
                        $this->params['warning_messages'][$fieldId] = 'Legacy-Inhalt konnte nicht migriert werden: Das konfigurierte Zielelement "' . $configuredTarget . '" ist in diesem Feld nicht verfügbar.';
                    }
                } elseif ($this->isLegacyModeEnabled() && $this->isLegacyHtmlString($postValue)) {
                    // Legacy-HTML explizit erlauben, damit bestehende Inhalte editierbar bleiben.
                    $this->setValue($postValue);
                } else {
                    // Niemals still auf leer setzen, wenn der Builder-POST fehlerhaft ist.
                    // Sonst werden bestehende Inhalte beim Speichern ungewollt gelöscht.
                    $this->params['warning'][] = $fieldId;
                    $this->params['warning_messages'][$fieldId] = 'Content-Builder konnte nicht verarbeitet werden. Der bestehende Inhalt wurde unverändert beibehalten.';
                }
            } else {
                // Falls als Array, zu JSON konvertieren
                $jsonValue = json_encode($postValue, JSON_UNESCAPED_UNICODE);
                if (is_string($jsonValue)) {
                    $this->setValue($jsonValue);
                } else {
                    $this->params['warning'][] = $fieldId;
                    $this->params['warning_messages'][$fieldId] = 'Content-Builder konnte nicht verarbeitet werden. Der bestehende Inhalt wurde unverändert beibehalten.';
                }
            }
            
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
            
            if ($this->saveInDb()) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
        }
    }

    protected function getTemplateVars(): array
    {
        $rawValue = (string) $this->getValue();
        $value = $this->parseValue();
        $framework = (string) $this->getElement('framework', 'uikit');
        $framework = ThemeProviderBridge::normalizeFramework($framework);
        $availableElements = $this->getAvailableElements();

        $legacyEnabled = $this->isLegacyModeEnabled();
        $legacyActive = $legacyEnabled && $this->isLegacyHtmlString($rawValue);

        $legacyProfile = trim((string) $this->getElement('legacy_cke5_profile', self::LEGACY_DEFAULT_PROFILE));
        if ($legacyProfile === '') {
            $legacyProfile = self::LEGACY_DEFAULT_PROFILE;
        }

        $legacyLang = trim((string) $this->getElement('legacy_cke5_lang', self::LEGACY_DEFAULT_LANG));
        if ($legacyLang === '') {
            $legacyLang = self::LEGACY_DEFAULT_LANG;
        }

        $migrationHintEnabled = $this->asBool($this->getElement('legacy_migration_hint', '1'));
        $migrationTarget = trim((string) $this->getElement('legacy_migration_target', ''));
        if ($migrationTarget === '') {
            $migrationTarget = self::LEGACY_DEFAULT_TARGET;
        }

        $migrationField = trim((string) $this->getElement('legacy_migration_field', 'text'));
        if ($migrationField === '') {
            $migrationField = 'text';
        }

        $wrapperMaxWidth = $this->normalizeWrapperMaxWidth($this->getElement('wrapper_max_width', $this->getElement('max_width', '')));

        $legacyEditorAttributes = $this->resolveLegacyEditorAttributes($legacyProfile, $legacyLang);
        $elementDefaults = $this->resolveElementDefaultsConfig();
        $elementDefaultsJson = json_encode($elementDefaults, JSON_UNESCAPED_UNICODE);
        if (!is_string($elementDefaultsJson) || $elementDefaultsJson === '') {
            $elementDefaultsJson = '{}';
        }
        
        $addon = rex_addon::get('builder');
        
        return [
            'value' => $value,
            'field_type' => 'content_builder',
            'field_name' => $this->getName(),
            'field_id' => $this->getFieldId(),
            'label' => $this->getLabel(),
            'attributes' => $this->getElement('attributes', ''),
            'notice' => $this->getElement('notice'),
            'required' => $this->getElement('required') ? true : false,
            'description' => $this->getElement('description', ''),
            'framework' => $framework,
            'available_elements' => $availableElements,
            'addon' => $addon,
            'legacy_mode_enabled' => $legacyEnabled,
            'legacy_is_active' => $legacyActive,
            'legacy_html' => $legacyActive ? $rawValue : '',
            'legacy_cke5_profile' => $legacyProfile,
            'legacy_cke5_lang' => $legacyLang,
            'legacy_editor_attributes' => $legacyEditorAttributes,
            'legacy_migration_hint' => $migrationHintEnabled,
            'legacy_migration_target' => $migrationTarget,
            'legacy_migration_field' => $migrationField,
            'wrapper_max_width' => $wrapperMaxWidth,
            'element_defaults_json' => $elementDefaultsJson,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveElementDefaultsConfig(): array
    {
        $defaults = [];

        $rawJson = trim((string) $this->getElement('element_defaults_json', ''));
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $defaults = $decoded;
            }
        }

        $sectionDefault = trim((string) $this->getElement('default_enable_section', ''));
        $containerDefault = trim((string) $this->getElement('default_enable_container', ''));

        if ($sectionDefault === '0' || $sectionDefault === '1' || $containerDefault === '0' || $containerDefault === '1') {
            if (!isset($defaults['*']) || !is_array($defaults['*'])) {
                $defaults['*'] = [];
            }

            if ($sectionDefault === '0' || $sectionDefault === '1') {
                $defaults['*']['enable_section'] = $sectionDefault;
            }

            if ($containerDefault === '0' || $containerDefault === '1') {
                $defaults['*']['enable_container'] = $containerDefault;
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, string>
     */
    protected function resolveLegacyEditorAttributes(string $legacyProfile, string $legacyLang): array
    {
        $rawAttributes = trim((string) $this->getElement('legacy_editor_attributes', ''));
        $attributes = $this->parseAttributeString($rawAttributes);

        $classTokens = [];
        if (isset($attributes['class'])) {
            $classTokens = preg_split('/\s+/', trim((string) $attributes['class'])) ?: [];
            $classTokens = array_values(array_filter($classTokens, static fn (string $token): bool => $token !== ''));
        }

        foreach (['form-control', 'yform-cb-legacy-editor'] as $requiredClass) {
            if (!in_array($requiredClass, $classTokens, true)) {
                $classTokens[] = $requiredClass;
            }
        }

        $hasEditorClass = in_array('cke5-editor', $classTokens, true) || in_array('tiny-editor', $classTokens, true);
        if (!$hasEditorClass) {
            $classTokens[] = 'cke5-editor';
        }

        $attributes['class'] = implode(' ', $classTokens);

        if (!isset($attributes['rows']) || trim((string) $attributes['rows']) === '') {
            $attributes['rows'] = '14';
        }

        if (!isset($attributes['data-profile']) || trim((string) $attributes['data-profile']) === '') {
            $attributes['data-profile'] = $legacyProfile;
        }

        if (!isset($attributes['data-lang']) || trim((string) $attributes['data-lang']) === '') {
            $attributes['data-lang'] = $legacyLang;
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    protected function parseAttributeString(string $rawAttributes): array
    {
        $attributes = [];
        if ($rawAttributes === '') {
            return $attributes;
        }

        preg_match_all("/([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\\s*=\\s*(?:\"([^\"]*)\"|'([^']*)'|([^\\s\"'=<>`]+)))?/", $rawAttributes, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = strtolower((string) ($match[1] ?? ''));
            if ($name === '') {
                continue;
            }

            $value = '';
            if (isset($match[2]) && $match[2] !== '') {
                $value = (string) $match[2];
            } elseif (isset($match[3]) && $match[3] !== '') {
                $value = (string) $match[3];
            } elseif (isset($match[4]) && $match[4] !== '') {
                $value = (string) $match[4];
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    protected function parseValue(): array
    {
        $value = $this->getValue();
        
        if (empty($value)) {
            return [];
        }

        $data = json_decode($value, true);
        
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    protected function isLegacyModeEnabled(): bool
    {
        return $this->asBool($this->getElement('legacy_cke5_enabled', '0'));
    }

    protected function buildLegacyMigrationJson(string $legacyHtml): ?string
    {
        $configuredTarget = trim((string) $this->getElement('legacy_migration_target', ''));
        $hasExplicitTarget = $configuredTarget !== '';
        if ($configuredTarget === '') {
            $configuredTarget = self::LEGACY_DEFAULT_TARGET;
        }

        $migrationTarget = $this->resolveMigrationTarget($configuredTarget, !$hasExplicitTarget);
        if ($migrationTarget === null) {
            return null;
        }

        $migrationField = trim((string) $this->getElement('legacy_migration_field', 'text'));
        if ($migrationField === '') {
            $migrationField = 'text';
        }

        $sliceData = $this->resolveSliceDefaults($migrationTarget);
        $sliceData[$migrationField] = $legacyHtml;

        $slice = [
            'id' => 'slice_' . uniqid(),
            'type' => $migrationTarget,
            'online' => true,
            'data' => $sliceData,
        ];

        $json = json_encode([$slice], JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '[]';
    }

    protected function resolveMigrationTarget(string $target, bool $allowFallback = true): ?string
    {
        $availableElements = $this->getAvailableElements();
        if (isset($availableElements[$target])) {
            return $target;
        }

        if (!$allowFallback) {
            return null;
        }

        if (isset($availableElements[self::LEGACY_DEFAULT_TARGET])) {
            return self::LEGACY_DEFAULT_TARGET;
        }

        foreach (array_keys($availableElements) as $elementKey) {
            return (string) $elementKey;
        }

        return self::LEGACY_DEFAULT_TARGET;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSliceDefaults(string $elementType): array
    {
        $defaults = $this->resolveElementDefaultsConfig();
        if (!is_array($defaults)) {
            return [];
        }

        $globalDefaults = [];
        if (isset($defaults['*']) && is_array($defaults['*'])) {
            $globalDefaults = $defaults['*'];
        }

        $typeDefaults = [];
        if (isset($defaults[$elementType]) && is_array($defaults[$elementType])) {
            $typeDefaults = $defaults[$elementType];
        }

        return array_merge($globalDefaults, $typeDefaults);
    }

    protected function isLegacyHtmlString(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return false;
        }

        if (strpos($trimmed, '<') !== false && strpos($trimmed, '>') !== false) {
            return true;
        }

        return trim(strip_tags($trimmed)) !== '';
    }

    protected function normalizeWrapperMaxWidth(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match(self::MAX_WIDTH_PATTERN, $value) !== 1) {
            return '';
        }

        return $value;
    }

    protected function isValidBuilderPayload(string $rawPayload, mixed $decodedPayload): bool
    {
        $trimmed = trim($rawPayload);
        if ($trimmed === '') {
            return false;
        }

        if (!is_array($decodedPayload)) {
            return false;
        }

        // Builder speichert seine Slices als JSON-Array. Andere JSON-Typen akzeptieren wir
        // absichtlich nicht, um versehentliche Datenverluste zu verhindern.
        return str_starts_with($trimmed, '[');
    }

    protected function hasMeaningfulExistingContent(string $rawValue): bool
    {
        $trimmed = trim($rawValue);
        if ($trimmed === '') {
            return false;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return $decoded !== [];
            }

            // JSON-Skalare sind für Builder-Inhalte nicht relevant.
            return false;
        }

        if ($this->isLegacyHtmlString($trimmed)) {
            return trim(strip_tags($trimmed)) !== '';
        }

        return true;
    }

    protected function asBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeElementKeyList(mixed $value): array
    {
        $list = $value;

        if (is_string($list)) {
            $decoded = json_decode($list, true);
            if (is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = array_map('trim', explode(',', $list));
            }
        }

        if (!is_array($list)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $list
        ), static fn (string $item): bool => $item !== '')));
    }

    /**
     * Lädt ALLE verfügbaren Elemente (für Definition/Config)
     * Ohne Filter durch allowed_elements
     */
    protected function getAllElementsForDefinition(): array
    {
        $addon = rex_addon::get('builder');
        $enableDemoElements = (bool) $addon->getConfig('enable_demo_elements', true);
        $bundledDemoKeys = array_flip(Config::getBundledDemoElementKeys());
        $elements = [];
        $customPaths = ElementModeResolver::getCustomPaths();
        
        // Replace ist nur das Signal, die mitgelieferten builder-Elemente
        // nicht zu laden. Externe AddOn-/Projekt-Elemente bleiben immer verfügbar.
        $demoPath = $addon->getPath('elements/');
        if (ElementModeResolver::shouldLoadBundledElements() && is_dir($demoPath)) {
            $dirs = scandir($demoPath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                $elementPath = $demoPath . $dir;
                $configFile = $elementPath . '/config.php';

                if (!$enableDemoElements || !isset($bundledDemoKeys[$dir])) {
                    continue;
                }
                
                if (is_dir($elementPath) && file_exists($configFile)) {
                    Helper::loadElementI18n($elementPath);
                    $config = include $configFile;
                    if (!is_array($config)) {
                        continue;
                    }
                    $config['_source'] = 'demo';
                    $config['_path'] = $elementPath;
                    $config['_addon'] = ElementModeResolver::resolveAddonKeyByPath($demoPath, 'demo');
                    $config['type'] = $dir;
                    $config['key'] = $dir;
                    $elements[$dir] = $config;
                }
            }
        }

        foreach ($customPaths as $customPath) {
            if (!is_dir($customPath)) {
                continue;
            }

            $dirs = scandir($customPath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                $elementPath = $customPath . $dir;
                $configFile = $elementPath . '/config.php';

                if (is_dir($elementPath) && file_exists($configFile)) {
                    Helper::loadElementI18n($elementPath);
                    $config = include $configFile;
                    if (!is_array($config)) {
                        continue;
                    }
                    $config['_source'] = 'custom';
                    $config['_path'] = $elementPath;
                    $config['_addon'] = ElementModeResolver::resolveAddonKeyByPath((string) $customPath, 'custom');
                    $config['type'] = $dir;
                    $config['key'] = $dir;
                    $elements[$dir] = $config;
                }
            }
        }

        $replaceKeepCoreElements = ElementModeResolver::getReplaceKeepCoreElements();
        if ($replaceKeepCoreElements !== [] && is_dir($demoPath)) {
            $elements = array_replace(
                $elements,
                $this->loadElementsByKeysFromBasePath($demoPath, $replaceKeepCoreElements, 'demo')
            );
        }

        return ElementModeResolver::filterElementsByEnabledAddons($elements);
    }

    /**
     * @param array<int, string> $elementKeys
     */
    protected function loadElementsByKeysFromBasePath(string $basePath, array $elementKeys, string $source): array
    {
        if (!is_dir($basePath)) {
            return [];
        }

        $requestedKeys = array_flip(array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $elementKeys
        ), static fn (string $value) => $value !== ''))));

        if ($requestedKeys === []) {
            return [];
        }

        $elements = [];
        foreach (array_keys($requestedKeys) as $elementKey) {
            $elementPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $elementKey;
            $configFile = $elementPath . '/config.php';

            if (!is_dir($elementPath) || !is_file($configFile)) {
                continue;
            }

            Helper::loadElementI18n($elementPath);
            $config = include $configFile;
            if (!is_array($config)) {
                continue;
            }

            $config['_source'] = $source;
            $config['_path'] = $elementPath;
            $config['_addon'] = ElementModeResolver::resolveAddonKeyByPath($basePath, $source);
            $config['type'] = $elementKey;
            $config['key'] = $elementKey;
            $elements[$elementKey] = $config;
        }

        return $elements;
    }

    /**
     * Lädt verfügbare Elemente mit optionalem Filter durch allowed_elements
     */
    protected function getAvailableElements(): array
    {
        // Alle Elemente laden
        $allElements = $this->getAllElementsForDefinition();

        // Elemente mit hidden=true ausblenden (z.B. Legacy-Elemente)
        $allElements = array_filter(
            $allElements,
            static function (mixed $config): bool {
                if (!is_array($config)) {
                    return false;
                }

                $hidden = $config['hidden'] ?? false;

                if (is_bool($hidden)) {
                    return !$hidden;
                }

                if (is_int($hidden)) {
                    return $hidden !== 1;
                }

                if (is_string($hidden)) {
                    return !in_array(strtolower(trim($hidden)), ['1', 'true', 'yes', 'on'], true);
                }

                return true;
            }
        );
        
        // Prüfen ob allowed_elements gesetzt ist
        $allowedElements = $this->getElement('allowed_elements');
        $preventSelfNestingElements = $this->normalizeElementKeyList($this->getElement('prevent_self_nesting'));

        $applyPreventSelfNesting = static function (array $elements, array $elementKeys): array {
            if ($elementKeys === []) {
                return $elements;
            }

            foreach ($elementKeys as $elementKey) {
                if (!isset($elements[$elementKey]) || !is_array($elements[$elementKey])) {
                    continue;
                }

                $elements[$elementKey]['prevent_self_nesting'] = true;
            }

            return $elements;
        };
        
        // Wenn allowed_elements leer ist oder nicht gesetzt: alle Elemente zurückgeben
        if (empty($allowedElements)) {
            return $applyPreventSelfNesting($allElements, $preventSelfNestingElements);
        }
        
        // allowed_elements kann als JSON-String gespeichert sein
        if (is_string($allowedElements)) {
            $decoded = json_decode($allowedElements, true);
            if (is_array($decoded)) {
                $allowedElements = $decoded;
            } else {
                // Komma-separierte Liste?
                $allowedElements = array_map('trim', explode(',', $allowedElements));
            }
        }
        
        // Nur erlaubte Elemente zurückgeben.
        // Falls alte/stale Keys gesetzt sind und dadurch kein Element mehr matcht,
        // auf die verfügbaren Elemente zurückfallen statt die Auswahl leer zu lassen.
        if (is_array($allowedElements) && !empty($allowedElements)) {
            $filtered = array_intersect_key($allElements, array_flip($allowedElements));
            if ($filtered !== []) {
                return $applyPreventSelfNesting($filtered, $preventSelfNestingElements);
            }

            return $applyPreventSelfNesting($allElements, $preventSelfNestingElements);
        }
        
        return $applyPreventSelfNesting($allElements, $preventSelfNestingElements);
    }

    protected function getElementPath(string $elementType): ?string
    {
        $elements = $this->getAvailableElements();
        
        if (isset($elements[$elementType]['_path'])) {
            return $elements[$elementType]['_path'];
        }

        $customPaths = rex_extension::registerPoint(new rex_extension_point(
            'BUILDER_ELEMENT_PATHS',
            ['']
        ));

        foreach ($customPaths as $customPath) {
            if ($customPath === '') {
                continue;
            }

            $candidate = rtrim($customPath, '/\\') . '/' . $elementType;
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        if (rex_addon::exists('project') && rex_addon::get('project')->isAvailable()) {
            $projectPath = rex_addon::get('project')->getPath('elements/' . $elementType);
            if (is_dir($projectPath)) {
                return $projectPath;
            }
        }

        $dataPath = rex_addon::get('builder')->getDataPath('elements/' . $elementType);
        if (is_dir($dataPath)) {
            return $dataPath;
        }
        
        // Fallback: Original Pfad
        $addonPath = rex_addon::get('builder')->getPath('elements/' . $elementType);
        return is_dir($addonPath) ? $addonPath : null;
    }

    public function renderSlice(string $elementType, array $elementData, int $index, string $sliceId, string $framework): string
    {
        $elementPath = $this->getElementPath($elementType);
        $elementLabel = ucfirst(str_replace('_', ' ', $elementType));
        
        if (!$elementPath || !is_dir($elementPath)) {
            return '<div class="alert alert-danger">Element not found: ' . rex_escape($elementType) . '</div>';
        }
        
        // Template-Datei finden
        $templateFile = '';
        foreach ([$framework, 'plain', 'uikit', 'bootstrap'] as $templateName) {
            $candidate = $elementPath . '/templates/' . $templateName . '.php';
            if (file_exists($candidate)) {
                $templateFile = $candidate;
                break;
            }
        }
        
        if ($templateFile === '') {
            return '<div class="alert alert-danger">Template not found: ' . rex_escape($elementType) . '</div>';
        }
        
        // Slice-Wrapper
        ob_start();
        ?>
        <div class="content-builder-slice" 
             data-slice-id="<?= rex_escape($sliceId) ?>"
             data-slice-type="<?= rex_escape($elementType) ?>"
             data-slice-index="<?= $index ?>">
            
            <div class="slice-toolbar" data-element-name="<?= rex_escape($elementLabel) ?>">
                <button type="button" class="btn btn-xs btn-default btn-slice-edit" title="Bearbeiten">
                    <i class="fa fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-xs btn-default btn-slice-move" title="Verschieben">
                    <i class="fa fa-arrows"></i>
                </button>
                <button type="button" class="btn btn-xs btn-danger btn-slice-delete" title="Löschen">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
            
            <div class="slice-rendered">
                <?php Helper::loadElementI18n(dirname(dirname($templateFile))); include $templateFile; ?>
            </div>
            
            <div class="slice-edit-form" style="display: none;">
                <!-- Wird per JS mit YForm-Formular gefüllt -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function getDescription(): string
    {
        return 'content_builder';
    }

    public function getDefinitions(): array
    {
        // Alle verfügbaren Elemente für Multiselect sammeln
        $elementChoices = $this->buildElementChoices();
        
        // Framework-Einstellung
        $frameworkField = [
            'type' => 'choice',
            'label' => 'Framework',
            'choices' => [
                'bootstrap' => 'Bootstrap',
                'uikit' => 'UIkit',
                'tailwind' => 'Tailwind',
                'plain' => 'Plain HTML'
            ],
            'default' => 'uikit'
        ];
        
        // Basis-Felder
        $values = [
            'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
            'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
            'framework' => $frameworkField,
        ];
        
        // Weitere Felder
        $values += [
            'allowed_elements' => [
                'type' => 'choice',
                'label' => 'Erlaubte Elemente (leer = alle)',
                'choices' => $elementChoices,
                'multiple' => true,
                'expanded' => false,
                'default' => '',
            ],
            'prevent_self_nesting' => [
                'type' => 'choice',
                'label' => 'Selbst-Verschachtelung verhindern',
                'choices' => $elementChoices,
                'multiple' => true,
                'expanded' => false,
                'default' => '',
                'notice' => 'Ausgewählte Elemente können nicht in sich selbst eingefügt werden (z.B. columns).',
            ],
            'default_enable_section' => [
                'type' => 'choice',
                'label' => 'Standard: Sektion aktiv',
                'choices' => [
                    '' => 'Element-Default verwenden',
                    '1' => 'Ja (aktiv)',
                    '0' => 'Nein (deaktiviert)',
                ],
                'default' => '',
                'notice' => 'Gilt global für neue Elemente über enable_section. Überschreibt nicht gespeicherte Inhalte.',
            ],
            'default_enable_container' => [
                'type' => 'choice',
                'label' => 'Standard: Container aktiv',
                'choices' => [
                    '' => 'Element-Default verwenden',
                    '1' => 'Ja (aktiv)',
                    '0' => 'Nein (deaktiviert)',
                ],
                'default' => '',
                'notice' => 'Gilt global für neue Elemente über enable_container. Überschreibt nicht gespeicherte Inhalte.',
            ],
            'element_defaults_json' => [
                'type' => 'textarea',
                'label' => 'Erweiterte Element-Defaults (JSON)',
                'default' => '',
                'notice' => 'Optionales JSON für Startwerte pro Elementtyp. Beispiel: {"*":{"enable_section":"0","enable_container":"0"},"cards":{"container_width":"uk-container-small"}}',
            ],
            'legacy_cke5_enabled' => [
                'type' => 'choice',
                'label' => 'Legacy-HTML mit CKE5 editierbar',
                'choices' => [
                    '0' => 'Nein',
                    '1' => 'Ja',
                ],
                'default' => '0',
                'notice' => 'Erlaubt die Bearbeitung alter HTML-Inhalte mit CKE5, statt sie als leer zu behandeln.',
            ],
            'legacy_cke5_profile' => [
                'type' => 'text',
                'label' => 'Legacy CKE5 Profil',
                'default' => self::LEGACY_DEFAULT_PROFILE,
                'notice' => 'Fallback für data-profile, wenn legacy_editor_attributes kein data-profile enthält.',
            ],
            'legacy_cke5_lang' => [
                'type' => 'text',
                'label' => 'Legacy CKE5 Sprache',
                'default' => self::LEGACY_DEFAULT_LANG,
                'notice' => 'Fallback für data-lang, wenn legacy_editor_attributes kein data-lang enthält.',
            ],
            'legacy_editor_attributes' => [
                'type' => 'text',
                'label' => 'Legacy Editor Attribute',
                'default' => 'class="' . self::LEGACY_DEFAULT_EDITOR_CLASSES . '" data-profile="' . self::LEGACY_DEFAULT_PROFILE . '" data-lang="' . self::LEGACY_DEFAULT_LANG . '" rows="14"',
                'notice' => 'Freier Attribute-String wie bei textarea. Beispiel CKE5: class="form-control cke5-editor yform-cb-legacy-editor" data-profile="default" data-lang="de" rows="14". Beispiel TinyMCE: class="form-control tiny-editor yform-cb-legacy-editor" data-profile="default" rows="14".',
            ],
            'legacy_migration_hint' => [
                'type' => 'choice',
                'label' => 'Migrations-Hinweis anzeigen',
                'choices' => [
                    '0' => 'Nein',
                    '1' => 'Ja',
                ],
                'default' => '1',
            ],
            'legacy_migration_target' => [
                'type' => 'choice',
                'label' => 'Legacy-Migration Zielelement',
                'choices' => $elementChoices,
                'default' => self::LEGACY_DEFAULT_TARGET,
                'notice' => 'Element-Typ, in den Legacy-HTML beim Wechsel in den modernen Builder übernommen wird.',
            ],
            'legacy_migration_field' => [
                'type' => 'text',
                'label' => 'Legacy-Migration Zielfeld (Key)',
                'default' => 'text',
                'notice' => 'Feldname im Zielelement, z. B. text, content oder body.',
            ],
            'wrapper_max_width' => [
                'type' => 'text',
                'label' => 'Wrapper max-width',
                'default' => '',
                'notice' => 'Optionale maximale Breite des Builder-Wrappers, z. B. 1400px, 60rem oder 100%. Alternativ wird max_width akzeptiert.',
            ],
            'description' => ['type' => 'text', 'label' => 'Beschreibung'],
            'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
        ];
        
        return [
            'type' => 'value',
            'name' => 'content_builder',
            'values' => $values,
            'description' => 'Slice-based Content Builder',
            'db_type' => ['text'],
        ];
    }

    public static function getListValue($params)
    {
        $value = (string) $params['subject'];
        
        if (empty($value)) {
            return '<em>-- Leer --</em>';
        }
        
        $data = json_decode($value, true);
        
        if (!is_array($data) || empty($data)) {
            if (trim($value) !== '') {
                return '<span class="label label-warning">classic</span>';
            }
            return '<em>-- Keine Elemente --</em>';
        }
        
        // Element-Übersicht mit Zähler
        $elements = [];
        foreach ($data as $slice) {
            $type = $slice['type'] ?? 'unknown';
            $label = ucfirst(str_replace('_', ' ', $type));
            
            if (!isset($elements[$label])) {
                $elements[$label] = 0;
            }
            $elements[$label]++;
        }
        
        $output = [];
        foreach ($elements as $label => $count) {
            if ($count > 1) {
                $output[] = $label . ' (' . $count . 'x)';
            } else {
                $output[] = $label;
            }
        }
        
        $summary = '<strong>' . count($data) . ' Element' . (count($data) !== 1 ? 'e' : '') . ':</strong> ' . implode(', ', $output);
        
        // Im Debug-Mode zusätzlich JSON anzeigen
        if (rex::isDebugMode()) {
            $summary .= '<br><details style="margin-top:5px;"><summary style="cursor:pointer; font-size:11px;">JSON (Debug)</summary>';
            $summary .= '<pre style="background:#f5f5f5; padding:10px; border-radius:3px; font-size:11px; overflow:auto; max-height:300px; margin-top:5px;">';
            $summary .= rex_escape(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $summary .= '</pre></details>';
        }
        
        return '<span>' . $summary . '</span>';
    }


    /**
     * List-Ansicht für Datenbank-Manager
     * Zeigt Element-Übersicht oder nur JSON mit Debug-Mode
     */
    /**
     * Baut Element-Choices für das Definitions-Formular
     * Statisch, damit es in getDefinitions() funktioniert
     */
    protected function buildElementChoices(): array
    {
        // Nutzt dieselbe Quelle/Logik wie der Editor (inkl. merge/replace und Extension Points).
        $elements = $this->getAllElementsForDefinition();

        /** @var array<string, array<string, string>> $choicesByCategory */
        $choicesByCategory = [];

        foreach ($elements as $elementKey => $config) {
            if (!is_array($config)) {
                continue;
            }

            $category = trim((string) ($config['category'] ?? ''));
            if ($category === '') {
                $category = 'other';
            }

            $label = trim((string) ($config['label'] ?? ''));
            if ($label === '') {
                $label = ucfirst(str_replace('_', ' ', (string) $elementKey));
            }

            $choicesByCategory[$category][(string) $elementKey] = $label;
        }

        ksort($choicesByCategory);

        /** @var array<string, string> $flatChoices */
        $flatChoices = [];
        foreach ($choicesByCategory as $category => $groupChoices) {
            asort($groupChoices, SORT_NATURAL | SORT_FLAG_CASE);
            $groupLabel = ucfirst(str_replace('_', ' ', $category));
            foreach ($groupChoices as $elementKey => $label) {
                // Flaches Choice-Format fuer YForm (verhindert array_flip-Warnings),
                // visuell gruppiert ueber den Kategorie-Prefix.
                $flatChoices[$elementKey] = $groupLabel . ' > ' . $label . ' [' . $elementKey . ']';
            }
        }

        return $flatChoices;
    }
}
