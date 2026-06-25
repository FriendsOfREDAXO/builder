<?php

namespace FriendsOfREDAXO\Builder\Config;

use rex;
use rex_addon;
use rex_extension;

/**
 * Zentrale Aufloesung von Element-Modus und registrierten Element-Pfaden.
 */
final class ElementModeResolver
{
    private const CONFIG_KEY_REPLACE_KEEP_CORE_ELEMENTS = 'replace_keep_core_elements';
    private const CONFIG_KEY_ENABLED_ELEMENT_ADDONS = 'enabled_element_addons';

    /**
     * @return array<int, string>
     */
    public static function getCustomPaths(): array
    {
        $customPaths = rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_ELEMENT_PATHS',
            []
        ));

        $normalized = [];
        if (is_array($customPaths)) {
            foreach ($customPaths as $customPath) {
                $path = trim((string) $customPath);
                if ($path !== '') {
                    $normalized[] = rtrim($path, '/\\') . '/';
                }
            }
        }

        if ($normalized === [] && rex_addon::exists('project') && rex_addon::get('project')->isAvailable()) {
            $projectPath = rex_addon::get('project')->getPath('elements/');
            if (is_dir($projectPath)) {
                $normalized[] = $projectPath;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function getElementMode(): string
    {
        $elementMode = (string) rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_ELEMENT_MODE',
            'replace'
        ));

        $normalizedMode = in_array($elementMode, ['replace', 'merge'], true) ? $elementMode : 'replace';
        if ($normalizedMode === 'replace') {
            return 'replace';
        }

        if (self::hasReplaceVoteInAddonConfigs(self::getCustomPaths())) {
            return 'replace';
        }

        return 'merge';
    }

    /**
     * @param array<int, string> $customPaths
     */
    private static function hasReplaceVoteInAddonConfigs(array $customPaths): bool
    {
        $checkedAddons = [];

        foreach ($customPaths as $customPath) {
            $addonKey = self::resolveAddonKeyByPath((string) $customPath, 'custom');
            if ($addonKey === '' || $addonKey === 'project') {
                continue;
            }

            if (isset($checkedAddons[$addonKey])) {
                continue;
            }
            $checkedAddons[$addonKey] = true;

            if (!rex_addon::exists($addonKey) || !rex_addon::get($addonKey)->isAvailable()) {
                continue;
            }

            $rawMode = rex_addon::get($addonKey)->getConfig('element_mode', null);
            if (!is_string($rawMode)) {
                continue;
            }

            if (strtolower(trim($rawMode)) === 'replace') {
                return true;
            }
        }

        return false;
    }

    public static function shouldLoadBundledElements(): bool
    {
        return self::getElementMode() === 'merge';
    }

    /**
     * @return array<int, string>
     */
    public static function getEnabledElementAddons(): array
    {
        $raw = rex_addon::get('builder')->getConfig(self::CONFIG_KEY_ENABLED_ELEMENT_ADDONS, []);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_map('trim', explode(',', $raw));
            }
        }

        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $addonKey) {
            $addonKey = trim((string) $addonKey);
            if ($addonKey !== '') {
                $normalized[] = $addonKey;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, array<string, mixed>> $elements
     * @return array<string, array<string, mixed>>
     */
    public static function filterElementsByEnabledAddons(array $elements): array
    {
        $enabledAddons = self::getEnabledElementAddons();
        if ($enabledAddons === []) {
            // Leere Konfiguration bedeutet: alle erlauben
            return $elements;
        }

        $enabledMap = array_flip($enabledAddons);
        $replaceKeepCoreElements = array_flip(self::getReplaceKeepCoreElements());

        return array_filter(
            $elements,
            static function (mixed $config) use ($enabledMap, $replaceKeepCoreElements): bool {
                if (!is_array($config)) {
                    return false;
                }

                $addonKey = trim((string) ($config['_addon'] ?? ''));
                if ($addonKey === '') {
                    return true;
                }

                // Explizite Ausnahmen für mitgelieferte Core-Elemente müssen auch dann sichtbar
                // bleiben, wenn das eigene AddOn in der AddOn-Auswahl nicht aktiviert ist.
                if ($addonKey === 'builder') {
                    $elementKey = trim((string) ($config['key'] ?? $config['type'] ?? ''));
                    if ($elementKey !== '' && isset($replaceKeepCoreElements[$elementKey])) {
                        return true;
                    }
                }

                return isset($enabledMap[$addonKey]);
            }
        );
    }

    /**
     * @param array<int, string> $customPaths
     * @return array<string, string>
     */
    public static function getAddonChoices(array $customPaths): array
    {
        $choices = [
            'builder' => self::resolveAddonLabel('builder'),
        ];

        foreach ($customPaths as $customPath) {
            $addonKey = self::resolveAddonKeyByPath((string) $customPath, 'custom');
            if ($addonKey === '') {
                continue;
            }

            $choices[$addonKey] = self::resolveAddonLabel($addonKey);
        }

        return $choices;
    }

    public static function resolveAddonLabel(string $addonKey): string
    {
        $addonKey = trim($addonKey);
        if ($addonKey === '') {
            return 'Unbekannt';
        }

        if (rex_addon::exists($addonKey)) {
            $addon = rex_addon::get($addonKey);
            $title = trim((string) $addon->getProperty('title'));
            if ($title !== '') {
                return $title . ' [' . $addonKey . ']';
            }
        }

        return ucfirst(str_replace('_', ' ', $addonKey)) . ' [' . $addonKey . ']';
    }

    public static function resolveAddonKeyByPath(string $basePath, string $source = 'custom'): string
    {
        if ($source === 'demo') {
            return 'builder';
        }

        $normalized = str_replace('\\', '/', trim($basePath));
        if ($normalized === '') {
            return '';
        }

        if (preg_match('#/(?:redaxo/src/)?addons/([a-z0-9_\-]+)/?#i', $normalized, $matches) === 1) {
            return strtolower((string) $matches[1]);
        }

        if (preg_match('#/data/addons/([a-z0-9_\-]+)/?#i', $normalized, $matches) === 1) {
            return strtolower((string) $matches[1]);
        }

        if (str_contains($normalized, '/project/')) {
            return 'project';
        }

        return trim((string) basename(rtrim($normalized, '/')));
    }

    /**
     * Liefert Core-Elemente, die trotz replace-Modus verfuegbar bleiben sollen.
     *
     * @return array<int, string>
     */
    public static function getReplaceKeepCoreElements(): array
    {
        $raw = rex_addon::get('builder')->getConfig(self::CONFIG_KEY_REPLACE_KEEP_CORE_ELEMENTS, []);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_map('trim', explode(',', $raw));
            }
        }

        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $elementKey) {
            $elementKey = trim((string) $elementKey);
            if ($elementKey !== '') {
                $normalized[] = $elementKey;
            }
        }

        return array_values(array_unique($normalized));
    }
}
