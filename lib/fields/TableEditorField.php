<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Tabellen-Editor Feld mit Zeilen-/Spaltenverwaltung.
 */
class TableEditorField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'table_editor';
    }

    /**
     * @param array<string, mixed> $fieldConfig
     * @param array<string, mixed> $sliceData
     */
    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = (string) ($fieldConfig['label'] ?? $fieldName);
        $notice = isset($fieldConfig['notice']) ? (string) $fieldConfig['notice'] : null;

        $minCols = max(1, (int) ($fieldConfig['min_cols'] ?? 1));
        $maxCols = max($minCols, (int) ($fieldConfig['max_cols'] ?? 999));
        $minRows = max(1, (int) ($fieldConfig['min_rows'] ?? 1));
        $maxRows = max($minRows, (int) ($fieldConfig['max_rows'] ?? 999));

        $headerRowPolicy = (string) ($fieldConfig['header_row_policy'] ?? 'user');
        $headerColPolicy = (string) ($fieldConfig['header_col_policy'] ?? 'user');

        if (!in_array($headerRowPolicy, ['user', 'yes', 'no'], true)) {
            $headerRowPolicy = 'user';
        }
        if (!in_array($headerColPolicy, ['user', 'yes', 'no'], true)) {
            $headerColPolicy = 'user';
        }

        $editorData = $this->normalizeValue($value, $minCols, $minRows, $headerRowPolicy, $headerColPolicy);

        $id = $this->generateId('cb_table_editor');
        $config = [
            'minCols' => $minCols,
            'maxCols' => $maxCols,
            'minRows' => $minRows,
            'maxRows' => $maxRows,
            'headerRowPolicy' => $headerRowPolicy,
            'headerColPolicy' => $headerColPolicy,
            'enableMedia' => (bool) ($fieldConfig['enable_media'] ?? false),
            'enableLink' => (bool) ($fieldConfig['enable_link'] ?? false),
            'enableTextarea' => (bool) ($fieldConfig['enable_textarea'] ?? true),
        ];

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="cb-table-editor" id="' . rex_escape($id) . '" data-config="' . rex_escape((string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '">';
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading">';
        echo '<div class="form-group" style="margin-bottom:0;">';
        echo '<label for="' . rex_escape($id . '_caption') . '">' . rex_escape($this->t('builder_table_editor_caption_label', 'Tabellen-Überschrift (Caption)')) . '</label>';
        echo '<input type="text" class="form-control cb-table-editor-caption" id="' . rex_escape($id . '_caption') . '" value="' . rex_escape((string) ($editorData['caption'] ?? '')) . '" placeholder="' . rex_escape($this->t('builder_table_editor_caption_placeholder', 'Beschriftung für Screenreader (wichtig für Barrierefreiheit)')) . '">';
        echo '</div>';
        echo '</div>';

        echo '<div class="panel-body" style="overflow-x:auto;">';
        echo '<div class="form-inline" style="margin-bottom:10px;">';

        if ($headerRowPolicy === 'user') {
            echo '<label class="checkbox-inline" style="margin-left:0;">';
            echo '<input type="checkbox" class="cb-table-editor-config" data-config="has_header_row"' . ((bool) ($editorData['has_header_row'] ?? false) ? ' checked' : '') . '> ';
            echo rex_escape($this->t('builder_table_editor_header_row', 'Erste Zeile ist Kopfzeile'));
            echo '</label>';
        } else {
            echo '<input type="hidden" class="cb-table-editor-config" data-config="has_header_row" value="' . ((bool) ($editorData['has_header_row'] ?? false) ? '1' : '0') . '">';
        }

        if ($headerColPolicy === 'user') {
            echo '<label class="checkbox-inline" style="margin-left:15px;">';
            echo '<input type="checkbox" class="cb-table-editor-config" data-config="has_header_col"' . ((bool) ($editorData['has_header_col'] ?? false) ? ' checked' : '') . '> ';
            echo rex_escape($this->t('builder_table_editor_header_col', 'Erste Spalte ist Kopfspalte'));
            echo '</label>';
        } else {
            echo '<input type="hidden" class="cb-table-editor-config" data-config="has_header_col" value="' . ((bool) ($editorData['has_header_col'] ?? false) ? '1' : '0') . '">';
        }

        echo '</div>';

        echo '<table class="table table-bordered table-striped cb-table-editor-table">';
        echo '<thead></thead>';
        echo '<tbody></tbody>';
        echo '</table>';

        echo '<div class="btn-group">';
        echo '<button type="button" class="btn btn-default btn-xs cb-table-editor-add-row"><i class="rex-icon fa-plus"></i> ' . rex_escape($this->t('builder_table_editor_add_row', 'Zeile +')) . '</button>';
        echo '<button type="button" class="btn btn-default btn-xs cb-table-editor-add-col"><i class="rex-icon fa-plus"></i> ' . rex_escape($this->t('builder_table_editor_add_col', 'Spalte +')) . '</button>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '<input type="hidden" name="' . rex_escape($fieldName) . '" class="cb-table-editor-value" value="' . rex_escape((string) json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '">';
        echo '</div>';

        $this->closeFormGroup($notice);
    }

    private function normalizeValue(mixed $value, int $minCols, int $minRows, string $headerRowPolicy, string $headerColPolicy): array
    {
        $data = [
            'caption' => '',
            'has_header_row' => true,
            'has_header_col' => false,
            'cols' => [],
            'rows' => [],
        ];

        $parsed = null;
        if (is_array($value)) {
            $parsed = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $parsed = $decoded;
            }
        }

        if (is_array($parsed)) {
            $data = array_merge($data, $parsed);
        }

        $rows = [];
        foreach ((array) ($data['rows'] ?? []) as $row) {
            if (is_array($row)) {
                $rows[] = array_map(static fn($v) => (string) $v, array_values($row));
            }
        }

        if ($rows === []) {
            $rows = [['']];
        }

        $columnCount = 1;
        foreach ($rows as $row) {
            $columnCount = max($columnCount, count($row));
        }

        $columnCount = max($columnCount, $minCols);

        $cols = [];
        foreach ((array) ($data['cols'] ?? []) as $col) {
            if (is_array($col)) {
                $type = (string) ($col['type'] ?? 'text');
                $headerType = (string) ($col['header_type'] ?? 'text');
                $cols[] = [
                    'type' => in_array($type, ['text', 'number', 'center', 'textarea', 'media', 'link'], true) ? $type : 'text',
                    'header_type' => in_array($headerType, ['text', 'number', 'center'], true) ? $headerType : 'text',
                ];
            }
        }

        while (count($cols) < $columnCount) {
            $cols[] = ['type' => 'text', 'header_type' => 'text'];
        }
        if (count($cols) > $columnCount) {
            $cols = array_slice($cols, 0, $columnCount);
        }

        foreach ($rows as &$row) {
            $missing = $columnCount - count($row);
            if ($missing > 0) {
                $row = array_merge($row, array_fill(0, $missing, ''));
            } elseif ($missing < 0) {
                $row = array_slice($row, 0, $columnCount);
            }
        }
        unset($row);

        while (count($rows) < $minRows) {
            $rows[] = array_fill(0, $columnCount, '');
        }

        $hasHeaderRow = (bool) ($data['has_header_row'] ?? true);
        $hasHeaderCol = (bool) ($data['has_header_col'] ?? false);

        if ($headerRowPolicy === 'yes') {
            $hasHeaderRow = true;
        } elseif ($headerRowPolicy === 'no') {
            $hasHeaderRow = false;
        }

        if ($headerColPolicy === 'yes') {
            $hasHeaderCol = true;
        } elseif ($headerColPolicy === 'no') {
            $hasHeaderCol = false;
        }

        return [
            'caption' => (string) ($data['caption'] ?? ''),
            'has_header_row' => $hasHeaderRow,
            'has_header_col' => $hasHeaderCol,
            'cols' => $cols,
            'rows' => $rows,
        ];
    }

    private function t(string $key, string $fallback): string
    {
        $msg = \rex_i18n::rawMsg($key);

        return $msg !== $key ? $msg : $fallback;
    }
}
