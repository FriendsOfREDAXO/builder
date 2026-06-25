<?php

namespace FriendsOfREDAXO\Builder\Config;

use rex_extension;
use rex_addon;

/**
 * Editor-Profile Verwaltung
 * Zentrale Konfiguration für TinyMCE/CKE5 Profile je nach Element/Kontext
 */
class EditorConfig
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
     * Liefert das Editor-Profil für ein Element
     * Default: 'default' (TinyMCE)
     * 
     * Extension Point: BUILDER_EDITOR_PROFILES
     *
     * @param string $elementKey z.B. 'starter_text'
     * @param string $fieldName z.B. 'text'
     * @return string Profil-Name
     */
    public static function getEditorProfile(string $elementKey, string $fieldName = 'text'): string
    {
        $cacheKey = "profile_{$elementKey}_{$fieldName}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        // Default-Mapping
        $defaults = [
            'starter_text' => 'default',
            'starter_cards' => 'default',
            'starter_media_split' => 'default',
            'starter_headline' => 'default',
            'starter_callout' => 'default',
        ];

        $defaultProfile = $defaults[$elementKey] ?? 'default';

        // Extension Point für Custom-Profile
        $result = self::applyExtensionPoint('BUILDER_EDITOR_PROFILES', $defaultProfile, [
            'element' => $elementKey,
            'field' => $fieldName,
        ]);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Liefert den Editor-Typ für ein Profil
     * z.B. 'tinymce', 'ckeditor5', 'plain'
     */
    public static function getEditorTypeForProfile(string $profile): string
    {
        // Prüfe ob TinyMCE-Profil existiert
        if (rex_addon::get('tinymce')->isAvailable()) {
            $profiles = rex_addon::get('tinymce')->getConfig('profiles') ?? [];
            if (isset($profiles[$profile])) {
                return 'tinymce';
            }
        }

        // Fallback auf CKE5
        return 'ckeditor5';
    }

    /**
     * Liefert alle registrierten Elemente mit ihren Profilen
     */
    public static function getElementProfiles(): array
    {
        if (isset(self::$cache['element_profiles'])) {
            return self::$cache['element_profiles'];
        }

        $default = [
            'starter_text' => 'default',
            'starter_cards' => 'default',
            'starter_media_split' => 'default',
            'starter_headline' => 'default',
            'starter_callout' => 'default',
        ];

        $result = self::applyExtensionPoint('BUILDER_ELEMENT_PROFILES', $default);

        self::$cache['element_profiles'] = $result;
        return $result;
    }

    /**
     * Registriert ein Custom-Editor-Profil (für externe Addons)
     * Diese Methode wird intern von Extension Points aufgerufen
     *
     * @param string $elementKey Element-Schlüssel
     * @param string $profile Editor-Profil-Name
     */
    public static function registerElementProfile(string $elementKey, string $profile): void
    {
        unset(self::$cache['element_profiles']);
    }

    /**
     * Reset-Methode für Tests
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
