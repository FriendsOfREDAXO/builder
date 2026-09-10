<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex;
use rex_escape;

/**
 * Abstrakte Basisklasse für Content Builder Feldtypen
 * 
 * Stellt gemeinsame Funktionalität bereit:
 * - Label-Rendering
 * - Verschachtelte Werte extrahieren
 * - Notice/Hilfetext
 * - Berechtigungsprüfung
 */
abstract class FieldAbstract implements FieldInterface
{
    /**
     * Statische Widget-Counter für eindeutige IDs
     *
     * UNGENUTZT seit der PHP-$GLOBALS-basierten Zaehler-Variante unten
     * (getNextMediaCounter()/getNextLinkCounter()) - bewusst stehen gelassen statt
     * geloescht, falls ein anderer Addon-Teil bereits darauf zugreift; neue Nutzung
     * bitte stattdessen ueber generateId() (echtes uniqid()) oder die beiden
     * getNext...Counter()-Methoden unten.
     */
    protected static array $widgetCounters = [
        'media' => 0,
        'link' => 0,
    ];

    /**
     * Referenz zur Registry (für verschachtelte Felder wie Repeater)
     */
    protected ?FieldRegistry $registry = null;

    public function setRegistry(FieldRegistry $registry): void
    {
        $this->registry = $registry;
    }

    /**
     * Prüft ob der Benutzer die Berechtigung hat dieses Feld zu sehen
     * 
     * @param array $fieldConfig Feldkonfiguration mit optionalem 'perm' Key
     * @return bool True wenn Feld sichtbar sein soll, false sonst
     * 
     * Unterstützte perm-Optionen:
     * - 'admin': Nur für Admin-Benutzer
     * - 'rolename': Nur für Benutzer mit dieser Rolle
     * - ['role1', 'role2']: Für Benutzer mit einer dieser Rollen
     * - 'power|editor': Für Benutzer mit rolle "power" ODER "editor" (pipe-getrennt)
     */
    protected function hasPermission(array $fieldConfig): bool
    {
        if (!isset($fieldConfig['perm'])) {
            // Keine Berechtigung definiert = für alle sichtbar
            return true;
        }

        $user = rex::getUser();
        if (!$user) {
            // Kein Benutzer eingeloggt
            return false;
        }

        $perm = $fieldConfig['perm'];

        // String-Format: Einzelne Rolle oder "admin"
        if (is_string($perm)) {
            // Spezialfall: "admin"
            if ($perm === 'admin') {
                return $user->isAdmin();
            }

            // Pipe-getrennte Rollen: "role1|role2|role3"
            if (strpos($perm, '|') !== false) {
                $roles = array_map('trim', explode('|', $perm));
                foreach ($roles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
                return false;
            }

            // Einzelne benutzerdefinierte Rolle
            return $user->hasRole($perm);
        }

        // Array-Format: Mehrere erlaubte Rollen
        if (is_array($perm)) {
            foreach ($perm as $role) {
                if ($role === 'admin' && $user->isAdmin()) {
                    return true;
                }
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        // Unbekanntes Format = erlauben (Fallback)
        return true;
    }

    /**
     * Standard-Wertverarbeitung (keine Änderung)
     */
    public function processValue(mixed $value, array $fieldConfig): mixed
    {
        return $value;
    }

    /**
     * Rendert das Label für ein Feld
     */
    protected function renderLabel(string $label): void
    {
        echo '<label>' . rex_escape($label) . '</label>';
    }

    /**
     * Öffnet eine Formulargruppe
     */
    protected function openFormGroup(): void
    {
        echo '<div class="form-group">';
    }

    /**
     * Schließt eine Formulargruppe und rendert optional Notice
     */
    protected function closeFormGroup(?string $notice = null): void
    {
        if ($notice) {
            echo '<p class="help-block">' . rex_escape($notice) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Holt Wert aus verschachteltem Array (z.B. "items[0][title]")
     */
    protected function getNestedValue(string $key, array $data): mixed
    {
        if (strpos($key, '[') === false) {
            return $data[$key] ?? '';
        }

        preg_match_all('/([^\[\]]+)/', $key, $matches);
        $keys = $matches[1];

        $value = $data;
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return '';
            }
        }

        return $value;
    }

    /**
     * Generiert eine eindeutige ID
     */
    protected function generateId(string $prefix = 'field'): string
    {
        return $prefix . '_' . uniqid();
    }

    /**
     * Holt nächsten Media-Counter (global eindeutig)
     */
    protected static function getNextMediaCounter(): int
    {
        if (!isset($GLOBALS['yform_cb_media_counter'])) {
            $GLOBALS['yform_cb_media_counter'] = self::counterBase();
        }
        return ++$GLOBALS['yform_cb_media_counter'];
    }

    /**
     * Holt nächsten Link-Counter
     */
    protected static function getNextLinkCounter(): int
    {
        if (!isset($GLOBALS['yform_cb_link_counter'])) {
            $GLOBALS['yform_cb_link_counter'] = self::counterBase();
        }
        return ++$GLOBALS['yform_cb_link_counter'];
    }

    /**
     * Request-eindeutige Startbasis fuer die beiden Counter oben.
     *
     * Die alte, fixe Basis (1000) war nur INNERHALB eines einzelnen PHP-Requests
     * eindeutig - jeder "Element hinzufuegen"-Klick im Content-Builder ist aber ein
     * eigener AJAX-Request (ContentBuilderApi::renderSlice() bzw.
     * ModuleBuilder::renderEditorSlice()/Helper::renderSliceBackend(), je nach
     * Aufrufkontext), der GLOBALS neu initialisiert. Zwei be_link-/be_media-Felder,
     * die in ZWEI verschiedenen Requests gerendert wurden (z.B. zwei nacheinander
     * eingefuegte Slices desselben oder unterschiedlicher Elementtypen), bekamen
     * dadurch identische IDs (z.B. zweimal "REX_LINK_1001_NAME") - HTML-Id-Kollision,
     * der Browser trifft bei getElementById()/querySelector() immer nur das ERSTE
     * Element mit dieser Id. Sichtbarer Effekt (per echtem User-Report gefunden,
     * reproduziert sowohl an einem eigenen Element als auch am Original
     * starter_cards): die Linkmap-Auswahl schrieb den gewaehlten Artikel ins
     * FALSCHE, meist unsichtbare Duplikat-Feld - das im Formular sichtbare
     * "Ziel-Seite"-Feld blieb leer, obwohl kein Fehler auftrat.
     *
     * mt_rand() auf Mikrosekunden-Basis (statt einer fixen Zahl) macht die Basis
     * ueber mehrere getrennte Requests hinweg praktisch kollisionsfrei, ohne echten
     * Cross-Request-Zustand (Session/DB) zu brauchen - die Counter muessen nur
     * INNERHALB des sichtbaren DOM eindeutig sein, nicht global ueber die Zeit
     * persistieren.
     */
    private static function counterBase(): int
    {
        return 1000 + random_int(0, 899999);
    }

    /**
     * Rendert zusätzliche HTML-Attribute aus dem Feld-Config-Key 'attributes'.
     *
     * Verwendung in config.php:
     * ```php
     * 'mein_feld' => [
     *     'type'       => 'text',
     *     'label'      => 'Name',
     *     'attributes' => [
     *         'data-validate' => 'required',
     *         'maxlength'     => '120',
     *         'autocomplete'  => 'name',
     *         'class'         => 'my-extra-class',  // wird zur form-control-Klasse addiert
     *     ],
     * ]
     * ```
     *
     * Geschützte Attribute (vom Feldtyp selbst gesteuert, werden ignoriert):
     *   name, type, value, checked, selected, id, rows
     *
     * @return string Fertig escapeter HTML-Attributstring inkl. führendem Leerzeichen,
     *                oder leerer String wenn keine Attribute konfiguriert.
     */
    protected function renderExtraAttributes(array $fieldConfig): string
    {
        $attributes = $fieldConfig['attributes'] ?? [];
        if (!is_array($attributes) || $attributes === []) {
            return '';
        }

        // Attribute die vom Feldtyp selbst gesetzt werden – Konflikte vermeiden
        $blocked = ['name', 'type', 'value', 'checked', 'selected', 'id', 'rows'];

        $parts = [];
        foreach ($attributes as $attr => $val) {
            $attr = strtolower(trim((string) $attr));
            if ($attr === '' || in_array($attr, $blocked, true)) {
                continue;
            }
            $parts[] = rex_escape($attr) . '="' . rex_escape((string) $val) . '"';
        }

        return $parts !== [] ? ' ' . implode(' ', $parts) : '';
    }
}
