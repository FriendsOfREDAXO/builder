<?php

use FriendsOfREDAXO\Builder\Helper;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

/**
 * Smart-Links Multi Showcase - Demo-Element
 */

$_ci = Helper::elementTranslator('smart_links_multi_showcase');

return [
    'label' => $_ci('label', 'Smart-Links Multi Showcase'),
    'icon' => 'fa fa-link',
    'description' => $_ci('description', 'Demonstriert ein einzelnes smart_link Feld im Multiple-Modus.'),
    'version' => '1.13.0',
    'category' => 'demo',

    'settings_modal' => [
        'label' => $_ci('settings_modal_label', 'Layout & Sektion'),
        'icon' => 'fa-cog',
        'fields' => array_merge(
            StarterConfig::getGridFieldNames(),
            [
                'headline',
                'intro',
                'show_preview',
            ],
            StarterConfig::getOptionalSectionFieldNames(),
        ),
    ],

    'fields' => array_merge(
        StarterConfig::getGridFields(),
        [
            'headline' => [
                'type' => 'text',
                'label' => $_ci('field_headline_label', 'Ueberschrift'),
                'default' => $_ci('field_headline_default', 'Mehrere Smart Links'),
            ],
            'intro' => [
                'type' => 'text',
                'label' => $_ci('field_intro_label', 'Einleitung'),
                'notice' => $_ci('field_intro_notice', 'Optionaler Text oberhalb der Linkliste.'),
            ],
            'show_preview' => [
                'type' => 'checkbox',
                'label' => $_ci('field_show_preview_label', 'Vorschaubilder anzeigen'),
                'default' => true,
            ],
            'links' => [
                'type' => 'smart_link',
                'label' => $_ci('field_links_label', 'Smart Links'),
                'multiple' => true,
                'types' => 'auto,url,intern,media,mail,tel,yform',
                'yform_table' => '',
                'yform_field' => 'name',
                'notice' => $_ci('field_links_notice', 'Mehrfachauswahl aktiv: Ein Feld fuer viele Smart Links.'),
            ],
        ],
        StarterConfig::getOptionalSectionFields(),
    ),
];
