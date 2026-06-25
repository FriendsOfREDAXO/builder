<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * TinyMCE WYSIWYG-Feld
 */
class TinyMceField extends FieldAbstract
{
    private static bool $assetsBootstrapped = false;

    public static function getType(): string
    {
        return 'tinymce';
    }

    private static function ensureAssetsBootstrapped(): void
    {
        if (self::$assetsBootstrapped) {
            return;
        }

        self::$assetsBootstrapped = true;

        if (!\rex_addon::get('tinymce')->isAvailable()) {
            return;
        }

        if (class_exists(\FriendsOfRedaxo\TinyMce\Provider\Assets::class)) {
            \FriendsOfRedaxo\TinyMce\Provider\Assets::provideBaseAssets();
        }
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        self::ensureAssetsBootstrapped();

        $label = $fieldConfig['label'] ?? $fieldName;
        $profile = $fieldConfig['profile'] ?? 'default';
        $rows = $fieldConfig['rows'] ?? 10;
        $notice = $fieldConfig['notice'] ?? null;

        $editorId = 'tinymce' . uniqid();

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<textarea id="' . $editorId . '" ';
        echo 'class="form-control tiny-editor" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'data-profile="' . rex_escape($profile) . '" ';
        echo 'rows="' . intval($rows) . '">';
        echo rex_escape($value);
        echo '</textarea>';

        $this->closeFormGroup($notice);
    }
}
