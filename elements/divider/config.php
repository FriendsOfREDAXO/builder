<?php
/**
 * Trennlinie Element - Konfiguration mit zentraler Config
 */

// Zentrale Konfigurationsklasse
$config = \FriendsOfREDAXO\Builder\Starter\StarterConfig::class;
$hasThemeBuilder = $config::hasThemeBuilder();

// Dynamische Farboptionen
$colorOptions = [
    'default' => 'Standard (Grau)',
    'primary' => 'Primary',
    'secondary' => 'Secondary',
    'success' => 'Success',
    'warning' => 'Warning',
    'danger' => 'Danger',
];

// Theme-Provider Farben nur als Strings hinzufügen
if ($hasThemeBuilder) {
    $themeColors = \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::getTextColorOptions('uikit');
    if (is_array($themeColors)) {
        foreach ($themeColors as $key => $value) {
            // Nur hinzufügen wenn Wert ein String ist, nicht array
            if (is_string($value)) {
                $colorOptions[$key] = $value;
            }
        }
    }
}

return [
    'label' => 'Trennelement',
    'icon' => 'fa fa-minus',
    'description' => 'Trennelement oder Abstandselement mit verschiedenen Styles',
    'version' => '1.13.0',
    'category' => 'layout',
    'settings_modal' => [
        'label' => 'Section-Einstellungen',
        'icon' => 'fa-cog',
        'fields' => $config::getOptionalSectionFieldNames()
    ],
    'fields' => array_merge(
        // Element-spezifische Felder
        [
        'style' => [
            'type' => 'choice',
            'label' => 'Style',
            'choices' => [
                'none' => 'Keine Linie (nur Abstand)',
                'simple' => 'Einfache Linie',
                'double' => 'Doppelte Linie',
                'dotted' => 'Gepunktet',
                'dashed' => 'Gestrichelt',
                'thick' => 'Dicke Linie',
                'gradient' => 'Farbverlauf',
                'icon' => 'Linie mit Icon',
                'text' => 'Linie mit Text',
                'scroll' => 'Scroll-Animation (Chevron)'
            ],
            'default' => 'simple'
        ],
        'icon' => [
            'type' => 'text',
            'label' => 'Icon Klasse',
            'notice' => 'Nur bei Style "icon", z.B. fa fa-star',
            'default' => 'fa fa-star'
        ],
        'text' => [
            'type' => 'text',
            'label' => 'Text',
            'notice' => 'Nur bei Style "text"'
        ],
        'text_position' => [
            'type' => 'choice',
            'label' => 'Text Position',
            'choices' => [
                'center' => 'Mitte',
                'left' => 'Links'
            ],
            'default' => 'center',
            'notice' => 'Position des Textes bei "Linie mit Text"'
        ],
        'color' => [
            'type' => 'choice',
            'label' => 'Farbe',
            'choices' => $colorOptions,
            'default' => 'default'
        ],
        'width' => [
            'type' => 'choice',
            'label' => 'Breite',
            'choices' => [
                'full' => '100%',
                'wide' => '80%',
                'medium' => '60%',
                'narrow' => '40%'
            ],
            'default' => 'full'
        ],
        'spacing_top' => [
            'type' => 'choice',
            'label' => 'Abstand oben',
            'choices' => [
                'none' => 'Keine',
                'small' => 'Klein (20px)',
                'medium' => 'Mittel (40px)',
                'large' => 'Groß (60px)',
                'xlarge' => 'Extra Groß (80px)'
            ],
            'default' => 'medium'
        ],
        'spacing_bottom' => [
            'type' => 'choice',
            'label' => 'Abstand unten',
            'choices' => [
                'none' => 'Keine',
                'small' => 'Klein (20px)',
                'medium' => 'Mittel (40px)',
                'large' => 'Groß (60px)',
                'xlarge' => 'Extra Groß (80px)'
            ],
            'default' => 'medium'
        ],
        'scroll_anchor' => [
            'type' => 'text',
            'label' => 'Scroll Ziel-ID',
            'notice' => 'ID des Elements, zu dem gescrollt werden soll. Nur bei "Scroll-Animation". Z.B. #mein-element',
            'default' => '#'
        ]
        ],
        
        // Section-Felder aus zentraler Config
        $config::getOptionalSectionFields()
    ),
];
