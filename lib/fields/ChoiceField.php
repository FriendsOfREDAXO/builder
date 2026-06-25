<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Choice-Feld (erweitertes Select mit Selectpicker, Farben, Icons)
 */
class ChoiceField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'choice';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $choices = $fieldConfig['choices'] ?? [];
        $default = $fieldConfig['default'] ?? '';
        $notice = $fieldConfig['notice'] ?? null;
        $isMultiple = !empty($fieldConfig['multiple']);
        
        // Selectpicker ist standardmäßig aktiviert
        $useSelectpicker = $fieldConfig['selectpicker'] ?? true;

        // Falls choices als String übergeben wurde (legacy)
        if (is_string($choices)) {
            $parsed = [];
            foreach (explode(',', $choices) as $choice) {
                if (strpos($choice, '=') !== false) {
                    [$key, $val] = explode('=', $choice, 2);
                    $parsed[trim($key)] = trim($val);
                } else {
                    $parsed[trim($choice)] = trim($choice);
                }
            }
            $choices = $parsed;
        }

        // Default verwenden wenn kein Wert gesetzt
        if (($value === null || $value === '') && !empty($default)) {
            $value = $default;
        }

        if ($isMultiple) {
            if (!is_array($value)) {
                if (is_string($value) && trim($value) !== '') {
                    $value = preg_split('/\s*,\s*/', trim($value)) ?: [];
                } elseif ($value === null || $value === '') {
                    $value = [];
                } else {
                    $value = [(string) $value];
                }
            }

            $value = array_values(array_map(static fn ($item): string => (string) $item, $value));
        }

        // Farbdaten und Icons für Selectpicker
        $choiceColors = $fieldConfig['choice_colors'] ?? [];
        $choiceIcons = $fieldConfig['choice_icons'] ?? [];

        $selectClass = 'form-control';
        if ($useSelectpicker) {
            $selectClass .= ' selectpicker';
        }

        $this->openFormGroup();
        $this->renderLabel($label);

        $fieldNameAttr = $fieldName;
        $multipleAttr = '';
        $sizeAttr = '';
        $liveSearchAttr = '';

        if ($isMultiple) {
            $fieldNameAttr .= '[]';
            $multipleAttr = ' multiple="multiple"';
            $sizeAttr = ' size="6"';
        }

        if ($useSelectpicker) {
            $liveSearchAttr = ' data-live-search="true" data-actions-box="true"';
        }

        echo '<select class="' . $selectClass . '" name="' . rex_escape($fieldNameAttr) . '"' . $multipleAttr . $sizeAttr . $liveSearchAttr . '>';
        
        foreach ($choices as $choiceValue => $choiceLabel) {
            if (is_array($choiceLabel)) {
                echo '<optgroup label="' . rex_escape($choiceValue) . '">';
                foreach ($choiceLabel as $subValue => $subLabel) {
                    $selected = $isMultiple
                        ? (in_array((string) $subValue, $value, true) ? ' selected' : '')
                        : ((string) $value === (string) $subValue ? ' selected' : '');
                    $dataContent = '';
                    if ($useSelectpicker && isset($choiceIcons[$subValue])) {
                        $iconHtml = $choiceIcons[$subValue];
                        $escapedIcon = str_replace('"', '&quot;', $iconHtml);
                        $dataContent = ' data-content="' . $escapedIcon . ' ' . rex_escape($subLabel) . '"';
                    } elseif ($useSelectpicker && isset($choiceColors[$subValue])) {
                        $colorData = $choiceColors[$subValue];
                        $color = is_array($colorData) ? ($colorData['color'] ?? '') : $colorData;
                        if (!empty($color)) {
                            $borderStyle = ($color === '#ffffff' || $color === 'transparent' || $color === '#fff') ? 'border: 1px solid #ccc;' : '';
                            $bgColor = $color === 'transparent' ? 'background: repeating-linear-gradient(45deg, #f0f0f0, #f0f0f0 5px, #fff 5px, #fff 10px);' : 'background-color: ' . rex_escape($color) . ';';
                            $dataContent = ' data-content="<span style=\'display:inline-block;width:16px;height:16px;margin-right:8px;vertical-align:middle;border-radius:3px;' . $bgColor . $borderStyle . '\'></span>' . rex_escape($subLabel) . '"';
                        }
                    }
                    echo '<option value="' . rex_escape($subValue) . '"' . $selected . $dataContent . '>';
                    echo rex_escape($subLabel);
                    echo '</option>';
                }
                echo '</optgroup>';
            } else {
                $selected = $isMultiple
                    ? (in_array((string) $choiceValue, $value, true) ? ' selected' : '')
                    : ((string) $value === (string) $choiceValue ? ' selected' : '');
                $dataContent = '';
                if ($useSelectpicker && isset($choiceIcons[$choiceValue])) {
                    $iconHtml = $choiceIcons[$choiceValue];
                    $escapedIcon = str_replace('"', '&quot;', $iconHtml);
                    $dataContent = ' data-content="' . $escapedIcon . ' ' . rex_escape($choiceLabel) . '"';
                } elseif ($useSelectpicker && isset($choiceColors[$choiceValue])) {
                    $colorData = $choiceColors[$choiceValue];
                    $color = is_array($colorData) ? ($colorData['color'] ?? '') : $colorData;
                    if (!empty($color)) {
                        $borderStyle = ($color === '#ffffff' || $color === 'transparent' || $color === '#fff') ? 'border: 1px solid #ccc;' : '';
                        $bgColor = $color === 'transparent' ? 'background: repeating-linear-gradient(45deg, #f0f0f0, #f0f0f0 5px, #fff 5px, #fff 10px);' : 'background-color: ' . rex_escape($color) . ';';
                        $dataContent = ' data-content="<span style=\'display:inline-block;width:16px;height:16px;margin-right:8px;vertical-align:middle;border-radius:3px;' . $bgColor . $borderStyle . '\'></span>' . rex_escape($choiceLabel) . '"';
                    }
                }
                echo '<option value="' . rex_escape($choiceValue) . '"' . $selected . $dataContent . '>';
                echo rex_escape($choiceLabel);
                echo '</option>';
            }
        }
        
        echo '</select>';

        $this->closeFormGroup($notice);
    }
}
