<?php

namespace FriendsOfREDAXO\Builder;

use FriendsOfREDAXO\Builder\Config\ThemeProviderBridge;
use FriendsOfREDAXO\Builder\Fields\FieldRegistry;
use rex;
use rex_addon;
use rex_article_content;
use rex_escape;
use rex_path;
use rex_request;
use rex_response;
use rex_sql;
use Throwable;

/**
 * YForm Content Builder für Module
 * Ermöglicht die Nutzung von Content Builder Elementen in normalen REDAXO Modulen
 * Nutzt das Field Plugin System für konsistentes Rendering
 * 
 * Verwendung:
 * Input:  echo Module::create('gallery')->renderInput();
 * Output: echo Module::create('gallery', 'REX_VALUE[id=1 output=html]')->renderOutput();
 */
class Module
{
    protected string $elementType = '';
    /** @var array<string, mixed> */
    protected array $data = [];
    protected mixed $rawValue = null;
    protected string $framework = 'uikit';
    protected int $valueId = 1;
    
    /**
     * Element erstellen
     * 
     * @param string $type Element-Typ (gallery, divider, cards, etc.)
     * @param mixed $rawValue Rohe REX_VALUE Daten oder JSON-String
     * @param string $framework CSS Framework für Output (bootstrap, uikit, plain)
     * @param mixed $initialValues Optionale Initialwerte als Array oder JSON (werden nur genutzt wenn kein gespeicherter Wert vorhanden ist)
     * @return self
     */
    public static function create(string $type, mixed $rawValue = null, string $framework = 'uikit', mixed $valueId = null, mixed $initialValues = null): self
    {
        $instance = new self();
        $instance->elementType = $type;
        $instance->rawValue = $rawValue;
        $instance->framework = ThemeProviderBridge::normalizeFramework($framework);
        if (is_int($valueId) && $valueId > 0) {
            $instance->valueId = $valueId;
        } else {
            $instance->valueId = $instance->detectValueIdFromRawValue($rawValue);
        }

        // Wenn kein Raw-Value übergeben wurde, den Slot-Wert aus dem aktuellen Slice laden.
        if ($rawValue === null || $rawValue === '') {
            try {
                $rawValue = self::loadRawValueFromCurrentSlice($instance->valueId);
            } catch (Throwable $e) {
                // Im Gridblock-Kontext (oder anderen Rendering-Kontexten) kann getCurrentSlice() 
                // fehlschlagen. Das ist normal und kein Fehler.
                $rawValue = '';
            }
            $instance->rawValue = $rawValue;
        }
        
        // Daten normalisieren
        if (is_string($rawValue)) {
            // Wenn der String escaped ist (aus REX_VALUE), erst decoden
            $decoded = html_entity_decode($rawValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $instance->data = json_decode($decoded, true) ?: [];
        } elseif (is_array($rawValue)) {
            $instance->data = $rawValue;
        } else {
            $instance->data = [];
        }

        if ($instance->data === [] && $initialValues !== null) {
            $instance->data = $instance->normalizeInitialElementData($initialValues);
        }
        
        return $instance;
    }

    /**
     * Element über einen Value-Slot erstellen (verständlichere API).
     *
     * Beispiel:
     * echo Module::createByValueId('cards', 2, 'uikit')->renderInput();
     *
     * @param string $type Element-Typ
     * @param int    $valueId REX_VALUE Slot (1-20)
     * @param string $framework CSS Framework
     * @param mixed  $initialValues Optionale Initialwerte als Array oder JSON
     * @return self
     */
    public static function createByValueId(string $type, int $valueId = 1, string $framework = 'uikit', mixed $initialValues = null): self
    {
        $normalizedValueId = (int) $valueId;
        if ($normalizedValueId <= 0) {
            $normalizedValueId = 1;
        }

        return self::create($type, null, $framework, $normalizedValueId, $initialValues);
    }

    /**
     * @param mixed $initialValues
     * @return array<string, mixed>
     */
    protected function normalizeInitialElementData(mixed $initialValues): array
    {
        $data = [];

        if (is_string($initialValues)) {
            $decodedString = html_entity_decode($initialValues, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decodedData = json_decode($decodedString, true);
            if (is_array($decodedData)) {
                $data = $decodedData;
            }
        } elseif (is_array($initialValues)) {
            $data = $initialValues;
        }

        if ($data === []) {
            return [];
        }

        // Unterstützt sowohl direkte Feld-Daten als auch Slice-Strukturen ({type,data} / [{...}]).
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        if ($this->isListArray($data)) {
            $first = $data[0] ?? null;
            if (is_array($first)) {
                if (isset($first['data']) && is_array($first['data'])) {
                    return $first['data'];
                }

                return $first;
            }
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     */
    protected function isListArray(array $data): bool
    {
        if ($data === []) {
            return true;
        }

        $expectedKey = 0;
        foreach (array_keys($data) as $key) {
            if ($key !== $expectedKey) {
                return false;
            }
            ++$expectedKey;
        }

        return true;
    }

    /**
     * Vollstaendigen Multi-Element Builder fuer normale REDAXO-Module erstellen.
     *
     * @param int   $valueId REX_VALUE Slot (1-20)
     * @param mixed $rawValue Rohe REX_VALUE Daten oder JSON-String
     * @param array<string, mixed> $options Builder-Optionen (framework, label, description, allowed_elements)
     */
    public static function createWithValue(int $valueId = 1, mixed $rawValue = null, array $options = []): ModuleBuilder
    {
        return ModuleBuilder::create($valueId, $rawValue, $options);
    }

    /**
     * Hilfsfunktion für Modul-Code-Generierung: zukünftig-sicher auf getCurrentSlice() vorbereitet
     * 
     * Diese Methode ist gedacht für die Nutzung in generierten Modul-Output-Dateien.
     * 
     * Strategie:
     * - Wenn Gridblock NICHT verfügbar: Nutzt getCurrentSlice() (Modern, REX_VALUE-unabhängig)
     * - Wenn Gridblock verfügbar: Nutzt REX_VALUE[id=X output=html] (Gridblock-kompatibel)
     * 
     * Zukünftige Migrations-Info:
     * Wenn REDAXO REX_VALUE[id=X output=html] nicht mehr unterstützt:
     * 1. Diese Methode wird automatisch nur noch getCurrentSlice() nutzen
     * 2. Kein Code-Update in Modulen nötig - die generierte Funktion bleibt gleich!
     * 3. Diese Methode wird vereinfacht (REX_VALUE-Fallback entfernt)
     * 
     * @param int $valueId REX_VALUE Slot (1-20)
     * @return string Raw-Wert (entweder direkt von getCurrentSlice() oder REX_VALUE-Placeholder)
     */
    public static function getSliceValueForModule(int $valueId = 1): string
    {
        // Wenn Gridblock geladen ist, verwende REX_VALUE als Fallback (Gridblock-Sicherheit)
        $useGridblockFallback = rex_addon::get('gridblock')?->isAvailable() ?? false;
        
        if ($useGridblockFallback) {
            // Gridblock-Kontext: REX_VALUE mit Try-Catch auf getCurrentSlice()
            try {
                $slice = rex_article_content::getCurrentSlice();
                if ($slice && $slice->getId() > 0) {
                    $value = $slice->getValue($valueId);
                    if ($value !== null && $value !== '') {
                        return (string) $value;
                    }
                }
            } catch (Throwable $e) {
                // getCurrentSlice() nicht verfügbar - Fallback auf REX_VALUE
            }
            
            return 'REX_VALUE[id=' . $valueId . ' output=html]';
        }
        
        // Nicht-Gridblock: Direkt getCurrentSlice() (Modern, future-proof)
        try {
            $slice = rex_article_content::getCurrentSlice();
            if ($slice && $slice->getId() > 0) {
                $value = $slice->getValue($valueId);
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        } catch (Throwable $e) {
            // getCurrentSlice() nicht verfügbar - leer zurückgeben (kein REX_VALUE Fallback)
        }
        
        return '';
    }

    /**
     * Lädt den rohen Value-Inhalt eines Slots aus dem aktuellen Slice.
     *
     * @param int $valueId
     * @return string
     */
    protected static function loadRawValueFromCurrentSlice(int $valueId): string
    {
        $slot = (int) $valueId;
        if ($slot < 1 || $slot > 20) {
            $slot = 1;
        }

        // Schutz vor Verwendung in Gridblock oder anderen Rendering-Kontexten
        // Nur im direkten Backend-Slice-Editor laden, wo ein sicherer Request-Kontext existiert
        $page = rex_request('page', 'string', '');
        $sliceId = rex_request('slice_id', 'int', 0);
        
        // Wenn wir nicht im content/edit Backend sind UND es keine slice_id gibt, abbrechen
        if (strpos($page, 'content/edit') === false && $sliceId <= 0) {
            return '';
        }

        // Beim Hinzufügen eines neuen Slices (function=add) keine Daten aus dem vorherigen Slice laden
        $function = rex_request('function', 'string', '');
        if ($function === 'add') {
            return '';
        }

        if ($sliceId <= 0) {
            return self::loadRawValueFromModuleContext($slot);
        }

        try {
            $field = 'value' . $slot;
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT ' . $field . ' FROM ' . rex::getTable('article_slice') . ' WHERE id = :id',
                [':id' => $sliceId]
            );

            if ($sql->getRows() !== 1) {
                return self::loadRawValueFromModuleContext($slot);
            }

            return (string) $sql->getValue($field);
        } catch (Throwable $e) {
            return self::loadRawValueFromModuleContext($slot);
        }
    }

    /**
     * Fallback für Modulkontext ohne slice_id (z.B. Backend-Reload).
     *
     * @param int $slot
     * @return string
     */
    protected static function loadRawValueFromModuleContext(int $slot): string
    {
        if (isset($GLOBALS['REX_VALUE']) && is_array($GLOBALS['REX_VALUE'])) {
            if (array_key_exists($slot, $GLOBALS['REX_VALUE'])) {
                return (string) $GLOBALS['REX_VALUE'][$slot];
            }

            $slotKey = (string) $slot;
            if (array_key_exists($slotKey, $GLOBALS['REX_VALUE'])) {
                return (string) $GLOBALS['REX_VALUE'][$slotKey];
            }
        }

        return '';
    }

    /**
     * Ermittelt die REX_VALUE-ID aus dem übergebenen Raw-Value-String.
     * Unterstützt REX_VALUE[1] und REX_VALUE[id=1 ...] Schreibweisen.
     *
     * @param mixed $rawValue
     * @return int
     */
    protected function detectValueIdFromRawValue(mixed $rawValue): int
    {
        if (!is_string($rawValue) || $rawValue === '') {
            return 1;
        }

        if (preg_match('/REX_VALUE\[(?:id=)?([0-9]+)/', $rawValue, $matches) === 1) {
            $valueId = (int) $matches[1];
            return $valueId > 0 ? $valueId : 1;
        }

        // Fallback für bereits ersetzte REX_VALUE-Strings:
        // versuche den passenden value-Slot über den aktuellen Slice-Wert zu erkennen.
        $detectedBySlice = $this->detectValueIdByCurrentSlice($rawValue);
        if ($detectedBySlice !== null) {
            return $detectedBySlice;
        }

        return 1;
    }

    /**
     * Versucht die Value-ID über einen Match mit den value1..value20 Daten
     * des aktuellen Slices zu erkennen.
     *
     * @param string $rawValue
     * @return int|null
     */
    protected function detectValueIdByCurrentSlice(string $rawValue): ?int
    {
        $sliceId = rex_request('slice_id', 'int', 0);
        if ($sliceId <= 0) {
            return null;
        }

        try {
            $fields = [];
            for ($i = 1; $i <= 20; ++$i) {
                $fields[] = 'value' . $i;
            }

            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT ' . implode(', ', $fields) . ' FROM ' . rex::getTable('article_slice') . ' WHERE id = :id',
                [':id' => $sliceId]
            );

            if ($sql->getRows() !== 1) {
                return null;
            }

            $needle = $this->normalizeValueForCompare($rawValue);
            if ($needle === '') {
                return null;
            }

            $matches = [];
            for ($i = 1; $i <= 20; ++$i) {
                $slotValue = (string) $sql->getValue('value' . $i);
                if ($slotValue === '') {
                    continue;
                }

                if ($this->normalizeValueForCompare($slotValue) === $needle) {
                    $matches[] = $i;
                }
            }

            return count($matches) === 1 ? $matches[0] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Normalisiert gespeicherte Werte für robuste Vergleichbarkeit.
     *
     * @param string $value
     * @return string
     */
    protected function normalizeValueForCompare(string $value): string
    {
        return trim((string) html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Liefert den Namen des passenden REX_INPUT_VALUE Feldes.
     *
     * @return string
     */
    protected function getInputValueFieldName(): string
    {
        return 'REX_INPUT_VALUE[' . $this->valueId . ']';
    }

    /**
     * Erzeugt eine eindeutige Instanz-ID aus sliceId + valueId.
     * Verhindert Kollisionen bei mehreren Instanzen auf derselben Seite.
     *
     * @return string
     */
    protected function getInstanceId(): string
    {
        $sliceId = rex_request('slice_id', 'int', 0);
        return 's' . $sliceId . '_v' . $this->valueId;
    }
    
    /**
     * Input-Formular ausgeben - nutzt YForm Content Builder Render-Logik
     * 
     * @return string HTML des Input-Formulars
     */
    public function renderInput(): string
    {
        // Config laden
        $config = $this->loadConfig();
        if (!$config) {
            return '<div class="alert alert-danger">Element-Config für "' . rex_escape($this->elementType) . '" nicht gefunden.</div>';
        }
        
        $instanceId = $this->getInstanceId();
        $storageId = 'yform_cb_data_storage_' . $instanceId;
        $formId = 'yform_cb_form_' . $instanceId;

        ob_start();
        
        echo '<div class="form-group yform-content-builder-module-input" data-element-type="' . rex_escape($this->elementType) . '">';
        
        // Hidden Field für JSON-Daten (wird von JavaScript gefüllt)
        echo '<input type="hidden" name="' . rex_escape($this->getInputValueFieldName()) . '" id="' . rex_escape($storageId) . '" value="' . rex_escape(json_encode($this->data)) . '" />';
        
        // Formular mit YForm Content Builder Render-Methoden
        echo '<div class="slice-form-container" id="' . rex_escape($formId) . '">';
        $this->renderFormFields($config, $this->data);
        echo '</div>';
        
        echo '</div>';
        
        // Script um Selectpicker mit sanitize:false zu initialisieren (für SVG/img data-content)
        ?>
        <script nonce="<?= rex_response::getNonce() ?>">
        $(function() {
            // Selectpicker mit sanitize:false neu initialisieren, damit SVG-Icons angezeigt werden
            $('#<?= rex_escape($formId) ?> .selectpicker').each(function() {
                var $select = $(this);
                // Wenn bereits initialisiert, zerstören
                if ($select.data('selectpicker')) {
                    $select.selectpicker('destroy');
                }
                // Mit sanitize:false neu initialisieren
                $select.selectpicker({
                    sanitize: false
                });
            });
        });
        </script>
        <?php
        
        // JavaScript für automatisches Data-Sync
        ?>
        <script nonce="<?= rex_response::getNonce() ?>">
        (function() {
            var storage = document.getElementById('<?= rex_escape($storageId) ?>');
            var form = document.getElementById('<?= rex_escape($formId) ?>');
            var moduleRoot = form ? form.closest('.yform-content-builder-module-input') : null;
            var enclosingForm = form ? form.closest('form') : null;
            
            if (!storage || !form) {
                console.warn('YForm Content Builder: Storage or form not found');
                return;
            }

            function syncTinyMceTextareas() {
                if (typeof tinymce === 'undefined') {
                    return;
                }

                form.querySelectorAll('textarea.tiny-editor').forEach(function(textarea) {
                    if (!textarea.id) {
                        return;
                    }

                    var editor = tinymce.get(textarea.id);
                    if (editor) {
                        textarea.value = editor.getContent();
                    }
                });
            }

            
            // Funktion zum Sammeln aller Formulardaten
            function collectFormData() {
                // TinyMCE-Inhalte in Textareas synchronisieren
                syncTinyMceTextareas();

                // Erst CKEditor5-Inhalte in Textareas synchronisieren (REDAXO CKE5)
                if (typeof ckeditors !== 'undefined') {
                    form.querySelectorAll('textarea.cke5-editor').forEach(function(textarea) {
                        var editorId = textarea.id;
                        if (ckeditors[editorId]) {
                            // Daten aus Editor ins Textarea schreiben
                            textarea.value = ckeditors[editorId].getData();
                        }
                    });
                }
                
                var data = {};
                
                // Sammle Felder aus dem Hauptformular
                var allFields = form.querySelectorAll('input[name], textarea[name], select[name]');
                
                // Modal-Felder nur innerhalb dieser Builder-Instanz sammeln
                var modalFields = moduleRoot
                    ? moduleRoot.querySelectorAll('.modal input[name], .modal textarea[name], .modal select[name]')
                    : [];
                
                // Beide NodeLists kombinieren
                var combinedFields = Array.from(allFields).concat(Array.from(modalFields));
                
                var processedFields = new Set(); // Track welche Felder wir schon verarbeitet haben
                
                // Alle Inputs, Textareas und Selects sammeln
                combinedFields.forEach(function(field) {
                    var name = field.getAttribute('name');
                    
                    // Skip fields from template items (hidden repeater templates)
                    var isInTemplate = field.closest('.repeater-item-template');
                    if (isInTemplate) {
                        return; // Skip template fields
                    }
                    
                    // Radio-Buttons: Nur den gechecked Button verarbeiten
                    if (field.type === 'radio') {
                        if (!field.checked) {
                            return; // Nicht-gecheckte Radio-Buttons überspringen
                        }
                        // Gechecked Radio darf auch bei gleichem Namen verarbeitet werden
                    } else {
                        // Skip wenn wir dieses Feld schon verarbeitet haben
                        // (REDAXO kann mehrere Inputs mit gleichem Namen haben)
                        if (processedFields.has(name)) {
                            return;
                        }
                    }
                    processedFields.add(name);
                    
                    // Repeater-Felder (z.B. items[0][media]) zu verschachteltem Array konvertieren
                    var repeaterMatch = name.match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
                    if (repeaterMatch) {
                        var repeaterName = repeaterMatch[1];  // z.B. "items"
                        var index = parseInt(repeaterMatch[2]); // z.B. 0
                        var fieldName = repeaterMatch[3];      // z.B. "media"
                        
                        // Repeater-Array initialisieren
                        if (!data[repeaterName]) {
                            data[repeaterName] = [];
                        }
                        
                        // Index-Objekt initialisieren
                        if (!data[repeaterName][index]) {
                            data[repeaterName][index] = {};
                        }
                        
                        // Wert setzen
                        if (field.type === 'checkbox') {
                            data[repeaterName][index][fieldName] = field.checked ? '1' : '';
                        } else {
                            data[repeaterName][index][fieldName] = field.value;
                        }
                    }
                    // Normale Felder
                    else {
                        // Checkboxen
                        if (field.type === 'checkbox') {
                            data[name] = field.checked ? '1' : '';
                        }
                        // Normale Felder
                        else {
                            data[name] = field.value;
                        }
                    }
                });
                
                // Leere Arrays aus Repeatern bereinigen (nur Items mit Werten behalten)
                Object.keys(data).forEach(function(key) {
                    if (Array.isArray(data[key])) {
                        // Leere Objekte aus Array entfernen
                        data[key] = data[key].filter(function(item) {
                            if (typeof item !== 'object') return true;
                            // Prüfen ob Objekt mindestens einen nicht-leeren Wert hat
                            return Object.values(item).some(function(val) {
                                return val !== '' && val !== null && val !== undefined;
                            });
                        });
                        // Leere Arrays ganz entfernen
                        if (data[key].length === 0) {
                            delete data[key];
                        }
                    }
                });
                
                // JSON in Hidden Field speichern
                storage.value = JSON.stringify(data);
            }
            
            // Bei Änderungen Daten sammeln
            form.addEventListener('change', collectFormData);
            form.addEventListener('input', collectFormData);
            
            // Repeater item removal event
            $(form).on('repeater:item-removed', function() {
                collectFormData();
            });
            
            // REDAXO Media Widget Change Events (für REX_MEDIA_ Felder)
            // REDAXO schreibt in Felder mit ID REX_MEDIA_X, triggert aber jQuery change
            $(form).on('change', 'input[id^="REX_MEDIA_"]', function() {
                collectFormData();
            });
            
            // REDAXO Link Widget Change Events (für REX_LINK_ Felder)
            // REDAXO's Linkmap setzt das Hidden Field, aber triggert kein change Event
            $(form).on('change', 'input[id^="REX_LINK_"]', function() {
                collectFormData();
            });
            
            // REDAXO's rex:selectLink Event (wird nach Linkmap-Auswahl gefeuert)
            $(window).on('rex:selectLink', function(event, link, name) {
                // 300ms warten, damit REDAXO's Callback das Hidden Field sicher gesetzt hat
                setTimeout(function() {
                    collectFormData();
                }, 300);
            });
            
            // Bootstrap Modal Events - Daten sammeln wenn Modal geschlossen wird
            $('.modal').on('hidden.bs.modal', function() {
                collectFormData();
            });
            
            // Auch auf Änderungen in Modals reagieren (nur diese Instanz)
            if (moduleRoot) {
                $(moduleRoot).on('change input', '.modal input, .modal textarea, .modal select', function() {
                    collectFormData();
                });
            }
            
            // CKEditor5 Change Events abfangen (REDAXO CKE5)
            // REDAXO CKE5 verwendet ClassicEditor und speichert Instanzen in window.ckeditors
            $(window).on('rex:cke5IsInit', function(event, editor, editorId) {
                // Prüfen ob dieser Editor zu unserem Formular gehört
                var textarea = form.querySelector('#' + editorId);
                if (textarea) {
                    // Change Event für Auto-Sync
                    editor.model.document.on('change:data', function() {
                        // Daten aus Editor ins Textarea schreiben
                        textarea.value = editor.getData();
                        collectFormData();
                    });
                }
            });
            
            // Für bereits initialisierte CKE5 Instanzen
            if (typeof ckeditors !== 'undefined') {
                form.querySelectorAll('textarea.cke5-editor').forEach(function(textarea) {
                    var editorId = textarea.id;
                    if (ckeditors[editorId]) {
                        var editor = ckeditors[editorId];
                        editor.model.document.on('change:data', function() {
                            textarea.value = editor.getData();
                            collectFormData();
                        });
                    }
                });
            }

            if (enclosingForm) {
                enclosingForm.addEventListener('submit', function() {
                    collectFormData();
                });
            }

            // Initial sammeln
            collectFormData();
            
            // Repeater-Funktionalität initialisieren (wird von content-builder.js bereitgestellt)
            if (typeof window.ContentBuilder !== 'undefined') {
                window.ContentBuilder.initRepeaters();
                
                // Nach Repeater-Änderungen auch Daten sammeln
                form.addEventListener('repeater:changed', collectFormData);
                
                // Nach Repeater-Änderungen auch TinyMCE neu initialisieren
                // (Neue Textareas in neuen Repeater-Items)
                form.addEventListener('repeater:changed', function() {
                    setTimeout(function() {
                        $(document).trigger('rex:ready', [$(moduleRoot)]);
                    }, 100);
                });
            }
        })();
        </script>
        <?php
        
        return (string) ob_get_clean();
    }
    
    /**
     * Output-HTML ausgeben
     * 
     * @return string HTML des Elements
     */
    public function renderOutput(): string
    {
        // Config laden
        $config = $this->loadConfig();
        if (!$config) {
            return rex::isDebugMode() ? '<div class="alert alert-warning">Element-Config nicht gefunden</div>' : '';
        }
        
        // Wenn keine Daten vorhanden, nichts ausgeben
        if (empty($this->data)) {
            return '';
        }
        
        // Element-Template suchen - erst in registrierten Custom-Pfaden, dann in Core-Pfaden.
        $elementDir = $this->resolveElementPath();
        if ($elementDir === null) {
            return rex::isDebugMode() ? '<div class="alert alert-warning">Element-Pfad nicht gefunden: ' . rex_escape($this->elementType) . '</div>' : '';
        }
        
        $templateCandidates = array_values(array_unique([
            $this->framework,
            'plain',
            'uikit',
            'bootstrap',
        ]));

        $elementFile = '';
        foreach ($templateCandidates as $templateName) {
            $candidate = $elementDir . '/templates/' . $templateName . '.php';
            if (file_exists($candidate)) {
                $elementFile = $candidate;
                break;
            }
        }

        if ($elementFile === '') {
            $allTemplateFiles = glob($elementDir . '/templates/*.php');
            if (is_array($allTemplateFiles)) {
                sort($allTemplateFiles);
                foreach ($allTemplateFiles as $file) {
                    $basename = basename($file);
                    if ($basename !== '' && $basename[0] !== '_') {
                        $elementFile = $file;
                        break;
                    }
                }
            }
        }

        // Fallback auf element.php (legacy)
        if ($elementFile === '' && file_exists($elementDir . '/element.php')) {
            $elementFile = $elementDir . '/element.php';
        }

        if ($elementFile === '') {
            return rex::isDebugMode() ? '<div class="alert alert-warning">Element-Template nicht gefunden: ' . rex_escape($this->elementType) . '</div>' : '';
        }
        
        Helper::loadElementI18n($elementDir);

        // Element-Template einbinden
        ob_start();
        $data = $this->data; // Für Template verfügbar machen
        $elementData = $this->data; // Alias für Template-Kompatibilität
        $config = $config; // Config auch verfügbar machen
        $framework = $this->framework; // Framework für Template verfügbar
        include $elementFile;
          return (string) ob_get_clean();
    }
    
    /**
     * Config des Elements laden
     * 
      * @return array<string, mixed>|null
     */
    protected function loadConfig(): array|null
    {
        $elementDir = $this->resolveElementPath();
        if ($elementDir === null) {
            return null;
        }

        $configFile = $elementDir . '/config.php';
        
        if (!file_exists($configFile)) {
            return null;
        }

        Helper::loadElementI18n(dirname($configFile));

        return include $configFile;
    }
    
    /**
     * Formular-Felder rendern - nutzt YForm Content Builder renderFormField
        *
        * @param array<string, mixed> $config
        * @param array<string, mixed> $sliceData
     */
    protected function renderFormFields(array $config, array $sliceData): void
    {
        $hasSettingsModal = isset($config['settings_modal']) && is_array($config['settings_modal']);
        $elementDir = $this->resolveElementPath();
        if ($elementDir === null) {
            $elementDir = rex_path::addon('builder', 'elements/' . $this->elementType . '/');
        }
        $helpModalConfig = ModalHelper::buildConfigForElementDir($elementDir);

        if ($hasSettingsModal || $helpModalConfig !== null) {
            echo '<div class="clearfix" style="margin-bottom: 15px; display: flex; justify-content: flex-end; gap: 6px;">';

            if ($hasSettingsModal) {
                $this->renderSettingsModalButton($config, $sliceData, true);
            }

            if ($helpModalConfig !== null) {
                $helpModalConfig['_modal_id'] = ModalHelper::createModalId();
                ModalHelper::renderButton($helpModalConfig, true);
            }

            echo '</div>';

            if ($helpModalConfig !== null) {
                ModalHelper::renderModal($helpModalConfig);
            }
        }
        
        // Prüfen ob Tabs definiert sind
        if (isset($config['field_groups']) && is_array($config['field_groups'])) {
            $this->renderFormWithTabs($config, $sliceData);
        } else {
            // Standard: Alle Felder ohne Tabs
            $modalFields = [];
            if (isset($config['settings_modal']['fields'])) {
                $modalFields = $config['settings_modal']['fields'];
            }
            
            $self = $this;
            FieldRegistry::renderFieldRowsGroup(
                $config['fields'],
                $modalFields,
                function (string $fieldName, array $fieldConfig) use ($self, $sliceData): void {
                    $self->renderFormField($fieldName, $fieldConfig, $sliceData);
                }
            );
        }
    }
    
    /**
     * Einzelnes Form-Feld rendern - nutzt FieldRegistry
        *
        * @param array<string, mixed> $fieldConfig
        * @param array<string, mixed> $sliceData
     */
    protected function renderFormField(string $fieldName, array $fieldConfig, array $sliceData): void
    {
        // Wert aus sliceData extrahieren
        $value = $this->getValueForField($fieldName, $sliceData);
        
        // Wenn ein Wert gefunden wurde, in sliceData einfügen
        if ($value !== null && $value !== '') {
            if (strpos($fieldName, '[') === false) {
                $sliceData[$fieldName] = $value;
            } else {
                $sliceData[$fieldName] = $value;
            }
        }
        
        // Field Registry nutzen für konsistentes Rendering
        FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
    }
    
    /**
     * Get value for a field from sliceData - handles nested arrays properly
        *
        * @param array<string, mixed> $sliceData
     */
    protected function getValueForField(string $fieldName, array $sliceData): mixed
    {
        // Einfacher Key ohne Brackets
        if (strpos($fieldName, '[') === false) {
            return $sliceData[$fieldName] ?? null;
        }
        
        // Repeater-Field: items[0][title]
        // Parsen: items[0][title] -> baseField=items, index=0, subField=title
        if (preg_match('/^(\w+)\[(\d+)\]\[(\w+)\]$/', $fieldName, $matches)) {
            $baseField = $matches[1];  // z.B. "items"
            $index = (int)$matches[2]; // z.B. 0
            $subField = $matches[3];   // z.B. "title"
            
            if (isset($sliceData[$baseField][$index][$subField])) {
                return $sliceData[$baseField][$index][$subField];
            }
        }
        
        return null;
    }
    
    /**
     * Settings Modal Button rendern
        *
        * @param array<string, mixed> $config
        * @param array<string, mixed> $sliceData
     */
    protected function renderSettingsModalButton(array $config, array $sliceData, bool $toolbarButton = false): void
    {
        $modalId = 'settings_modal_' . uniqid();
        $modalConfig = $config['settings_modal'];
        $label = $modalConfig['label'] ?? 'Einstellungen';
        $icon = $modalConfig['icon'] ?? 'fa-cog';

        if ($toolbarButton) {
            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#' . $modalId . '">';
        } else {
            echo '<div class="form-group">';
            echo '<button type="button" class="btn btn-default btn-block" data-toggle="modal" data-target="#' . $modalId . '">';
        }

        echo '<i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label);
        echo '</button>';

        if (!$toolbarButton) {
            echo '</div>';
        }
        
        // Modal HTML
        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog">';
        echo '<div class="modal-dialog modal-lg" role="document">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>';
        echo '<h4 class="modal-title"><i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label) . '</h4>';
        echo '</div>';
        echo '<div class="modal-body" style="text-align: left;">';
        
        // Felder im Modal rendern
        if (isset($modalConfig['fields']) && is_array($modalConfig['fields'])) {
            foreach ($modalConfig['fields'] as $fieldName) {
                if (isset($config['fields'][$fieldName])) {
                    $this->renderFormField($fieldName, $config['fields'][$fieldName], $sliceData);
                }
            }
        }
        
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-primary" data-dismiss="modal">Übernehmen</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }


    /**
     * Formular mit Tabs rendern
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sliceData
     */
    protected function renderFormWithTabs(array $config, array $sliceData): void
    {
        $tabId = 'tab_' . uniqid();

        echo '<ul class="nav nav-tabs" role="tablist">';
        $firstTab = true;
        foreach ($config['field_groups'] as $groupKey => $group) {
            $active = $firstTab ? ' class="active"' : '';
            echo '<li role="presentation"' . $active . '>';
            echo '<a href="#' . $tabId . '_' . $groupKey . '" role="tab" data-toggle="tab">';
            
            // Icon anzeigen falls vorhanden
            if (!empty($group['icon'])) {
                echo '<i class="fa ' . rex_escape($group['icon']) . '"></i> ';
            }
            
            echo rex_escape($group['label'] ?? $groupKey);
            echo '</a>';
            echo '</li>';
            $firstTab = false;
        }
        echo '</ul>';
        
        echo '<div class="tab-content" style="padding-top: 20px;">';
        $firstTab = true;
        foreach ($config['field_groups'] as $groupKey => $group) {
            $active = $firstTab ? ' active' : '';
            echo '<div role="tabpanel" class="tab-pane' . $active . '" id="' . $tabId . '_' . $groupKey . '">';
            
            // Felder dieser Gruppe rendern
            if (isset($group['fields']) && is_array($group['fields'])) {
                $groupFieldMap = [];
                foreach ($group['fields'] as $fieldName) {
                    if (isset($config['fields'][$fieldName])) {
                        $groupFieldMap[$fieldName] = $config['fields'][$fieldName];
                    }
                }
                $self = $this;
                FieldRegistry::renderFieldRowsGroup(
                    $groupFieldMap,
                    [],
                    function (string $fieldName, array $fieldConfig) use ($self, $sliceData): void {
                        $self->renderFormField($fieldName, $fieldConfig, $sliceData);
                    }
                );
            }
            
            echo '</div>';
            $firstTab = false;
        }
        echo '</div>';
    }

    protected function resolveElementPath(): ?string
    {
        $customPaths = \rex_extension::registerPoint(new \rex_extension_point(
            'BUILDER_ELEMENT_PATHS',
            ['']
        ));

        foreach ($customPaths as $customPath) {
            if ($customPath === '') {
                continue;
            }

            $elementPath = rtrim($customPath, '/\\') . '/' . $this->elementType;
            if (is_dir($elementPath)) {
                return $elementPath;
            }
        }

        if (rex_addon::exists('project') && rex_addon::get('project')->isAvailable()) {
            $projectPath = rex_addon::get('project')->getPath('elements/' . $this->elementType);
            if (is_dir($projectPath)) {
                return $projectPath;
            }
        }

        $dataPath = rex_addon::get('builder')->getDataPath('elements/' . $this->elementType);
        if (is_dir($dataPath)) {
            return $dataPath;
        }

        $addonPath = rex_addon::get('builder')->getPath('elements/' . $this->elementType);
        return is_dir($addonPath) ? $addonPath : null;
    }
}
