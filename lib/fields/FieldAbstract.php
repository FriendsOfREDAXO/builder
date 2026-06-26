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
            // Wir starten bei 1000 um Kollisionen mit Standard-Slices zu vermeiden
            $GLOBALS['yform_cb_media_counter'] = 1000;
        }
        return ++$GLOBALS['yform_cb_media_counter'];
    }

    /**
     * Holt nächsten Link-Counter
     */
    protected static function getNextLinkCounter(): int
    {
        if (!isset($GLOBALS['yform_cb_link_counter'])) {
            // Wir starten bei 1000 um Kollisionen mit Standard-Slices zu vermeiden
            $GLOBALS['yform_cb_link_counter'] = 1000;
        }
        return ++$GLOBALS['yform_cb_link_counter'];
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
