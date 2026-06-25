<?php

namespace FriendsOfREDAXO\Builder\Fields;

/**
 * Interface für Content Builder Feldtypen
 * 
 * Jeder Feldtyp implementiert dieses Interface und kann so:
 * - Einfach registriert werden
 * - Von anderen Addons überschrieben werden
 * - Sauber getrennt gepflegt werden
 */
interface FieldInterface
{
    /**
     * Gibt den Feldtyp-Namen zurück (z.B. 'text', 'be_media', 'repeater')
     */
    public static function getType(): string;

    /**
     * Rendert das Formularfeld im Backend
     *
     * @param string $fieldName Name des Feldes (für name-Attribut)
     * @param array $fieldConfig Konfiguration aus config.php
     * @param mixed $value Aktueller Wert
     * @param array $sliceData Komplette Slice-Daten (für verschachtelte Felder)
     */
    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void;

    /**
     * Gibt den Wert für die Ausgabe/Speicherung zurück
     * Kann für Validierung/Transformation genutzt werden
     *
     * @param mixed $value Roher Eingabewert
     * @param array $fieldConfig Feldkonfiguration
     * @return mixed Verarbeiteter Wert
     */
    public function processValue(mixed $value, array $fieldConfig): mixed;
}
