<?php

namespace FriendsOfREDAXO\Builder\Fields;

use FriendsOfREDAXO\Builder\SmartLink;
use rex_escape;
use rex_media;
use rex_sql;
use rex_url;
use Throwable;

/**
 * Kombiniertes Link-Feld fuer URL, intern, Media, Mail, Tel und YForm.
 */
class SmartLinkField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'smart_link';
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

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $multiple = (bool) ($fieldConfig['multiple'] ?? false);
        $allowedTypes = $fieldConfig['types'] ?? ['auto', 'url', 'intern', 'media', 'tel', 'mail', 'yform'];

        if (is_string($allowedTypes)) {
            $allowedTypes = array_values(array_filter(array_map('trim', explode(',', $allowedTypes))));
        }

        if ($allowedTypes === []) {
            $allowedTypes = ['auto', 'url', 'intern', 'media', 'tel', 'mail', 'yform'];
        }

        $items = SmartLink::normalize($value, $multiple);
        if ($items === []) {
            $items[] = [
                'type' => 'auto',
                'value' => '',
                'label' => '',
                'pdfjs' => false,
            ];
        }

        // YForm-Konfiguration direkt aus dem Feld-Config (analog zu be_manager_relation)
        $yformTable = trim((string) ($fieldConfig['yform_table'] ?? ''));
        $yformField = trim((string) ($fieldConfig['yform_field'] ?? 'name'));
        $yformProfile = trim((string) ($fieldConfig['yform_profile'] ?? ''));
        $yformChoices = [];
        $yformStatus = 'unconfigured'; // 'unconfigured' | 'ok' | 'missing' | 'noyform'

        $needsYForm = in_array('yform', $allowedTypes, true) || in_array('auto', $allowedTypes, true);
        if ($needsYForm) {
            if ($yformTable === '') {
                $yformStatus = 'unconfigured';
            } elseif (!class_exists('rex_yform_manager_table')) {
                $yformStatus = 'noyform';
            } else {
                [$yformChoices, $yformStatus] = $this->loadYFormChoices($yformTable, $yformField);
            }
        }

        $id = 'cb_smart_link_' . uniqid();

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="cb-smart-link-widget" id="' . rex_escape($id) . '" data-multiple="' . ($multiple ? '1' : '0') . '">';
        echo '<input type="hidden" class="cb-smart-link-value" name="' . rex_escape($fieldName) . '" value="' . rex_escape((string) json_encode(['multiple' => $multiple, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '">';
        echo '<div class="cb-smart-link-rows">';

        foreach ($items as $idx => $item) {
            $this->renderRow($id, $idx, $item, $yformChoices, $yformStatus, $yformTable, $yformField, $yformProfile, $allowedTypes);
        }

        echo '</div>';

        if ($multiple) {
            echo '<button type="button" class="btn btn-default btn-sm cb-smart-link-add"><i class="fa fa-plus"></i> ' . $this->t('builder_smart_link_add_link', 'Link hinzufügen') . '</button>';
        }

        echo '</div>';

        $this->closeFormGroup($notice);
    }

    /**
     * Lädt YForm-Einträge direkt aus der konfigurierten Tabelle (analog be_manager_relation).
     *
     * @return array{0: array<string,string>, 1: string}  [choices, status]
     */
    private function loadYFormChoices(string $tableName, string $displayField): array
    {
        $table = \rex_yform_manager_table::get($tableName);
        if ($table === null) {
            return [[], 'missing:' . $tableName];
        }
        try {
            $sql = rex_sql::factory();
            // Sicherstellen dass das Anzeigefeld existiert
            $cols = [];
            foreach ($sql->getArray('SHOW COLUMNS FROM ' . $sql->escapeIdentifier($tableName)) as $col) {
                $cols[] = $col['Field'];
            }
            $safeField = in_array($displayField, $cols, true) ? $displayField : 'id';
            $rows = $sql->getArray(
                'SELECT id, ' . $sql->escapeIdentifier($safeField)
                . ' FROM ' . $sql->escapeIdentifier($tableName)
                . ' ORDER BY ' . $sql->escapeIdentifier($safeField) . ' ASC LIMIT 500'
            );
            $choices = [];
            foreach ($rows as $row) {
                $choices[(string) $row['id']] = (string) ($row[$safeField] ?? '#' . $row['id']);
            }
            return [$choices, 'ok'];
        } catch (Throwable) {
            return [[], 'error:' . $tableName];
        }
    }

    /**
     * @param array{type:string,value:string,label:string,pdfjs:bool} $item
     * @param array<string,string> $yformChoices
     * @param array<string> $allowedTypes
     */
    private function renderRow(string $baseId, int $idx, array $item, array $yformChoices, string $yformStatus, string $yformTable, string $yformField, string $yformProfile, array $allowedTypes): void
    {
        $rowId = $baseId . '_row_' . $idx;
        $widgetId = 'cbsl_' . self::getNextMediaCounter();
        $targetFieldName = 'cb_smart_link_target_' . $widgetId;
        $type = (string) $item['type'];
        $value = (string) $item['value'];
        $label = (string) $item['label'];
        $pdfjs = (bool) $item['pdfjs'];

        $preview = '';
        if (SmartLink::detectType($value) === 'media' && $value !== '') {
            $media = rex_media::get($value);
            if ($media !== null) {
                $preview = '<a href="' . rex_escape(rex_url::media($value)) . '" target="_blank" rel="noopener">' . rex_escape($value) . '</a>';
            }
        }

        $widgetHtml = $this->renderTargetWidget($widgetId, $targetFieldName, $value, $allowedTypes);

        echo '<div class="cb-smart-link-row panel panel-default" data-row-id="' . rex_escape($rowId) . '">';
        echo '<div class="panel-body cb-smart-link-panel-body">';

        echo '<div class="row cb-smart-link-main-row">';
        echo '<div class="col-md-2 col-sm-3">';
        echo '<label>' . $this->t('builder_smart_link_type_label', 'Typ') . '</label>';
        echo '<select class="form-control cb-smart-link-select cb-smart-link-type">';

        $allTypes = [
            'auto' => $this->t('builder_smart_link_type_auto', 'Auto'),
            'url' => $this->t('builder_smart_link_type_url', 'URL'),
            'intern' => $this->t('builder_smart_link_type_intern', 'Intern'),
            'media' => $this->t('builder_smart_link_type_media', 'Media'),
            'tel' => $this->t('builder_smart_link_type_tel', 'Telefon'),
            'mail' => $this->t('builder_smart_link_type_mail', 'E-Mail'),
            'yform' => 'YForm',
        ];

        foreach ($allTypes as $optVal => $optLabel) {
            if (!in_array($optVal, $allowedTypes, true) && $optVal !== 'auto') {
                continue;
            }
            $sel = $type === $optVal ? ' selected' : '';
            echo '<option value="' . rex_escape($optVal) . '"' . $sel . '>' . rex_escape($optLabel) . '</option>';
        }

        echo '</select>';
        echo '</div>';

        echo '<div class="col-md-6 col-sm-5">';
    echo '<label>' . $this->t('builder_smart_link_target_label', 'Ziel') . '</label>';
        echo $widgetHtml;
        echo '<div class="cb-smart-link-preview text-muted small">' . $preview . '</div>';
        echo '</div>';

        echo '<div class="col-md-4 col-sm-4">';
    echo '<label>' . $this->t('builder_smart_link_custom_label', 'Label (optional)') . '</label>';
        echo '<input type="text" class="form-control cb-smart-link-input cb-smart-link-label" value="' . rex_escape($label) . '">';
        echo '</div>';
        echo '</div>';

        echo '<div class="row cb-smart-link-secondary-row">';
        echo '<div class="col-sm-7">';

        if (in_array('yform', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            echo '<div class="cb-smart-link-yform-wrap" style="display:none;">';
            if ($yformStatus === 'unconfigured') {
                echo '<div class="alert alert-info cb-smart-link-yform-info" style="margin:6px 0 0;padding:8px 12px;font-size:12px;">';
                echo '<i class="fa fa-info-circle"></i> ';
                echo '<strong>' . $this->t('builder_smart_link_yform_dataset_label', 'YForm-Datensatz:') . '</strong> ';
                echo $this->t('builder_smart_link_yform_unconfigured', 'Kein Typ konfiguriert. Demo-Modus - konfigurieren Sie yform_table und yform_field im Element.');
                echo '</div>';
            } elseif (str_starts_with($yformStatus, 'missing:')) {
                $missingTable = rex_escape(substr($yformStatus, 8));
                echo '<div class="alert alert-warning cb-smart-link-yform-info" style="margin:6px 0 0;padding:8px 12px;font-size:12px;">';
                echo '<i class="fa fa-exclamation-triangle"></i> ';
                echo $this->t('builder_smart_link_yform_missing_prefix', 'Tabelle') . ' <code>' . $missingTable . '</code> ' . $this->t('builder_smart_link_yform_missing_suffix', 'nicht gefunden. Bitte yform_table im Element konfigurieren oder die Tabelle in YForm anlegen.');
                echo '</div>';
            } elseif (str_starts_with($yformStatus, 'error:')) {
                $errTable = rex_escape(substr($yformStatus, 6));
                echo '<div class="alert alert-danger cb-smart-link-yform-info" style="margin:6px 0 0;padding:8px 12px;font-size:12px;">';
                echo '<i class="fa fa-times-circle"></i> ' . $this->t('builder_smart_link_yform_error_prefix', 'Fehler beim Laden der Tabelle') . ' <code>' . $errTable . '</code>.';
                echo '</div>';
            } elseif ($yformStatus === 'noyform') {
                echo '<div class="alert alert-warning cb-smart-link-yform-info" style="margin:6px 0 0;padding:8px 12px;font-size:12px;">';
                echo '<i class="fa fa-exclamation-triangle"></i> ' . $this->t('builder_smart_link_yform_addon_missing', 'YForm-Addon nicht verfügbar.');
                echo '</div>';
            } else {
                echo '<label>' . $this->t('builder_smart_link_yform_entry_label', 'YForm-Eintrag');
                if ($yformTable !== '') {
                    echo ' <small class="text-muted">(' . rex_escape($yformTable) . '.' . rex_escape($yformField) . ')</small>';
                }
                echo '</label>';
                echo '<select class="form-control cb-smart-link-select cb-smart-link-yform">';
                echo '<option value="">' . $this->t('builder_smart_link_yform_select', '-- auswählen --') . '</option>';
                foreach ($yformChoices as $choiceValue => $choiceLabel) {
                    $choiceId = (string) $choiceValue;
                    $tableAlias = $this->normalizeYformTableName($yformTable);
                    $choiceWithPrefix = 'yform://' . $tableAlias . '/' . $choiceId;
                    $choiceStoredValue = '' !== $yformProfile ? $yformProfile . ':' . $choiceId : $choiceWithPrefix;
                    $legacyTablePrefix = 'yform://' . $yformTable . '/' . $choiceId;
                    $sel = (
                        $value === $choiceStoredValue
                        || $value === $choiceWithPrefix
                        || $value === $legacyTablePrefix
                        || $value === $choiceId
                    ) ? ' selected' : '';
                    echo '<option value="' . rex_escape($choiceStoredValue) . '"' . $sel . '>' . rex_escape((string) $choiceLabel) . '</option>';
                }
                echo '</select>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="col-sm-5">';
        echo '<div class="cb-smart-link-toolbar">';
        echo '<label class="cb-smart-link-pdfjs-label"><input type="checkbox" class="cb-smart-link-pdfjs" value="1"' . ($pdfjs ? ' checked' : '') . '> ' . $this->t('builder_smart_link_pdfjs_label', 'PDF.js Lightbox') . '</label>';
        echo '<div class="btn-group btn-group-sm cb-smart-link-actions" role="group" aria-label="' . $this->t('builder_smart_link_actions_aria', 'Smart-Link Aktionen') . '">';
        echo '<button type="button" class="btn btn-default cb-smart-link-btn cb-smart-link-btn-secondary cb-smart-link-detect" title="' . $this->t('builder_smart_link_detect_title', 'Typ automatisch erkennen') . '"><i class="fa fa-magic"></i></button>';
        echo '<button type="button" class="btn btn-danger cb-smart-link-btn cb-smart-link-btn-danger cb-smart-link-remove" title="' . $this->t('builder_smart_link_remove_title', 'Link entfernen') . '"><i class="fa fa-trash"></i></button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // closes secondary-row
        echo '</div>'; // closes panel-body
        echo '</div>'; // closes cb-smart-link-row panel
    }

    /**
     * @param array<string> $allowedTypes
     */
    private function renderTargetWidget(string $widgetId, string $fieldName, string $value, array $allowedTypes): string
    {
        $display = $value;
        if (ctype_digit($value) && class_exists('rex_article')) {
            $article = \rex_article::get((int) $value);
            if ($article instanceof \rex_article) {
                $display = trim($article->getName() . ' [' . $article->getId() . ']');
            }
        }

        $clang = (string) \rex_clang::getCurrentId();

        $html = '<div class="custom-link cb-smart-link-target-wrap" data-link-widget-id="' . rex_escape($widgetId) . '" data-clang="' . rex_escape($clang) . '" data-extern-link-prefix="https://">';
        $html .= '<input class="form-control cb-smart-link-input cb-smart-link-target-display" type="text" id="REX_LINK_' . rex_escape($widgetId) . '_NAME" value="' . rex_escape($display) . '" readonly="readonly">';
        $html .= '<input type="hidden" class="cb-smart-link-target" name="' . rex_escape($fieldName) . '" id="REX_LINK_' . rex_escape($widgetId) . '" value="' . rex_escape($value) . '">';
        $html .= '<div class="btn-group btn-group-sm cb-smart-link-target-actions" role="group" aria-label="' . $this->t('builder_smart_link_target_actions_aria', 'Smart-Link Zielauswahl') . '">';

        if (in_array('intern', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker intern_link" title="' . rex_escape(\rex_i18n::msg('var_link_open')) . '"><i class="rex-icon rex-icon-open-linkmap"></i></a>';
        }
        if (in_array('url', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker external_link" title="' . rex_escape(\rex_i18n::msg('var_extern_link')) . '"><i class="rex-icon fa-external-link"></i></a>';
        }
        if (in_array('media', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker media_link" title="' . rex_escape(\rex_i18n::msg('var_media_open')) . '"><i class="rex-icon fa-file-o"></i></a>';
        }
        if (in_array('mail', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker email_link" title="' . rex_escape(\rex_i18n::msg('var_mailto_link')) . '"><i class="rex-icon fa-envelope-o"></i></a>';
        }
        if (in_array('tel', $allowedTypes, true) || in_array('auto', $allowedTypes, true)) {
            $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker phone_link" title="' . rex_escape(\rex_i18n::msg('var_phone_link')) . '"><i class="rex-icon fa-phone"></i></a>';
        }

        $html .= '<a href="#" class="btn btn-popup cb-smart-link-btn cb-smart-link-btn-picker delete_link" title="' . rex_escape(\rex_i18n::msg('var_link_delete')) . '"><i class="rex-icon rex-icon-delete-link"></i></a>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function t(string $key, string $fallback): string
    {
        $msg = \rex_i18n::rawMsg($key);

        return $msg !== $key ? $msg : $fallback;
    }

    private function normalizeYformTableName(string $tableName): string
    {
        $normalized = strtolower(trim($tableName));
        if (str_starts_with($normalized, 'rex_')) {
            $normalized = substr($normalized, 4);
        }

        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return '' !== $normalized ? $normalized : 'dataset';
    }
}
