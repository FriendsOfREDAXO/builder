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

        $manualAlt = trim($manualAlt);
        if (self::isMeaningfulText($manualAlt, $fileName)) {
            return $manualAlt;
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
