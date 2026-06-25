<?php

namespace FriendsOfREDAXO\Builder;

use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;
use rex_extension_point;

class MediaManagerFilterset
{
    /**
     * @param rex_extension_point<list<array{effect: string, params: array<string, mixed>}>> $ep
     * @return list<array{effect: string, params: array<string, mixed>}>
     */
    public static function apply(rex_extension_point $ep): array
    {
        $subject = $ep->getSubject();
        $mediaType = (string) $ep->getParam('rex_media_type');

        $aliasMap = [
            'content_slideshow' => ['preset' => 'section_background', 'width' => 1920],
            'builder_preview' => ['preset' => 'backend_preview', 'width' => 800],
        ];
        if (isset($aliasMap[$mediaType])) {
            $parsed = $aliasMap[$mediaType];
        } else {
            $parsed = MediaTypeRegistry::parseVirtualType($mediaType);
        }

        if ($parsed === null) {
            return $subject;
        }

        $presets = MediaTypeRegistry::getPresets();
        $preset = $parsed['preset'];
        if (!isset($presets[$preset])) {
            return $subject;
        }

        $config = $presets[$preset];
        $width = MediaTypeRegistry::normalizeWidth($config, $parsed['width']);

        return [[
            'effect' => 'content_builder',
            'params' => [
                'preset' => $preset,
                'ratio' => (string) ($config['ratio'] ?? '16_9'),
                'mode' => (string) ($config['mode'] ?? 'focuspoint'),
                'width' => $width,
                'allow_enlarge' => 'not_enlarge',
            ],
        ], ...self::getOptionalEffects()];
    }

    /**
     * @return list<array{effect: string, params: array<string, mixed>}>
     */
    private static function getOptionalEffects(): array
    {
        if (!\FriendsOfREDAXO\Builder\MediaNegotiatorBridge::isEnabled() || !class_exists('rex_effect_negotiator')) {
            return [];
        }

        return [[
            'effect' => 'negotiator',
            'params' => [],
        ]];
    }
}
