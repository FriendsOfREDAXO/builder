<?php

namespace FriendsOfREDAXO\Builder\Config;

use rex_extension;

/**
 * Theme-Provider Bridge für optionale Theme-Integrationen.
 *
 * Diese Klasse kapselt alle Extension-Points für Theme-Provider,
 * damit der Content Builder keine direkte Addon-Abhängigkeit benötigt.
 */
class ThemeProviderBridge
{
    /**
     * @return array<string, string>
     */
    public static function getThemeChoices(): array
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_CHOICES',
            []
        ));

        if (!is_array($result)) {
            return [];
        }

        $choices = [];
        foreach ($result as $key => $label) {
            if (!is_string($key) || !is_string($label)) {
                continue;
            }

            $themeKey = trim($key);
            $themeLabel = trim($label);
            if ($themeKey === '' || $themeLabel === '') {
                continue;
            }

            $choices[$themeKey] = $themeLabel;
        }

        return $choices;
    }

    public static function isProviderAvailable(): bool
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_PROVIDER_AVAILABLE',
            false
        ));

        if (is_bool($result)) {
            return $result;
        }

        return self::getThemeChoices() !== [];
    }

    public static function resetThemeContext(): void
    {
        rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_CONTEXT_RESET',
            null
        ));
    }

    public static function setTheme(string $themeName): void
    {
        rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_CONTEXT_SET',
            $themeName
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public static function getBackgroundOptions(string $framework = 'uikit'): array
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_BACKGROUND_OPTIONS',
            [],
            ['framework' => $framework]
        ));

        return is_array($result) ? $result : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getTextColorOptions(string $framework = 'uikit'): array
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_TEXT_COLOR_OPTIONS',
            [],
            ['framework' => $framework]
        ));

        return is_array($result) ? $result : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getCardStyleOptions(string $framework = 'uikit'): array
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_THEME_CARD_STYLE_OPTIONS',
            [],
            ['framework' => $framework]
        ));

        return is_array($result) ? $result : [];
    }

    /**
     * Setzt den Theme-Kontext anhand der Tabelle-zu-Theme-Zuordnung (Einstellungen >
     * Content Builder, addon-Config "table_themes") fuer eine gegebene YForm-
     * Tabelle - gemeinsam genutzt von rex_yform_value_content_builder (Formular-
     * Rendering) UND ContentBuilderApi::renderSlice() (AJAX-Live-Preview beim
     * Bearbeiten eines Elements), damit BEIDE denselben Theme-Kontext sehen, ohne die
     * Logik zweimal zu pflegen. Faellt auf den globalen "theme"-Fallback zurueck, wenn
     * die Tabelle keine eigene Zuordnung hat; setzt gar keinen Kontext, wenn auch der
     * Fallback leer ist (BUILDER_SLICE_PREVIEW_HTML-Consumer wie Ncss\
     * BuilderThemeProvider fallen dann selbst auf ihre eigene Domain-Herleitung
     * zurueck, siehe Ncss\BuilderPreviewIsolation::wrapIfNcss()).
     */
    public static function applyThemeContextForTable(string $tableName): void
    {
        $addon = \rex_addon::get('builder');

        $tableTheme = '';
        if ($tableName !== '') {
            $rawMapping = $addon->getConfig('table_themes', []);
            if (is_array($rawMapping) && array_key_exists($tableName, $rawMapping)) {
                $tableTheme = trim((string) $rawMapping[$tableName]);
            }
        }

        $fallbackTheme = trim((string) $addon->getConfig('theme', ''));
        $theme = $tableTheme !== '' ? $tableTheme : $fallbackTheme;

        self::resetThemeContext();
        if ($theme !== '') {
            self::setTheme($theme);
        }
    }

    public static function normalizeFramework(string $framework): string
    {
        $result = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_FRAMEWORK_NORMALIZE',
            $framework,
            ['framework' => $framework]
        ));

        if (!is_string($result) || trim($result) === '') {
            return $framework;
        }

        return trim($result);
    }
}