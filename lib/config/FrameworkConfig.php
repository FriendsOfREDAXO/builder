<?php

namespace FriendsOfREDAXO\Builder\Config;

use rex_extension;

/**
 * Framework-agnostische Konfigurations-Verwaltung
 * Nutzt Extension Points, um Framework-spezifische Optionen zu registrieren.
 * Ermöglicht UIkit, Bootstrap, Plain und Custom-Frameworks ohne StarterConfig zu ändern.
 */
class FrameworkConfig
{
    private static array $cache = [];

    /**
     * Führt einen Extension Point mit Default-Subject aus.
     *
     * @template T
     * @param string $name
     * @param T $default
     * @param array<string, mixed> $params
     * @return T
     */
    private static function applyExtensionPoint(string $name, mixed $default, array $params = []): mixed
    {
        return rex_extension::registerPoint(new \rex_extension_point($name, $default, $params));
    }

    /**
     * Liefert Hintergrund-Optionen für ein Framework
     * Extension Point: BUILDER_FRAMEWORK_OPTIONS
     *
     * @param string $framework z.B. 'uikit', 'bootstrap', 'plain', 'custom'
     * @return array<string, string> Assoziatives Array: klasse => label
     */
    public static function getBackgroundChoices(string $framework = 'uikit'): array
    {
        $cacheKey = "backgrounds_{$framework}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $defaults = [
            'uikit' => [
                '' => 'Keine',
                'uk-background-default' => 'Default (Weiß)',
                'uk-background-transparent' => 'Transparent',
                'uk-background-muted' => 'Muted (Grau)',
                'uk-background-primary' => 'Primary',
                'uk-background-secondary' => 'Secondary',
            ],
            'bootstrap' => [
                '' => 'Keine',
                'uk-background-default' => 'Default (Weiß)',
                'uk-background-transparent' => 'Transparent',
                'uk-background-muted' => 'Muted (Grau)',
                'uk-background-primary' => 'Primary',
                'uk-background-secondary' => 'Secondary',
            ],
            'plain' => [
                '' => 'Keine',
                'uk-background-default' => 'Default (Weiß)',
                'uk-background-transparent' => 'Transparent',
                'uk-background-muted' => 'Muted (Grau)',
                'uk-background-primary' => 'Primary',
                'uk-background-secondary' => 'Secondary',
            ],
        ];

        $defaultChoices = $defaults[$framework] ?? $defaults['uikit'];

        if ('uikit' === $framework) {
            $themeBackgrounds = ThemeProviderBridge::getBackgroundOptions($framework);
            if (!empty($themeBackgrounds)) {
                $baseChoices = [
                    '' => 'Keine',
                    'uk-background-default' => 'Default (Weiß)',
                    'uk-background-transparent' => 'Transparent',
                    'uk-background-muted' => 'Muted (Grau)',
                    'uk-background-primary' => 'Primary',
                    'uk-background-secondary' => 'Secondary',
                ];

                $defaultChoices = $baseChoices;
                foreach ($themeBackgrounds as $class => $data) {
                    if (!is_string($class) || trim($class) === '') {
                        continue;
                    }

                    $label = null;
                    if (is_array($data) && isset($data['label']) && is_string($data['label'])) {
                        $label = $data['label'];
                    } elseif (is_string($data)) {
                        $label = $data;
                    }

                    if (!is_string($label) || trim($label) === '') {
                        $label = ucfirst(str_replace('uk-background-', '', $class));
                    }

                    $defaultChoices[$class] = $label;
                }
            }
        }

        $result = self::applyExtensionPoint('BUILDER_FRAMEWORK_OPTIONS', $defaultChoices, [
            'framework' => $framework,
            'option_type' => 'backgrounds',
        ]);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Liefert Padding-Optionen für ein Framework
     */
    public static function getPaddingChoices(string $framework = 'uikit'): array
    {
        $cacheKey = "paddings_{$framework}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $defaults = [
            'uikit' => [
                '' => 'Standard',
                'uk-padding-remove' => 'Keine Füllung',
                'uk-padding-small' => 'Klein',
                'uk-padding' => 'Mittel',
                'uk-padding-large' => 'Groß',
            ],
            'bootstrap' => [
                '' => 'Standard',
                'p-0' => 'Keine Füllung',
                'p-2' => 'Klein',
                'p-4' => 'Mittel',
                'p-5' => 'Groß',
            ],
            'plain' => [
                '' => 'Standard',
                'no-padding' => 'Keine Füllung',
                'padding-small' => 'Klein',
                'padding-medium' => 'Mittel',
                'padding-large' => 'Groß',
            ],
        ];

        $defaultChoices = $defaults[$framework] ?? $defaults['uikit'];

        $result = self::applyExtensionPoint('BUILDER_FRAMEWORK_OPTIONS', $defaultChoices, [
            'framework' => $framework,
            'option_type' => 'paddings',
        ]);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Liefert Container/Grid-Optionen für ein Framework
     */
    public static function getContainerChoices(string $framework = 'uikit'): array
    {
        $cacheKey = "containers_{$framework}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $defaults = [
            'uikit' => [
                'uk-container' => 'Standard',
                'uk-container uk-container-xsmall' => 'Extra schmal',
                'uk-container uk-container-small' => 'Schmal',
                'uk-container uk-container-large' => 'Weit',
                'uk-container uk-container-xlarge' => 'Extra weit',
                'uk-container uk-container-expand' => 'Maximale Breite',
                '' => 'Volle Breite (kein Container)',
            ],
            'bootstrap' => [
                'container' => 'Standard',
                'container-sm' => 'Klein',
                'container-md' => 'Mittel',
                'container-lg' => 'Groß',
                'container-xl' => 'Extra groß',
                'container-fluid' => 'Volle Breite',
                '' => 'Kein Container',
            ],
            'plain' => [
                'container' => 'Standard',
                'container-narrow' => 'Schmal',
                'container-wide' => 'Weit',
                'container-full' => 'Volle Breite',
                '' => 'Kein Container',
            ],
        ];

        $defaultChoices = $defaults[$framework] ?? $defaults['uikit'];

        $result = self::applyExtensionPoint('BUILDER_FRAMEWORK_OPTIONS', $defaultChoices, [
            'framework' => $framework,
            'option_type' => 'containers',
        ]);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Liefert Hintergrund-Farben für color_swatches (mit Hex-Werten)
     */
    public static function getBackgroundColors(string $framework = 'uikit'): array
    {
        $cacheKey = "background_colors_{$framework}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $defaults = [
            'uikit' => [
                '' => ['color' => '#ffffff', 'label' => 'Keine'],
                'uk-background-default' => ['color' => '#ffffff', 'label' => 'Default (Weiß)'],
                'uk-background-transparent' => ['color' => 'transparent', 'label' => 'Transparent'],
                'uk-background-muted' => ['color' => '#f8f8f8', 'label' => 'Muted (Grau)'],
                'uk-background-primary' => ['color' => '#1e87f0', 'label' => 'Primary'],
                'uk-background-secondary' => ['color' => '#222222', 'label' => 'Secondary'],
            ],
            'bootstrap' => [
                '' => ['color' => '#ffffff', 'label' => 'Keine'],
                'uk-background-default' => ['color' => '#ffffff', 'label' => 'Default (Weiß)'],
                'uk-background-transparent' => ['color' => 'transparent', 'label' => 'Transparent'],
                'uk-background-muted' => ['color' => '#f8f9fa', 'label' => 'Muted (Grau)'],
                'uk-background-primary' => ['color' => '#0d6efd', 'label' => 'Primary'],
                'uk-background-secondary' => ['color' => '#6c757d', 'label' => 'Secondary'],
            ],
            'plain' => [
                '' => ['color' => '#ffffff', 'label' => 'Keine'],
                'uk-background-default' => ['color' => '#ffffff', 'label' => 'Default (Weiß)'],
                'uk-background-transparent' => ['color' => 'transparent', 'label' => 'Transparent'],
                'uk-background-muted' => ['color' => '#f5f5f5', 'label' => 'Muted (Grau)'],
                'uk-background-primary' => ['color' => '#1e87f0', 'label' => 'Primary'],
                'uk-background-secondary' => ['color' => '#333333', 'label' => 'Secondary'],
            ],
        ];

        $defaultColors = $defaults[$framework] ?? $defaults['uikit'];

        if ('uikit' === $framework) {
            $themeBackgrounds = ThemeProviderBridge::getBackgroundOptions($framework);
            if (!empty($themeBackgrounds)) {
                $baseColors = [
                    '' => ['color' => '#ffffff', 'label' => 'Keine'],
                    'uk-background-default' => ['color' => '#ffffff', 'label' => 'Default (Weiß)'],
                    'uk-background-transparent' => ['color' => 'transparent', 'label' => 'Transparent'],
                    'uk-background-muted' => ['color' => '#f8f8f8', 'label' => 'Muted (Grau)'],
                    'uk-background-primary' => ['color' => '#1e87f0', 'label' => 'Primary'],
                    'uk-background-secondary' => ['color' => '#222222', 'label' => 'Secondary'],
                ];

                $defaultColors = $baseColors;
                foreach ($themeBackgrounds as $class => $data) {
                    if (!is_string($class) || trim($class) === '') {
                        continue;
                    }

                    if (is_array($data)) {
                        $color = (string) ($data['color'] ?? 'transparent');
                        $label = (string) ($data['label'] ?? ucfirst(str_replace('uk-background-', '', $class)));
                        $defaultColors[$class] = ['color' => $color, 'label' => $label];
                    }
                }
            }
        }

        $result = self::applyExtensionPoint('BUILDER_FRAMEWORK_OPTIONS', $defaultColors, [
            'framework' => $framework,
            'option_type' => 'background_colors',
        ]);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * CSS-Klasse Prefix für diesen Framework
     * z.B. 'uk-' für UIkit, 'bs-' für Bootstrap
     */
    public static function getCssPrefix(string $framework = 'uikit'): string
    {
        $prefixes = [
            'uikit' => 'uk-',
            'bootstrap' => 'bs-',
            'plain' => '',
        ];

        return self::applyExtensionPoint('BUILDER_FRAMEWORK_OPTIONS', $prefixes[$framework] ?? 'uk-', [
            'framework' => $framework,
            'option_type' => 'css_prefix',
        ]);
    }

    /**
     * Reset-Methode für Tests
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
