<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_extension;
use rex_extension_point;
use rex_escape;

/**
 * Registry für Content Builder Feldtypen
 * 
 * Ermöglicht:
 * - Registrierung von Feldtypen
 * - Überschreiben von Feldtypen durch andere Addons
 * - Abrufen von Feldtypen nach Name
 * 
 * Verwendung:
 * ```php
 * // Feldtyp registrieren
 * FieldRegistry::register(new TextField());
 * 
 * // Feldtyp abrufen und rendern
 * $field = FieldRegistry::get('text');
 * $field->render('title', $config, $value, $sliceData);
 * ```
 */
class FieldRegistry
{
    /**
     * @var array<string, FieldInterface>
     */
    private static array $fields = [];

    /**
     * @var bool Wurden Standard-Felder bereits geladen?
     */
    private static bool $initialized = false;

    /**
     * Registriert einen Feldtyp
     * Überschreibt existierende Feldtypen mit gleichem Namen
     */
    public static function register(FieldInterface $field): void
    {
        self::$fields[$field::getType()] = $field;
        
        // Registry-Referenz setzen für verschachtelte Felder
        if ($field instanceof FieldAbstract) {
            $field->setRegistry(new self());
        }
    }

    /**
     * Gibt einen Feldtyp zurück
     * 
     * @param string $type Feldtyp-Name
     * @return FieldInterface|null
     */
    public static function get(string $type): ?FieldInterface
    {
        self::ensureInitialized();
        return self::$fields[$type] ?? null;
    }

    /**
     * Prüft ob ein Feldtyp registriert ist
     */
    public static function has(string $type): bool
    {
        self::ensureInitialized();
        return isset(self::$fields[$type]);
    }

    /**
     * Gibt alle registrierten Feldtypen zurück
     * 
     * @return array<string, FieldInterface>
     */
    public static function getAll(): array
    {
        self::ensureInitialized();
        return self::$fields;
    }

    /**
     * Rendert eine Feldliste mit optionalem Bootstrap-Column-Layout.
     *
     * Felder mit 'col' => N (1-12) werden automatisch in eine .row gruppiert.
     * Felder ohne 'col' werden wie gewohnt als volle Breite gerendert.
     *
     * @param iterable<string, array>  $fields     Assoziatives Array fieldName => fieldConfig
     * @param string[]                 $skipFields Feldnamen überspringen (z.B. Modal-Felder)
     * @param callable                 $renderFn   function(string $fieldName, array $fieldConfig): void
     */
    public static function renderFieldRowsGroup(iterable $fields, array $skipFields, callable $renderFn): void
    {
        $rowOpen = false;
        $colSum = 0;

        foreach ($fields as $fieldName => $fieldConfig) {
            if (!empty($skipFields) && in_array($fieldName, $skipFields, true)) {
                continue;
            }

            $col = isset($fieldConfig['col']) ? (int)$fieldConfig['col'] : null;
            $visibleIf = $fieldConfig['visible_if'] ?? null;
            $hasVisibleIf = is_array($visibleIf) && [] !== $visibleIf;
            $encodedVisibleIf = null;

            if ($hasVisibleIf) {
                $encodedVisibleIf = json_encode($visibleIf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (!is_string($encodedVisibleIf) || $encodedVisibleIf === '') {
                    $hasVisibleIf = false;
                    $encodedVisibleIf = null;
                }
            }

            // Row öffnen wenn das erste col-Feld kommt
            if ($col !== null && !$rowOpen) {
                echo '<div class="row" style="margin-left:-5px;margin-right:-5px;">';
                $rowOpen = true;
                $colSum = 0;
            }
            // Row schließen wenn ein Feld ohne col kommt
            elseif ($col === null && $rowOpen) {
                echo '</div>';
                $rowOpen = false;
            }

            $renderConfig = $fieldConfig;

            if ($col !== null && $hasVisibleIf && $encodedVisibleIf !== null) {
                echo '<div class="yfcb-conditional-field" data-yfcb-visible-if="' . rex_escape($encodedVisibleIf) . '">';
                $renderConfig['__visible_if_wrapped'] = true;
            }

            if ($col !== null) {
                echo '<div class="col-sm-' . $col . '" style="padding-left:5px;padding-right:5px;">';
            }

            $renderFn($fieldName, $renderConfig);

            if ($col !== null) {
                echo '</div>';

                if ($col !== null && $hasVisibleIf && $encodedVisibleIf !== null) {
                    echo '</div>';
                }

                $colSum += $col;
                // Row schließen wenn Spalten voll (≥12)
                if ($colSum >= 12) {
                    echo '</div>';
                    $rowOpen = false;
                    $colSum = 0;
                }
            }
        }

        if ($rowOpen) {
            echo '</div>';
        }
    }

    /**
     * Rendert ein Feld basierend auf Typ
     */
    public static function renderField(string $fieldName, array $fieldConfig, array $sliceData): void
    {
        $visibleIf = $fieldConfig['visible_if'] ?? null;
        $hasVisibleIf = is_array($visibleIf) && [] !== $visibleIf && empty($fieldConfig['__visible_if_wrapped']);

        if ($hasVisibleIf) {
            $encodedVisibleIf = json_encode($visibleIf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encodedVisibleIf) && '' !== $encodedVisibleIf) {
                echo '<div class="yfcb-conditional-field" data-yfcb-visible-if="' . rex_escape($encodedVisibleIf) . '">';
            } else {
                $hasVisibleIf = false;
            }
        }

        $type = $fieldConfig['type'] ?? 'text';
        $field = self::get($type);

        if ($field === null) {
            // Fallback auf Text-Feld
            $field = self::get('text');
            if ($field === null) {
                echo '<div class="alert alert-danger">Unbekannter Feldtyp: ' . \rex_escape($type) . '</div>';
                return;
            }
        }

        // Wert aus sliceData extrahieren
        $value = self::getNestedValue($fieldName, $sliceData);
        if ($value === null) {
            $value = $fieldConfig['default'] ?? '';
        }

        $field->render($fieldName, $fieldConfig, $value, $sliceData);

        if ($hasVisibleIf) {
            echo '</div>';
        }
    }

    /**
     * Initialisiert die Standard-Feldtypen
     */
    private static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Standard-Feldtypen registrieren
        self::registerDefaultFields();

        // Extension Point für zusätzliche Feldtypen
        self::$fields = rex_extension::registerPoint(new rex_extension_point(
            'BUILDER_FIELDS',
            self::$fields
        ));
    }

    /**
     * Registriert alle Standard-Feldtypen
     */
    private static function registerDefaultFields(): void
    {
        $defaultFields = [
            new InfoField(),
            new TextField(),
            new TextareaField(),
            new CheckboxField(),
            new SelectField(),
            new ChoiceField(),
            new Cke5Field(),
            new TinyMceField(),
            new BeMediaField(),
            new BeLinkField(),
            new RadioImageField(),
            new ColorSwatchesField(),
            new ColorField(),
            new BeTableSelectField(),
            new YFormPickerField(),
            new SmartLinkField(),
            new RichHeadlineField(),
            new TableEditorField(),
            new RepeaterField(),
        ];

        foreach ($defaultFields as $field) {
            self::register($field);
        }
    }

    /**
     * Holt Wert aus verschachteltem Array
     */
    private static function getNestedValue(string $key, array $data): mixed
    {
        if (strpos($key, '[') === false) {
            return $data[$key] ?? null;
        }

        preg_match_all('/([^\[\]]+)/', $key, $matches);
        $keys = $matches[1];

        $value = $data;
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Setzt die Registry zurück (für Tests)
     */
    public static function reset(): void
    {
        self::$fields = [];
        self::$initialized = false;
    }
}
