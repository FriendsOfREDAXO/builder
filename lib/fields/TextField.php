<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Text-Eingabefeld
 */
class TextField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'text';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $placeholder = $fieldConfig['placeholder'] ?? '';
        $notice = $fieldConfig['notice'] ?? null;

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<input type="text" class="form-control" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'value="' . rex_escape($value) . '"';
        if ($placeholder) {
            echo ' placeholder="' . rex_escape($placeholder) . '"';
        }
        echo '>';

        $this->closeFormGroup($notice);
    }
}
