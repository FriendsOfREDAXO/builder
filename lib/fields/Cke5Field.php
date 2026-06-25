<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * CKEditor 5 WYSIWYG-Feld
 */
class Cke5Field extends FieldAbstract
{
    public static function getType(): string
    {
        return 'cke5';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $profile = $fieldConfig['profile'] ?? 'default';
        $rows = $fieldConfig['rows'] ?? 10;
        $notice = $fieldConfig['notice'] ?? null;

        $editorId = 'ck' . uniqid();

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<textarea id="' . $editorId . '" ';
        echo 'class="form-control cke5-editor" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'data-profile="' . rex_escape($profile) . '" ';
        echo 'rows="' . intval($rows) . '">';
        echo rex_escape($value);
        echo '</textarea>';

        $this->closeFormGroup($notice);
    }
}
