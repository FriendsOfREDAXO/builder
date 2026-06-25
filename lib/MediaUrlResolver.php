<?php

namespace FriendsOfREDAXO\Builder;

/**
 * Zentrale Media-URL-Resolver für konsistente Media-Handhabung.
 * 
 * - SVG: direkt aus Medienpool (rex_url::media)
 * - Pixelbilder (PNG, JPG, WebP, AVIF): über Media Manager
 */
class MediaUrlResolver
{
    /**
     * Ermittelt die korrekte Media-URL basierend auf Dateityp.
     *
     * @param string $mediaFile Dateiname aus Medienpool
     * @param string $mediaManagerType Media Manager Typ (z.B. 'card_1_1_w400')
     * @return string Absolute Media-URL
     */
    public static function getUrl(string $mediaFile, string $mediaManagerType = ''): string
    {
        if (!$mediaFile) {
            return '';
        }

        // Dateityp ermitteln
        $ext = strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION));

        // SVG direkt aus Medienpool laden (absolute Pfade)
        if ($ext === 'svg') {
            return rex_url::media($mediaFile);
        }

        // Pixelbilder (PNG, JPG, WebP, AVIF, GIF, etc.) über Media Manager
        if (!$mediaManagerType) {
            // Fallback: direkt aus Medienpool, wenn kein MM-Type angegeben
            return rex_url::media($mediaFile);
        }

        return rex_media_manager::getUrl($mediaManagerType, $mediaFile);
    }

    /**
     * Gibt ein Srcset mit mehreren Breiten und AVIF/WebP Unterstützung zurück.
     *
     * @param string $mediaFile Dateiname aus Medienpool
     * @param string $baseType Media Manager Basis-Typ (z.B. 'card_16_9_w')
     * @param array $widths Breiten für Srcset (z.B. [400, 800, 1200])
     * @return string Srcset String für img[srcset]
     */
    public static function getSrcset(string $mediaFile, string $baseType, array $widths = [400, 800, 1200]): string
    {
        if (!$mediaFile || !$baseType) {
            return '';
        }

        $ext = strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION));

        // SVG hat kein Srcset
        if ($ext === 'svg') {
            return '';
        }

        $srcset = [];
        foreach ($widths as $w) {
            $mmType = $baseType . $w;
            $url = rex_media_manager::getUrl($mmType, $mediaFile);
            if ($url) {
                $srcset[] = $url . ' ' . $w . 'w';
            }
        }

        return implode(', ', $srcset);
    }
}
