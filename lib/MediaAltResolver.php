<?php

namespace FriendsOfREDAXO\Builder;

use rex_exception;
use rex_file;
use rex_media;

/**
 * Ermittelt sinnvolle ALT-Texte elementübergreifend aus Mediapool-Metadaten.
 */
class MediaAltResolver
{
    /**
     * @var array<string, array{alt: string, title: string}>
     */
    private static array $metaCache = [];

    /**
     * Liefert einen ALT-Text anhand von Priorität:
     * 0) Als "dekorativ" markiert (mediaplace) -> immer leerer String, WCAG-korrekt
     * 1) Manuell übergebener Alt-Text (wenn sinnvoll)
     * 2) med_alt (wenn sinnvoll)
     * 3) Titel aus Mediapool (wenn sinnvoll und nicht Dateiname)
     * 4) Kontext-Fallback (wenn sinnvoll)
     * 5) leerer String
     */
    public static function resolve(
        string $mediaFile,
        string $manualAlt = '',
        string $contextFallback = '',
        bool $isLinkedImageWithDescriptiveText = false,
        string $linkedText = ''
    ): string {
        $fileName = basename(trim($mediaFile));

        if ('' === $fileName) {
            return '';
        }

        if ($isLinkedImageWithDescriptiveText && self::isMeaningfulText($linkedText, $fileName)) {
            // Bei verlinktem Bild mit vorhandenem beschreibendem Linktext ist das Bild dekorativ.
            return '';
        }

        if (self::isDecorative($fileName)) {
            // Redakteur hat das Bild im Mediapool (mediaplace-Addon) explizit als rein
            // dekorativ markiert - WCAG verlangt dafuer ein LEERES alt-Attribut, nicht
            // gar keins und nicht einen aufgeloesten Alt-Text (der sonst trotzdem
            // greifen wuerde, z.B. der Mediapool-Titel). Gewinnt bewusst gegen einen
            // manuell im Element gesetzten Alt-Text - "dekorativ" ist eine Aussage
            // ueber das BILD selbst, sollte also nicht durch einen abweichenden lokalen
            // Text im jeweiligen Slice unterlaufen werden.
            return '';
        }

        $manualAlt = trim($manualAlt);
        if (self::isMeaningfulText($manualAlt, $fileName)) {
            return $manualAlt;
        }

        $ownAlt = self::ownMetadataAlt($fileName);
        if (null !== $ownAlt && self::isMeaningfulText($ownAlt, $fileName)) {
            return $ownAlt;
        }

        $meta = self::getMediaMeta($fileName);

        if (self::isMeaningfulText($meta['alt'], $fileName)) {
            return $meta['alt'];
        }

        if (self::isMeaningfulText($meta['title'], $fileName)) {
            return $meta['title'];
        }

        $contextFallback = trim($contextFallback);
        if (self::isMeaningfulText($contextFallback, $fileName)) {
            return $contextFallback;
        }

        return '';
    }

    /**
     * Prueft BEIDE mediaplace-Mechanismen fuer "dekoratives Bild, kein Alt-Text
     * noetig": das klassische Checkbox-Metainfo-Feld "med_alt_decorative" UND -
     * falls mediaplace's neueres, JSON-basiertes Metadaten-System aktiv ist
     * (eigener Alt-Feld-Widget-Typ "alt" in med_json_data) - dessen "decorative"-
     * Flag. mediaplace ist fuer builder ein rein OPTIONALES Addon (wie schon der
     * bestehende mediaplace-bridge.js-Include in boot.php) - fehlt die Klasse/das
     * Metainfo-Feld, gilt ein Bild einfach als nicht-dekorativ (kein Fehlerfall,
     * kein Fatal Error).
     */
    private static function isDecorative(string $fileName): bool
    {
        if (!class_exists(\rex_media::class)) {
            return false;
        }

        $media = \rex_media::get($fileName);
        if (null === $media) {
            return false;
        }

        if (class_exists(\FriendsOfRedaxo\Mediaplace\AltTextStatus::class)) {
            $ownField = \FriendsOfRedaxo\Mediaplace\AltTextStatus::resolveOwnAltField();
            if (null !== $ownField) {
                $json = json_decode((string) $media->getValue('med_json_data'), true);
                $ownData = is_array($json) ? $json : [];
                $value = $ownData[$ownField->getKey()] ?? null;
                return is_array($value) && !empty($value['decorative']);
            }
        }

        try {
            return (bool) $media->getValue('med_alt_decorative');
        } catch (rex_exception $e) {
            // Feld existiert nicht (mediaplace nicht installiert/Feld nicht angelegt) -
            // dann ist "dekorativ" schlicht nicht bekannt, kein Fehlerfall.
            return false;
        }
    }

    /**
     * Liest einen Alt-Text aus mediaplace's neuerem JSON-Metadaten-System (Widget-Typ
     * "alt", siehe isDecorative()-Docblock), sprachrichtig fuer die aktuelle rex_clang
     * mit Fallback auf irgendeine nicht-leere Sprache. Liefert null, wenn dieses System
     * nicht aktiv ist oder das Feld leer ist - resolve() faellt dann auf das klassische
     * med_alt-Metainfo-Feld zurueck (siehe getMediaMeta()).
     */
    private static function ownMetadataAlt(string $fileName): ?string
    {
        if (!class_exists(\FriendsOfRedaxo\Mediaplace\AltTextStatus::class)) {
            return null;
        }

        $ownField = \FriendsOfRedaxo\Mediaplace\AltTextStatus::resolveOwnAltField();
        if (null === $ownField) {
            return null;
        }

        $media = \rex_media::get($fileName);
        if (null === $media) {
            return null;
        }

        $json = json_decode((string) $media->getValue('med_json_data'), true);
        $ownData = is_array($json) ? $json : [];
        $value = $ownData[$ownField->getKey()] ?? null;
        if (!is_array($value) || empty($value['text'])) {
            return null;
        }

        $texts = (array) $value['text'];
        $currentClangId = class_exists(\rex_clang::class) ? \rex_clang::getCurrentId() : null;
        if (null !== $currentClangId && isset($texts[$currentClangId]) && '' !== trim((string) $texts[$currentClangId])) {
            return trim((string) $texts[$currentClangId]);
        }

        foreach ($texts as $text) {
            if ('' !== trim((string) $text)) {
                return trim((string) $text);
            }
        }

        return null;
    }

    /**
     * @return array{alt: string, title: string}
     */
    private static function getMediaMeta(string $fileName): array
    {
        if (isset(self::$metaCache[$fileName])) {
            return self::$metaCache[$fileName];
        }

        $meta = [
            'alt' => '',
            'title' => '',
        ];

        $media = rex_media::get($fileName);
        if (null !== $media) {
            foreach (['med_alt', 'med_alttext', 'med_alt_text'] as $key) {
                try {
                    $value = trim((string) $media->getValue($key));
                } catch (rex_exception $e) {
                    $value = '';
                }

                if ('' !== $value) {
                    $meta['alt'] = $value;
                    break;
                }
            }

            $title = trim((string) $media->getTitle());
            if ('' !== $title) {
                $meta['title'] = $title;
            }
        }

        self::$metaCache[$fileName] = $meta;

        return $meta;
    }

    private static function isMeaningfulText(string $text, string $fileName): bool
    {
        $text = trim($text);
        if ('' === $text) {
            return false;
        }

        $normalizedText = self::normalizeForCompare($text);
        if ('' === $normalizedText) {
            return false;
        }

        $normalizedFileName = self::normalizeForCompare($fileName);
        $normalizedFileStem = self::normalizeForCompare(self::fileStem($fileName));

        if ($normalizedText === $normalizedFileName || $normalizedText === $normalizedFileStem) {
            return false;
        }

        return true;
    }

    private static function fileStem(string $fileName): string
    {
        $ext = rex_file::extension($fileName);
        if ('' === $ext) {
            return $fileName;
        }

        return substr($fileName, 0, -(strlen($ext) + 1));
    }

    private static function normalizeForCompare(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['_', '-', '.'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }
}
