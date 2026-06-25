<?php
/**
 * Section / Container Element (Auto-Close Wrapper)
 * 
 * Definiert einen visuellen Container für nachfolgende Elemente.
 * Das nächste Section-Element oder das Ende schließt automatisch.
 */

// Prüfen ob Theme-Provider verfügbar ist
$hasThemeProvider = \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::isProviderAvailable()
    || \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::getThemeChoices() !== [];

// Theme-Auswahl Optionen (nur wenn Theme-Provider verfügbar)
$themeChoices = [];
if ($hasThemeProvider) {
    $themeChoices = ['' => '-- Automatisch (Domain) --'];
    $availableThemes = \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::getThemeChoices();
    $themeChoices = array_merge($themeChoices, $availableThemes);
}

// Dynamische Optionen aus Theme-Provider
$backgroundOptions = [
    'none' => 'Keine',
    'transparent' => 'Transparent',
    'muted' => 'Muted',
    'primary' => 'Primary',
    'secondary' => 'Secondary',
];

if ($hasThemeProvider) {
    $themeBackgrounds = \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::getBackgroundOptions('uikit');
    if (is_array($themeBackgrounds) && [] !== $themeBackgrounds) {
        foreach ($themeBackgrounds as $class => $data) {
            if (!is_string($class) || '' === trim($class)) {
                continue;
            }

            $label = null;
            if (is_array($data) && isset($data['label']) && is_string($data['label']) && '' !== trim($data['label'])) {
                $label = $data['label'];
            } elseif (is_string($data) && '' !== trim($data)) {
                $label = $data;
            }

            if (!is_string($label) || '' === trim($label)) {
                $label = ucfirst(str_replace(['uk-section-', 'uk-background-', '_', '-'], ['', '', ' ', ' '], $class));
            }

            $backgroundOptions[$class] = $label;
        }
    }
}

return [
    'label' => 'Section / Container',
    'icon' => 'fa-object-group',
    'description' => 'Visueller Abschnitt mit Hintergrund (Auto-Close)',
    'version' => '1.13.0',
    'category' => 'layout',
    'auto_close' => true, // Markierung: Dieses Element ist ein Auto-Close Container
    
    'fields' => [
        'label' => [
            'type' => 'text',
            'label' => 'Bezeichnung',
            'notice' => 'Interne Bezeichnung (nicht sichtbar im Frontend)'
        ],
        
        'background_color' => [
            'type' => 'choice',
            'label' => 'Hintergrundfarbe',
            'choices' => $backgroundOptions,
            'default' => 'none'
        ],
        
        'background_image' => [
            'type' => 'be_media',
            'label' => 'Hintergrundbild',
            'notice' => 'Optional: Hintergrundbild statt Farbe'
        ],
        
        'padding_top' => [
            'type' => 'choice',
            'label' => 'Abstand oben',
            'choices' => [
                'none' => 'Kein',
                'small' => 'Klein',
                'medium' => 'Mittel',
                'large' => 'Groß',
                'xlarge' => 'Extra groß'
            ],
            'default' => 'medium'
        ],
        
        'padding_bottom' => [
            'type' => 'choice',
            'label' => 'Abstand unten',
            'choices' => [
                'none' => 'Kein',
                'small' => 'Klein',
                'medium' => 'Mittel',
                'large' => 'Groß',
                'xlarge' => 'Extra groß'
            ],
            'default' => 'medium'
        ],
        
        'container' => [
            'type' => 'choice',
            'label' => 'Container',
            'choices' => [
                'container' => 'Container (max-width)',
                'container-fluid' => 'Container Fluid (100%)',
                'none' => 'Kein Container'
            ],
            'default' => 'container',
            'notice' => 'Breite des Inhalts'
        ],
        
        'text_align' => [
            'type' => 'choice',
            'label' => 'Textausrichtung',
            'choices' => [
                '' => 'Standard',
                'left' => 'Links',
                'center' => 'Zentriert',
                'right' => 'Rechts'
            ],
            'default' => ''
        ],
        
        'custom_class' => [
            'type' => 'text',
            'label' => 'Custom CSS-Klasse',
            'notice' => 'Eigene CSS-Klasse für individuelle Styles'
        ],
        
        'custom_id' => [
            'type' => 'text',
            'label' => 'Custom ID',
            'notice' => 'Eigene ID für Anker-Links (z.B. #kontakt)'
        ],

        // ===== GRID-OPTIONEN (UIkit) =====
        'grid_enabled' => [
            'type' => 'checkbox',
            'label' => 'Grid-Modus aktivieren',
            'notice' => 'Elemente innerhalb dieser Section automatisch als Grid-Spalten anordnen (uk-grid + uk-child-width)'
        ],

        'grid_child_width' => [
            'type' => 'choice',
            'label' => 'Spaltenbreite (Desktop ≥960px)',
            'choices' => [
                '1-1'  => 'Volle Breite (1 Spalte)',
                '1-2'  => '½ – 2 Spalten',
                '1-3'  => '⅓ – 3 Spalten',
                '1-4'  => '¼ – 4 Spalten',
                '1-5'  => '⅕ – 5 Spalten',
                '1-6'  => '⅙ – 6 Spalten',
                '2-3'  => '⅔ – automatisch 1,5 Spalten',
                'auto' => 'Auto (Breite durch Inhalt)',
                'expand' => 'Gleichmäßig verteilt (expand)',
            ],
            'default' => '1-3',
        ],

        'grid_child_width_tablet' => [
            'type' => 'choice',
            'label' => 'Spaltenbreite (Tablet ≥640px)',
            'choices' => [
                '1-1' => 'Volle Breite',
                '1-2' => '½ – 2 Spalten',
                '1-3' => '⅓ – 3 Spalten',
                '1-4' => '¼ – 4 Spalten',
            ],
            'default' => '1-2',
        ],

        'grid_child_width_mobile' => [
            'type' => 'choice',
            'label' => 'Spaltenbreite (Mobil)',
            'choices' => [
                '1-1' => 'Volle Breite (empfohlen)',
                '1-2' => '½ – 2 Spalten',
            ],
            'default' => '1-1',
        ],

        'grid_gap' => [
            'type' => 'choice',
            'label' => 'Grid-Abstand',
            'choices' => [
                ''         => 'Standard (30px)',
                'small'    => 'Klein (15px)',
                'medium'   => 'Mittel (30px)',
                'large'    => 'Groß (40px)',
                'collapse' => 'Kein Abstand',
            ],
            'default' => '',
        ],

        'grid_match' => [
            'type' => 'checkbox',
            'label' => 'Match Height (uk-grid-match)',
            'notice' => 'Alle Grid-Zellen auf gleiche Höhe bringen – ideal für Karten- und Box-Layouts'
        ],

        'grid_divider' => [
            'type' => 'checkbox',
            'label' => 'Trennlinien (uk-grid-divider)',
            'notice' => 'Vertikale und horizontale Trennlinien zwischen den Grid-Zellen anzeigen'
        ],
    ]
];
