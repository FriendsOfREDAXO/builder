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
            'section_padding' => 'uk-section-medium',
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
            'section_padding' => 'uk-section-medium',
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
                            'section_light' => 0,
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
                            'section_padding' => 'uk-section-medium',
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
            'section_padding' => 'uk-section-medium',
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
            'section_padding' => 'uk-section-medium',
            'container_width' => 'uk-container',
            'section_light' => 0,
            'enable_section' => true,
            'enable_container' => true,
        ],
    ],
];

$builder = \FriendsOfREDAXO\Builder\Module::createWithValue(1, null, [
    'framework' => 'plain',
    'wrapper_max_width' => '1400px',
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
$demoMarkup .= '<p class="builder-hero__kicker">Builder AddOn · Version ' . rex_escape((string) $addon->getVersion()) . '</p>';
$demoMarkup .= '<h2 class="builder-hero__title">Willkommen im Builder-Einstieg</h2>';
$demoMarkup .= '<p class="builder-hero__lead">Das ist der zentrale Einstieg in das AddOn. Du arbeitest hier direkt mit dem echten Builder-Editor und siehst sofort, wie sich Inhalte verhalten. Die Demo bleibt bewusst ohne dauerhafte Speicherung.</p>';
$demoMarkup .= '<div class="builder-hero__chips">';
$demoMarkup .= '<span class="builder-chip">AddOn-Einstieg</span>';
$demoMarkup .= '<span class="builder-chip">Version ' . rex_escape((string) $addon->getVersion()) . '</span>';
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
$demoMarkup .= '<div style="display: flex; justify-content: space-between; align-items: center;">';
$demoMarkup .= '<div>';
$demoMarkup .= '<h3>Arbeiten in der Demo</h3>';
$demoMarkup .= '<p class="text-muted">Du kannst die vorhandenen Elemente bearbeiten, neue Demo-Bausteine hinzufügen und die verschachtelte Spaltenstruktur direkt im Builder verändern. Die Demo rendert absichtlich mit dem framework-neutralen Plain-Template und speichert nichts dauerhaft.</p>';
$demoMarkup .= '</div>';
$demoMarkup .= '<div style="white-space: nowrap; margin-left: 20px;">';
$demoMarkup .= '<label style="display: flex; align-items: center; gap: 8px; margin: 0; font-weight: normal; cursor: pointer;">';
$demoMarkup .= '<input type="checkbox" id="builder-compact-mode-toggle" style="cursor: pointer;" />';
$demoMarkup .= '<span style="font-size: 14px;">Kompaktmodus</span>';
$demoMarkup .= '</label>';
$demoMarkup .= '</div>';
$demoMarkup .= '</div>';
$demoMarkup .= '<div class="alert alert-info" style="margin-top: 12px; margin-bottom: 0;">Für die Demo wird ein installiertes TinyMCE-Addon empfohlen, damit die Textbausteine direkt im Editor bearbeitet werden können.</div>';
$demoMarkup .= '</div>';

$demoMarkup .= '<style>';
$demoMarkup .= '.builder-demo-page .slice-rendered > header{max-width:760px;margin:0 0 28px;padding:0 8px;text-align:left;}';
$demoMarkup .= '.builder-demo-page .slice-rendered > header p:first-child{margin:0 0 10px;color:#b86118;font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;}';
$demoMarkup .= '.builder-demo-page .slice-rendered > header h1,.builder-demo-page .slice-rendered > header h2,.builder-demo-page .slice-rendered > header h3,.builder-demo-page .slice-rendered > header h4{margin:0 0 14px;color:#23364a;font-weight:800;line-height:1.08;}';
$demoMarkup .= '.builder-demo-page .slice-rendered > header mark{padding:0 .18em;border-radius:.35em;background:#ffe3b8;color:#23364a;}';
$demoMarkup .= '.builder-demo-page .slice-rendered > header p:last-child{margin:0;color:#5f7693;font-size:18px;line-height:1.65;max-width:680px;}';
$demoMarkup .= '.builder-demo-page .slice-rendered .cb-divider{margin:20px 0 26px;}';
$demoMarkup .= '.builder-demo-page .slice-rendered .cb-divider-text{display:inline-block;padding:6px 14px;border-radius:999px;background:#edf5ff;color:#31506f;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;}';
$demoMarkup .= '.builder-demo-page .slice-rendered .cb-divider hr{margin-top:10px;border:0;border-top:1px solid #d5e2f0;}';
$demoMarkup .= '.builder-demo-page .slice-rendered .cb-plain-row{row-gap:24px;}';
$demoMarkup .= '.builder-demo-page .slice-rendered .cb-plain-row > div{margin-bottom:12px;}';
$demoMarkup .= '.builder-demo-page .slice-rendered [style*="background:#fff"]{color:#23364a !important;}';
$demoMarkup .= '.builder-demo-page .slice-rendered [style*="background:#fff"] h1,.builder-demo-page .slice-rendered [style*="background:#fff"] h2,.builder-demo-page .slice-rendered [style*="background:#fff"] h3,.builder-demo-page .slice-rendered [style*="background:#fff"] h4,.builder-demo-page .slice-rendered [style*="background:#fff"] p,.builder-demo-page .slice-rendered [style*="background:#fff"] li{color:#23364a !important;}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style]{border-radius:18px;overflow:hidden;box-shadow:0 18px 40px rgba(37,61,88,.08);}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style] img{aspect-ratio:16/10;object-fit:cover;background:#dfe8f2;}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style] h4{margin:0 0 10px;color:#23364a;font-size:19px;font-weight:700;}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style] a{display:inline-flex;align-items:center;gap:6px;color:#0d5aa7;font-weight:700;text-decoration:none;}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style] a:hover{text-decoration:underline;}';
$demoMarkup .= '.builder-demo-page .slice-rendered article[style] a::after{content:"\\2192";}';
$demoMarkup .= '@media (max-width: 991px){.builder-demo-page .slice-rendered .cb-plain-row{display:block !important;margin-left:0 !important;margin-right:0 !important;}.builder-demo-page .slice-rendered .cb-plain-row > div{max-width:none !important;flex:0 0 100% !important;padding:0 !important;}}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered > header h1,body.rex-theme-dark .builder-demo-page .slice-rendered > header h2,body.rex-theme-dark .builder-demo-page .slice-rendered > header h3,body.rex-theme-dark .builder-demo-page .slice-rendered > header h4{color:#edf4fb;}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered > header p:last-child{color:#b1c3d8;}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered > header mark{background:#5d451d;color:#fff1d7;}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered .cb-divider-text{background:#24384d;color:#dce9f7;}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered .cb-divider hr{border-top-color:#41586f;}';
$demoMarkup .= 'body.rex-theme-dark .builder-demo-page .slice-rendered article[style]{box-shadow:0 18px 36px rgba(0,0,0,.28);}';
$demoMarkup .= '@media (prefers-color-scheme: dark){body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header h1,body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header h2,body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header h3,body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header h4{color:#edf4fb;}body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header p:last-child{color:#b1c3d8;}body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered > header mark{background:#5d451d;color:#fff1d7;}body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered .cb-divider-text{background:#24384d;color:#dce9f7;}body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered .cb-divider hr{border-top-color:#41586f;}body.rex-has-theme:not(.rex-theme-light) .builder-demo-page .slice-rendered article[style]{box-shadow:0 18px 36px rgba(0,0,0,.28);}}';
$demoMarkup .= '</style>';

$builderHtml = $builder->getEditor();
$demoMarkup .= '<div style="max-width:1400px;margin:0;width:100%;">';
$demoMarkup .= $builderHtml;
$demoMarkup .= '</div>';

// JavaScript für Kompaktmodus-Toggle
$demoMarkup .= '<script nonce="' . rex_response::getNonce() . '">';
$demoMarkup .= 'document.addEventListener("DOMContentLoaded", function() {';
$demoMarkup .= '  const toggle = document.getElementById("builder-compact-mode-toggle");';
$demoMarkup .= '  const builderElement = document.querySelector(".yform-content-builder");';
$demoMarkup .= '  if (!toggle || !builderElement) return;';
$demoMarkup .= '  const savedState = localStorage.getItem("builder-demo-compact-mode") === "1";';
$demoMarkup .= '  toggle.checked = savedState;';
$demoMarkup .= '  if (savedState) {';
$demoMarkup .= '    builderElement.classList.add("compact-mode");';
$demoMarkup .= '  }';
$demoMarkup .= '  toggle.addEventListener("change", function() {';
$demoMarkup .= '    const isChecked = this.checked;';
$demoMarkup .= '    if (isChecked) {';
$demoMarkup .= '      builderElement.classList.add("compact-mode");';
$demoMarkup .= '      localStorage.setItem("builder-demo-compact-mode", "1");';
$demoMarkup .= '    } else {';
$demoMarkup .= '      builderElement.classList.remove("compact-mode");';
$demoMarkup .= '      localStorage.setItem("builder-demo-compact-mode", "0");';
$demoMarkup .= '    }';
$demoMarkup .= '  });';
$demoMarkup .= '});';
$demoMarkup .= '</script>';

$demoMarkup .= '<div class="builder-demo-panel">';
$demoMarkup .= '<h3>Was diese Demo bewusst nicht macht</h3>';
$demoMarkup .= '<ul class="builder-demo-list">';
$demoMarkup .= '<li>keine dauerhafte Speicherung in Datenbank oder JSON</li>';
$demoMarkup .= '<li>keine produktive Modulbearbeitung</li>';
$demoMarkup .= '<li>nur Demo- und Default-Elemente als Auswahl</li>';
$demoMarkup .= '</ul>';
$demoMarkup .= '</div>';

$demoMarkup .= '</div>';

echo $demoMarkup;
