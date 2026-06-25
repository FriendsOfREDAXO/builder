<?php
use FriendsOfREDAXO\Builder\Starter\StarterConfig;
use FriendsOfREDAXO\Builder\Svg;

$elementConfig = StarterConfig::class;

$layoutChoices = [
    '50_50' => '2 Spalten (50% / 50%)',
    '66_33' => '2 Spalten (66.6% / 33.3%)',
    '33_66' => '2 Spalten (33.3% / 66.6%)',
    '75_25' => '2 Spalten (75% / 25%)',
    '25_75' => '2 Spalten (25% / 75%)',
    '33_33_33' => '3 Spalten (33.3% / 33.3% / 33.3%)',
    '25_50_25' => '3 Spalten (25% / 50% / 25%)',
    '50_25_25' => '3 Spalten (50% / 25% / 25%)',
    '25_25_50' => '3 Spalten (25% / 25% / 50%)',
    '25_25_25_25' => '4 Spalten (25% / 25% / 25% / 25%)',
];

$layoutIcons = class_exists(Svg::class)
    ? Svg::getColumnLayoutChoiceIcons(array_keys($layoutChoices))
    : [];

return [
    'label' => 'Spalten-Layout',
    'icon' => 'fa-columns',
    'description' => 'Erzeugt ein mehrspaltiges Layout für geschachtelte Elemente',
    'allow_self_nesting' => true,
    'version' => '1.0.0',
    'category' => 'layout',
    
    'fields' => array_merge([
        'col_layout' => [
            'type' => 'choice',
            'label' => 'Layout-Verteilung',
            'choices' => $layoutChoices,
            'choice_icons' => $layoutIcons,
            'selectpicker' => true,
            'default' => '50_50'
        ]
    ], $elementConfig::getOptionalSectionFields())
];

