<?php
/**
 * Starter Media Split - Bild/Video links oder rechts
 */

$config = \FriendsOfREDAXO\Builder\Starter\StarterConfig::class;

return [
    'label' => 'Bild oder Video + Text',
    'icon' => 'fa fa-columns',
    'description' => 'Einfaches 2-Spalten-Element mit Medienausrichtung links/rechts.',
    'version' => '1.13.0',
    'category' => 'standards',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['headline', 'text', 'media_position'],
        ],
        'media_tab' => [
            'label' => 'Medium',
            'icon' => 'fa-image',
            'fields' => ['media_file', 'media_alt'],
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
            'label' => 'Ueberschrift',
        ],
        'text' => [
            'type' => 'tinymce',
            'profile' => 'default',
            'label' => 'Text',
        ],
        'media_position' => [
            'type' => 'choice',
            'label' => 'Medium-Ausrichtung',
            'choices' => [
                'left' => 'Links',
                'right' => 'Rechts',
            ],
            'default' => 'left',
        ],
        'media_file' => [
            'type' => 'be_media',
            'label' => 'Bild oder Video',
            'notice' => 'Datei aus dem Medienpool waehlen (Bild oder MP4/WebM).',
        ],
        'media_alt' => [
            'type' => 'text',
            'label' => 'Alt-Text',
        ],
    ], $config::getOptionalSectionFields()),
];
