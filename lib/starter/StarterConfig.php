<?php

namespace FriendsOfREDAXO\Builder\Starter;

use FriendsOfREDAXO\Builder\Config\FrameworkConfig;
use FriendsOfREDAXO\Builder\Config\ElementRegistry;
use FriendsOfREDAXO\Builder\Config\ThemeProviderBridge;

/**
 * Konfigurationshelfer ausschließlich für die mitgelieferten Starter/Demo-Elemente.
 * Delegiert Framework-spezifische Optionen an FrameworkConfig.
 * Für produktive Projekte: Nutze FrameworkConfig / ElementRegistry direkt.
 *
 * @deprecated 3.1.0 Nutze FrameworkConfig und ElementRegistry stattdessen
 */
class StarterConfig
{
    private static ?bool $hasUikitThemeBuilder = null;

    /**
     * Liefert bundled Elements aus der ElementRegistry
     * Fallback auf Hard-coded Default falls nicht verfügbar
     *
     * @return array<int, string>
     */
    public static function getBundledDemoElementKeys(): array
    {
        try {
            return ElementRegistry::getBundledElements();
        } catch (\Exception $e) {
            // Fallback auf Default wenn Registry nicht erreichbar
            return [
                'section',
                'headline',
                'divider',
                'accordion',
                'starter_headline',
                'starter_text',
                'starter_media_split',
                'starter_cards',
                'starter_callout',
            ];
        }
    }

    /**
     * Prüft ob UIkit Theme Builder verfügbar ist
     */
    public static function hasThemeBuilder(): bool
    {
        if (self::$hasUikitThemeBuilder === null) {
            self::$hasUikitThemeBuilder = ThemeProviderBridge::isProviderAvailable()
                || ThemeProviderBridge::getThemeChoices() !== [];
        }
        return self::$hasUikitThemeBuilder;
    }

    /**
     * Liefert die Hintergrund-Optionen (für choice)
     * Delegiert intern an FrameworkConfig (UIkit default)
     */
    public static function getBackgroundChoices(): array
    {
        return FrameworkConfig::getBackgroundChoices('uikit');
    }

    /**
     * Liefert die Hintergrund-Farben (für color_swatches)
     * Delegiert intern an FrameworkConfig (UIkit default)
     */
    public static function getBackgroundColors(): array
    {
        return FrameworkConfig::getBackgroundColors('uikit');
    }

    /**
     * Liefert die Padding-Optionen
     * Delegiert intern an FrameworkConfig (UIkit default)
     */
    public static function getPaddingOptions(): array
    {
        return FrameworkConfig::getPaddingChoices('uikit');
    }

    /**
     * Liefert die Container-Breiten Optionen
     * Delegiert intern an FrameworkConfig (UIkit default)
     */
    public static function getContainerOptions(): array
    {
        return FrameworkConfig::getContainerChoices('uikit');
    }

    /**
     * Liefert die Standard Section-Felder
     * Können in jedem Element verwendet werden
     */
    public static function getSectionFields(): array
    {
        return [
            'section_bg' => [
                'type' => 'choice',
                'label' => 'Sektions-Hintergrund',
                'choices' => self::getBackgroundChoices(),
                'choice_colors' => self::getBackgroundColors(),
                'selectpicker' => true,
                'default' => 'uk-background-default',
                'visible_if' => [
                    'enable_section' => [true, 1, '1'],
                ],
            ],
            'section_bg_image' => [
                'type' => 'be_media',
                'label' => 'Sektions-Hintergrund (Bild/Video)',
                'notice' => 'Hintergrundbild oder -video (MP4, WebM). Video wird automatisch mit Autoplay und Loop abgespielt.',
                'visible_if' => [
                    'enable_section' => [true, 1, '1'],
                ],
            ],
            'section_padding' => [
                'type' => 'choice',
                'label' => 'Sektions-Padding',
                'choices' => self::getPaddingOptions(),
                'default' => '',
                'visible_if' => [
                    'enable_section' => [true, 1, '1'],
                ],
            ],
            'container_width' => [
                'type' => 'choice',
                'label' => 'Container-Breite',
                'choices' => self::getContainerOptions(),
                'default' => 'uk-container',
                'visible_if' => [
                    'enable_container' => [true, 1, '1'],
                ],
            ],
            'section_light' => [
                'type' => 'checkbox',
                'label' => 'Heller Text (uk-light)',
                'notice' => 'Aktiviert uk-light Klasse für Text auf dunklem Hintergrund',
                'visible_if' => [
                    'enable_section' => [true, 1, '1'],
                ],
            ]
        ];
    }

    /**
     * Liefert die Section-Feldnamen für settings_modal
     */
    public static function getSectionFieldNames(): array
    {
        return ['section_bg', 'section_bg_image', 'section_padding', 'container_width', 'section_light'];
    }

    /**
     * Liefert optionale Wrapper-Control-Felder (Section/Container)
     */
    public static function getWrapperControlFields(array $overrides = []): array
    {
        $fields = [
            'enable_section' => [
                'type' => 'checkbox',
                'label' => 'Sektion aktivieren',
                'default' => true,
                'notice' => 'Steuert, ob dieses Element eine eigene Section-Ausgabe rendert.',
            ],
            'enable_container' => [
                'type' => 'checkbox',
                'label' => 'Container aktivieren',
                'default' => true,
                'notice' => 'Steuert, ob dieses Element einen eigenen Container rendert.',
            ],
        ];

        foreach ($overrides as $fieldName => $fieldOverrides) {
            if (isset($fields[$fieldName]) && is_array($fieldOverrides)) {
                $fields[$fieldName] = array_merge($fields[$fieldName], $fieldOverrides);
            }
        }

        return $fields;
    }

    /**
     * Liefert die Feldnamen der Wrapper-Control-Felder
     */
    public static function getWrapperControlFieldNames(): array
    {
        return array_keys(self::getWrapperControlFields());
    }

    /**
     * Liefert die kombinierten Feldnamen aus Wrapper + Section
     */
    public static function getOptionalSectionFieldNames(): array
    {
        return array_merge(self::getWrapperControlFieldNames(), self::getSectionFieldNames());
    }

    /**
     * Liefert kombinierte Felder aus Wrapper + Section
     */
    public static function getOptionalSectionFields(array $wrapperOverrides = []): array
    {
        return array_merge(self::getWrapperControlFields($wrapperOverrides), self::getSectionFields());
    }

    /**
     * Liefert Standard Grid-Spalten Optionen
     */
    public static function getColumnOptions(string $device = 'desktop'): array
    {
        switch ($device) {
            case 'mobile':
                return [
                    '1' => '1 Spalte',
                    '2' => '2 Spalten'
                ];
            case 'tablet':
                return [
                    '1' => '1 Spalte',
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten'
                ];
            default: // desktop
                return [
                    '1' => '1 Spalte (100%)',
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                    '5' => '5 Spalten',
                    '6' => '6 Spalten'
                ];
        }
    }

    /**
     * Liefert Grid-Gap Optionen
     */
    public static function getGapOptions(): array
    {
        return [
            'collapse' => 'Kein Abstand',
            'small' => 'Klein (15px)',
            'medium' => 'Mittel (30px)',
            'large' => 'Groß (40px)'
        ];
    }

    /**
     * Liefert die Standard Grid-Felder für Repeater-Elemente
     */
    public static function getGridFields(): array
    {
        return [
            'columns' => [
                'type' => 'choice',
                'label' => 'Spalten (Desktop)',
                'choices' => self::getColumnOptions('desktop'),
                'default' => '3'
            ],
            'columns_tablet' => [
                'type' => 'choice',
                'label' => 'Spalten (Tablet)',
                'choices' => self::getColumnOptions('tablet'),
                'default' => '2'
            ],
            'columns_mobile' => [
                'type' => 'choice',
                'label' => 'Spalten (Mobile)',
                'choices' => self::getColumnOptions('mobile'),
                'default' => '1'
            ],
            'gap' => [
                'type' => 'choice',
                'label' => 'Abstand zwischen Elementen',
                'choices' => self::getGapOptions(),
                'default' => 'medium'
            ]
        ];
    }

    /**
     * Liefert die Grid-Feldnamen für settings_modal
     */
    public static function getGridFieldNames(): array
    {
        return ['columns', 'columns_tablet', 'columns_mobile', 'gap'];
    }

    /**
     * Liefert kombinierte Starter-Felder aus Grid + optionaler Section.
     */
    public static function getStandardFields(array $additionalFields = []): array
    {
        return array_merge(self::getGridFields(), $additionalFields, self::getOptionalSectionFields());
    }

    // -------------------------------------------------------------------------
    // Framework-Mappings für Templates (plain / bootstrap / uikit)
    // Damit Templates keine duplizierten lokalen Mapping-Arrays brauchen.
    // -------------------------------------------------------------------------

    /**
     * Gibt den Hintergrundwert für das jeweilige Framework zurück.
     *
     * uikit  → CSS-Klasse (z. B. "uk-background-muted")
     * bootstrap → CSS-Klasse (z. B. "bg-light")
     * plain  → inline-Style-Wert (z. B. "background:#f7f7f7;")
     */
    public static function mapBg(string $ukKey, string $framework): string
    {
        $normalizedKey = trim($ukKey);

        $aliases = [
            'none' => '',
            'default' => 'uk-background-default',
            'transparent' => 'uk-background-transparent',
            'muted' => 'uk-background-muted',
            'primary' => 'uk-background-primary',
            'secondary' => 'uk-background-secondary',
            'uk-section-default' => 'uk-background-default',
            'uk-section-muted' => 'uk-background-muted',
            'uk-section-primary' => 'uk-background-primary',
            'uk-section-secondary' => 'uk-background-secondary',
        ];

        if (isset($aliases[$normalizedKey])) {
            $normalizedKey = $aliases[$normalizedKey];
        }

        if ($normalizedKey === '') {
            return '';
        }

        if ($framework === 'uikit') {
            return $normalizedKey;
        }

        $bsMap = [
            'uk-background-default'   => 'bg-white',
            'uk-background-transparent' => 'bg-transparent',
            'uk-background-muted'     => 'bg-light',
            'uk-background-primary'   => 'bg-primary text-white',
            'uk-background-secondary' => 'bg-secondary text-white',
        ];

        $plainMap = [
            'uk-background-transparent' => '',
            'uk-background-default'   => 'background:#ffffff;',
            'uk-background-muted'     => 'background:#f7f7f7;',
            'uk-background-primary'   => 'background:#1e87f0;',
            'uk-background-secondary' => 'background:#222222;',
        ];

        if ($framework === 'bootstrap') {
            return $bsMap[$normalizedKey] ?? '';
        }

        // plain
        return $plainMap[$normalizedKey] ?? '';
    }

    /**
     * Gibt den Padding-Wert für das jeweilige Framework zurück.
     *
     * uikit  → CSS-Klasse (z. B. "uk-padding-small")
     * bootstrap → CSS-Klasse (z. B. "py-3")
     * plain  → inline-Style-Wert (z. B. "padding:18px 0;")
     */
    public static function mapPadding(string $ukKey, string $framework): string
    {
        if ($ukKey === '') {
            return '';
        }

        if ($framework === 'uikit') {
            return $ukKey;
        }

        $bsMap = [
            'uk-padding-remove' => 'py-0',
            'uk-padding-small'  => 'py-3',
            'uk-padding'        => 'py-5',
            'uk-padding-large'  => 'py-7',
        ];

        $plainMap = [
            'uk-padding-remove' => 'padding:0;',
            'uk-padding-small'  => 'padding:18px 0;',
            'uk-padding'        => 'padding:35px 0;',
            'uk-padding-large'  => 'padding:55px 0;',
        ];

        if ($framework === 'bootstrap') {
            return $bsMap[$ukKey] ?? '';
        }

        // plain
        return $plainMap[$ukKey] ?? '';
    }

    /**
     * Gibt die Container-Klasse für das jeweilige Framework zurück.
     *
     * uikit  → uk-container (+ Modifier)
     * bootstrap → container / container-fluid / ''
     * plain  → max-width inline-Style-Wert (z. B. "max-width:1140px;margin:0 auto;padding:0 15px;")
     */
    public static function mapContainer(string $ukKey, string $framework): string
    {
        if ($framework === 'uikit') {
            return $ukKey;
        }

        $bsMap = [
            'uk-container'                    => 'container',
            'uk-container uk-container-xsmall' => 'container',
            'uk-container uk-container-small'  => 'container',
            'uk-container uk-container-large'  => 'container-lg',
            'uk-container uk-container-xlarge' => 'container-fluid',
            'uk-container uk-container-expand' => 'container-fluid',
            ''                                 => '',
        ];

        $plainMap = [
            'uk-container'                    => 'max-width:1140px;margin:0 auto;padding:0 15px;',
            'uk-container uk-container-xsmall' => 'max-width:480px;margin:0 auto;padding:0 15px;',
            'uk-container uk-container-small'  => 'max-width:640px;margin:0 auto;padding:0 15px;',
            'uk-container uk-container-large'  => 'max-width:1320px;margin:0 auto;padding:0 15px;',
            'uk-container uk-container-xlarge' => 'max-width:1600px;margin:0 auto;padding:0 15px;',
            'uk-container uk-container-expand' => 'width:100%;padding:0 15px;',
            ''                                 => 'padding:0 15px;',
        ];

        if ($framework === 'bootstrap') {
            return $bsMap[$ukKey] ?? 'container';
        }

        // plain
        return $plainMap[$ukKey] ?? 'max-width:1140px;margin:0 auto;padding:0 15px;';
    }
}
