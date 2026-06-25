<?php

namespace FriendsOfRedaxo\Builder;

use rex_escape;

class PageBuilder
{
    /**
     * Renders the Page Builder workspace input interface.
     */
    public static function renderInput(string $fieldName, ?string $value, string $fieldId): string
    {
        $elements = ElementManager::getElements();
        
        // Prepare elements without path info for security/size in JS
        $jsElements = [];
        foreach ($elements as $key => $el) {
            $jsElements[$key] = [
                'key' => $el['key'] ?? $key,
                'label' => $el['label'] ?? $key,
                'icon' => $el['icon'] ?? 'fa-cube',
                'fields' => $el['fields'] ?? [],
            ];
        }
        
        $elementsJson = json_encode($jsElements, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
        $escapedValue = rex_escape($value ?: '[]');

        // Output editor HTML
        $html = '
        <div class="builder-editor-wrapper" id="builder_wrapper_' . rex_escape($fieldId) . '" data-field-id="' . rex_escape($fieldId) . '">
            <!-- Hidden textarea for database storage -->
            <textarea class="builder-value-textarea" id="' . rex_escape($fieldId) . '" name="' . rex_escape($fieldName) . '" style="display:none;">' . $escapedValue . '</textarea>

            <div class="builder-editor-container" data-elements=\'' . $elementsJson . '\'>
                <!-- Header Panel -->
                <div class="builder-header">
                    <div class="builder-header-title">
                        <i class="rex-icon fa-th-large"></i> Page Builder Workspace
                    </div>
                </div>

                <div class="builder-workspace-layout">
                    <!-- Left Sidebar (Sidebar Elements Library) -->
                    <div class="builder-sidebar">
                        <div class="sidebar-section-title">Layout-Zeilen</div>
                        <div class="sidebar-grid-layouts">
                            <div class="sidebar-item layout-item" data-layout="1fr" draggable="true">
                                <i class="rex-icon fa-square-o"></i> 1 Spalte (100%)
                            </div>
                            <div class="sidebar-item layout-item" data-layout="1fr 1fr" draggable="true">
                                <i class="rex-icon fa-columns"></i> 2 Spalten (50/50)
                            </div>
                            <div class="sidebar-item layout-item" data-layout="1fr 2fr" draggable="true">
                                <i class="rex-icon fa-columns"></i> 2 Spalten (33/66)
                            </div>
                            <div class="sidebar-item layout-item" data-layout="1fr 1fr 1fr" draggable="true">
                                <i class="rex-icon fa-th-large"></i> 3 Spalten (33/33/33)
                            </div>
                            <div class="sidebar-item layout-item" data-layout="1fr 1fr 1fr 1fr" draggable="true">
                                <i class="rex-icon fa-th"></i> 4 Spalten (25x4)
                            </div>
                        </div>

                        <div class="sidebar-section-title" style="margin-top:20px;">Inhaltselemente</div>
                        <div class="sidebar-content-elements">';

        foreach ($jsElements as $key => $el) {
            $html .= '
                            <div class="sidebar-item element-item" data-type="' . rex_escape($key) . '" draggable="true">
                                <i class="rex-icon ' . rex_escape($el['icon']) . '"></i> ' . rex_escape($el['label']) . '
                            </div>';
        }

        $html .= '
                        </div>
                    </div>

                    <!-- Visual Canvas Designer -->
                    <div class="builder-canvas-area">
                        <div class="builder-canvas" id="canvas_' . rex_escape($fieldId) . '">
                            <!-- Rows will be dynamically loaded here by JS -->
                        </div>
                        <div class="canvas-empty-state" id="empty_' . rex_escape($fieldId) . '">
                            <i class="rex-icon fa-info-circle"></i>
                            <p>Der Arbeitsbereich ist leer. Ziehen Sie ein Layout-Zeilen-Element hierher, um zu beginnen.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Settings Edit Modal (Scoped to this builder instance) -->
            <div class="builder-modal" id="modal_' . rex_escape($fieldId) . '" style="display:none;">
                <div class="builder-modal-dialog">
                    <div class="builder-modal-content">
                        <div class="builder-modal-header">
                            <h4 class="builder-modal-title">Element konfigurieren</h4>
                            <button type="button" class="builder-modal-close">&times;</button>
                        </div>
                        <div class="builder-modal-form">
                            <div class="builder-modal-body">
                                <!-- Element inputs will be dynamically generated here by JS -->
                            </div>
                            <div class="builder-modal-footer">
                                <button type="button" class="btn btn-default btn-cancel">Abbrechen</button>
                                <button type="button" class="btn btn-save btn-primary">Übernehmen</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';

        return $html;
    }

    /**
     * Specific input rendering helper for REDAXO modules.
     */
    public static function renderModuleInput(int $valueId, ?string $currentValue): string
    {
        return self::renderInput("REX_INPUT_VALUE[{$valueId}]", $currentValue, "rex_value_{$valueId}");
    }

    /**
     * Compiles page builder JSON layout into frontend HTML output.
     */
    public static function renderOutput(?string $jsonString): string
    {
        if (!$jsonString) {
            return '';
        }

        $data = json_decode($jsonString, true);
        if (!is_array($data)) {
            return '';
        }

        $html = '<div class="builder-content-wrap">';

        foreach ($data as $row) {
            $layout = $row['layout'] ?? '1fr';
            $html .= '<div class="builder-grid-row" style="display: grid; grid-template-columns: ' . rex_escape($layout) . '; gap: 30px; margin-bottom: 30px;">';

            $columns = $row['columns'] ?? [];
            foreach ($columns as $col) {
                $html .= '<div class="builder-grid-column">';

                $elements = $col['elements'] ?? [];
                foreach ($elements as $element) {
                    $type = $element['type'] ?? '';
                    $values = $element['values'] ?? [];

                    $elementConfig = ElementManager::getElement($type);
                    if ($elementConfig && isset($elementConfig['path'])) {
                        $templatePath = $elementConfig['path'] . '/template.php';
                        if (file_exists($templatePath)) {
                            // Render template in isolated clean variable scope
                            ob_start();
                            (static function(array $values, string $templatePath) {
                                include $templatePath;
                            })($values, $templatePath);
                            $html .= ob_get_clean();
                        }
                    }
                }

                $html .= '</div>'; // builder-grid-column
            }

            $html .= '</div>'; // builder-grid-row
        }

        $html .= '</div>'; // builder-content-wrap
        return $html;
    }
}
