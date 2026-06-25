<?php

namespace FriendsOfREDAXO\Builder\Config;

use rex_addon;
use rex_extension;
use rex_path;

/**
 * Element Registry - Zentrale Verwaltung aller verfügbaren Elemente
 * Extension Point: BUILDER_ELEMENTS
 * Extension Point: BUILDER_BUNDLED_ELEMENTS
 */
class ElementRegistry
{
    private static ?array $bundledElements = null;
    private static ?array $registeredElements = null;
    private static ?array $elementPaths = null;

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
     * Liefert alle bundled/Starter-Elemente
     * @return array<int, string> Element-Keys
     */
    public static function getBundledElements(): array
    {
        if (self::$bundledElements !== null) {
            return self::$bundledElements;
        }

        // Default bundled elements
        $default = [
            'section',
            'headline',
            'divider',
            'accordion',
            'starter_text',
            'starter_headline',
            'starter_media_split',
            'starter_cards',
            'starter_callout',
            'columns',
            'yform_list',
            'forcal_list',
            'table',
            'smart_link_showcase',
            'smart_links_multi_showcase',
        ];

        // Extension Point für Custom-Bundled-Elemente
        self::$bundledElements = self::applyExtensionPoint('BUILDER_BUNDLED_ELEMENTS', $default);

        return self::$bundledElements;
    }

    /**
     * Prüft ob ein Element bundled ist
     */
    public static function isBundledElement(string $elementKey): bool
    {
        return in_array($elementKey, self::getBundledElements(), true);
    }

    /**
     * Liefert alle Element-Pfade
     * Registrierbare Pfade für externe Addons (z.B. klxm_elements)
     *
     * @return array<string, string> Verzeichnispfade
     */
    public static function getElementPaths(): array
    {
        if (self::$elementPaths !== null) {
            return self::$elementPaths;
        }

        // Default: Haupt-Addon + Externe
        $default = [
            'core' => rex_path::addon('builder', 'elements'),
        ];

        // Prüfe externe Addons
        if (rex_addon::get('klxm_elements')->isAvailable()) {
            $default['klxm_elements'] = rex_path::addon('klxm_elements', 'elements');
        }

        // Extension Point für weitere Addons
        $resolvedPaths = self::applyExtensionPoint('BUILDER_ELEMENT_PATHS', $default);

        // Normalisieren: numerische Keys in stabile Namen überführen
        $normalizedPaths = [];
        foreach ((array) $resolvedPaths as $pathKey => $path) {
            $path = (string) $path;
            if ($path === '') {
                continue;
            }

            if (is_int($pathKey)) {
                if (preg_match('#/addons/([^/]+)/#', $path, $matches) === 1) {
                    $pathKey = (string) $matches[1];
                } else {
                    $pathKey = 'external_' . $pathKey;
                }
            }

            // Bei Kollisionsfall (gleiches Key-Label) letzten Eintrag behalten
            $normalizedPaths[(string) $pathKey] = $path;
        }

        // Core-Pfad immer sicherstellen, damit Starter/Demo verfügbar bleiben
        $corePath = rex_path::addon('builder', 'elements');
        if (!isset($normalizedPaths['core'])) {
            $normalizedPaths = ['core' => $corePath] + $normalizedPaths;
        }

        self::$elementPaths = $normalizedPaths;

        return self::$elementPaths;
    }

    /**
     * Liefert Element-Keys aus einem bestimmten Pfad
     *
     * @param string $pathKey z.B. 'core', 'klxm_elements'
     * @return array<int, string> Element-Keys
     */
    public static function getElementsFromPath(string $pathKey): array
    {
        $paths = self::getElementPaths();
        if (!isset($paths[$pathKey])) {
            return [];
        }

        $path = $paths[$pathKey];
        if (!is_dir($path)) {
            return [];
        }

        $elements = [];
        foreach (scandir($path) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $elementPath = $path . '/' . $file;
            if (is_dir($elementPath) && is_file($elementPath . '/config.php')) {
                $elements[] = $file;
            }
        }

        return $elements;
    }

    /**
     * Liefert alle verfügbaren Elemente (bundled + externe)
     *
     * @return array<int, string> Alle Element-Keys
     */
    public static function getAllElements(): array
    {
        $all = [];

        // Bundled-Elemente
        $all = array_merge($all, self::getBundledElements());

        // Externe Element-Pfade
        foreach (self::getElementPaths() as $pathKey => $path) {
            if ('core' === $pathKey) {
                continue; // Bereits in bundled
            }

            $external = self::getElementsFromPath($pathKey);
            $all = array_merge($all, $external);
        }

        // Duplikate entfernen
        return array_unique($all);
    }

    /**
     * Liefert Element-Konfiguration
     *
     * @param string $elementKey z.B. 'starter_text'
     * @return array|null Element-Config oder null
     */
    public static function getElementConfig(string $elementKey): ?array
    {
        foreach (self::getElementPaths() as $pathKey => $basePath) {
            $configPath = $basePath . '/' . $elementKey . '/config.php';
            if (is_file($configPath)) {
                return (array) include $configPath;
            }
        }

        return null;
    }

    /**
     * Registriert einen neuen Element-Pfad (für externe Addons in boot.php)
     * Extension Point-Alternative zu BUILDER_ELEMENT_PATHS
     */
    public static function registerPath(string $key, string $path): void
    {
        self::$elementPaths = null; // Cache invalidieren
    }

    /**
     * Registriert ein bundled Element
     */
    public static function registerBundledElement(string $elementKey): void
    {
        self::$bundledElements = null; // Cache invalidieren
    }

    /**
     * Reset für Tests
     */
    public static function clearCache(): void
    {
        self::$bundledElements = null;
        self::$elementPaths = null;
        self::$registeredElements = null;
    }
}
