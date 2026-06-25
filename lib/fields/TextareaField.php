<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Textarea-Feld
 */
class TextareaField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'textarea';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $rows = $fieldConfig['rows'] ?? 5;
        $notice = $fieldConfig['notice'] ?? null;

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<textarea class="form-control" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'rows="' . intval($rows) . '">';
        echo rex_escape($value);
        echo '</textarea>';

        $this->closeFormGroup($notice);
    }
}
