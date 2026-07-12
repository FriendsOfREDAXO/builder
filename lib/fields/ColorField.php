<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Color Picker Feld mit Swatch und Hex-Anzeige.
 */
class ColorField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'color';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;
        $default = (string) ($fieldConfig['default'] ?? '#3b82f6');

        $resolved = is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1
            ? strtolower($value)
            : (preg_match('/^#[0-9a-fA-F]{6}$/', $default) === 1 ? strtolower($default) : '#3b82f6');

        $id = $this->generateId('cb_color');

        $this->openFormGroup();
        $this->renderLabel((string) $label);

        echo '<div class="cb-color-field" data-cb-color-field="1">';
        echo '<div class="cb-color-row">';
        echo '<input type="color" class="cb-color-input" id="' . rex_escape($id) . '" name="' . rex_escape($fieldName) . '" value="' . rex_escape($resolved) . '"' . $this->renderExtraAttributes($fieldConfig) . '>';
        echo '<input type="text" class="form-control cb-color-code" value="' . rex_escape($resolved) . '" readonly tabindex="-1">';
        echo '</div>';
        echo '</div>';

        $this->closeFormGroup(is_string($notice) ? $notice : null);
    }
}
