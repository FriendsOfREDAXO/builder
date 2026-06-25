<?php

use FriendsOfREDAXO\Builder\Helper;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

/**
 * Smart-Link Showcase - Demo-Element
 */

$_ci = Helper::elementTranslator('smart_link_showcase');

return [
    'label' => $_ci('label', 'Smart-Link Showcase'),
    'icon' => 'fa fa-link',
    'description' => $_ci('description', 'Demo-Linkliste mit smart_link, automatischer Medienvorschau und Icon-Fallback.'),
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
                'default' => $_ci('field_headline_default', 'Linkliste'),
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
            'items' => [
                'type' => 'repeater',
                'label' => $_ci('field_items_label', 'Links'),
                'add_label' => $_ci('field_items_add_label', 'Link hinzufuegen'),
                'view' => 'list',
                'fields' => [
                    'title' => [
                        'type' => 'text',
                        'label' => $_ci('field_item_title_label', 'Titel'),
                        'col' => 12,
                    ],
                    'text' => [
                        'type' => 'text',
                        'label' => $_ci('field_item_text_label', 'Beschreibung'),
                    ],
                    'link' => [
                        'type' => 'smart_link',
                        'label' => $_ci('field_item_link_label', 'Link'),
                        'multiple' => false,
                        'types' => 'auto,url,intern,media,mail,tel,yform',
                        'yform_table' => '',
                        'yform_field' => 'name',
                        'yform_profile' => '',
                        'notice' => $_ci('field_item_link_notice', 'Alle ueblichen Linktypen sind aktiv.'),
                    ],
                ],
            ],
        ],
        StarterConfig::getOptionalSectionFields(),
    ),
];
