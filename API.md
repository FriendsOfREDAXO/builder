# Builder API

Diese Dokumentation beschreibt die API des Builders, wie man eigene Elemente erstellt und das System mit eigenen Feldtypen erweitert.

## 📚 Inhaltsverzeichnis

- [API-Endpunkte](#api-endpunkte)
- [Modul-Integration](#modul-integration)
- [Frontend-Output API](#frontend-output-api)
- [Frameworks & Templates](#frameworks--templates)
- [Media-Manager-Typen](#media-manager-typen)
- [Feldtypen-System](#feldtypen-system)
- [Feld-Konfiguration](#feld-konfiguration)
  - [Gemeinsame Optionen](#gemeinsame-optionen-alle-felder)
  - [Permission System](#permission-system)
- [Eigene Elemente erstellen](#eigene-elemente-erstellen)
- [Eigene Feldtypen erstellen](#eigene-feldtypen-erstellen)
- [Datensatz-Picker Feldtypen](#datensatz-picker-feldtypen)
- [Extra-Felder System](#extra-felder-system)
- [Extension Points](#extension-points)
- [Helper-Klassen](#helper-klassen)
- [Datenstruktur](#datenstruktur)
- [Best Practices](#best-practices)
- [Fehlerbehebung](#fehlerbehebung)

---

## API-Endpunkte

Der Content Builder nutzt eine dedizierte `rex_api_function` für alle AJAX-Requests. Die URL ist immer gleich:

```
/redaxo/index.php?rex-api-call=content_builder&action=<action>
```

### Verfügbare Actions

| Action | Methode | Beschreibung | Parameter |
|--------|---------|--------------|-----------|
| `load_slice_form` | POST | Lädt das Bearbeitungsformular für ein Element | `slice_type`, `slice_data` |
| `render_slice` | POST | Rendert ein Element mit Template | `slice_type`, `slice_data`, `framework` |
| `load_media_categories` | POST | Lädt Medienpool-Kategorien | - |
| `load_media_list` | POST | Lädt Medienliste einer Kategorie | `category_id`, `type` |
| `get_media_preview` | POST | Erzeugt Media-Preview HTML | `media_file`, `types` |
| `get_element_css` | GET | Liefert gemergtes Element-CSS | `framework`, `elements` |

### Beispiel: Element-Formular laden

```javascript
$.ajax({
    url: '/redaxo/index.php?rex-api-call=content_builder',
    method: 'POST',
    data: {
        action: 'load_slice_form',
        slice_type: 'text_image',
        slice_data: {
            headline: 'Mein Titel',
            text: '<p>Inhalt</p>'
        }
    },
    success: function(html) {
        // Formular HTML einfügen
    }
});
```

### Beispiel: Element rendern

```javascript
$.ajax({
    url: '/redaxo/index.php?rex-api-call=content_builder',
    method: 'POST',
    data: {
        action: 'render_slice',
        slice_type: 'headline',
        slice_data: { text: 'Willkommen', level: 'h1' },
        framework: 'uikit'
    },
    success: function(html) {
        // Gerenderten HTML-Output einfügen
    }
});
```

### Beispiel: Gemergtes Element-CSS laden

Frontend (manuell einbinden):

```html
<link rel="stylesheet" href="/index.php?rex-api-call=content_builder&action=get_element_css&framework=uikit">
```

Optional nur für bestimmte Elemente:

```html
<link rel="stylesheet" href="/index.php?rex-api-call=content_builder&action=get_element_css&framework=bootstrap&elements=starter_cards,starter_media_split">
```

Hinweise:

- Das Backend bindet diese CSS-Ausgabe automatisch über den Backend-Controller ein (z. B. `/redaxo/index.php?...`).
- Im Frontend bleibt die Einbindung bewusst bei der integrativen Seite bzw. beim Projekt.
- Die API-Action unterstützt `framework` (`uikit` oder `bootstrap`) und optional `elements` als CSV-Filter.
- Gesucht werden pro Element in dieser Reihenfolge: `assets/<framework>.css`, `templates/<framework>.css`, `assets/common.css`, `templates/common.css`.

---

## Modul-Integration

Der Content Builder kann auf verschiedene Arten in REDAXO Modulen verwendet werden:

### A. Full Builder API (Multi Element)

Verwende den kompletten Content Builder mit mehreren Elementen und Drag & Drop.

**INPUT:**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

// Wert aus Slice holen
$currentValue = $this->getCurrentSlice()->getValue(1);

// Content Builder erstellen
$contentBuilder = Module::createWithValue(1, $currentValue, [
    'framework' => 'bootstrap', // Framework für Backend-Preview
    'label' => 'Seiteninhalt',
    'description' => 'Fügen Sie Content-Elemente hinzu',
    // Optional: Wrapper-Breite des Editors begrenzen
    // 'wrapper_max_width' => '1140px',
    // Optional: Nur bestimmte Elemente erlauben
    // 'allowed_elements' => ['headline', 'text', 'gallery', 'hero'],
    // Optional: Selbst-Verschachtelung je Elementtyp verhindern
    // 'prevent_self_nesting' => ['columns']
]);

// Editor ausgeben
echo $contentBuilder->getEditor();
?>
```

**OUTPUT:**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

// Wert aus Slice holen
$currentValue = $this->getCurrentSlice()->getValue(1);

// Content Builder erstellen
$contentBuilder = Module::createWithValue(1, $currentValue, [
    'framework' => 'uikit',
    // Optional: Wrapper-Breite des Editors begrenzen
    // 'wrapper_max_width' => '1140px'
]);

// Frontend-Output ausgeben
echo $contentBuilder->renderOutput();
?>
```

### B. Single Element API (Einzelnes Element)

Verwende einzelne Content Builder Elemente direkt in Modulen.

Empfohlen ist die Slot-basierte Schreibweise mit `createByValueId(...)`.

**INPUT:**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

echo Module::createByValueId('gallery', 1, 'bootstrap')->renderInput();
?>
```

**OUTPUT:**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

echo Module::createByValueId('gallery', 1, 'uikit')->renderOutput();
?>
```

**Abwärtskompatibilität:**

Die alte Schreibweise bleibt gültig:

```php
<?php
use FriendsOfREDAXO\Builder\Module;

echo Module::create('gallery', 'REX_VALUE[1]', 'uikit')->renderOutput();
?>
```

### Modul-Optionen (Auszug)

| Option | Typ | Beschreibung |
|--------|-----|--------------|
| `allowed_elements` | array/string | Whitelist der erlaubten Element-Keys |
| `prevent_self_nesting` | array/string | Verhindert Selbst-Verschachtelung für ausgewählte Elementtypen (CSV oder Array) |
| `framework` | string | Preview/Render-Framework (`bootstrap`, `uikit`, `plain`, ...) |
| `label` / `description` | string | UI-Texte für den Editor |

### C. YForm-Feldparameter (value: content_builder)

Beim Einsatz als YForm-Wertfeld `content_builder` können projektweite Standardwerte direkt in der Felddefinition gesetzt werden.

| Parameter | Typ | Beschreibung |
|--------|-----|--------------|
| `default_enable_section` | choice (`'', '1', '0'`) | Globaler Standard für `enable_section` bei neu angelegten Elementen |
| `default_enable_container` | choice (`'', '1', '0'`) | Globaler Standard für `enable_container` bei neu angelegten Elementen |
| `prevent_self_nesting` | choice (multiple) | Verhindert Selbst-Verschachtelung für ausgewählte Elemente |
| `element_defaults_json` | textarea (JSON) | Erweiterte Defaults pro Elementtyp (inkl. Wildcard `*`) |

Beispiel für `element_defaults_json`:

```json
{
    "*": {
        "enable_section": "0",
        "enable_container": "0"
    },
    "cards": {
        "container_width": "uk-container-small"
    }
}
```

Priorität bei der Auflösung:

1. `element_defaults_json` als Basis
2. `default_enable_section` und `default_enable_container` überschreiben `*` gezielt, wenn gesetzt
3. Typspezifische Defaults im JSON (z. B. `cards`) bleiben erhalten

Wichtig:

- Defaults greifen nur für neu angelegte Elemente.
- Bereits gespeicherte Inhalte bleiben unverändert.

### D. Selbst-Verschachtelung steuern

Self-Nesting kann auf drei Ebenen gesetzt werden:

1. Element-Config (`elements/<key>/config.php`)
    - `prevent_self_nesting` (bevorzugt)
    - oder `allow_self_nesting` (invers)
2. Modul-Option `prevent_self_nesting`
3. YForm-Feldoption `prevent_self_nesting`

Priorität:

1. Modul-Option
2. YForm-Feldoption
3. Element-Config

Hinweis:

- Die Regel gilt für Einfügen/Erstellen neuer Slices.
- Bereits vorhandene Inhalte werden nicht automatisch umgebaut.

---

## Media-Manager-Typen

Der Builder nutzt ein zentrales virtuelles Typschema:

- Basistyp in der DB: `content_builder`
- Virtuelles Format: `cb_<preset>__<width>`
- Beispiel: `cb_starter_cards_16_9__800`

### Typ erzeugen

```php
<?php
$type = \FriendsOfREDAXO\Builder\Config\MediaTypeRegistry::buildVirtualType('starter_cards_16_9', 1200);
$url = rex_media_manager::getUrl($type, $file);
```

### Presets registrieren (externes Addon)

```php
rex_extension::register(
    'BUILDER_MEDIA_TYPE_PRESETS',
    static function (rex_extension_point $ep): array {
        $presets = (array) $ep->getSubject();

        $presets['myaddon_card_1_1'] = [
            'ratio' => '1_1',
            'mode' => 'focuspoint',
            'widths' => [200, 400, 600, 800],
            'default_width' => 400,
        ];

        return $presets;
    },
    rex_extension::EARLY
);
```

### Effektreihenfolge

1. `content_builder` (Resize/Crop mit Ratio-Logik)
2. optional `negotiator` (Format-Aushandlung)

`negotiator` muss immer zuletzt kommen.

### Wichtige Hinweise

- Statische Typen wie `card_16_9_w1200` sollen nicht mehr neu eingeführt werden.
- Für ratio-basierte Presets ist das Addon `focuspoint` erforderlich.
- Bei aktivem `media_negotiator` wird der Cache-Key um Format-/Qualitätsparameter ergänzt.

---

## Frameworks & Templates

Das Addon ist **Framework-agnostic**: Es lädt beim Rendern einfach die passende Template-Datei.

### Backend vs. Frontend

Du kannst für das Backend (Preview) und das Frontend unterschiedliche Frameworks nutzen:

1. **Backend Preview** – wird in der YForm-Felddefinition unter „Framework" eingestellt.
   - Default: `bootstrap` (passt zum REDAXO-Backend)
   - Empfehlung: Lass dies auf `bootstrap`, damit die Vorschau sauber aussieht – auch wenn du im Frontend Tailwind nutzt.

2. **Frontend Output** – wird beim Aufruf von `Helper::outputDataset(...)`, `Helper::outputDatasetById(...)` oder `Helper::outputRaw(...)` festgelegt.
   - Volle Freiheit: `bootstrap`, `uikit`, `tailwind`, `plain`, etc.

### Template-Struktur

Das System sucht automatisch nach der Datei `elements/{element}/templates/{framework}.php`:

```text
elements/
└── hero/
    ├── config.php
    └── templates/
        ├── bootstrap.php   ← bei output* Methode mit Framework `bootstrap`
        ├── uikit.php       ← bei output* Methode mit Framework `uikit`
        ├── tailwind.php    ← bei output* Methode mit Framework `tailwind`
        └── plain.php       ← Fallback
```

Gibt es kein passendes Template, wird `plain.php` als Fallback geladen.

Element-spezifische Sprachdateien werden ebenfalls automatisch geladen, wenn vorhanden:

```text
elements/{element}/lang/de_de.lang
elements/{element}/lang/en_gb.lang
```

Fuer konsistente Uebersetzungen in `config.php` empfiehlt sich `Helper::elementTranslator('{element}')`.

### Custom Templates und Frameworks

Eigene Frameworks ergänzt du einfach durch eine neue Template-Datei:

```text
project/elements/mein_element/templates/tailwind.php
```

---

## Frontend-Output API

Die empfohlene Frontend-Ausgabe fuer YForm-Datensaetze laeuft ueber `FriendsOfREDAXO\Builder\Helper`.

### Methoden

| Methode | Beschreibung |
|--------|--------------|
| `Helper::outputDataset($dataset, $fieldName, $framework)` | Rendert aus einem vorhandenen YORM/YForm-Datensatz |
| `Helper::outputDatasetById($tableName, $id, $fieldName, $framework)` | Rendert direkt ueber Tabelle + Datensatz-ID |
| `Helper::outputRaw($rawContent, $framework)` | Rendert direkt aus dem gespeicherten JSON-String |

### Beispiele

```php
<?php
use FriendsOfREDAXO\Builder\Helper;

// Einzeiler mit vorhandenem Datensatz
echo Helper::outputDataset($dataset, 'content_builder', 'bootstrap');

// Einzeiler ueber Tabelle + ID
echo Helper::outputDatasetById('rex_pages', 42, 'content_builder', 'uikit');

// Direkt aus einem JSON-Feld
echo Helper::outputRaw($dataset->getValue('content_builder'), 'plain');
```

YORM mit `where`-Bedingungen:

```php
<?php
use FriendsOfREDAXO\Builder\Helper;

$item = \Project\Model\ContentPage::query()
    ->where('status', 1)
    ->where('clang_id', rex_clang::getCurrentId())
    ->where('slug', 'startseite')
    ->findOne();

if ($item !== null) {
    echo Helper::outputDataset($item, 'content_builder', 'bootstrap');
}
```

---

## Feldtypen-System

Das Addon nutzt ein **Plugin-System für Feldtypen**. Jeder Feldtyp ist eine eigene Klasse im Namespace `FriendsOfREDAXO\Builder\Fields`.

### Architektur

```
lib/fields/
├── FieldInterface.php   # Interface (muss implementiert werden)
├── FieldAbstract.php    # Abstrakte Basisklasse (empfohlen)
├── FieldRegistry.php    # Registry zum Registrieren/Abrufen
└── [FieldName]Field.php               # Konkrete Feldtypen
```

### Verfügbare Feldtypen

| Typ | Klasse | Beschreibung |
|-----|--------|--------------|
| `text` | `TextField` | Einzeiliges Textfeld |
| `textarea` | `TextareaField` | Mehrzeiliges Textfeld |
| `checkbox` | `CheckboxField` | Checkbox (Boolean) |
| `select` | `SelectField` | Einfaches Select-Dropdown |
| `choice` | `ChoiceField` | Erweitertes Select mit Selectpicker, Farben, Icons |
| `cke5` | `Cke5Field` | CKEditor 5 WYSIWYG |
| `be_media` | `BeMediaField` | REDAXO Mediapool Widget |
| `be_link` | `BeLinkField` | REDAXO Linkmap Widget |
| `radio_image` | `RadioImageField` | Radio-Buttons mit Bildern/SVGs |
| `color_swatches` | `ColorSwatchesField` | Farbauswahl mit visuellen Farbfeldern |
| `smart_link` | `SmartLinkField` | Kombiniertes Linkfeld für URL, intern, Media, Mail, Tel und YForm |
| `repeater` | `RepeaterField` | Wiederholbare Feldgruppen |

---

## Feld-Konfiguration

Jeder Feldtyp unterstützt spezifische Konfigurationsoptionen:

### Gemeinsame Optionen (alle Felder)

| Option | Typ | Beschreibung |
|--------|-----|--------------|
| `type` | string | Feldtyp (z.B. `text`, `be_media`) |
| `label` | string | Beschriftung des Feldes |
| `notice` | string | Hilfetext unter dem Feld |
| `default` | mixed | Standardwert |
| `perm` | string/array | Berechtigungen - siehe Permission System unten |

### Feldtyp `smart_link`

Der Feldtyp `smart_link` speichert Linkziele in einem einheitlichen JSON-Format und unterstützt mehrere Linkarten in einem Feld.

| Option | Typ | Beschreibung |
|--------|-----|--------------|
| `multiple` | bool | Erlaubt mehrere Links in einem Feld |
| `types` | array/string | Erlaubte Linktypen als Array oder CSV |
| `yform_table` | string | YForm-Tabellenname (z.B. `rex_kontakt`) für Typ `yform` |
| `yform_field` | string | Spaltenname, der im Select angezeigt wird (z.B. `name`, Fallback: `id`) |
| `yform_profile` | string | Optionales Listenprofil für Pattern-Auflösung; mit Profil: `profilId:id`, ohne Profil: `yform://table_alias/id` |
| `notice` | string | Hilfetext unter dem Feld |

Erlaubte Typwerte für `types`:

- `auto`
- `url`
- `intern`
- `media`
- `mail`
- `tel`
- `yform`

Hinweise zu YForm:

- `yform_table` muss der echte Tabellenname sein (inkl. Präfix, z.B. `rex_kontakt`).
- `yform_field` ist die Spalte für die Anzeige im Dropdown.
- Wenn `yform_field` nicht existiert, wird automatisch `id` verwendet.
- Die Optionen werden über `SELECT id, <yform_field> ...` geladen.
- Ohne `yform_profile` wird `yform://table_alias/id` gespeichert (Alias = normalisierter Tabellenname ohne `rex_`).
- Mit `yform_profile` wird `profilId:id` gespeichert (z.B. `events:42`) und damit das URL-Pattern des Profils genutzt.
- Die YForm-Auswahl ist relevant, wenn `types` den Wert `yform` oder `auto` enthält.

Beispiel:

```php
'link' => [
    'type' => 'smart_link',
    'label' => 'Link',
    'multiple' => false,
    'types' => 'auto,url,intern,media,mail,tel,yform',
    'yform_table' => 'rex_kontakt',
    'yform_field' => 'name',
    'yform_profile' => 'events',
]
```

Ausgabe im Template (Outputfilter-Äquivalent):

`smart_link` arbeitet nicht mit einem separaten String-Outputfilter wie bei mform, sondern mit PHP-Helpern:

- `FriendsOfREDAXO\Builder\SmartLinkView::resolveSingle(...)` für die direkte Ausgabe eines Links (inkl. finaler `href`)
- `FriendsOfREDAXO\Builder\SmartLink::normalize(...)` + `FriendsOfREDAXO\Builder\SmartLink::buildHref(...)` für eigene Render-Logik

Beispiel Single-Link:

```php
<?php
$resolved = \FriendsOfREDAXO\Builder\SmartLinkView::resolveSingle($data['link'] ?? null, 'Mehr erfahren');
if (is_array($resolved)) {
    $target = $resolved['is_external'] ? ' target="_blank" rel="noopener"' : '';
    echo '<a href="' . rex_escape($resolved['href']) . '"' . $target . '>' . rex_escape($resolved['label']) . '</a>';
}
```

Beispiel Multiple-Link:

```php
<?php
$items = \FriendsOfREDAXO\Builder\SmartLink::normalize($data['links'] ?? null, true);
foreach ($items as $item) {
    $href = \FriendsOfREDAXO\Builder\SmartLink::buildHref($item);
    if ($href === '') {
        continue;
    }

    $label = \FriendsOfREDAXO\Builder\SmartLink::linkLabel($item);
    if ($label === '') {
        $label = $href;
    }

    echo '<a href="' . rex_escape($href) . '">' . rex_escape($label) . '</a>';
}
```

Hinweis zu `yform`-Werten:

- Mit Profil: `profilId:datensatzId` (z. B. `events:42`).
- Ohne Profil: `yform://table_alias/datensatzId` (z. B. `yform://kontakt/42`).
- Die finale URL wird über das konfigurierte Listen-Profil (Pattern/URL-Profil) aufgelöst.
- Ohne auflösbares Profil fällt die URL auf `?id=<datensatzId>` zurück.

Auflösung im Detail (YForm-Link):

1. Eingabe aus dem SmartLink-Feld:
    - mit `yform_profile`: `profilId:id`
    - ohne `yform_profile`: `yform://table_alias/id`
2. Profil-Ermittlung:
    - bei `profilId:id`: direkt über die Profil-ID
    - bei `yform://table_alias/id`: über das erste Listenprofil mit passender Tabelle (Alias ohne `rex_`)
3. URL-Generierung in dieser Reihenfolge:
    - `virtual_urls` (wenn im Profil aktiviert)
    - Url-Addon-Profil (`url_profile`)
    - `url_pattern` (mindestens `{id}`)
    - Fallback `?id=<id>`

Beispiel-Auflösung:

- Gespeicherter Wert: `events:42`
- Profil `events` hat `url_profile = event_namespace`
- Ergebnis: URL über `rex_getUrl('', '', ['event_namespace' => 42])`

- Gespeicherter Wert: `yform://kontakt/42`
- Gefundenes Profil zur Tabelle `rex_kontakt` hat `url_pattern = /kontakt/{id}`
- Ergebnis: `/kontakt/42`

### Permission System

Mit dem `perm` Key kannst du einzelne Felder nur für bestimmte Benutzerrollen sichtbar machen:

#### Format

**Einzelne Rolle:**
```php
'perm' => 'admin'      // Nur für Admins
'perm' => 'editor'     // Nur für Redakteure
```

**Mehrere Rollen (pipe-getrennt):**
```php
'perm' => 'editor|reviewer|admin'  // Eine dieser Rollen genügt
```

**Array-Format:**
```php
'perm' => ['editor', 'reviewer', 'admin']
```

#### Verfügbare Rollen

```php
'admin'      // Admin-Benutzer (isAdmin() = true)
'editor'     // Redakteur
'reviewer'   // Freigabekontrolle
'contributor' // Mitarbeiter
'power'      // Power User
// Plus alle benutzerdefinierten Rollen aus deinem REDAXO-System
```

#### Praktische Beispiele

```php
// Kontaktformular mit Admin-Feld
'fields' => [
    'form_title' => [
        'type' => 'text',
        'label' => 'Formular-Titel'
    ],
    
    // Nur Admins sehen diese Feld
    'sql_options' => [
        'type' => 'textarea',
        'label' => 'SQL-Optionen',
        'notice' => 'Nur für Administratoren sichtbar',
        'perm' => 'admin'
    ],
    
    // Nur Power-User und Admins
    'advanced_settings' => [
        'type' => 'repeater',
        'label' => 'Erweiterte Einstellungen',
        'perm' => 'power|admin'
    ]
]
```

#### Sicherheit

- ✅ Permission-Prüfung läuft **serverseitig** (nicht zu umgehen)
- ✅ Funktioniert in **allen Feldtypen**
- ✅ Funktioniert im **Backend-Editor** und **Frontend-Formular**
- ✅ Nicht berechtigt = gar nicht zu sehen

- ✅ Nicht berechtigt = gar nicht zu sehen

### attributes – beliebige HTML-Attribute

Jedes Feld akzeptiert einen optionalen `attributes`-Key mit einem assoziativen Array beliebiger HTML-Attribute.
Der Builder escapt alle Schlüssel und Werte sicher.

Unterstützt von: `text`, `textarea`, `checkbox`, `select`, `choice`

```php
'beschreibung' => [
    'type'       => 'textarea',
    'label'      => 'Beschreibung',
    'attributes' => [
        'data-autoresize'  => 'true',
        'maxlength'        => '500',
        'style'            => 'background:#fffbe6;',
        'aria-describedby' => 'hint-beschreibung',
    ],
]
```

Gesperrte Attribute (vom Feldtyp selbst gesteuert, werden ignoriert):
`name`, `type`, `value`, `checked`, `selected`, `id`, `rows`

---

### text

```php
'title' => [
    'type'        => 'text',
    'label'       => 'Titel',
    'placeholder' => 'Titel eingeben...',
    'notice'      => 'Max. 100 Zeichen',
    // Optionale HTML-Attribute:
    'attributes'  => [
        'maxlength'    => '100',
        'autocomplete' => 'off',
    ],
]
```

### textarea

```php
'description' => [
    'type'       => 'textarea',
    'label'      => 'Beschreibung',
    'rows'       => 5,
    // Optionale HTML-Attribute:
    'attributes' => [
        'data-autoresize' => 'true',
        'maxlength'       => '500',
    ],
]
```

### checkbox

```php
'show_title' => [
    'type'       => 'checkbox',
    'label'      => 'Titel anzeigen',
    'default'    => 1,
    // Optionale HTML-Attribute:
    'attributes' => [
        'data-toggle' => 'collapse',
        'data-target' => '#title-section',
    ],
]
```

### select

```php
'size' => [
    'type'    => 'select',
    'label'   => 'Größe',
    'options' => [
        'small'  => 'Klein',
        'medium' => 'Mittel',
        'large'  => 'Groß',
    ],
    // Optionale HTML-Attribute:
    'attributes' => [
        'data-placeholder' => 'Bitte wählen…',
    ],
]
```

### choice (erweitertes Select)

```php
'color' => [
    'type' => 'choice',
    'label' => 'Farbe',
    'choices' => [
        'primary' => 'Primär',
        'secondary' => 'Sekundär',
        'muted' => 'Gedämpft'
    ],
    'selectpicker' => true,  // Bootstrap Selectpicker
    'choice_colors' => [     // Farbvorschau im Dropdown
        'primary' => '#1e87f0',
        'secondary' => '#222222',
        'muted' => '#999999'
    ],
    'choice_icons' => [      // Icons im Dropdown
        'left' => '<svg>...</svg>',
        'center' => '<svg>...</svg>'
    ],
    'default' => 'primary'
]
```

Optionale HTML-Attribute:

```php
'color' => [
    'type'       => 'choice',
    'label'      => 'Farbe',
    'choices'    => ['primary' => 'Primär'],
    'attributes' => [
        'data-container' => 'body',   // Selectpicker-Option
        'aria-label'     => 'Farbe wählen',
    ],
]
```

### cke5

```php
'content' => [
    'type' => 'cke5',
    'label' => 'Inhalt',
    'profile' => 'default',  // CKE5 Profil
    'rows' => 10
]
```

### be_media

```php
'image' => [
    'type' => 'be_media',
    'label' => 'Bild',
    'allowed_types' => 'jpg,jpeg,png,gif,webp,svg'  // oder als Array
]
```

### be_link

```php
'link' => [
    'type' => 'be_link',
    'label' => 'Link',
    'category' => 1  // Start-Kategorie in Linkmap
]
```

### radio_image

```php
'layout' => [
    'type' => 'radio_image',
    'label' => 'Layout',
    'options' => [
        'left' => [
            'label' => 'Bild links',
            'image' => 'data:image/svg+xml;base64,...'
        ],
        'right' => [
            'label' => 'Bild rechts',
            'image' => 'data:image/svg+xml;base64,...'
        ]
    ],
    'default' => 'left'
]
```

### color_swatches

```php
'background' => [
    'type' => 'color_swatches',
    'label' => 'Hintergrund',
    'options' => [
        'white' => ['color' => '#ffffff', 'label' => 'Weiß'],
        'light' => ['color' => '#f8f8f8', 'label' => 'Hell'],
        'dark' => ['color' => '#222222', 'label' => 'Dunkel'],
        'transparent' => ['color' => 'transparent', 'label' => 'Transparent']
    ],
    'default' => 'white'
]
```

### repeater

```php
'items' => [
    'type' => 'repeater',
    'label' => 'Elemente',
    'add_label' => 'Element hinzufügen',
    'view' => 'list',        // 'list' oder 'grid'
    'grid_columns' => 3,     // Bei view: 'grid'
    'fields' => [
        'title' => ['type' => 'text', 'label' => 'Titel'],
        'image' => ['type' => 'be_media', 'label' => 'Bild'],
        'description' => ['type' => 'textarea', 'label' => 'Beschreibung']
    ],
    // Optional: Zusätzliche Felder in Modal auslagern
    'item_modal' => [
        'label' => 'Erweiterte Optionen',
        'icon' => 'fa-cog',
        'fields' => ['description'],  // Diese Felder im Modal
        // Optional: Modal nach einem bestimmten Feld positionieren
        'trigger_after' => 'title'  // Button wird nach 'title'-Feld gezeigt
        // Ohne trigger_after: Button erscheint ganz am Anfang
    ],
    // Optional: Weitere Modals mit trigger_after
    'settings_modal' => [
        'label' => 'Einstellungen',
        'icon' => 'fa-sliders',
        'trigger_after' => 'image',  // Button nach 'image'-Feld
        'fields' => ['alt_text', 'caption']
    ]
]
```

**Modal-Buttons Positionierung:**
- `item_modal` **ohne** `trigger_after`: Button erscheint ganz am Anfang (nach Move-Buttons)
- `item_modal` **mit** `trigger_after`: Button erscheint nach dem angegebenen Feld
- Beliebig viele Modals mit `trigger_after` möglich (z.B. `media_modal`, `settings_modal`, etc.)
- Alle Modal-Namen müssen mit `_modal` enden

---

## Eigene Elemente erstellen

### Verzeichnisstruktur

Elemente können an drei Orten definiert werden (Priorität von oben nach unten):

1. **project Addon**: `redaxo/src/addons/project/elements/`
2. **data Ordner**: `redaxo/data/addons/builder/elements/`
3. **Addon selbst**: `redaxo/src/addons/builder/elements/`

### Minimales Element

```
elements/
└── quote/
    ├── config.php
    └── templates/
        ├── bootstrap.php
        ├── uikit.php
        └── plain.php
```

### config.php

```php
<?php
return [
    // Pflichtfelder
    'label' => 'Zitat',
    'icon' => 'fa-quote-left',
    
    // Optionale Metadaten
    'description' => 'Hervorgehobenes Zitat mit Autor',
    'category' => 'content',  // Für Gruppierung
    
    // Feldkonfiguration
    'fields' => [
        'quote' => [
            'type' => 'textarea',
            'label' => 'Zitat-Text',
            'rows' => 4
        ],
        'author' => [
            'type' => 'text',
            'label' => 'Autor'
        ],
        'source' => [
            'type' => 'text',
            'label' => 'Quelle',
            'notice' => 'Optional: Buch, Website, etc.'
        ]
    ]
];
```

### Template (templates/bootstrap.php)

```php
<?php
/**
 * @var array $elementData Enthält alle Feldwerte
 */
$quote = $elementData['quote'] ?? '';
$author = $elementData['author'] ?? '';
$source = $elementData['source'] ?? '';

if (empty($quote)) {
    return;
}
?>
<blockquote class="blockquote">
    <p class="mb-0"><?= nl2br(rex_escape($quote)) ?></p>
    <?php if ($author || $source): ?>
        <footer class="blockquote-footer">
            <?= rex_escape($author) ?>
            <?php if ($source): ?>
                <cite title="<?= rex_escape($source) ?>"><?= rex_escape($source) ?></cite>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</blockquote>
```

### Template (templates/uikit.php)

```php
<?php
$quote = $elementData['quote'] ?? '';
$author = $elementData['author'] ?? '';
$source = $elementData['source'] ?? '';

if (empty($quote)) {
    return;
}
?>
<blockquote class="uk-margin">
    <p class="uk-margin-small-bottom"><?= nl2br(rex_escape($quote)) ?></p>
    <?php if ($author): ?>
        <footer>
            — <?= rex_escape($author) ?>
            <?php if ($source): ?>
                <cite><?= rex_escape($source) ?></cite>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</blockquote>
```

### Komplexes Element mit Tabs

```php
<?php
return [
    'label' => 'Hero Banner',
    'icon' => 'fa-image',
    'description' => 'Großes Banner mit Bild, Text und Call-to-Action',
    
    // Felder in Tabs gruppieren
    'field_groups' => [
        'content' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['headline', 'subline', 'text']
        ],
        'media' => [
            'label' => 'Medien',
            'icon' => 'fa-image',
            'fields' => ['image', 'video']
        ],
        'cta' => [
            'label' => 'Call-to-Action',
            'icon' => 'fa-mouse-pointer',
            'fields' => ['button_text', 'button_link', 'button_style']
        ],
        'design' => [
            'label' => 'Design',
            'icon' => 'fa-paint-brush',
            'fields' => ['overlay_color', 'text_color', 'height']
        ]
    ],
    
    // Alle Felder definieren
    'fields' => [
        'headline' => [
            'type' => 'text',
            'label' => 'Überschrift'
        ],
        'subline' => [
            'type' => 'text',
            'label' => 'Unterzeile'
        ],
        'text' => [
            'type' => 'cke5',
            'label' => 'Text',
            'profile' => 'light'
        ],
        'image' => [
            'type' => 'be_media',
            'label' => 'Hintergrundbild',
            'allowed_types' => 'jpg,jpeg,png,webp'
        ],
        'video' => [
            'type' => 'be_media',
            'label' => 'Hintergrundvideo (optional)',
            'allowed_types' => 'mp4,webm'
        ],
        'button_text' => [
            'type' => 'text',
            'label' => 'Button Text'
        ],
        'button_link' => [
            'type' => 'be_link',
            'label' => 'Button Link'
        ],
        'button_style' => [
            'type' => 'choice',
            'label' => 'Button Style',
            'choices' => [
                'primary' => 'Primär',
                'secondary' => 'Sekundär',
                'outline' => 'Outline'
            ],
            'default' => 'primary'
        ],
        'overlay_color' => [
            'type' => 'color_swatches',
            'label' => 'Overlay',
            'options' => [
                'none' => ['color' => 'transparent', 'label' => 'Kein Overlay'],
                'dark' => ['color' => 'rgba(0,0,0,0.5)', 'label' => 'Dunkel'],
                'light' => ['color' => 'rgba(255,255,255,0.5)', 'label' => 'Hell']
            ],
            'default' => 'dark'
        ],
        'text_color' => [
            'type' => 'choice',
            'label' => 'Textfarbe',
            'choices' => [
                'light' => 'Hell',
                'dark' => 'Dunkel'
            ],
            'default' => 'light'
        ],
        'height' => [
            'type' => 'choice',
            'label' => 'Höhe',
            'choices' => [
                'small' => 'Klein (300px)',
                'medium' => 'Mittel (500px)',
                'large' => 'Groß (700px)',
                'fullscreen' => 'Vollbild'
            ],
            'default' => 'medium'
        ]
    ]
];
```

### Element mit Settings-Modal

Für Felder die selten gebraucht werden, können diese in ein Modal ausgelagert werden:

```php
<?php
return [
    'label' => 'Karte',
    'icon' => 'fa-square',
    
    // Settings-Modal für erweiterte Optionen
    'settings_modal' => [
        'label' => 'Erweiterte Einstellungen',
        'icon' => 'fa-cog',
        'fields' => ['css_class', 'anchor_id', 'animation']
    ],
    
    'fields' => [
        // Haupt-Felder (immer sichtbar)
        'title' => ['type' => 'text', 'label' => 'Titel'],
        'content' => ['type' => 'cke5', 'label' => 'Inhalt'],
        'image' => ['type' => 'be_media', 'label' => 'Bild'],
        
        // Diese Felder erscheinen nur im Modal
        'css_class' => ['type' => 'text', 'label' => 'CSS-Klasse'],
        'anchor_id' => ['type' => 'text', 'label' => 'Anker-ID'],
        'animation' => [
            'type' => 'choice',
            'label' => 'Animation',
            'choices' => [
                '' => 'Keine',
                'fade' => 'Einblenden',
                'slide-up' => 'Nach oben',
                'slide-left' => 'Von links'
            ]
        ]
    ]
];
```

### Conditional Felder mit `visible_if`

Felder koennen ohne eigenes JavaScript per Konfiguration ein- und ausgeblendet werden.

```php
'fields' => [
    'enable_section' => [
        'type' => 'checkbox',
        'label' => 'Sektion aktivieren',
    ],
    'layout_variant' => [
        'type' => 'choice',
        'label' => 'Layout',
        'choices' => [
            'cards' => 'Cards',
            'list' => 'Liste',
        ],
    ],
    'cards_gap' => [
        'type' => 'choice',
        'label' => 'Karten-Abstand',
        'choices' => [
            'small' => 'Klein',
            'medium' => 'Mittel',
            'large' => 'Gross',
        ],
        'visible_if' => [
            'enable_section' => '1',
            'layout_variant' => 'cards',
        ],
    ],
]
```

Regeln:

- `visible_if` ist ein Mapping aus `feldname => erwarteter_wert`.
- Mehrere Bedingungen werden als UND ausgewertet.
- Erwartete Werte koennen `string` oder `array` sein.

Werte je Quellfeld-Typ:

- `checkbox`: `1` (aktiv) oder `0` (inaktiv)
- `radio`: `value` des ausgewaehlten Eintrags
- `select` (single): ausgewaehlter `value`
- `select` (multiple): Array der ausgewaehlten Werte

Mehrfachwerte-Beispiel:

```php
'visible_if' => [
    'image_mode' => ['cover', 'contain'],
]
```

Scope:

- Funktioniert in YForm-Editoren und Modul-Editoren (`Module::createWithValue()`, `Module::createByValueId()`), da dieselbe Render-/JS-Logik genutzt wird.

### WICHTIG: Demo-Elemente

Das Verhalten ist über den Modus steuerbar:

- `merge`: externe Elemente aus registrierten Pfaden + mitgelieferte Elemente
- `replace`: externe Elemente aus registrierten Pfaden, mitgelieferte Elemente ausgeblendet

Wichtig zur Priorität:

- Wenn mindestens ein registriertes Provider-Addon `replace` signalisiert, gilt effektiv `replace`.
- `merge` ist nur effektiv, wenn kein Provider `replace` signalisiert.

Ausnahmen im Replace-Modus:

- Über die Addon-Settings `replace_keep_core_elements` können einzelne mitgelieferte Elemente weiterhin freigegeben werden.
- Diese Ausnahmen bleiben auch sichtbar, wenn das eigene Addon in der AddOn-Quellauswahl ausgeblendet ist.

Bei Namensgleichheit gewinnt im `merge`-Modus immer das eigene Element.

### Registrierung via Extension Point (Alternative)

In `boot.php` eines beliebigen AddOns:

```php
rex_extension::register('BUILDER_ELEMENT_PATHS', function($ep) {
    $paths = $ep->getSubject();
    $paths[] = rex_addon::get('mein_addon')->getPath('content_elements/');
    return $paths;
});

// Optional: Ladeverhalten steuern
rex_extension::register('BUILDER_ELEMENT_MODE', static function(): string {
    return 'merge'; // 'merge' oder 'replace'
});
```

---

## Eigene Feldtypen erstellen

### Interface implementieren

Jeder Feldtyp muss das `FieldInterface` implementieren:

```php
<?php
namespace FriendsOfREDAXO\Builder\Fields;

interface FieldInterface
{
    /**
     * Gibt den Feldtyp-Namen zurück
     */
    public static function getType(): string;

    /**
     * Rendert das Formularfeld
     */
    public function render(string $fieldName, array $fieldConfig, $value, array $sliceData = []): void;

    /**
     * Verarbeitet den Wert (optional)
     */
    public function processValue($value, array $fieldConfig);
}
```

### Abstrakte Basisklasse nutzen (empfohlen)

Die `FieldAbstract` Klasse bietet hilfreiche Methoden:

| Methode | Beschreibung |
|---------|--------------|
| `openFormGroup()` | Öffnet `<div class="form-group">` |
| `closeFormGroup($notice)` | Schließt Form-Group, optional mit Notice |
| `renderLabel($label)` | Rendert Label |
| `generateId($prefix)` | Generiert eindeutige ID |
| `getNestedValue($key, $data)` | Holt Wert aus verschachteltem Array |
| `getNextMediaCounter()` | Globaler Counter für Media-Widgets |
| `getNextLinkCounter()` | Counter für Link-Widgets |

### Beispiel: Einfaches Feld

```php
<?php
namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * E-Mail-Eingabefeld mit Validierung
 */
class EmailField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'email';
    }

    public function render(string $fieldName, array $fieldConfig, $value, array $sliceData = []): void
    {
        $label = $fieldConfig['label'] ?? $fieldName;
        $placeholder = $fieldConfig['placeholder'] ?? 'email@beispiel.de';
        $notice = $fieldConfig['notice'] ?? null;

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<input type="email" class="form-control" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'value="' . rex_escape($value) . '" ';
        echo 'placeholder="' . rex_escape($placeholder) . '">';

        $this->closeFormGroup($notice);
    }

    public function processValue($value, array $fieldConfig)
    {
        // E-Mail validieren/bereinigen
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }
}
```

### Beispiel: Komplexes Feld (Icon-Picker)

```php
<?php
namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Icon-Auswahl mit Vorschau
 */
class IconPickerField extends FieldAbstract
{
    private static array $icons = [
        'fa-home' => 'Home',
        'fa-user' => 'Benutzer',
        'fa-envelope' => 'E-Mail',
        'fa-phone' => 'Telefon',
        'fa-map-marker' => 'Standort',
        'fa-calendar' => 'Kalender',
        'fa-clock-o' => 'Uhr',
        'fa-star' => 'Stern',
        'fa-heart' => 'Herz',
        'fa-check' => 'Haken'
    ];

    public static function getType(): string
    {
        return 'icon_picker';
    }

    public function render(string $fieldName, array $fieldConfig, $value, array $sliceData = []): void
    {
        $label = $fieldConfig['label'] ?? 'Icon';
        $icons = $fieldConfig['icons'] ?? self::$icons;
        $notice = $fieldConfig['notice'] ?? null;

        $groupId = $this->generateId('icon');

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<div class="icon-picker-group">';
        
        foreach ($icons as $iconClass => $iconLabel) {
            $checked = ($value === $iconClass) ? ' checked' : '';
            $inputId = $groupId . '_' . md5($iconClass);
            $activeClass = $checked ? ' active' : '';

            echo '<div class="icon-picker-item' . $activeClass . '">';
            echo '<input type="radio" name="' . rex_escape($fieldName) . '" ';
            echo 'id="' . $inputId . '" ';
            echo 'value="' . rex_escape($iconClass) . '"' . $checked . '>';
            echo '<label for="' . $inputId . '" title="' . rex_escape($iconLabel) . '">';
            echo '<i class="fa ' . rex_escape($iconClass) . ' fa-2x"></i>';
            echo '</label>';
            echo '</div>';
        }
        
        echo '</div>';

        // CSS für Icon-Picker
        echo '<style>
            .icon-picker-group { display: flex; flex-wrap: wrap; gap: 8px; }
            .icon-picker-item { position: relative; }
            .icon-picker-item input { position: absolute; opacity: 0; }
            .icon-picker-item label { 
                display: flex; align-items: center; justify-content: center;
                width: 50px; height: 50px; border: 2px solid #ddd;
                border-radius: 4px; cursor: pointer; transition: all 0.2s;
            }
            .icon-picker-item label:hover { border-color: #999; }
            .icon-picker-item.active label,
            .icon-picker-item input:checked + label { 
                border-color: #3498db; background: #ecf5fc; 
            }
        </style>';

        $this->closeFormGroup($notice);
    }
}
```

### Feldtyp registrieren

#### Option 1: Direkt registrieren

```php
// In boot.php deines Addons
use FriendsOfREDAXO\Builder\Fields\FieldRegistry;

if (rex_addon::get('builder')->isAvailable()) {
    FieldRegistry::register(new EmailField());
    FieldRegistry::register(new IconPickerField());
}
```

#### Option 2: Per Extension Point

```php
rex_extension::register('BUILDER_FIELDS', function(rex_extension_point $ep) {
    $fields = $ep->getSubject();
    
    // Neues Feld hinzufügen
    $fields['email'] = new EmailField();
    $fields['icon_picker'] = new IconPickerField();
    
    // Bestehendes Feld überschreiben
    $fields['text'] = new MyEnhancedTextField();
    
    return $fields;
});
```

### Bestehendes Feld erweitern

```php
<?php
namespace MyAddon\Fields;

use FriendsOfREDAXO\Builder\Fields\BeMediaField;

/**
 * Erweitertes Media-Feld mit Drag & Drop
 */
class EnhancedMediaField extends BeMediaField
{
    public static function getType(): string
    {
        return 'be_media';  // Überschreibt das Standard be_media
    }

    public function render(string $fieldName, array $fieldConfig, $value, array $sliceData = []): void
    {
        // Erst Standard-Rendering
        parent::render($fieldName, $fieldConfig, $value, $sliceData);
        
        // Dann eigene Erweiterungen
        echo '<div class="dropzone" data-field="' . rex_escape($fieldName) . '">';
        echo 'Datei hierher ziehen';
        echo '</div>';
    }
}
```

---

## Extension Points

### BUILDER_FIELDS

Wird aufgerufen wenn die Feldtypen initialisiert werden.

```php
rex_extension::register('BUILDER_FIELDS', function(rex_extension_point $ep) {
    $fields = $ep->getSubject();
    // $fields ist ein Array: ['type' => FieldInstance, ...]
    return $fields;
});
```

### BUILDER_ELEMENTS

Wird aufgerufen beim Laden der verfügbaren Elemente.

```php
rex_extension::register('BUILDER_ELEMENTS', function(rex_extension_point $ep) {
    $elements = $ep->getSubject();
    
    // Element hinzufügen
    $elements['my_element'] = [
        'label' => 'Mein Element',
        'icon' => 'fa-star',
        '_path' => rex_path::addon('my_addon', 'elements/my_element')
    ];
    
    // Element entfernen
    unset($elements['section']);
    
    return $elements;
});
```

### BUILDER_ELEMENT_PATHS

Wird aufgerufen um zusätzliche Pfade für Elemente zu registrieren.

```php
rex_extension::register('BUILDER_ELEMENT_PATHS', function($ep) {
    $paths = $ep->getSubject();
    $paths['my_addon'] = rex_path::addon('my_addon', 'elements/');

    // Theme-Ordner: hier setzt du den Key 'theme'
    $paths['theme'] = rex_path::base('theme/private/builder/theme_elements');

    return $paths;
});
```

Praxis-Hinweis: Der Key wird in `$paths['theme'] = ...` gesetzt. Dieser Key taucht spaeter in den Builder-Einstellungen als Quelle auf.

### BUILDER_BUNDLED_ELEMENTS

Registriert, welche Elemente als "bundled" (Haupt-Addon) gelten. Standard: Core + Starter-Elemente.

```php
rex_extension::register('BUILDER_BUNDLED_ELEMENTS', function($ep) {
    $bundled = $ep->getSubject() ?? [];
    $bundled[] = 'my_custom_element'; // Diese Ele wird Teil des bundles
    return $bundled;
});
```

### BUILDER_ELEMENT_MODE

Steuert, ob mitgelieferte Elemente zusätzlich zu externen Pfaden geladen werden.

- `replace`: Registrierte externe Pfade, mitgelieferte Elemente aus
- `merge`: Registrierte externe Pfade plus mitgelieferte Elemente

Priorität:

- Sobald mindestens ein registrierter Provider `replace` meldet, ist der effektive Modus `replace`.

```php
rex_extension::register('BUILDER_ELEMENT_MODE', static function(): string {
    return 'merge';
});
```

### BUILDER_FRAMEWORK_OPTIONS ⭐ *NEU (v3.1.0)*

Framework-agnostische Optionen für Hintergründe, Padding, Container etc.
Ermöglicht es, UIkit/Bootstrap/Plain/Custom-Frameworks zu unterstützen.

**Parameter:**
- `framework`: 'uikit', 'bootstrap', 'plain', oder custom
- `option_type`: 'backgrounds', 'paddings', 'containers', 'background_colors', 'css_prefix'

```php
rex_extension::register('BUILDER_FRAMEWORK_OPTIONS', function($ep) {
    $framework = $ep->getParam('framework');
    $optionType = $ep->getParam('option_type');
    
    if ('bootstrap' === $framework && 'backgrounds' === $optionType) {
        return [
            '' => 'Keine',
            'bg-primary' => 'Primary (Blau)',
            'bg-secondary' => 'Secondary (Grau)',
            'bg-danger' => 'Danger (Rot)',
        ];
    }
    
    return $ep->getSubject();
});
```

**Verfügbare Option-Types:**
- `backgrounds` → Array [class => label]
- `background_colors` → Array [class => ['color' => hex, 'label' => str]]
- `paddings` → Array [class => label]
- `containers` → Array [class => label]
- `css_prefix` → String (z.B. 'uk-', 'bs-')

### BUILDER_EDITOR_PROFILES ⭐ *NEU (v3.1.0)*

Editor-Profile pro Element-Feld. Erlaubt verschiedene TinyMCE/CKE5-Profile.

**Parameter:**
- `element`: Element-Key (z.B. 'starter_text')
- `field`: Feld-Name (z.B. 'text')

```php
rex_extension::register('BUILDER_EDITOR_PROFILES', function($ep) {
    $element = $ep->getParam('element');
    $field = $ep->getParam('field');
    
    if ('starter_callout' === $element && 'description' === $field) {
        return 'minimal'; // Nutze 'minimal' Profil statt default
    }
    
    return $ep->getSubject() ?? 'default';
});
```

### Theme-Provider Hooks ⭐ *NEU (v3.2.0)*

Diese Hooks entkoppeln Theme-Integration vollständig vom Content Builder. Jeder Provider (z. B. UIkit, Bootstrap, Design-System-Addon) kann sie registrieren.

#### BUILDER_THEME_PROVIDER_AVAILABLE

Signalisiert, dass ein Theme-Provider aktiv ist.

```php
rex_extension::register('BUILDER_THEME_PROVIDER_AVAILABLE', static function(rex_extension_point $ep): bool {
    return true;
});
```

#### BUILDER_THEME_CHOICES

Liefert auswählbare Themes als Array `theme_key => label`.

```php
rex_extension::register('BUILDER_THEME_CHOICES', static function(rex_extension_point $ep): array {
    return [
        'default' => 'Default',
        'dark' => 'Dark',
    ];
});
```

#### BUILDER_THEME_CONTEXT_RESET

Setzt den Theme-Kontext zurück, bevor ein neues Theme angewendet wird.

```php
rex_extension::register('BUILDER_THEME_CONTEXT_RESET', static function(rex_extension_point $ep) {
    // Provider-interner Reset
    return $ep->getSubject();
});
```

#### BUILDER_THEME_CONTEXT_SET

Setzt den Theme-Kontext auf den übergebenen Theme-Key (`$ep->getSubject()`).

```php
rex_extension::register('BUILDER_THEME_CONTEXT_SET', static function(rex_extension_point $ep) {
    $theme = (string) $ep->getSubject();
    if ($theme !== '') {
        // Provider-interne Aktivierung
    }
    return $ep->getSubject();
});
```

#### BUILDER_THEME_BACKGROUND_OPTIONS

Liefert Theme-Hintergrundoptionen (für `section_bg`, Color-Preview etc.).

Parameter:

- `framework` (string), z. B. `uikit`

```php
rex_extension::register('BUILDER_THEME_BACKGROUND_OPTIONS', static function(rex_extension_point $ep): array {
    if ((string) $ep->getParam('framework', 'uikit') !== 'uikit') {
        return (array) $ep->getSubject();
    }

    return [
        'uk-background-default' => ['label' => 'Default', 'color' => '#ffffff'],
        'uk-background-primary' => ['label' => 'Primary', 'color' => '#1e87f0'],
    ];
});
```

#### BUILDER_THEME_TEXT_COLOR_OPTIONS

Liefert Textfarben für Elemente wie Divider.

Parameter:

- `framework` (string), z. B. `uikit`

```php
rex_extension::register('BUILDER_THEME_TEXT_COLOR_OPTIONS', static function(rex_extension_point $ep): array {
    if ((string) $ep->getParam('framework', 'uikit') !== 'uikit') {
        return (array) $ep->getSubject();
    }

    return [
        'uk-text-primary' => 'Primary',
        'uk-text-secondary' => 'Secondary',
    ];
});
```

#### BUILDER_FRAMEWORK_NORMALIZE

Erlaubt Provider-spezifische Framework-Normalisierung (z. B. `bootstrap` => `uikit`).

```php
rex_extension::register('BUILDER_FRAMEWORK_NORMALIZE', static function(rex_extension_point $ep): string {
    $framework = trim((string) $ep->getSubject());
    if ($framework === 'bootstrap') {
        return 'uikit';
    }

    return $framework;
});
```

---

## Helper-Klassen ⭐ *ERWEITERT (v3.1.0)*

### FrameworkConfig

```php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

// Hintergrund-Optionen für ein Framework
$options = FrameworkConfig::getBackgroundChoices('bootstrap');
// => ['', 'bg-white', 'bg-light', ...]

// Padding-Optionen
$paddings = FrameworkConfig::getPaddingChoices('uikit');

// Container-Optionen
$containers = FrameworkConfig::getContainerChoices('plain');

// Farben für Color-Swatches
$colors = FrameworkConfig::getBackgroundColors('bootstrap');
// => ['' => ['color' => 'transparent', ...], ...]

// CSS-Präfix für Framework
$prefix = FrameworkConfig::getCssPrefix('uikit');  // 'uk-'
$prefix = FrameworkConfig::getCssPrefix('bootstrap');  // 'bs-'
```

### EditorConfig

```php
use FriendsOfREDAXO\Builder\Config\EditorConfig;

// Editor-Profil für Element abrufen
$profile = EditorConfig::getEditorProfile('starter_text', 'text');
// => 'default'

// Editor-Typ für Profil ermitteln
$editorType = EditorConfig::getEditorTypeForProfile('default');
// => 'tinymce' oder 'ckeditor5'
```

### ElementRegistry

```php
use FriendsOfREDAXO\Builder\Config\ElementRegistry;

// Alle bundled Elements
$bundled = ElementRegistry::getBundledElements();
// => ['section', 'headline', 'starter_text', ...]

// Prüfen ob bundled
if (ElementRegistry::isBundledElement('starter_text')) {
    // ...
}

// Alle Element-Pfade
$paths = ElementRegistry::getElementPaths();
// => ['core' => '/path/to/elements', 'klxm_elements' => '/path/to/klxm/elements']

// Elemente aus Pfad
$elements = ElementRegistry::getElementsFromPath('klxm_elements');
// => ['cards', 'hero_banner', ...]

// Alle Elemente (bundled + extern)
$all = ElementRegistry::getAllElements();

// Element-Config laden
$config = ElementRegistry::getElementConfig('starter_text');
```

### TemplateEngine

```php
use FriendsOfREDAXO\Builder\TemplateEngine;

// Template mit Framework-Dispatch rendern
$html = TemplateEngine::render('wrapper', $data, 'bootstrap');
// Lädt: elements/wrapper/templates/bootstrap.php

// Fragment rendern
$html = TemplateEngine::renderFragment('ycb_elements/wrapper', $data, 'uikit');

// Prüfen ob Template existiert
if (TemplateEngine::hasTemplate('cards', 'bootstrap')) {
    // ...
}

// Verfügbare Frameworks
$frameworks = TemplateEngine::getAvailableFrameworks();
// => ['uikit', 'bootstrap', 'plain']
```

### MediaManagerHelper

```php
use FriendsOfREDAXO\Builder\MediaManagerHelper;

$mm = MediaManagerHelper::factory();

$mm->addType('example_16_9', 'Beispieltyp 16:9')
   ->addEffect('example_16_9', 'resize', [
       'width' => 1200,
       'height' => 1200,
       'style' => 'maximum',
       'allow_enlarge' => 'not_enlarge',
   ], 1)
   ->addEffect('example_16_9', 'crop', [
       'width' => 1200,
       'height' => 675,
       'hpos' => 'center',
       'vpos' => 'middle',
       'offset_width' => '',
       'offset_height' => '',
   ], 2)
   ->install();
```

Empfehlung:

- Jedes Addon legt nur die tatsächlich selbst verwendeten MediaManager-Typen in seiner eigenen `install.php` an.
- Typenbeschreibungen sollten den Addon-Bezug klar enthalten, z. B. `Builder: ...` oder `KLXM Elements: ...`.

### ResponsiveImage

```php
use FriendsOfREDAXO\Builder\Media\ResponsiveImage;

$image = ResponsiveImage::forFile($file)
    ->withDesktopPreset('starter_cards_16_9')
    ->withMobilePreset('starter_cards_4_3')
    ->withWidths([400, 800, 1200, 1600])
    ->withContainerWidth('uk-container')
    ->withColumns(3, 2, 1)
    ->withMediaFraction(1.0)
    ->withMobileBreakpoint(639);

$imgData = $image->toImage();
$pictureData = $image->toPicture();

echo $image->toImageTag([
    'alt' => $alt,
    'class' => 'uk-width-1-1',
]);

echo $image->toPictureTag([
    'alt' => $alt,
    'class' => 'uk-width-1-1',
]);
```

Rückgaben:

- `toImage()` → `['src' => string, 'srcset' => string, 'sizes' => string]`
- `toPicture()` → `['sources' => list<array{media,srcset,sizes}>, 'img' => array{src,srcset,sizes}]`

Hinweise:

- Die Klasse ist für Template-Nutzung gedacht und reduziert duplizierte `<picture>/<img>`-Logik.
- Intern wird weiterhin das virtuelle Typschema `cb_<preset>__<width>` verwendet.
- Ohne verfügbaren Media Manager fällt die Ausgabe auf `rex_url::media(...)` zurück.

### FieldRegistry

```php
use FriendsOfREDAXO\Builder\Fields\FieldRegistry;

// Feld registrieren
FieldRegistry::register(new MyField());

// Feld abrufen
$field = FieldRegistry::get('text');

// Prüfen ob Feld existiert
if (FieldRegistry::has('my_custom')) {
    // ...
}

// Alle Felder abrufen
$allFields = FieldRegistry::getAll();

// Feld rendern (empfohlener Weg)
FieldRegistry::renderField($fieldName, $fieldConfig, $sliceData);
```

### Helper

```php
// Prüfen ob Datei ein Bild ist
Helper::isImage('foto.jpg');  // true

// Prüfen ob Datei ein Video ist
Helper::isVideo('clip.mp4');  // true

// Verfügbare Elemente abrufen
$elements = Helper::getAvailableElements();

// Element-Konfiguration laden
$config = Helper::getElementConfig('text_image');
```

### Frontend-Rendering

```php
use FriendsOfREDAXO\Builder\Helper;

// Aus vorhandenem YForm/YORM-Datensatz
$page = rex_yform_manager_dataset::get(1, 'rex_pages');
if ($page !== null) {
    echo Helper::outputDataset($page, 'content_builder', 'bootstrap');
}

// Direkt ueber Tabelle + Datensatz-ID
echo Helper::outputDatasetById('rex_pages', 1, 'content_builder', 'uikit');

// Direkt aus Rohdaten
echo Helper::outputRaw((string) $page?->getValue('content_builder'), 'plain');
```

> **Hinweis zur Abwärtskompatibilität:** Die alten Klassennamen `builder_helper`, `ContentBuilderFieldRegistry`, `ContentBuilderFieldAbstract` und `ContentBuilderFieldInterface` stehen weiterhin über PHP `class_alias()` zur Verfügung. Neuer Code sollte immer die kanonischen Klassennamen mit `use FriendsOfREDAXO\Builder\...` verwenden.

---

## Best Practices

### Element-Templates

1. **Keine Funktionen definieren** – Templates werden mehrfach eingebunden. Vermeide `function myHelper() { ... }` direkt im Template: das führt bei mehrfach genutzten Elementen zu einem *„Cannot redeclare function"* Fatal Error.

   ```php
   // ❌ Falsch – führt zu Fehler bei mehrfacher Verwendung
   function formatPrice($price) {
       return number_format($price, 2, ',', '.');
   }
   echo formatPrice($price);
   
   // ✅ Richtig – Closure oder Helper-Klasse verwenden
   use FriendsOfREDAXO\Builder\Helper;
   
   if (Helper::isImage($file)) { ... }
   
   $formatPrice = static function (float $price): string {
       return number_format($price, 2, ',', '.');
   };
   echo $formatPrice($price);
   ```

2. **Immer escapen** – `rex_escape()` für alle Ausgaben nutzen.
3. **Leere Werte prüfen** – `$elementData['field'] ?? ''` als Fallback.
4. **Framework-agnostisch denken** – Logik im Template, Styling per CSS.

### Feldtypen

1. **Basisklasse nutzen** - `FieldAbstract` vereinfacht vieles
2. **Eindeutige IDs** - `$this->generateId()` verwenden
3. **processValue()** - Für Validierung/Transformation nutzen
4. **Kompatibilität** - CSS im Feld nur wenn nötig, besser in eigenem Stylesheet

### Performance

1. **Lazy Loading** - Felder werden erst bei Bedarf initialisiert
2. **Caching** - Element-Konfigurationen werden gecacht
3. **Minimale Requests** - API-Calls bündeln wo möglich

---

## Datensatz-Picker Feldtypen

Es gibt **zwei verschiedene Feldtypen** für die Auswahl von Datensätzen aus Datenbanktabellen:

### `be_table_select` – Einfacher Datensatz-Picker (Selectpicker)

Leichtgewichtiger selectpicker für einfache Einzel- oder Mehrfachauswahl.

**Features:**
- Single & Multiple (kommagetrennte Speicherung bei Multiple: `"1,2,3"`)
- Live Search im Dropdown
- Responsive

```php
'featured_product' => [
    'type' => 'be_table_select',
    'label' => 'Produkt verknüpfen',
    'table' => 'rex_yform_products',
    'field' => 'title',
    'multiple' => false,
    'notice' => 'Einzelnes Produkt auswählen'
],

'related_events' => [
    'type' => 'be_table_select',
    'label' => 'Termine verknüpfen',
    'table' => 'rex_yform_calendar',
    'field' => 'title',
    'multiple' => true,
    'notice' => 'Mehrere Termine möglich'
]
```

| Parameter | Typ | Erforderlich | Beschreibung |
|-----------|-----|:---:|--------------|
| `table` | string | ✅ | Tabellenname (z. B. `rex_yform_calendar`) |
| `field` | string | ✅ | Anzeige-Spalte (z. B. `title`) |
| `multiple` | bool | – | Mehrfachauswahl (default: `false`) |
| `label` | string | ✅ | Feldbezeichnung im Backend |
| `notice` | string | – | Hinweis-Text |

### `yformpicker` – YForm Datensatz-Picker (Popup)

Öffnet den YForm-Manager in einem Popup – ideal für große Datenmengen.

**Features:**
- Native YForm-Integration mit Pagination
- Single & Multiple
- Sortierbar (Drag & Drop oder Move-Buttons bei Multiple)

```php
'main_event' => [
    'type' => 'yformpicker',
    'label' => 'Haupttermin',
    'table' => 'rex_yform_calendar',
    'field' => 'title',
    'multiple' => false
],

'team_members' => [
    'type' => 'yformpicker',
    'label' => 'Team-Mitglieder',
    'table' => 'rex_kontakte',
    'field' => 'nachname',
    'multiple' => true
]
```

### Vergleich

| Feature | `be_table_select` | `yformpicker` |
|---------|:-----------------:|:-------------:|
| Darstellung | Selectpicker / Dropdown | Popup-Modal |
| Große Datenmengen | ⚠️ | ✅ Pagination |
| Sortierbar (Multiple) | – | ✅ |
| Abhängigkeiten | Bootstrap Selectpicker | YForm |

---

## Extra-Felder System

Das Extra-Felder System ermöglicht es, bestehenden Elementen projektspezifische Zusatzfelder hinzuzufügen **ohne den Element-Code zu modifizieren**.

### Konzept

1. **Extra-Klasse erstellen** – externe PHP-Klasse definiert zusätzliche Felder.
2. **Backend-Rendering** – Felder erscheinen automatisch in einem Modal oder Extra-Tab.
3. **Datenspeicherung** – Werte werden mit den Element-Daten gespeichert.
4. **Frontend-Output** – `GetOutput()` formatiert die Werte für die Ausgabe.

### Extra-Klasse erstellen

```php
<?php
/**
 * Projekt-spezifische Extra-Felder für das Cards-Element (Repeater-Items)
 */
class CardsRepeaterExtra
{
    /**
     * Definiert zusätzliche Felder (Format wie Element-Config)
     */
    public static function GetConfig(): array
    {
        return [
            'card_badge' => [
                'type' => 'text',
                'label' => 'Badge-Text',
                'notice' => 'Z. B. „NEU" oder „SALE"'
            ],
            'card_premium' => [
                'type' => 'choice',
                'label' => 'Karten-Status',
                'choices' => [
                    'free'     => 'Kostenlos',
                    'standard' => 'Standard',
                    'premium'  => 'Premium',
                ],
                'default' => 'standard'
            ],
        ];
    }

    /**
     * Formatiert Extra-Felder als HTML für das Frontend-Template
     */
    public static function GetOutput(array $item): string
    {
        $html = '';

        if (!empty($item['card_badge'])) {
            $html .= '<span class="badge">' . rex_escape($item['card_badge']) . '</span>';
        }

        return $html;
    }
}
```

### Element-Config für Extra-Felder anpassen

```php
<?php
// Extra-Felder von Projekt-Addon laden
$extra = [];
if (class_exists('CardsRepeaterExtra') && method_exists('CardsRepeaterExtra', 'GetConfig')) {
    $extra = CardsRepeaterExtra::GetConfig();
}

return [
    'label' => 'Cards',
    'icon'  => 'fa-th',
    'fields' => [
        'items' => [
            'type'  => 'repeater',
            'label' => 'Cards',

            // Extras-Modal – nur wenn Extra-Felder vorhanden
            ...(!empty($extra) ? [
                'extras_modal' => [
                    'label'         => 'Extras',
                    'icon'          => 'fa-star',
                    'trigger_after' => 'title',
                    'fields'        => array_keys($extra),
                ]
            ] : []),

            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'cke5', 'label' => 'Text'],

                // Extra-Felder integrieren
                ...$extra,
            ],
        ],
    ],
];
```

### Frontend-Template mit Extra-Ausgabe

```php
<?php
// Extra-Felder Ausgabe
$extraHtml = '';
if (class_exists('CardsRepeaterExtra') && method_exists('CardsRepeaterExtra', 'GetOutput')) {
    $extraHtml = CardsRepeaterExtra::GetOutput($item);
}

if ($extraHtml !== '') {
    echo '<div class="card-extras">' . $extraHtml . '</div>';
}
```

---

## Datenstruktur

Content wird als **JSON-Array** in einer YForm-Textspalte oder einem REDAXO Module-Slot (REX_VALUE) gespeichert:

```json
[
    {
        "element_type": "headline",
        "data": {
            "text": "Willkommen",
            "level": "h1",
            "color": "default"
        },
        "slice_id": "slice_abc123"
    },
    {
        "element_type": "media_text",
        "data": {
            "headline": "Über uns",
            "text": "<p>...</p>",
            "image": "team.jpg",
            "layout": "media-left"
        },
        "slice_id": "slice_def456"
    }
]
```

Verschachtelte Felder (Repeater) werden als Arrays innerhalb von `data` gespeichert:

```json
{
    "element_type": "cards",
    "data": {
        "items": [
            { "title": "Karte 1", "text": "<p>...</p>", "image": "card1.jpg" },
            { "title": "Karte 2", "text": "<p>...</p>", "image": "card2.jpg" }
        ]
    }
}
```

---

## Fehlerbehebung

### Element wird nicht angezeigt

1. Prüfe Ordnerstruktur: `elements/mein_element/config.php` vorhanden?
2. Ist `config.php` syntaktisch valides PHP? Gibt es ein `return [...]`?
3. Backend-Cache leeren: **REDAXO → System → Cache löschen**

### CKE5 initialisiert nicht

1. CKE5-Addon installiert und aktiviert?
2. Browser-Konsole auf JavaScript-Fehler prüfen
3. Feld-ID muss mit `ck` beginnen (wird intern automatisch vergeben)

### Linkmap funktioniert nicht

1. `REX_LINK_X` und `REX_LINK_X_NAME` korrekt referenziert?
2. `deleteREXLink()` verfügbar (REDAXO Media/Link-Erweiterung geladen)?

### Repeater-Daten werden nicht gespeichert

1. `setNestedValue()` in `content-builder.js` auf Fehler prüfen
2. Browser-Konsole auf JSON-Fehler prüfen
3. Netzwerk-Tab: POST-Request auf `rex-api-call=content_builder` prüfen

### Klasse nicht gefunden nach Update auf 2.0

Alle alten Klassennamen stehen als `class_alias` zur Verfügung. Sollte eine Klasse fehlen, prüfe:

1. `boot.php` hat alle benötigten `class_alias`-Einträge.
2. REDAXO-Autoloader findet die Klasse in `lib/` (Cache löschen hilft).
3. Für neuen Code: `use FriendsOfREDAXO\Builder\Module;` statt des alten Alias verwenden.

