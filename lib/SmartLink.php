<?php

namespace FriendsOfREDAXO\Builder;

use rex_addon;
use rex_escape;
use rex_media;
use rex_url;
use Throwable;

/**
 * Normalisiert kombinierte Link-Feldwerte und erzeugt ausgabefähige Hrefs.
 */
class SmartLink
{
    /**
     * @return list<array{type:string,value:string,label:string,pdfjs:bool}>
     */
    public static function normalize(mixed $rawValue, bool $multiple = false): array
    {
        if (is_array($rawValue)) {
            $items = $rawValue['items'] ?? $rawValue;

            return self::normalizeItems($items, $multiple);
        }

        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            $items = $decoded['items'] ?? $decoded;

            return self::normalizeItems($items, $multiple);
        }

        $value = trim($rawValue);
        if ($value === '') {
            return [];
        }

        return [[
            'type' => self::detectType($value),
            'value' => $value,
            'label' => '',
            'pdfjs' => false,
        ]];
    }

    /**
     * @param iterable<mixed> $items
     * @return list<array{type:string,value:string,label:string,pdfjs:bool}>
     */
    private static function normalizeItems(iterable $items, bool $multiple): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $value = trim((string) ($item['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $type = trim((string) ($item['type'] ?? 'auto'));
            if ($type === '' || $type === 'auto') {
                $type = self::detectType($value);
            }

            $normalized[] = [
                'type' => $type,
                'value' => $value,
                'label' => trim((string) ($item['label'] ?? '')),
                'pdfjs' => (bool) ($item['pdfjs'] ?? false),
            ];

            if (!$multiple) {
                break;
            }
        }

        return $normalized;
    }

    public static function detectType(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'url';
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'mail';
        }

        if (preg_match('/^\+?[0-9\s\-\(\)\/]{5,}$/', $value) === 1) {
            return 'tel';
        }

        if (preg_match('/^[a-z0-9_]+:\d+$/i', $value) === 1) {
            return 'yform';
        }

        if (preg_match('@^yform://[a-z0-9_]+/\d+$@i', $value) === 1) {
            return 'yform';
        }

        if (ctype_digit($value)) {
            return 'intern';
        }

        if (self::looksLikeMedia($value)) {
            return 'media';
        }

        return 'url';
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function buildHref(array $item): string
    {
        $type = (string) ($item['type'] ?? 'url');
        $value = trim((string) ($item['value'] ?? ''));

        if ($value === '') {
            return '';
        }

        if ($type === 'mail') {
            return 'mailto:' . $value;
        }

        if ($type === 'tel') {
            return 'tel:' . preg_replace('/[^\d\+]/', '', $value);
        }

        if ($type === 'media') {
            if (preg_match('@^https?://@i', $value) === 1) {
                return $value;
            }

            return rex_url::media($value);
        }

        if ($type === 'yform') {
            [$profileId, $id] = array_pad(explode(':', $value, 2), 2, '');
            if ($profileId !== '' && ctype_digit($id)) {
                $profile = ListProfiles::get($profileId);
                if (is_array($profile)) {
                    return self::resolveYformHrefFromProfile($profile, (int) $id);
                }

                return '?id=' . $id;
            }

            if (preg_match('@^yform://([a-z0-9_]+)/([0-9]+)$@i', $value, $matches) === 1) {
                $tableAlias = strtolower((string) $matches[1]);
                $id = (int) $matches[2];
                if ($id > 0) {
                    $profile = self::findProfileByTableAlias($tableAlias);
                    if (is_array($profile)) {
                        return self::resolveYformHrefFromProfile($profile, $id);
                    }

                    return '?id=' . $id;
                }
            }

            if (ctype_digit($value)) {
                return '?id=' . $value;
            }

            return '';
        }

        if ($type === 'intern' && ctype_digit($value)) {
            return rex_getUrl((int) $value);
        }

        return $value;
    }

    public static function isMediaPdf(string $mediaFile): bool
    {
        return strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION)) === 'pdf';
    }

    public static function buildPdfJsHref(string $mediaFile): string
    {
        $mediaUrl = rex_url::media($mediaFile);
        if (!rex_addon::get('pdfout')->isAvailable()) {
            return $mediaUrl;
        }

        $viewer = rex_url::addonAssets('pdfout', 'pdfjs/web/viewer.html');

        return $viewer . '?file=' . rawurlencode($mediaUrl);
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function linkLabel(array $item): string
    {
        $label = trim((string) ($item['label'] ?? ''));
        if ($label !== '') {
            return $label;
        }

        $value = trim((string) ($item['value'] ?? ''));
        if ($value === '') {
            return '';
        }

        return rex_escape($value);
    }

    private static function looksLikeMedia(string $value): bool
    {
        $media = rex_media::get($value);
        if ($media !== null) {
            return true;
        }

        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'webm', 'mov', 'avi'], true);
    }

    /**
     * @param array<string,mixed> $profile
     */
    private static function resolveYformHrefFromProfile(array $profile, int $id): string
    {
        $tableName = (string) ($profile['table'] ?? '');

        if (!empty($profile['use_virtual_urls']) && $id > 0 && '' !== $tableName && ListProfiles::hasVirtualUrls()) {
            try {
                $vUrl = \FriendsOfRedaxo\VirtualUrl\VirtualUrlsHelper::getUrl($tableName, $id);
                if (null !== $vUrl && '' !== $vUrl) {
                    return $vUrl;
                }
            } catch (Throwable) {
                // fallback
            }
        }

        $urlProfile = trim((string) ($profile['url_profile'] ?? ''));
        if ('' !== $urlProfile && $id > 0 && function_exists('rex_getUrl')) {
            try {
                $url = rex_getUrl('', '', [$urlProfile => $id]);
                if ('' !== $url) {
                    return $url;
                }
            } catch (Throwable) {
                // fallback
            }
        }

        $pattern = trim((string) ($profile['url_pattern'] ?? ''));
        if ('' !== $pattern) {
            return str_replace('{id}', (string) $id, $pattern);
        }

        return '?id=' . $id;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function findProfileByTableAlias(string $tableAlias): ?array
    {
        $needle = strtolower(trim($tableAlias));
        if ($needle === '') {
            return null;
        }

        foreach (ListProfiles::getAll() as $profile) {
            $tableName = strtolower(trim((string) ($profile['table'] ?? '')));
            if ($tableName === '') {
                continue;
            }

            $normalized = str_starts_with($tableName, 'rex_') ? substr($tableName, 4) : $tableName;
            if ($normalized === $needle || $tableName === $needle) {
                return $profile;
            }
        }

        return null;
    }
}
