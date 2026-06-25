<?php
/**
 * Table Element - Barrierefreier Tabelleneditor mit CSV-Import.
 */

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

return [
    'label' => 'Tabelle',
    'icon' => 'fa fa-table',
    'description' => 'Responsive, barrierefreie Tabelle mit CSV-Import',
    'version' => '1.13.0',
    'category' => 'data',
    'field_groups' => [
        'content' => [
            'label' => 'Inhalt',
            'icon' => 'fa-table',
            'fields' => ['table_data'],
        ],
        'styling' => [
            'label' => 'Styling',
            'icon' => 'fa-paint-brush',
            'fields' => ['table_style', 'table_size', 'table_hover', 'table_responsive'],
        ],
        'design' => [
            'label' => 'Design',
            'icon' => 'fa-paint-brush',
            'fields' => array_merge(['table_align'], StarterConfig::getOptionalSectionFieldNames()),
        ],
    ],
    'fields' => array_merge(
        [
            'table_data' => [
                'type' => 'table_editor',
                'label' => 'Tabelleneditor',
                'notice' => 'Kopfzeile und Kopfspalte koennen direkt gesetzt werden. Caption verbessert die Accessibility.',
                'min_cols' => 1,
                'min_rows' => 1,
                'header_row_policy' => 'user',
                'header_col_policy' => 'user',
                'enable_textarea' => true,
                'enable_media' => false,
                'enable_link' => false,
            ],
            'table_style' => [
                'type' => 'choice',
                'label' => 'Stil',
                'choices' => [
                    'default' => 'Standard',
                    'uk-table-divider' => 'Mit Trennlinien',
                    'uk-table-striped' => 'Zebra-Streifen',
                    'uk-table-striped uk-table-divider' => 'Zebra + Linien',
                ],
                'default' => 'default',
            ],
            'table_size' => [
                'type' => 'choice',
                'label' => 'Groesse',
                'choices' => [
                    'default' => 'Standard',
                    'uk-table-small' => 'Klein',
                    'uk-table-large' => 'Gross',
                ],
                'default' => 'default',
            ],
            'table_hover' => [
                'type' => 'checkbox',
                'label' => 'Hover-Effekt (Zeilen hervorheben)',
            ],
            'table_responsive' => [
                'type' => 'choice',
                'label' => 'Responsiv',
                'choices' => [
                    '' => 'Scroll horizontal',
                    'uk-table-responsive' => 'Spalten stacking auf Mobile',
                ],
                'default' => '',
            ],
            'table_align' => [
                'type' => 'choice',
                'label' => 'Vertikale Ausrichtung',
                'choices' => [
                    '' => 'Standard',
                    'uk-table-middle' => 'Mittig',
                ],
                'default' => '',
            ],
        ],
        StarterConfig::getOptionalSectionFields()
    ),
];
