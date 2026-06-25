<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Farbauswahl mit visuellen Farbfeldern (wie MForm RadioColorField)
 */
class ColorSwatchesField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'color_swatches';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $options = $fieldConfig['options'] ?? [];
        $default = $fieldConfig['default'] ?? '';
        $notice = $fieldConfig['notice'] ?? null;

        if (($value === null || $value === '') && !empty($default)) {
            $value = $default;
        }

        // Eindeutiger Prefix für diese Radio-Gruppe
        $groupId = 'color_' . uniqid() . '_';

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="color-swatches-group">';
        
        foreach ($options as $optValue => $optData) {
            $checked = ($value == $optValue) ? ' checked' : '';
            $inputId = $groupId . md5($optValue);

            // Label und Color extrahieren
            $optLabel = $optData;
            $color = '#cccccc';
            if (is_array($optData)) {
                $optLabel = $optData['label'] ?? $optValue;
                $color = $optData['color'] ?? '#cccccc';
            }

            // Transparenz-Style für spezielle Farben
            $bgStyle = '';
            if ($color === 'transparent' || $color === '') {
                $bgStyle = 'background: linear-gradient(135deg, #fff 0%, #fff 45%, #ff0000 50%, #fff 55%, #fff 100%);';
            } else {
                $bgStyle = 'background-color: ' . $color . ';';
            }

            // Border für helle Farben
            $borderStyle = '';
            if ($color === '#ffffff' || $color === '#fff' || $color === 'white') {
                $borderStyle = 'border: 1px solid #ccc;';
            }

            echo '<div class="color-swatch-item' . ($checked ? ' active' : '') . '">';
            echo '<input type="radio" name="' . rex_escape($fieldName) . '" ';
            echo 'id="' . $inputId . '" ';
            echo 'value="' . rex_escape($optValue) . '"' . $checked . '>';
            echo '<label for="' . $inputId . '" title="' . rex_escape($optLabel) . '">';
            echo '<span class="color-swatch" style="' . $bgStyle . $borderStyle . '"></span>';
            echo '</label>';
            echo '</div>';
        }
        
        echo '</div>';

        $this->closeFormGroup($notice);
    }
}
