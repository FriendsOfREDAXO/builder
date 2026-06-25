<?php

namespace FriendsOfRedaxo\Builder;

use rex_addon;
use rex_extension;
use rex_extension_point;

class ElementManager
{
    /** @var array<string, array<string, mixed>> */
    private static array $elements = [];

    /**
     * Initializes the element registry.
     */
    public static function init(): void
    {
        self::$elements = [];

        // Scan built-in elements folder
        $defaultDir = '';
        if (class_exists('rex_addon') && rex_addon::exists('builder')) {
            $addon = rex_addon::get('builder');
            if ($addon instanceof \rex_addon && $addon->isAvailable()) {
                $defaultDir = $addon->getPath('elements');
            }
        }
        if (!$defaultDir) {
            $defaultDir = dirname(__DIR__) . '/elements';
        }
        self::registerDir($defaultDir);

        // Allow other plugins/addons to register elements
        self::$elements = rex_extension::registerPoint(
            new rex_extension_point('BUILDER_REGISTER_ELEMENTS', self::$elements)
        );
    }

    /**
     * Registers all elements found in a directory.
     */
    public static function registerDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $elementPath = $dir . '/' . $item;
            if (is_dir($elementPath) && file_exists($elementPath . '/config.json')) {
                $configJson = file_get_contents($elementPath . '/config.json');
                if ($configJson !== false) {
                    $config = json_decode($configJson, true);
                    if (is_array($config) && isset($config['key'])) {
                        $config['path'] = $elementPath;
                        self::$elements[$config['key']] = $config;
                    }
                }
            }
        }
    }

    /**
     * Returns all registered elements.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getElements(): array
    {
        if (empty(self::$elements)) {
            self::init();
        }
        return self::$elements;
    }

    /**
     * Resolves a single element definition by key.
     */
    public static function getElement(string $key): ?array
    {
        $elements = self::getElements();
        return $elements[$key] ?? null;
    }
}
