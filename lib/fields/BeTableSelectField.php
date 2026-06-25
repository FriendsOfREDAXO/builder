<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;
use rex_select;
use rex_sql;
use rex_yform_manager_table;

/**
 * YForm Datensatz-Picker mit selectpicker
 * 
 * Supports:
 * - Beliebige Tabellen (YForm oder Native)
 * - Single & Multiple Auswahl
 * - selectpicker mit Live Search
 * - Komma-getrennte ID-Speicherung bei Multiple
 */
class BeTableSelectField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'be_table_select';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $tableName = $fieldConfig['table'] ?? '';
        $displayField = $fieldConfig['field'] ?? 'name';
        $multiple = $fieldConfig['multiple'] ?? false;
        
        if (!$tableName) {
            $this->renderError('Tabelle nicht konfiguriert (table erforderlich)');
            return;
        }

        // Versuche die Tabelle zu laden
        $table = null;
        try {
            $table = rex_yform_manager_table::get($tableName);
        } catch (\Exception $e) {
            // Keine YForm Tabelle, könnte Native Tabelle sein
        }

        $this->openFormGroup();
        $this->renderLabel($label);

        $this->renderSelectPicker($fieldName, $value, $tableName, $displayField, $multiple);

        $this->closeFormGroup($notice);
    }

    /**
     * Rendert ein Select-Feld mit selectpicker
     */
    protected function renderSelectPicker(
        string $fieldName,
        $value,
        string $tableName,
        string $displayField,
        bool $multiple
    ): void {
        // Debug: Value prüfen
        // echo "<!-- DEBUG: fieldName=$fieldName, value=" . var_export($value, true) . ", multiple=" . var_export($multiple, true) . " -->";
        
        // Bei Multiple: Value in Array konvertieren
        $selectedIds = [];
        if ($multiple && !empty($value)) {
            $selectedIds = array_map('trim', explode(',', $value));
        } elseif (!$multiple && !empty($value)) {
            $selectedIds = [(string) $value];
        }

        // Direktes HTML statt rex_select (selectpicker kompatibel)
        $multipleAttr = $multiple ? ' multiple="multiple"' : '';
        $sizeAttr = $multiple ? ' size="5"' : ' size="1"';
        
        echo '<select name="' . rex_escape($fieldName) . '" class="form-control selectpicker" data-live-search="true" data-style="btn-default"' . $multipleAttr . $sizeAttr . '>';

        // Leer-Option für Single-Select
        if (!$multiple) {
            echo '<option value="">-- Bitte wählen --</option>';
        }

        // Datensätze laden
        try {
            $query = rex_sql::factory();
            
            // Escape für SQL
            $displayFieldEscaped = $query->escapeIdentifier($displayField);
            $tableNameEscaped = $query->escapeIdentifier($tableName);
            
            // Query: Hole ID und Display-Feld
            $query->setQuery("SELECT id, {$displayFieldEscaped} FROM {$tableNameEscaped} ORDER BY id ASC");

            while ($query->hasNext()) {
                $id = $query->getValue('id');
                $display = $query->getValue($displayField) ?? '';
                $optionLabel = $display ? sprintf('%s [#%s]', $display, $id) : '#' . $id;

                // Prüfe ob ausgewählt
                $isSelected = in_array((string) $id, $selectedIds, true);
                $selectedAttr = $isSelected ? ' selected="selected"' : '';

                echo '<option value="' . rex_escape($id) . '"' . $selectedAttr . '>' . rex_escape($optionLabel) . '</option>';
                $query->next();
            }
        } catch (\Exception $e) {
            // Fehler beim Laden - zeige Error
            $this->renderError('Fehler beim Laden der Datensätze: ' . $e->getMessage());
            return;
        }

        echo '</select>';
    }

    /**
     * Rendert einen Fehler
     */
    protected function renderError(string $message): void
    {
        $this->openFormGroup();
        echo '<div class="alert alert-danger alert-small" role="alert">';
        echo '<strong>Fehler:</strong> ' . rex_escape($message);
        echo '</div>';
        $this->closeFormGroup();
    }
}
