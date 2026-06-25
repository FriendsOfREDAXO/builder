<?php
/**
 * Starter Ueberschrift - bewusst minimal
 */

use FriendsOfREDAXO\Builder\Starter\StarterConfig;
use FriendsOfREDAXO\Builder\Helper;

$config = StarterConfig::class;
$_ci = Helper::elementTranslator('starter_headline');

return [
    'label' => $_ci('label', 'Ueberschrift'),
    'icon' => 'fa fa-header',
    'description' => $_ci('description', 'Semantische Rich-Headline mit einem kombinierten Eingabefeld.'),
    'version' => '1.15.0',
    'category' => 'standards',
    'field_groups' => [
        'content_tab' => [
            'label' => $_ci('group_content_label', 'Inhalt'),
            'icon' => 'fa-header',
            'fields' => ['headline'],
        ],
        'layout_tab' => [
            'label' => $_ci('group_layout_label', 'Layout'),
            'icon' => 'fa-columns',
            'fields' => ['container_width', 'section_padding'],
        ],
    ],
    'fields' => [
        'headline' => [
            'type' => 'rich_headline',
            'label' => $_ci('field_headline_label', 'Ueberschrift'),
            'notice' => $_ci('field_headline_notice', 'Eyebrow, Highlight, Subline und Tag werden zusammen gepflegt.'),
        ],
        'container_width' => [
            'type' => 'choice',
            'label' => $_ci('field_container_width_label', 'Container-Breite'),
            'choices' => $config::getContainerOptions(),
            'default' => 'uk-container',
        ],
        'section_padding' => [
            'type' => 'choice',
            'label' => $_ci('field_section_padding_label', 'Section-Breite'),
            'choices' => [
                '' => $_ci('field_section_padding_choice_default', 'Standard'),
                'uk-section-xsmall' => $_ci('field_section_padding_choice_xsmall', 'Sehr kompakt'),
                'uk-section-small' => $_ci('field_section_padding_choice_small', 'Kompakt'),
                'uk-section' => $_ci('field_section_padding_choice_normal', 'Normal'),
                'uk-section-large' => $_ci('field_section_padding_choice_large', 'Gross'),
                'uk-section-xlarge' => $_ci('field_section_padding_choice_xlarge', 'Sehr gross'),
            ],
            'default' => '',
            'notice' => $_ci('field_section_padding_notice', 'Steuert die vertikale Section-Groesse (ohne Farben/Hintergrundbild).'),
        ],
    ],
];
