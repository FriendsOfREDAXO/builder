<?php
/**
 * Starter Text - sehr einfaches Textelement
 */

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$elementConfig = StarterConfig::class;

return [
    'label' => 'Text',
    'icon' => 'fa fa-align-left',
    'description' => 'Einfacher Textblock mit TinyMCE (Profil: default).',
    'version' => '1.13.0',
    'category' => 'standards',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['text'],
        ],
        'section_settings_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-columns',
            'fields' => $elementConfig::getOptionalSectionFieldNames(),
        ],
    ],
    'fields' => array_merge([
        'text' => [
            'type' => 'tinymce',
            'profile' => 'default',
            'label' => 'Text',
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
