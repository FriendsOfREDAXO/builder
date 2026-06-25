<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Statisches Info-Feld ohne Speicherung.
 */
class InfoField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'info';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? '';
        $text = (string) ($fieldConfig['text'] ?? '');
        $notice = $fieldConfig['notice'] ?? null;
        $style = (string) ($fieldConfig['style'] ?? 'info');

        $allowedStyles = ['info', 'warning', 'success', 'danger'];
        if (!in_array($style, $allowedStyles, true)) {
            $style = 'info';
        }

        $this->openFormGroup();
        if ($label !== '') {
            $this->renderLabel($label);
        }

        echo '<div class="alert alert-' . rex_escape($style) . '" style="margin-bottom:0;">';
        echo nl2br(rex_escape($text));
        echo '</div>';

        $this->closeFormGroup($notice);
    }
}
