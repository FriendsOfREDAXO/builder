<?php

use FriendsOfREDAXO\Builder\ListProfiles;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

/**
 * YForm-Listen-Element
 *
 * Zeigt server-seitig gerenderte Auflistung aus einer YForm-Tabelle an.
 * Tabelle, Felder, Sortierung, Filter und URL-Schema werden zentral
 * über Profile in den Addon-Einstellungen verwaltet.
 */

$profileChoices = ListProfiles::getChoices();

return [
    'label' => 'YForm-Liste',
    'description' => 'Dynamische Liste aus einer YForm-Tabelle (Profile via Addon-Einstellungen)',
    'version' => '1.13.0',
    'icon' => 'fa-list-alt',
    'category' => 'data',

    'settings_modal' => [
        'label' => 'Layout & Sektion',
        'icon' => 'fa-cog',
        'fields' => array_merge(
            StarterConfig::getGridFieldNames(),
            ['layout', 'limit', 'teaser_length'],
            StarterConfig::getOptionalSectionFieldNames(),
        ),
    ],

    'fields' => array_merge(
        StarterConfig::getGridFields(),
        [
            'profile' => [
                'type' => 'choice',
                'label' => 'Profil',
                'choices' => [] !== $profileChoices
                    ? $profileChoices
                    : ['' => '— Bitte zuerst Profile in den Addon-Einstellungen anlegen —'],
                'notice' => 'Profile (Tabelle, Spalten, Filter, URL-Schema) werden zentral unter '
                    . 'Builder → Einstellungen verwaltet.',
                'default' => '',
            ],
            'headline' => [
                'type' => 'text',
                'label' => 'Überschrift',
                'notice' => 'Optional über der Liste angezeigt',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Beschreibung',
                'notice' => 'Optional als Einleitungstext',
            ],
            'layout' => [
                'type' => 'choice',
                'label' => 'Layout',
                'choices' => [
                    '' => '— Profil-Default verwenden —',
                    'cards' => 'Kacheln (Cards)',
                    'list' => 'Liste mit Bild + Anriss',
                    'compact' => 'Kompakt (nur Titel)',
                    'contact' => 'Kontakt-Karten (Avatar, Name, Funktion, Telefon, E-Mail)',
                    'contact_compact' => 'Kontakt kompakt (Card-Header mit Avatar)',
                ],
                'default' => '',
            ],
            'limit' => [
                'type' => 'text',
                'label' => 'Anzahl Einträge',
                'notice' => 'Leer lassen für Profil-Default. Maximal 200.',
                'default' => '',
            ],
            'teaser_length' => [
                'type' => 'text',
                'label' => 'Teaser-Länge in Zeichen',
                'notice' => '30–800. Default: 160',
                'default' => '160',
            ],
            'show_links' => [
                'type' => 'checkbox',
                'label' => 'Datensätze verlinken',
                'default' => true,
            ],
        ],
        StarterConfig::getOptionalSectionFields(),
    ),
];
