<?php

namespace FriendsOfREDAXO\Builder;

use rex_path;
use rex_fragment;

/**
 * Template Engine - Framework-agnostische Template-Verwaltung
 * Lädt Templates basierend auf Framework und Element
 * Ermöglicht Fragment-Dispatch ohne hartcodierte Pfade
 */
class TemplateEngine
{
    private static array $fragmentCache = [];

    /**
     * Rendert ein Template mit dynamischem Framework-Dispatch
     *
     * @param string $templateName z.B. 'wrapper', 'section', 'cards'
     * @param array $data Variablen für das Template
     * @param string $framework z.B. 'uikit', 'bootstrap', 'plain'
     * @return string Gerendertes HTML
     */
    public static function render(string $templateName, array $data = [], string $framework = 'uikit'): string
    {
        // Versuche Framework-spezifisches Template zu laden
        $templatePath = self::getTemplatePath($templateName, $framework);

        if (!$templatePath || !file_exists($templatePath)) {
            // Fallback auf default framework
            $templatePath = self::getTemplatePath($templateName, 'uikit');
        }

        if (!$templatePath || !file_exists($templatePath)) {
            // Fallback auf plain
            $templatePath = self::getTemplatePath($templateName, 'plain');
        }

        if (!$templatePath || !file_exists($templatePath)) {
            return '';
        }

        return self::renderFile($templatePath, $data);
    }

    /**
     * Rendert ein Fragment mit Framework-Dispatch
     * Nutzt rex_fragment intern
     *
     * @param string $fragmentName z.B. 'ycb_elements/wrapper'
     * @param array $data Variablen
     * @param string $framework Framework-Name
     * @return string Gerendertes HTML
     */
    public static function renderFragment(string $fragmentName, array $data = [], string $framework = 'uikit'): string
    {
        // Versuche Framework-spezifisches Fragment zu laden
        $frameworkFragment = "{$fragmentName}.{$framework}";
        
        try {
            $fragment = new rex_fragment();
            foreach ($data as $key => $value) {
                $fragment->setVar($key, $value, false);
            }
            $fragment->setVar('framework', $framework, false);
            
            // Versuche Framework-Variante
            try {
                return $fragment->parse("ycb_fragments/{$framework}/{$fragmentName}.php");
            } catch (\Exception $e) {
                // Fallback auf Standard-Fragment
                return $fragment->parse("ycb_elements/{$fragmentName}.php");
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Ermittelt den Pfad zu einem Template
     *
     * @param string $templateName Template-Name ohne Extension
     * @param string $framework Framework-Name
     * @return string|null Dateipfad oder null
     */
    private static function getTemplatePath(string $templateName, string $framework): ?string
    {
        // Interne Addon-Pfade
        $possiblePaths = [
            // Framework-spezifische Pfade
            rex_path::addon('builder', "elements/{$templateName}/templates/{$framework}.php"),
            rex_path::addon('builder', "fragments/{$framework}/{$templateName}.php"),
            
            // Generic Element-Pfade (ohne Framework-Suffix)
            rex_path::addon('builder', "elements/{$templateName}/template.php"),
            rex_path::addon('builder', "fragments/{$templateName}.php"),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Rendert eine PHP-Datei mit Scope-Isolation
     *
     * @param string $filePath Absolute Dateipfad
     * @param array $data Variablen für das Template
     * @return string Output
     */
    private static function renderFile(string $filePath, array $data = []): string
    {
        // Variablen in lokalen Scope bringen
        extract($data, EXTR_SKIP);

        ob_start();
        include $filePath;
        return ob_get_clean() ?: '';
    }

    /**
     * Registriert ein Custom-Template-Verzeichnis
     * Extension Point: BUILDER_TEMPLATE_PATHS
     *
     * @param string $framework Framework-Name
     * @param string $path Verzeichnispfad
     */
    public static function registerTemplatePath(string $framework, string $path): void
    {
        // Zukünftige Extension für Custom-Pfade
        self::$fragmentCache = [];
    }

    /**
     * Gibt Template-Cache frei (für Tests/Reloads)
     */
    public static function clearCache(): void
    {
        self::$fragmentCache = [];
    }

    /**
     * Prüft, ob ein Template existiert
     */
    public static function hasTemplate(string $templateName, string $framework): bool
    {
        return self::getTemplatePath($templateName, $framework) !== null;
    }

    /**
     * Liefert alle verfügbaren Frameworks
     */
    public static function getAvailableFrameworks(): array
    {
        return ['uikit', 'bootstrap', 'plain'];
    }
}
