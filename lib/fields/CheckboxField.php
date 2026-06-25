<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Checkbox-Feld
 */
class CheckboxField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'checkbox';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $checked = !empty($value) ? ' checked' : '';

        echo '<div class="form-group">';
        echo '<div class="checkbox">';
        echo '<label>';
        echo '<input type="checkbox" name="' . rex_escape($fieldName) . '" value="1"' . $checked . '> ';
        echo rex_escape($label);
        echo '</label>';
        echo '</div>';

        if ($notice) {
            echo '<p class="help-block">' . rex_escape($notice) . '</p>';
        }
        echo '</div>';
    }
}
