<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;
use rex_formatter;
use rex_i18n;
use rex_sql;
use rex_url;
use rex_yform_manager_table;
use rex_csrf_token;

/**
 * YForm Datensatz-Picker Widget (Native YForm Integration)
 */
class YFormPickerField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'yformpicker';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $tableName = $fieldConfig['table'] ?? '';
        $displayField = $fieldConfig['field'] ?? 'id';
        
        if (!$tableName) {
            $this->renderError('Tabelle nicht konfiguriert');
            return;
        }

        $table = rex_yform_manager_table::get($tableName);
        if (!$table) {
            $this->renderError('Tabelle nicht gefunden: ' . $tableName);
            return;
        }

        $this->openFormGroup();
        $this->renderLabel($label);
        
        $this->renderWidget($fieldName, $value, $table, $displayField);
        
        $this->closeFormGroup($notice);
    }

    private function renderWidget($fieldName, $value, $table, $displayField)
    {
        $tableName = $table->getTableName();
        
        // Modal Link (Absolut roh als String ohne rex_url Overhead)
        $link = 'index.php?page=yform/manager/data_edit&table_name=' . $tableName;

        $csrfToken = rex_csrf_token::factory($table->getCSRFKey())->getValue();
        $id = 'yform-dataset-' . uniqid();

        // Get Display Value
        $displayValue = '';
        if ($value) {
            $sql = rex_sql::factory();
            $sql->setQuery("SELECT $displayField FROM $tableName WHERE id = ?", [$value]);
            if ($sql->getRows()) {
                $displayValue = $sql->getValue($displayField);
            }
        }

        // Standard YForm Widget Markup
        ?>
        <div id="<?= $id ?>" class="yform-dataset-widget" 
             data-field_name="<?php echo rex_escape($displayField); ?>" 
             data-link="<?php echo $link; ?>" 
             data-widget_type="single"
             data-csrf_token="<?php echo rex_escape($csrfToken); ?>">
            
            <div class="input-group">
                <input type="text" class="form-control yform-dataset-view" value="<?php echo rex_escape($displayValue); ?>" readonly>
                <input type="hidden" class="yform-dataset-real" name="<?php echo $fieldName; ?>" value="<?php echo rex_escape((string)$value); ?>">
                <span class="input-group-btn">
                    <a href="javascript:void(0);" class="btn btn-popup yform-dataset-widget-open" title="Auswählen"><i class="rex-icon rex-icon-view-list"></i></a>
                    <a href="javascript:void(0);" class="btn btn-popup yform-dataset-widget-delete" title="Auswahl löschen"><i class="rex-icon rex-icon-delete"></i></a>
                </span>
            </div>
        </div>
        <script>
            // Initialisierung des YForm Widgets sicherstellen
            if (typeof jQuery !== 'undefined') {
                (function($) {
                    var $widget = $('#<?= $id ?>');
                    
                    // rex:ready triggern, damit YForm's widget.js das Widget findet und initialisiert
                    $(document).trigger('rex:ready', [$widget]);

                    // YForm selection callback abfangen
                    $(document).on('rex:YForm_selectData', function(event, id, label) {
                        // Prüfen ob ID dieses Widgets (YForm widget.js nutzt dies intern)
                        // Da wir rex:ready auf dem Widget gerufen haben, sollte es initialisiert sein.
                        // Zur Sicherheit triggern wir einen change auf dem hidden input, 
                        // falls YForm ihn befüllt hat.
                        $widget.find('.yform-dataset-real').trigger('change');
                    });
                })(jQuery);
            }
        </script>
        <?php
    }
}
