<?php

namespace FriendsOfREDAXO\Builder\Media;

use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

final class ResponsiveImage
{
    private string $file;
    private string $desktopPreset = '';
    private string $mobilePreset = '';

    /** @var list<int> */
    private array $widths = [400, 800, 1200, 1600];

    private string $containerWidth = 'uk-container';
    private int $columns = 3;
    private int $columnsTablet = 2;
    private int $columnsMobile = 1;
    private float $mediaFraction = 1.0;
    private int $mobileBreakpoint = 639;

    private function __construct(string $file)
    {
        $this->file = $file;
    }

    public static function forFile(string $file): self
    {
        return new self($file);
    }

    public function withDesktopPreset(string $preset): self
    {
        $this->desktopPreset = $preset;
        return $this;
    }

    public function withMobilePreset(string $preset): self
    {
        $this->mobilePreset = $preset;
        return $this;
    }

    /**
     * @param list<int> $widths
     */
    public function withWidths(array $widths): self
    {
        $normalized = [];
        foreach ($widths as $width) {
            $w = (int) $width;
            if ($w > 0) {
                $normalized[] = $w;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_NUMERIC);

        if ($normalized !== []) {
            $this->widths = $normalized;
        }

        return $this;
    }

    public function withContainerWidth(string $containerWidth): self
    {
        $this->containerWidth = $containerWidth;
        return $this;
    }

    public function withColumns(int $desktop, int $tablet, int $mobile): self
    {
        $this->columns = max(1, $desktop);
        $this->columnsTablet = max(1, $tablet);
        $this->columnsMobile = max(1, $mobile);

        return $this;
    }

    public function withMediaFraction(float $fraction): self
    {
        $this->mediaFraction = max(0.05, min(1.0, $fraction));
        return $this;
    }

    public function withMobileBreakpoint(int $breakpoint): self
    {
        $this->mobileBreakpoint = max(1, $breakpoint);
        return $this;
    }

    /**
     * @return array{src:string, srcset:string, sizes:string}
     */
    public function toImage(): array
    {
        if ($this->file === '') {
            return ['src' => '', 'srcset' => '', 'sizes' => ''];
        }

        $src = $this->resolveSrc($this->desktopPreset);
        $srcset = $this->buildSrcset($this->desktopPreset);

        return [
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => $this->buildSizes(),
        ];
    }

    /**
     * @return array{sources:list<array{media:string, srcset:string, sizes:string}>, img:array{src:string, srcset:string, sizes:string}}
     */
    public function toPicture(): array
    {
        $img = $this->toImage();
        $sources = [];

        if (
            $this->mobilePreset !== ''
            && $this->mobilePreset !== $this->desktopPreset
            && $this->file !== ''
        ) {
            $mobileSrcset = $this->buildSrcset($this->mobilePreset);
            if ($mobileSrcset !== '') {
                $sources[] = [
                    'media' => '(max-width: ' . $this->mobileBreakpoint . 'px)',
                    'srcset' => $mobileSrcset,
                    'sizes' => $this->buildMobileSizes(),
                ];
            }
        }

        return [
            'sources' => $sources,
            'img' => $img,
        ];
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    public function toImageTag(array $attributes = []): string
    {
        $img = $this->toImage();
        if ($img['src'] === '') {
            return '';
        }

        $attrs = [
            'src' => $img['src'],
            'alt' => '',
            'loading' => 'lazy',
        ];

        if ($img['srcset'] !== '') {
            $attrs['srcset'] = $img['srcset'];
        }

        if ($img['sizes'] !== '') {
            $attrs['sizes'] = $img['sizes'];
        }

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $attrs[$key] = $value;
        }

        return self::renderTag('img', $attrs, true);
    }

    /**
     * @param array<string, scalar|null> $imgAttributes
     * @param array<string, scalar|null> $pictureAttributes
     */
    public function toPictureTag(array $imgAttributes = [], array $pictureAttributes = []): string
    {
        $picture = $this->toPicture();
        $imgTag = $this->toImageTag($imgAttributes);

        if ($imgTag === '') {
            return '';
        }

        if ($picture['sources'] === []) {
            return $imgTag;
        }

        $html = self::renderOpenTag('picture', $pictureAttributes);
        foreach ($picture['sources'] as $source) {
            $html .= self::renderTag('source', [
                'media' => $source['media'],
                'srcset' => $source['srcset'],
                'sizes' => $source['sizes'],
            ], true);
        }

        $html .= $imgTag;
        $html .= '</picture>';

        return $html;
    }

    private function resolveSrc(string $preset): string
    {
        if ($this->file === '') {
            return '';
        }

        if (!\rex_addon::get('media_manager')->isAvailable() || $preset === '') {
            return \rex_url::media($this->file);
        }

        $preferredWidth = in_array(1200, $this->widths, true)
            ? 1200
            : (int) max($this->widths);

        return \rex_media_manager::getUrl(
            MediaTypeRegistry::buildVirtualType($preset, $preferredWidth),
            $this->file
        );
    }

    private function buildSrcset(string $preset): string
    {
        if ($this->file === '' || $preset === '' || !\rex_addon::get('media_manager')->isAvailable()) {
            return '';
        }

        $srcset = [];
        foreach ($this->widths as $width) {
            $srcset[] = \rex_media_manager::getUrl(
                MediaTypeRegistry::buildVirtualType($preset, $width),
                $this->file
            ) . ' ' . $width . 'w';
        }

        return implode(', ', $srcset);
    }

    private function buildSizes(): string
    {
        $containerMaxPx = $this->estimateContainerMaxPx($this->containerWidth);

        $desktopPx = (int) max(220, round(($containerMaxPx / max(1, $this->columns)) * $this->mediaFraction));
        $tabletVw = (int) max(20, min(100, floor((100 / max(1, $this->columnsTablet)) * $this->mediaFraction)));
        $mobileVw = (int) max(30, min(100, floor((100 / max(1, $this->columnsMobile)) * $this->mediaFraction)));

        return sprintf(
            '(min-width: 1200px) %dpx, (min-width: 640px) %dvw, %dvw',
            $desktopPx,
            $tabletVw,
            $mobileVw
        );
    }

    private function buildMobileSizes(): string
    {
        $mobileVw = (int) max(30, min(100, floor((100 / max(1, $this->columnsMobile)) * $this->mediaFraction)));

        return $mobileVw . 'vw';
    }

    private function estimateContainerMaxPx(string $container): int
    {
        if (str_contains($container, 'xsmall')) {
            return 640;
        }
        if (str_contains($container, 'small')) {
            return 900;
        }
        if (str_contains($container, 'xlarge')) {
            return 1600;
        }
        if (str_contains($container, 'large')) {
            return 1400;
        }
        if (str_contains($container, 'expand') || $container === '') {
            return 1920;
        }

        return 1200;
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    private static function renderOpenTag(string $tag, array $attributes = []): string
    {
        return '<' . $tag . self::renderAttributes($attributes) . '>';
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    private static function renderTag(string $tag, array $attributes = [], bool $selfClosing = false): string
    {
        $html = '<' . $tag . self::renderAttributes($attributes);

        if ($selfClosing) {
            return $html . '>';
        }

        return $html . '></' . $tag . '>';
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    private static function renderAttributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $parts[] = $name;
                continue;
            }

            $parts[] = $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
        }

        return $parts === [] ? '' : ' ' . implode(' ', $parts);
    }
}
