<?php
/**
 * Starter Cards – Demo-Element mit Bild, Titel, Text, Link und Card-Stil.
 */

$config = \FriendsOfREDAXO\Builder\Starter\StarterConfig::class;

return [
    'label' => 'Cards',
    'icon' => 'fa fa-th-large',
    'description' => 'Karten mit Bild, Titel, Text, internem oder externem Link und wählbarem Card-Stil.',
    'version' => '1.15.0',
    'category' => 'standards',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-th-large',
            'fields' => ['headline', 'items'],
        ],
        'layout_tab' => [
            'label' => 'Layout',
            'icon' => 'fa-columns',
            'fields' => array_merge(['image_ratio', 'image_ratio_mobile', 'card_style'], $config::getGridFieldNames()),
        ],
        'section_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-columns',
            'fields' => $config::getOptionalSectionFieldNames(),
        ],
    ],
    'fields' => array_merge([
        'headline' => [
            'type' => 'text',
            'label' => 'Überschrift (optional)',
        ],
        'card_style' => [
            'type' => 'choice',
            'label' => 'Card-Stil',
            'choices' => [
                'default'     => 'Standard (Rahmen)',
                'primary'     => 'Primary',
                'secondary'   => 'Secondary',
                'muted'       => 'Muted (Grau)',
                'hover'       => 'Hover-Effekt',
                'transparent' => 'Transparent',
            ],
            'default' => 'default',
        ],
        'image_ratio' => [
            'type' => 'choice',
            'label' => 'Bildformat',
            'choices' => [
                '16_9' => '16:9 (Standard)',
                '21_9' => '21:9 (Cinema)',
                '4_3' => '4:3',
                '1_1' => '1:1',
                'original' => 'Original (kein Crop)',
            ],
            'default' => '16_9',
        ],
        'image_ratio_mobile' => [
            'type' => 'choice',
            'label' => 'Bildformat mobil (optional, Art Direction)',
            'choices' => [
                '' => 'Wie Desktop',
                '16_9' => '16:9',
                '21_9' => '21:9 (Cinema)',
                '4_3' => '4:3',
                '1_1' => '1:1',
                'original' => 'Original (kein Crop)',
            ],
            'default' => '',
        ],
        'items' => [
            'type' => 'repeater',
            'label' => 'Karten',
            'min' => 1,
            'add_label' => 'Karte hinzufügen',
            'fields' => [
                'image' => [
                    'type' => 'be_media',
                    'label' => 'Bild',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Titel',
                ],
                'text' => [
                    'type' => 'tinymce',
                    'label' => 'Text',
                    'profile' => 'default',
                ],
                'link_type' => [
                    'type' => 'choice',
                    'label' => 'Link',
                    'choices' => [
                        ''         => 'Kein Link',
                        'external' => 'Externe URL',
                        'internal' => 'Interne Seite',
                    ],
                    'default' => '',
                ],
                'link_url' => [
                    'type' => 'text',
                    'label' => 'Externe URL',
                    'notice' => 'https://…',
                    'visible_if' => ['link_type' => ['external']],
                ],
                'link_internal' => [
                    'type' => 'be_link',
                    'label' => 'Interne Seite',
                    'visible_if' => ['link_type' => ['internal']],
                ],
                'link_text' => [
                    'type' => 'text',
                    'label' => 'Link-Text',
                    'default' => 'Mehr erfahren',
                    'visible_if' => ['link_type' => ['external', 'internal']],
                ],
                'link_target' => [
                    'type' => 'choice',
                    'label' => 'Öffnen in',
                    'choices' => [
                        ''       => 'Gleiches Fenster',
                        '_blank' => 'Neues Fenster/Tab',
                    ],
                    'default' => '',
                    'visible_if' => ['link_type' => ['external', 'internal']],
                ],
            ],
        ],
    ], $config::getStandardFields()),
];
