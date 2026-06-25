<?php

namespace FriendsOfREDAXO\Builder;

use rex_addon;
use rex_escape;

/**
 * SVG Layout Preview Generator
 * Generiert programmatisch SVG-Vorschaubilder für Layout-Optionen.
 * Vorrangig werden externe SVG-Dateien aus assets/icons/ verwendet,
 * da data:-URLs in Bootstrap-Select nicht zuverlässig gerendert werden.
 */
class Svg
{
    /**
     * Gibt die URL zu einer Icon-SVG-Datei zurück.
     * Dateien liegen in assets/icons/<name>.svg
     */
    public static function iconUrl(string $name): string
    {
        return rex_addon::get('builder')->getAssetsUrl('icons/' . $name . '.svg');
    }

    /**
     * Erzeugt img-Tag für Selectpicker-Icon.
     */
    public static function iconImg(string $name, string $style = 'width:24px;height:18px;vertical-align:middle;margin-right:6px;'): string
    {
        return '<img src="' . rex_escape(self::iconUrl($name)) . '" style="' . $style . '" alt="">';
    }

    /**
     * Erzeugt img-Tag aus einer beliebigen Data-URI (z.B. generierte SVGs).
     */
    public static function dataImg(string $dataUri, string $style = 'width:32px;height:18px;vertical-align:middle;margin-right:6px;'): string
    {
        return '<img src="' . rex_escape($dataUri) . '" style="' . $style . '" alt="">';
    }


    private int $width = 100;
    private int $height = 80;
    private string $bgColor = '#f8f8f8';
    private string $strokeColor = '#333';
    private string $mediaColor = '#666';
    private string $titleColor = '#999';
    private string $textColor = '#ccc';

    public static function factory(): self
    {
        return new self();
    }

    public function setSize(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function setColors(string $bgColor = '#f8f8f8', string $mediaColor = '#666', string $textColor = '#ccc'): self
    {
        $this->bgColor = $bgColor;
        $this->mediaColor = $mediaColor;
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * Generiert ein Base64-kodiertes SVG für Verwendung in img src
     */
    public function toBase64(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Media oben Layout
     */
    public function mediaTop(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" width="80" height="96">
                <rect width="100" height="120" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <rect x="5" y="5" width="90" height="45" fill="' . $this->mediaColor . '"/>
                <line x1="10" y1="60" x2="70" y2="60" stroke="' . $this->titleColor . '" stroke-width="2"/>
                <line x1="10" y1="68" x2="90" y2="68" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="74" x2="80" y2="74" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="80" x2="85" y2="80" stroke="' . $this->textColor . '" stroke-width="1.5"/>
            </svg>
        ');
    }

    /**
     * Media unten Layout
     */
    public function mediaBottom(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" width="80" height="96">
                <rect width="100" height="120" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <line x1="10" y1="10" x2="70" y2="10" stroke="' . $this->titleColor . '" stroke-width="2"/>
                <line x1="10" y1="18" x2="90" y2="18" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="24" x2="80" y2="24" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="30" x2="85" y2="30" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <rect x="5" y="45" width="90" height="70" fill="' . $this->mediaColor . '"/>
            </svg>
        ');
    }

    /**
     * Media links Layout
     */
    public function mediaLeft(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80" width="120" height="80">
                <rect width="120" height="80" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <rect x="5" y="5" width="35" height="70" fill="' . $this->mediaColor . '"/>
                <line x1="48" y1="15" x2="95" y2="15" stroke="' . $this->titleColor . '" stroke-width="2"/>
                <line x1="48" y1="25" x2="110" y2="25" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="48" y1="32" x2="100" y2="32" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="48" y1="39" x2="105" y2="39" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="48" y1="46" x2="95" y2="46" stroke="' . $this->textColor . '" stroke-width="1.5"/>
            </svg>
        ');
    }

    /**
     * Media rechts Layout
     */
    public function mediaRight(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80" width="120" height="80">
                <rect width="120" height="80" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <line x1="10" y1="15" x2="57" y2="15" stroke="' . $this->titleColor . '" stroke-width="2"/>
                <line x1="10" y1="25" x2="72" y2="25" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="32" x2="62" y2="32" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="39" x2="67" y2="39" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <line x1="10" y1="46" x2="57" y2="46" stroke="' . $this->textColor . '" stroke-width="1.5"/>
                <rect x="80" y="5" width="35" height="70" fill="' . $this->mediaColor . '"/>
            </svg>
        ');
    }

    /**
     * Grid Layout (für Cards Grid)
     */
    public function grid(int $columns = 3): string
    {
        $cardWidth = (100 - 10 - ($columns - 1) * 5) / $columns;
        $cards = '';
        
        for ($i = 0; $i < $columns; $i++) {
            $x = 5 + $i * ($cardWidth + 5);
            $cards .= '<rect x="' . $x . '" y="5" width="' . $cardWidth . '" height="50" fill="' . $this->mediaColor . '" rx="2"/>';
            $cards .= '<line x1="' . ($x + 2) . '" y1="60" x2="' . ($x + $cardWidth - 2) . '" y2="60" stroke="' . $this->textColor . '" stroke-width="1"/>';
            $cards .= '<line x1="' . ($x + 2) . '" y1="65" x2="' . ($x + $cardWidth - 2) . '" y2="65" stroke="' . $this->textColor . '" stroke-width="1"/>';
        }
        
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 80" width="100" height="80">
                <rect width="100" height="80" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                ' . $cards . '
            </svg>
        ');
    }

    /**
     * Generiert ein simples Spalten-Layout-Icon aus Prozentwerten.
     *
     * @param array<int, float|int> $ratios
     */
    public function columnsLayout(array $ratios): string
    {
        $normalized = [];
        foreach ($ratios as $ratio) {
            $value = max(0.0, (float) $ratio);
            if ($value > 0) {
                $normalized[] = $value;
            }
        }

        if ($normalized === []) {
            $normalized = [50.0, 50.0];
        }

        $sum = array_sum($normalized);
        if ($sum <= 0) {
            $normalized = [50.0, 50.0];
            $sum = 100.0;
        }

        $gap = 2.0;
        $innerWidth = 92.0;
        $x = 4.0;
        $columns = '';
        $count = count($normalized);
        $totalGap = ($count - 1) * $gap;
        $available = max(1.0, $innerWidth - $totalGap);

        foreach ($normalized as $index => $ratio) {
            $width = $available * ($ratio / $sum);
            if ($index === $count - 1) {
                // Letzte Spalte auf Restbreite ausrichten, um Rundungsfehler zu vermeiden.
                $width = (4.0 + $innerWidth) - $x;
            }

            $columns .= '<rect x="' . round($x, 3) . '" y="4" width="' . round($width, 3) . '" height="24" fill="' . $this->mediaColor . '" rx="1.5"/>';
            $x += $width + $gap;
        }

        return $this->toBase64(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 32" width="100" height="32">'
            . '<rect width="100" height="32" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="1.5"/>'
            . $columns
            . '</svg>'
        );
    }

    /**
     * Full Width Card
     */
    public function fullWidth(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 60" width="120" height="60">
                <rect width="120" height="60" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <rect x="5" y="5" width="110" height="30" fill="' . $this->mediaColor . '"/>
                <line x1="10" y1="42" x2="60" y2="42" stroke="' . $this->titleColor . '" stroke-width="2"/>
                <line x1="10" y1="50" x2="110" y2="50" stroke="' . $this->textColor . '" stroke-width="1.5"/>
            </svg>
        ');
    }

    /**
     * Card mit Overlay Text
     */
    public function mediaOverlay(): string
    {
        return $this->toBase64('
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 80" width="100" height="80">
                <rect width="100" height="80" fill="' . $this->bgColor . '" stroke="' . $this->strokeColor . '" stroke-width="2"/>
                <rect x="5" y="5" width="90" height="70" fill="' . $this->mediaColor . '"/>
                <rect x="10" y="50" width="80" height="20" fill="rgba(0,0,0,0.5)" rx="2"/>
                <line x1="15" y1="58" x2="55" y2="58" stroke="#fff" stroke-width="2"/>
                <line x1="15" y1="65" x2="85" y2="65" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            </svg>
        ');
    }

    /**
     * Generiert alle Standard-Layouts als Array für ChoiceField / RadioImgField.
     * Gibt externe SVG-URLs zurück (kein Base64 mehr).
     * @return array<string, array{img: string, label: string}>
     */
    public static function getLayoutOptions(): array
    {
        return [
            'media-top' => [
                'img' => self::iconUrl('layout-media-top'),
                'label' => 'Medium oben'
            ],
            'media-bottom' => [
                'img' => self::iconUrl('layout-media-bottom'),
                'label' => 'Medium unten'
            ],
            'media-left' => [
                'img' => self::iconUrl('layout-media-left'),
                'label' => 'Medium links'
            ],
            'media-right' => [
                'img' => self::iconUrl('layout-media-right'),
                'label' => 'Medium rechts'
            ],
            'media-overlay' => [
                'img' => self::iconUrl('layout-media-overlay'),
                'label' => 'Overlay'
            ]
        ];
    }

    /**
     * Generiert Grid-Optionen als Array
     * @return array<string, array{img: string, label: string}>
     */
    public static function getGridOptions(): array
    {
        $svg = self::factory();
        
        return [
            '2' => [
                'img' => $svg->grid(2),
                'label' => '2 Spalten'
            ],
            '3' => [
                'img' => $svg->grid(3),
                'label' => '3 Spalten'
            ],
            '4' => [
                'img' => $svg->grid(4),
                'label' => '4 Spalten'
            ]
        ];
    }

    /**
     * Liefert Choice-Icons (img-HTML) für Spalten-Layouts.
     *
     * @param array<int, string> $layoutKeys
     * @return array<string, string>
     */
    public static function getColumnLayoutChoiceIcons(array $layoutKeys): array
    {
        $icons = [];
        $svg = self::factory();

        foreach ($layoutKeys as $layoutKey) {
            $parts = array_values(array_filter(array_map('trim', explode('_', (string) $layoutKey)), static fn (string $part): bool => $part !== ''));
            if ($parts === []) {
                continue;
            }

            $ratios = [];
            foreach ($parts as $part) {
                $ratios[] = (float) str_replace(',', '.', $part);
            }

            $icons[$layoutKey] = self::dataImg($svg->columnsLayout($ratios));
        }

        return $icons;
    }
}
