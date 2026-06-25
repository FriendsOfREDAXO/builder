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