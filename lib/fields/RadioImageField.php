<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Radio-Buttons mit Bildern/SVGs für visuelle Layout-Auswahl
 */
class RadioImageField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'radio_image';
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
        $groupId = 'radio_' . uniqid() . '_';

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="radio-image-group">';
        
        foreach ($options as $optValue => $optData) {
            $checked = ($value == $optValue) ? ' checked' : '';
            $inputId = $groupId . md5($optValue);

            // Label und Image aus optData extrahieren
            $optLabel = $optData;
            $image = '';
            if (is_array($optData)) {
                $optLabel = $optData['label'] ?? $optValue;
                $image = $optData['image'] ?? '';
            }

            echo '<div class="radio-image-item' . ($checked ? ' active' : '') . '">';
            echo '<input type="radio" name="' . rex_escape($fieldName) . '" ';
            echo 'id="' . $inputId . '" ';
            echo 'value="' . rex_escape($optValue) . '"' . $checked . '>';
            echo '<label for="' . $inputId . '">';

            if (!empty($image)) {
                // Bild oder SVG Base64
                if (strpos($image, 'data:image/svg+xml;base64,') === 0) {
                    echo '<img src="' . $image . '" alt="' . rex_escape($optLabel) . '">';
                } else {
                    echo '<img src="' . rex_escape($image) . '" alt="' . rex_escape($optLabel) . '">';
                }
            }

            echo '<span class="radio-image-label">' . rex_escape($optLabel) . '</span>';
            echo '</label>';
            echo '</div>';
        }
        
        echo '</div>';

        $this->closeFormGroup($notice);
    }
}
