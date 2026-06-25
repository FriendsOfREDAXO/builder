<?php

/**
 * Builder - Demo
 *
 * Reine Editor-Demo-Seite ohne Speichermöglichkeit.
 */

$addon = rex_addon::get('builder');

$initialSlices = [
    [
        'id' => 'slice_intro',
        'type' => 'starter_headline',
        'online' => true,
        'data' => [
            'headline' => [
                'eyebrow' => 'Builder Demo',
                'text' => 'Echte Inhalte mit Demo- und Default-Bausteinen',
                'highlight' => 'echte Inhalte',
                'subline' => 'Hier arbeitest du direkt im Builder-Editor, aber ohne dauerhafte Speicherung.',
                'tag' => 'h2',
            ],
            'container_width' => 'uk-container',
            'section_padding' => 'uk-section-large',
            'enable_section' => true,
            'enable_container' => true,
        ],
    ],
    [
        'id' => 'slice_text',
        'type' => 'starter_text',
        'online' => true,
        'data' => [
            'text' => '<p>Diese Demo zeigt die typischen Default- und Starter-Elemente so, wie sie später in echten Projekten zusammengesetzt werden können.</p><p>Du kannst die Elemente verschieben, ergänzen und bearbeiten. Gespeichert wird hier bewusst nichts.</p>',
            'container_width' => 'uk-container',
            'section_padding' => 'uk-section-small',
            'enable_section' => true,
            'enable_container' => true,
        ],
    ],
    [
        'id' => 'slice_divider',
        'type' => 'divider',
        'online' => true,
        'data' => [
            'style' => 'text',
            'text' => 'Starter- und Default-Elemente',
            'text_position' => 'center',
            'color' => 'primary',
            'width' => 'wide',
            'spacing_top' => 'medium',
            'spacing_bottom' => 'medium',
            'scroll_anchor' => '#',
            'enable_section' => true,
            'enable_container' => true,
        ],
    ],
    [
        'id' => 'slice_columns',
        'type' => 'columns',
        'online' => true,
        'data' => [
            'col_layout' => '50_50',
            'section_bg' => 'uk-background-muted',
            'columns' => [
                [
                    [
                        'id' => 'slice_columns_left_headline',
                        'type' => 'starter_headline',
                        'online' => true,
                        'data' => [
                            'headline' => [
                                'eyebrow' => 'Spaltenlayout',
                                'text' => 'Zwei Spalten direkt im Editor',
                                'highlight' => 'Spaltenlayout',
                                'subline' => 'Linke Seite mit einem kurzen Einstieg und klarer Struktur.',
                                'tag' => 'h3',
                            ],
                            'container_width' => 'uk-container',
                            'section_padding' => '',
                            'enable_section' => false,
                            'enable_container' => false,
                        ],
                    ],
                    [
                        'id' => 'slice_columns_left_text',
                        'type' => 'starter_text',
                        'online' => true,
                        'data' => [
                            'text' => '<p>In der linken Spalte kannst du typische Einstiegsinhalte platzieren, zum Beispiel Einleitungstexte oder Hinweise.</p>',
                            'enable_section' => false,
                            'enable_container' => false,
                        ],
                    ],
                    [
                        'id' => 'slice_columns_left_nested',
                        'type' => 'columns',
                        'online' => true,
                        'data' => [
                            'col_layout' => '66_33',
                            'section_bg' => 'uk-background-primary',
                            'section_light' => true,
                            'columns' => [
                                [
                                    [
                                        'id' => 'slice_columns_nested_left_text',
                                        'type' => 'starter_text',
                                        'online' => true,
                                        'data' => [
                                            'text' => '<p>Verschachtelte Spalten in der linken Hauptspalte. So kannst du Sektionen noch feiner strukturieren.</p>',
                                            'enable_section' => false,
                                            'enable_container' => false,
                                        ],
                                    ],
                                ],
                                [
                                    [
                                        'id' => 'slice_columns_nested_right_text',
                                        'type' => 'starter_text',
                                        'online' => true,
                                        'data' => [
                                            'text' => '<p>Auch diese kleine Neben-Spalte bleibt editierbar und nutzt nur Demo-/Default-Elemente.</p>',
                                            'enable_section' => false,
                                            'enable_container' => false,
                                        ],
                                    ],
                                ],
                            ],
                            'enable_section' => true,
                            'enable_container' => true,
                            'section_padding' => 'uk-section-small',
                            'container_width' => 'uk-container',
                        ],
                    ],
                ],
                [
                    [
                        'id' => 'slice_columns_right_text',
                        'type' => 'starter_text',
                        'online' => true,
                        'data' => [
                            'text' => '<p>Die rechte Spalte eignet sich für ergänzende Inhalte wie Details, Argumente oder eine kleine Zusammenfassung.</p><p>So lässt sich das Layout direkt im Editor zusammenbauen.</p>',
                            'enable_section' => false,
                            'enable_container' => false,
                        ],
                    ],
                ],
            ],
            'enable_section' => true,
            'enable_container' => true,
            'section_padding' => 'uk-section-small',
            'container_width' => 'uk-container',
        ],
    ],
    [
        'id' => 'slice_cards',
        'type' => 'starter_cards',
        'online' => true,
        'data' => [
            'headline' => 'Was die Demo zeigt',
            'card_style' => 'hover',
            'image_ratio' => '16_9',
            'image_ratio_mobile' => '',
            'columns' => '3',
            'columns_tablet' => '2',
            'columns_mobile' => '1',
            'gap' => 'large',
            'items' => [
                [
                    'title' => 'Starter Headline',
                    'text' => '<p>Eine saubere Überschrift mit Eyebrow und Subline.</p>',
                    'link_type' => '',
                ],
                [
                    'title' => 'Starter Text',
                    'text' => '<p>Ein einfacher Textblock mit dem Standardprofil von TinyMCE.</p>',
                    'link_type' => '',
                ],
                [
                    'title' => 'Trennelement',
                    'text' => '<p>Ein flexibler Trenner für Struktur und Rhythmus im Layout.</p>',
                    'link_type' => '',
                ],
            ],
            'section_bg' => '',
            'section_bg_image' => '',
            'section_padding' => 'uk-section-small',
            'container_width' => 'uk-container',
            'section_light' => 0,
            'enable_section' => true,
            'enable_container' => true,
        ],
    ],
];

$builder = \FriendsOfREDAXO\Builder\Module::createWithValue(1, null, [
    'framework' => 'uikit',
    'label' => 'Builder Demo',
    'description' => 'Demo-Editor mit echten Starter- und Default-Elementen',
    'allowed_elements' => ['starter_headline', 'starter_text', 'divider', 'columns', 'starter_cards'],
    'initial_slices' => $initialSlices,
]);

$demoMarkup = '';
$demoMarkup .= '<div class="builder-demo-page">';
$demoMarkup .= '<section class="builder-hero">';
$demoMarkup .= '<div class="builder-hero__bg" aria-hidden="true">';
$demoMarkup .= '<span class="builder-hero__block builder-hero__block--a"></span>';
$demoMarkup .= '<span class="builder-hero__block builder-hero__block--b"></span>';
$demoMarkup .= '<span class="builder-hero__block builder-hero__block--c"></span>';
$demoMarkup .= '<span class="builder-hero__block builder-hero__block--d"></span>';
$demoMarkup .= '</div>';
$demoMarkup .= '<div class="builder-hero__content">';
$demoMarkup .= '<div class="builder-hero__logo" aria-hidden="true"></div>';
$demoMarkup .= '<div>';
$demoMarkup .= '<p class="builder-hero__kicker">Builder</p>';
$demoMarkup .= '<h2 class="builder-hero__title">Demo-Editor zum Arbeiten</h2>';
$demoMarkup .= '<p class="builder-hero__lead">Hier kannst du mit dem echten Builder-Editor arbeiten. Er ist bewusst auf Demo- und Default-Bausteine begrenzt und speichert nichts dauerhaft.</p>';
$demoMarkup .= '<div class="builder-hero__chips">';
$demoMarkup .= '<span class="builder-chip">Demo-Editor</span>';
$demoMarkup .= '<span class="builder-chip">Readonly-Speicherung</span>';
$demoMarkup .= '<span class="builder-chip">Starter-Elemente</span>';
$demoMarkup .= '</div>';
$demoMarkup .= '</div>';
$demoMarkup .= '<div class="builder-hero__stats">';
$demoMarkup .= '<div class="builder-stat"><strong>5</strong><span>Start-Bausteine</span></div>';
$demoMarkup .= '<div class="builder-stat"><strong>5</strong><span>Erlaubte Elemente</span></div>';
$demoMarkup .= '</div>';
$demoMarkup .= '</div>';
$demoMarkup .= '</section>';

$demoMarkup .= '<div class="builder-demo-panel">';
$demoMarkup .= '<h3>Arbeiten in der Demo</h3>';
$demoMarkup .= '<p class="text-muted">Du kannst die vorhandenen Elemente bearbeiten, neue Demo-Bausteine hinzufügen und die verschachtelte Spaltenstruktur direkt im Builder verändern. Diese Seite schreibt bewusst nichts in den Slice-Speicher zurück.</p>';
$demoMarkup .= '<div class="alert alert-info" style="margin-top: 12px; margin-bottom: 0;">Für die Demo wird ein installiertes TinyMCE-Addon empfohlen, damit die Textbausteine direkt im Editor bearbeitet werden können.</div>';
$demoMarkup .= '</div>';

$demoMarkup .= $builder->getEditor();

$demoMarkup .= '<div class="builder-demo-panel">';
$demoMarkup .= '<h3>Was diese Demo bewusst nicht macht</h3>';
$demoMarkup .= '<ul class="builder-demo-list">';
$demoMarkup .= '<li>keine dauerhafte Speicherung in Datenbank oder JSON</li>';
$demoMarkup .= '<li>keine produktive Modulbearbeitung</li>';
$demoMarkup .= '<li>nur Demo- und Default-Elemente als Auswahl</li>';
$demoMarkup .= '</ul>';
$demoMarkup .= '</div>';

$demoMarkup .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Demo', false);
$fragment->setVar('body', $demoMarkup, false);
echo $fragment->parse('core/page/section.php');
