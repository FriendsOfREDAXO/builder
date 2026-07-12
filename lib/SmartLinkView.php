<?php

namespace FriendsOfREDAXO\Builder;

use rex_file;
use rex_media;
use rex_media_manager;
use rex_url;

/**
 * Kleine View-Hilfe fuer smart_link Ausgaben in Templates.
 */
class SmartLinkView
{
    /**
     * @return array{label:string,uikit_icon:string,fa_icon:string}
     */
    public static function getTypeMeta(string $type): array
    {
        $types = [
            'url' => ['label' => 'URL', 'uikit_icon' => 'link', 'fa_icon' => 'fa-link'],
            'intern' => ['label' => 'Intern', 'uikit_icon' => 'file-text', 'fa_icon' => 'fa-file-lines'],
            'media' => ['label' => 'Media', 'uikit_icon' => 'image', 'fa_icon' => 'fa-image'],
            'mail' => ['label' => 'E-Mail', 'uikit_icon' => 'mail', 'fa_icon' => 'fa-envelope'],
            'tel' => ['label' => 'Telefon', 'uikit_icon' => 'receiver', 'fa_icon' => 'fa-phone'],
            'yform' => ['label' => 'YForm', 'uikit_icon' => 'database', 'fa_icon' => 'fa-database'],
        ];

        return $types[$type] ?? ['label' => 'Link', 'uikit_icon' => 'link', 'fa_icon' => 'fa-link'];
    }

    /**
     * @return array{type:string,href:string,label:string,is_external:bool}|null
     */
    public static function resolveSingle(mixed $rawLink, string $fallbackLabel = ''): ?array
    {
        $normalized = SmartLink::normalize($rawLink, false);
        if ($normalized === []) {
            return null;
        }

        $item = $normalized[0];
        $type = (string) ($item['type'] ?? 'url');
        $href = SmartLink::buildHref($item);
        if ($href === '') {
            return null;
        }

        $value = (string) ($item['value'] ?? '');
        if ($type === 'media' && (($item['pdfjs'] ?? false) === true) && SmartLink::isMediaPdf($value)) {
            $href = SmartLink::buildPdfJsHref($value);
        }

        $label = trim((string) SmartLink::linkLabel($item));
        if ($label === '') {
            $label = $fallbackLabel !== '' ? $fallbackLabel : $href;
        }

        return [
            'type' => $type,
            'href' => $href,
            'label' => $label,
            'is_external' => preg_match('@^https?://@i', $href) === 1,
        ];
    }

    /**
     * @return array{kind:string,src:string}|null
     */
    public static function resolvePreview(mixed $rawLink): ?array
    {
        $normalized = SmartLink::normalize($rawLink, false);
        if ($normalized === []) {
            return null;
        }

        $item = $normalized[0];
        $type = (string) ($item['type'] ?? 'url');
        $value = trim((string) ($item['value'] ?? ''));
        if ($value === '') {
            return null;
        }

        $href = SmartLink::buildHref($item);
        $valuePath = (string) parse_url($value, PHP_URL_PATH);
        $extension = strtolower(rex_file::extension($valuePath !== '' ? $valuePath : $value));

        if (preg_match('@^https?://@i', $value) === 1) {
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true)) {
                return [
                    'kind' => 'image',
                    'src' => $value,
                ];
            }

            if (in_array($extension, ['mp4', 'webm', 'mov'], true)) {
                return [
                    'kind' => 'video',
                    'src' => $value,
                ];
            }

            return null;
        }

        if ($type !== 'media') {
            return null;
        }

            $mediaName = (string) parse_url($href, PHP_URL_PATH);
            $mediaName = preg_replace('@^.*/media/@', '', $mediaName) ?? '';
            $media = $mediaName !== '' ? rex_media::get($mediaName) : null;
        if ($media === null) {
            return null;
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            $previewType = \FriendsOfREDAXO\Builder\Config\MediaTypeRegistry::buildVirtualType('starter_cards_16_9', 800);
            return [
                'kind' => 'image',
                    'src' => rex_media_manager::getUrl($previewType, $mediaName),
            ];
        }

        if (in_array($extension, ['mp4', 'webm', 'mov'], true)) {
            return [
                'kind' => 'video',
                    'src' => rex_url::media($mediaName),
            ];
        }

        return null;
    }
}