<?php

namespace FriendsOfREDAXO\Builder\Api;

use FriendsOfREDAXO\Builder\Fields\FieldRegistry;
use FriendsOfREDAXO\Builder\Helper;
use FriendsOfREDAXO\Builder\ModalHelper;
use rex;
use rex_addon;
use rex_api_function;
use rex_api_result;
use rex_escape;
use rex_exception;
use rex_i18n;
use rex_media_category;
use rex_request;
use rex_response;
use rex_sql;
use rex_url;
use rex_file;

/**
 * API Handler für YForm Content Builder
 * 
 * Stellt Endpunkte für AJAX-Requests bereit:
 * - load_slice_form: Lädt das Formular für ein Element
 * - render_slice: Rendert ein Element mit Template
 * - load_media_categories: Lädt Medienpool-Kategorien
 * - load_media_list: Lädt Medienliste
 * - get_media_preview: Erzeugt Media-Preview HTML
 * 
 * Aufruf: /redaxo/index.php?rex-api-call=content_builder&action=<action>
 */
class ContentBuilderApi extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        $action = rex_request::request('action', 'string', '');

        if ($action === 'get_element_css') {
            $this->getElementCss();
            exit;
        }

        // Nur im Backend und für eingeloggte Benutzer
        if (!rex::isBackend() || !rex::getUser()) {
            return new rex_api_result(false, 'Zugriff verweigert');
        }

        $locale = $this->resolveBackendLocale();
        if ($locale !== '') {
            try {
                rex_i18n::setLocale($locale, false);
            } catch (rex_exception $e) {
                // Fallback: aktuelle REDAXO-Locale beibehalten.
            }
        }

        rex_response::cleanOutputBuffers();

        switch ($action) {
            case 'load_slice_form':
                $this->loadSliceForm();
                break;

            case 'render_slice':
                $this->renderSlice();
                break;

            case 'load_media_categories':
                $this->loadMediaCategories();
                break;

            case 'load_media_list':
                $this->loadMediaList();
                break;

            case 'get_media_preview':
                $this->getMediaPreview();
                break;

            default:
                rex_response::sendJson(['error' => 'Unbekannte Aktion: ' . $action]);
                exit;
        }

        exit;
    }

    protected function getElementCss(): void
    {
        rex_response::cleanOutputBuffers();

        $framework = trim((string) rex_request::request('framework', 'string', ''));
        $elementsParam = trim((string) rex_request::request('elements', 'string', ''));
        $requestedElements = [];
        if ($elementsParam !== '') {
            $requestedElements = array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $elementsParam)
            )));
        }

        $elementPaths = $this->getResolvedElementPaths();
        if ($requestedElements !== []) {
            $requestedLookup = array_flip($requestedElements);
            $elementPaths = array_filter(
                $elementPaths,
                static fn (string $key): bool => isset($requestedLookup[$key]),
                ARRAY_FILTER_USE_KEY
            );
        }

        $css = [];
        foreach ($elementPaths as $elementKey => $path) {
            $files = $this->getCssFilesForElement($path, $framework);
            foreach ($files as $file) {
                $content = rex_file::get($file);
                if (!is_string($content) || trim($content) === '') {
                    continue;
                }

                $css[] = '/* element: ' . $elementKey . ' | file: ' . basename($file) . ' */';
                $css[] = trim($content);
            }
        }

        $output = implode("\n\n", $css);
        rex_response::setStatus(rex_response::HTTP_OK);
        rex_response::setHeader('Cache-Control', 'public, max-age=3600');
        rex_response::sendContent($output, 'text/css; charset=UTF-8');
        exit;
    }

    /**
     * @return array<string, string>
     */
    protected function getResolvedElementPaths(): array
    {
        $sources = [];

        $customPaths = \rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_ELEMENT_PATHS',
            ['']
        ));
        foreach ((array) $customPaths as $customPath) {
            $path = rtrim((string) $customPath, '/\\');
            if ($path !== '' && is_dir($path)) {
                $sources[] = $path;
            }
        }

        $projectPath = rex_addon::get('project')->getPath('elements');
        if (is_dir($projectPath)) {
            $sources[] = rtrim($projectPath, '/\\');
        }

        $dataPath = rex_addon::get('builder')->getDataPath('elements');
        if (is_dir($dataPath)) {
            $sources[] = rtrim($dataPath, '/\\');
        }

        $addonPath = rex_addon::get('builder')->getPath('elements');
        if (is_dir($addonPath)) {
            $sources[] = rtrim($addonPath, '/\\');
        }

        $resolved = [];
        foreach ($sources as $sourcePath) {
            $entries = scandir($sourcePath);
            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $elementPath = $sourcePath . '/' . $entry;
                if (!is_dir($elementPath) || isset($resolved[$entry])) {
                    continue;
                }

                $resolved[$entry] = $elementPath;
            }
        }

        ksort($resolved);

        return $resolved;
    }

    /**
     * @return list<string>
     */
    protected function getCssFilesForElement(string $elementPath, string $framework): array
    {
        $candidates = [];

        if ($framework !== '') {
            $candidates[] = $elementPath . '/assets/' . $framework . '.css';
            $candidates[] = $elementPath . '/templates/' . $framework . '.css';
        }

        $candidates[] = $elementPath . '/assets/common.css';
        $candidates[] = $elementPath . '/templates/common.css';

        $files = [];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $files[] = $candidate;
            }
        }

        return $files;
    }

    protected function resolveBackendLocale(): string
    {
        $userLanguage = trim((string) rex::getUser()?->getLanguage());
        if ($userLanguage === '') {
            return rex_i18n::getLocale();
        }

        if (preg_match('/^[a-z]{2}_[a-z]{2}$/', $userLanguage) === 1) {
            return $userLanguage;
        }

        if (preg_match('/^[a-z]{2}$/', $userLanguage) === 1) {
            foreach (rex_i18n::getLocales() as $availableLocale) {
                if (str_starts_with($availableLocale, $userLanguage . '_')) {
                    return $availableLocale;
                }
            }
        }

        return rex_i18n::getLocale();
    }

    /**
     * Lädt das Formular für ein Element
     */
    protected function loadSliceForm(): void
    {
        $sliceType = rex_request::post('slice_type', 'string');
        $sliceData = rex_request::post('slice_data', 'array', []);

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

            FieldRegistry::renderFieldRowsGroup(
                $config['fields'],
                $modalFields,
                function (string $fieldName, array $fieldConfig) use ($sliceData): void {
                    FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
                }
            );
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
    }

    /**
     * Rendert ein Element mit Template
     */
    protected function renderSlice(): void
    {
        $sliceType = rex_request::post('slice_type', 'string');
        $sliceData = rex_request::post('slice_data', 'array', []);
        $framework = rex_request::post('framework', 'string', 'bootstrap');
        $availableElementsRaw = rex_request::post('available_elements', 'string', '');

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

            $available_elements = [];
            if ($availableElementsRaw !== '') {
                $decodedAvailableElements = json_decode($availableElementsRaw, true);
                if (is_array($decodedAvailableElements)) {
                    foreach ($decodedAvailableElements as $elementKey => $elementConfig) {
                        $normalizedKey = trim((string) $elementKey);
                        if ($normalizedKey === '' || !is_array($elementConfig)) {
                            continue;
                        }

                        $elementConfig['type'] = $elementConfig['type'] ?? $normalizedKey;
                        $elementConfig['key'] = $elementConfig['key'] ?? $normalizedKey;
                        $available_elements[$normalizedKey] = $elementConfig;
                    }
                }
            }

            if ($available_elements === []) {
                $elementKeys = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getAllElements();
                foreach ($elementKeys as $key) {
                    $config = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementConfig($key);
                    if ($config === null) {
                        continue;
                    }

                    $config['type'] = $key;
                    $config['key'] = $key;
                    $available_elements[$key] = $config;
                }
            }

            $groupedAvailableElements = [];
            foreach ($available_elements as $elementType => $config) {
                $category = trim((string) ($config['category'] ?? ''));
                if ($category === '') {
                    $category = 'sonstiges';
                }

                if (!isset($groupedAvailableElements[$category])) {
                    $groupedAvailableElements[$category] = [];
                }

                $groupedAvailableElements[$category][$elementType] = $config;
            }

            include $templateFile;
        } else {
            echo '<div class="alert alert-danger">Template nicht gefunden</div>';
        }
    }


    /**
     * Ermittelt den Pfad zu einem Element (mit Override-Support)
     */
    protected function getElementPath(string $elementType): string
    {
        $customPaths = \rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_ELEMENT_PATHS',
            ['']
        ));

        foreach ($customPaths as $customPath) {
            if ($customPath === '') {
                continue;
            }

            $elementPath = rtrim($customPath, '/\\') . '/' . $elementType;
            if (is_dir($elementPath)) {
                return $elementPath;
            }
        }

        // Zuerst in project/elements suchen (Override)
        $projectPath = rex_addon::get('project')->getPath('elements/' . $elementType);
        if (is_dir($projectPath)) {
            return $projectPath;
        }

        // Dann in data/addons/builder/elements (User-Override)
        $dataPath = rex_addon::get('builder')->getDataPath('elements/' . $elementType);
        if (is_dir($dataPath)) {
            return $dataPath;
        }

        // Standard: Addon-Pfad
        return rex_addon::get('builder')->getPath('elements/' . $elementType);
    }

    /**
     * Rendert Tabs für das Formular.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sliceData
     */
    protected function renderFormWithTabs(array $config, array $sliceData): void
    {
        $tabId = 'tab_' . uniqid();

        echo '<ul class="nav nav-tabs" role="tablist">';
        $firstTab = true;
        foreach ($config['field_groups'] as $groupKey => $group) {
            $active = $firstTab ? ' class="active"' : '';
            echo '<li role="presentation"' . $active . '>';
            echo '<a href="#' . $tabId . '_' . $groupKey . '" role="tab" data-toggle="tab">';

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

            if (isset($group['fields']) && is_array($group['fields'])) {
                $groupFieldMap = [];
                foreach ($group['fields'] as $fieldName) {
                    if (isset($config['fields'][$fieldName])) {
                        $groupFieldMap[$fieldName] = $config['fields'][$fieldName];
                    }
                }
                FieldRegistry::renderFieldRowsGroup(
                    $groupFieldMap,
                    [],
                    function (string $fieldName, array $fieldConfig) use ($sliceData): void {
                        FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
                    }
                );
            }

            echo '</div>';
            $firstTab = false;
        }
        echo '</div>';
    }

    /**
     * Rendert den Settings-Modal Button.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sliceData
     */
    protected function renderSettingsModalButton(array $config, array $sliceData, bool $toolbarButton = false): void
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
        echo '<div class="modal-body">';

        if (isset($modalConfig['fieldsets']) && is_array($modalConfig['fieldsets'])) {
            $accordionConfig = [];
            if (isset($modalConfig['accordion']) && is_array($modalConfig['accordion'])) {
                $accordionConfig = $modalConfig['accordion'];
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
            foreach ($modalConfig['fieldsets'] as $fieldsetKey => $fieldsetDef) {
                if (!is_array($fieldsetDef)) {
                    continue;
                }

                $fsLabel = $fieldsetDef['label'] ?? $fieldsetKey;
                $fsIcon = $fieldsetDef['icon'] ?? 'fa-folder';
                $fsFields = isset($fieldsetDef['fields']) && is_array($fieldsetDef['fields']) ? $fieldsetDef['fields'] : [];
                $isOpen = match ($initialOpen) {
                    'all' => true,
                    'none' => false,
                    default => $fieldsetIndex === 0,
                };

                echo '<div class="builder-fieldset' . ($isOpen ? ' accordion-open' : '') . '">';
                echo '<button type="button" class="builder-fieldset-legend" aria-expanded="' . ($isOpen ? 'true' : 'false') . '">';
                echo '<span class="builder-fieldset-legend-main">';
                if ($fsIcon !== '') {
                    echo '<span class="builder-fieldset-legend-icon"><i class="fa ' . rex_escape((string) $fsIcon) . '"></i></span>';
                }
                echo '<span class="builder-fieldset-legend-label">' . rex_escape((string) $fsLabel) . '</span>';
                echo '</span>';
                echo '<span class="builder-fieldset-legend-toggle" aria-hidden="true">▼</span>';
                echo '</button>';

                echo '<div class="builder-fieldset-body' . ($isOpen ? '' : ' accordion-collapsed') . '">';
                $modalFieldMap = [];
                foreach ($fsFields as $fieldName) {
                    if (isset($config['fields'][$fieldName])) {
                        $modalFieldMap[$fieldName] = $config['fields'][$fieldName];
                    }
                }

                FieldRegistry::renderFieldRowsGroup(
                    $modalFieldMap,
                    [],
                    function (string $fieldName, array $fieldConfig) use ($sliceData): void {
                        FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
                    }
                );
                echo '</div>';
                echo '</div>';
                $fieldsetIndex++;
            }

            echo '</div>';
        } elseif (isset($modalConfig['fields']) && is_array($modalConfig['fields'])) {
            $modalFieldMap = [];
            foreach ($modalConfig['fields'] as $fieldName) {
                if (isset($config['fields'][$fieldName])) {
                    $modalFieldMap[$fieldName] = $config['fields'][$fieldName];
                }
            }
            FieldRegistry::renderFieldRowsGroup(
                $modalFieldMap,
                [],
                function (string $fieldName, array $fieldConfig) use ($sliceData): void {
                    FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
                }
            );
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
     * Lädt Medienpool-Kategorien
     */
    protected function loadMediaCategories(): void
    {
        $categories = [];
        $categoryList = rex_media_category::getRootCategories();

        foreach ($categoryList as $category) {
            $categories[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
            $this->addSubcategories($category, $categories);
        }

        rex_response::sendJson(['success' => true, 'categories' => $categories]);
    }

    /**
     * Fügt Unterkategorien rekursiv hinzu.
     *
     * @param array<int, array{id: int, name: string}> $categories
     */
    protected function addSubcategories(rex_media_category $parent, array &$categories, int $level = 1): void
    {
        foreach ($parent->getChildren() as $child) {
            $categories[] = [
                'id' => $child->getId(),
                'name' => str_repeat('— ', $level) . $child->getName(),
            ];
            $this->addSubcategories($child, $categories, $level + 1);
        }
    }

    /**
     * Lädt Medienliste für Kategorie
     */
    protected function loadMediaList(): void
    {
        $categoryId = rex_request::post('category_id', 'int', 0);
        $type = rex_request::post('type', 'string', 'image');
        $media = [];

        $sql = rex_sql::factory();
        $where = $categoryId > 0 ? 'category_id = ' . $categoryId : 'category_id = 0 OR category_id IS NULL';

        // Nach Typ filtern
        $typeCondition = '';
        if ($type === 'image') {
            $typeCondition = " AND (filetype LIKE 'image/%' OR filename REGEXP '\\.(jpg|jpeg|png|gif|svg|webp)$')";
        } elseif ($type === 'video') {
            $typeCondition = " AND (filetype LIKE 'video/%' OR filename REGEXP '\\.(mp4|webm|mov|avi)$')";
        }

        $sql->setQuery('SELECT filename, title FROM ' . rex::getTable('media') . ' WHERE (' . $where . ')' . $typeCondition . ' ORDER BY filename');

        foreach ($sql as $row) {
            $media[] = [
                'filename' => $row->getValue('filename'),
                'title' => $row->getValue('title'),
            ];
        }

        rex_response::sendJson(['success' => true, 'media' => $media]);
    }

    /**
     * Erzeugt Media-Preview HTML
     */
    protected function getMediaPreview(): void
    {
        $filename = rex_request::post('media_file', 'string', '');
        $types = rex_request::post('types', 'string', 'image');

        if (empty($filename)) {
            rex_response::sendJson(['success' => false]);
            return;
        }

        $isImage = Helper::isImage($filename);
        $isVideo = Helper::isVideo($filename);

        $html = '';
        if ($isImage) {
            $html = '<img src="' . rex_url::media($filename) . '" style="max-height:100px; max-width:100%;">';
        } elseif ($isVideo) {
            $html = '<video src="' . rex_url::media($filename) . '" style="max-height:100px; max-width:100%;" controls muted></video>';
        } else {
            $html = '<span class="label label-default">' . rex_escape($filename) . '</span>';
        }

        rex_response::sendJson(['success' => true, 'html' => $html]);
    }
}
