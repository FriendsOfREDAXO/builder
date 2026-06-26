# Builder - JSON Schema

This addon includes a formal JSON Schema to validate Element Configurations.

## 📋 Schema File

**File:** `element-config.schema.json`  
**Path:** `redaxo/src/addons/builder/element-config.schema.json`

## 🤖 Usage for AI Agents

When asking an AI to generate a new element, you can provide this schema to ensure the output is valid.

**Prompt Example:**
> "Create a new Builder element for a 'Testimonial Slider'. Please follow the structure defined in `element-config.schema.json`."

## 🔧 Usage in IDEs (VS Code)

You can configure VS Code to use this schema for validation and autocompletion in your `config.php` files (if you use a JSON-to-PHP mapping or just for reference).

Since `config.php` files are PHP, direct JSON schema validation isn't native without plugins, but you can use the schema to understand the structure.

## 📄 Structure Overview

The schema defines:
- **Root Properties**: `label`, `description`, `icon`, `fields`.
- **Optional Root Properties**: `field_groups`, `settings_modal`, `category`, `version`, `allow_self_nesting`, `prevent_self_nesting`.
- **Field Types**: `text`, `textarea`, `choice`, `checkbox`, `ckeditor5`, `be_media`, `be_media_enhanced`, `be_link`, `repeater`.
- **Recursive Definitions**: `repeater` fields can contain other fields.

### settings_modal: Fieldsets + Akkordeon

`settings_modal` kann entweder eine flache `fields`-Liste oder gruppierte `fieldsets` enthalten.

Zusatzoptionen fuer das Gruppierungsverhalten:

- `accordion.initial`: `first` | `all` | `none`
- `accordion.single_open`: nur eine Gruppe gleichzeitig offen
- `accordion.collapsible`: geoeffnete Gruppe wieder zuklappbar

Beispiel:

```php
'settings_modal' => [
    'label' => 'Layout & Sektion',
    'icon' => 'fa-cog',
    'accordion' => [
        'initial' => 'first',
        'single_open' => true,
        'collapsible' => true,
    ],
    'fieldsets' => [
        'layout' => [
            'label' => 'Layout',
            'icon' => 'fa-th',
            'fields' => ['columns', 'gap'],
        ],
        'section' => [
            'label' => 'Sektion',
            'icon' => 'fa-window-maximize',
            'fields' => ['section_bg', 'section_padding'],
        ],
    ],
]
```

### Nesting-Flags in Element-Configs

Elemente können ihr Selbstverschachtelungs-Verhalten direkt in `config.php` definieren:

- `allow_self_nesting` (`boolean`) – explizit erlauben/verbieten
- `prevent_self_nesting` (`boolean`) – explizit verhindern

Empfehlung:

- Verwende bevorzugt `prevent_self_nesting` für klare Semantik.
- `allow_self_nesting` bleibt für rückwärtskompatible Konfigurationen verfügbar.

Beispiel:

```php
return [
    'label' => 'Spalten-Layout',
    'icon' => 'fa-columns',
    'prevent_self_nesting' => true,
    'fields' => [
        // ...
    ],
];
```

In current element configs you will also see newer field types such as `cke5`, `tinymce`, `radio_image`, `color_swatches`, `be_table_select`, `yformpicker`, `smart_link`, `rich_headline`, `info`, `table_editor`.

## 🧩 Terminologie im Gesamtsystem

- **Core-Elemente**: Basis-Bausteine im Haupt-Addon `builder`
- **Starter-Elemente**: Demo-Bausteine im Haupt-Addon `builder`
- **Projekt-Elemente**: Produktive externe Bausteine in Addons wie `klxm_elements`
- **Modul-Erstellung**: Bleibt zentral im Haupt-Addon auf `index.php?page=builder/modules`

## 🖼️ Media-Output-Konvention

Für Bildausgaben in Templates gilt:

- Verwende virtuelle Typen: `cb_<preset>__<width>`
- Erzeuge Typen ausschließlich über `MediaTypeRegistry::buildVirtualType(...)`
- Führe keine neuen statischen Typnamen wie `card_16_9_w1200` ein

Beispiel:

```php
<?php
$type = \FriendsOfREDAXO\Builder\Config\MediaTypeRegistry::buildVirtualType('starter_cards_16_9', 1200);
echo rex_media_manager::getUrl($type, $image);
```

Externe Addons erweitern Presets über den Extension Point `BUILDER_MEDIA_TYPE_PRESETS`.

### ResponsiveImage Helper (Template-API)

Für die Template-Ausgabe kann zusätzlich `FriendsOfREDAXO\Builder\Media\ResponsiveImage` verwendet werden.

- erzeugt zentral `src`, `srcset`, `sizes`
- unterstützt optional Mobile-Art-Direction (`<picture>/<source>`)
- stellt mit `toImageTag()` und `toPictureTag()` direkte HTML-Renderer bereit

Wichtig für Schema-Verständnis:

- Die Klasse betrifft ausschließlich die Laufzeit-Ausgabe in Templates.
- Es wurden keine neuen `config.php`-Keys eingeführt.
- Daher war keine Änderung an `element-config.schema.json` bzw. `schema/element-config.schema.json` erforderlich.

### Legacy Editor & Migration

For YForm value-based builder fields, the following configuration fields control Legacy-HTML handling:

- **`legacy_cke5_enabled`** (choice): Enable legacy HTML editing (fallback if no modern content exists).
- **`legacy_editor_attributes`** (text): Free attribute string for legacy textarea (e.g., `class="form-control tiny-editor" data-profile="default" rows="14"`). Supports both CKE5 (`cke5-editor` class) and TinyMCE (`tiny-editor` class).
- **`legacy_cke5_profile`** (text): CKE5 profile name (fallback if `legacy_editor_attributes` has no `data-profile`).
- **`legacy_cke5_lang`** (text): CKE5 language (fallback if `legacy_editor_attributes` has no `data-lang`).
- **`legacy_migration_hint`** (choice): Show migration prompt to modern editor.
- **`legacy_migration_target`** (choice): Element type for HTML migration (e.g., `starter_text`). Auto-resolves invalid choices.
- **`legacy_migration_field`** (text): Target field key in migration element (e.g., `text`, `content`, `body`).

### Conditional Visibility (`visible_if`)

Every field definition can include `visible_if` rules to conditionally show/hide fields without writing custom JavaScript.

Example:

```php
'section_bg' => [
    'type' => 'choice',
    'label' => 'Sektions-Hintergrund',
    'visible_if' => ['enable_section' => '1'],
]
```

Rules:

- `visible_if` is a map of `source_field => expected_value`
- multiple conditions are evaluated as logical AND
- expected values can be `string` or `array`

Supported source values:

- `checkbox`: `1` / `0`
- `radio`: selected `value`
- `select` (single): selected `value`
- `select` (multiple): array of selected values

This behavior is shared by YForm and module editors.

See `element-config.schema.json` for the full definition.

## 📂 Physical Element Structure

To create a valid element, an AI must understand the file system structure.

### Directory Layout

Each element resides in its own subdirectory within the `elements/` folder. The folder name is the **Element Key**.

```text
elements/
└── {element_key}/              # e.g. "hero_header" (snake_case)
    ├── config.php              # Returns the Configuration Array (see Schema)
    └── templates/              # Output Templates
        ├── bootstrap.php       # Required: Bootstrap 3/4/5 Template
        ├── uikit.php           # Optional: UIkit 3 Template
        ├── tailwind.php        # Optional: Tailwind CSS Template
        └── plain.php           # Optional: Fallback/Plain HTML
```

### File Contents

#### 1. `config.php`
Must return a PHP array matching the JSON Schema structure.

```php
<?php
return [
    'label' => 'Hero Header',
    'icon' => 'fa-header',
    'fields' => [ ... ]
];
```

#### 2. Templates (`templates/*.php`)
Standard PHP templates. Variables are available via `$elementData` array.

```php
<?php
/** @var array $elementData */
// Access fields defined in config.php
$headline = $elementData['headline'] ?? '';
?>
<div class="hero">
    <h1><?= rex_escape($headline) ?></h1>
</div>
```

## 🏗️ Configuration Architecture

### Config-Klassen (Framework-Abstraktion)

**Vier neue Klassen ersetzen hartcodierte Optionen:**

#### 1. FrameworkConfig
Zentralisiert Framework-spezifische CSS-Klassen und Optionen.

```php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

// Hintergrund-Optionen pro Framework
FrameworkConfig::getBackgroundChoices('uikit');
// => ['', 'uk-background-default', 'uk-background-muted', ...]

FrameworkConfig::getBackgroundChoices('bootstrap');
// => ['', 'bg-white', 'bg-light', 'bg-dark', ...]

// Padding-Optionen
FrameworkConfig::getPaddingChoices('bootstrap');
// => ['', 'p-0', 'p-2', 'p-4', ...]

// Container-Optionen
FrameworkConfig::getContainerChoices('plain');
// => ['container', 'container-narrow', 'container-full', ...]

// Hex-Farben für Color-Swatches
FrameworkConfig::getBackgroundColors('uikit');
// => ['' => ['color' => 'transparent', 'label' => 'Keine'], ...]

// CSS-Präfix
FrameworkConfig::getCssPrefix('bootstrap');
// => 'bs-'
```

**Registrierbar via Extension Point:**
```php
rex_extension::register('BUILDER_FRAMEWORK_OPTIONS', 
    function($ep) {
        if ('custom' === $ep->getParam('framework')) {
            return ['my-option' => 'Custom Option'];
        }
        return $ep->getSubject();
    }
);
```

#### 2. EditorConfig
Verwaltet Editor-Profile pro Element/Feld (TinyMCE vs CKE5).

```php
use FriendsOfREDAXO\Builder\Config\EditorConfig;

// Profil pro Element
EditorConfig::getEditorProfile('starter_text', 'text');
// => 'default'

// Editor-Typ für Profil
EditorConfig::getEditorTypeForProfile('default');
// => 'tinymce' oder 'ckeditor5'

// Alle Elemente-Profile
EditorConfig::getElementProfiles();
// => ['starter_text' => 'default', 'starter_cards' => 'default', ...]
```

**Registrierbar via Extension Point:**
```php
rex_extension::register('BUILDER_EDITOR_PROFILES',
    function($ep) {
        if ('my_element' === $ep->getParam('element')) {
            return 'minimal'; // Custom profil
        }
        return $ep->getSubject() ?? 'default';
    }
);
```

#### 3. ElementRegistry
Verwaltet Element-Pfade und Bundled-Elements.

```php
use FriendsOfREDAXO\Builder\Config\ElementRegistry;

// Alle bundled Elements
ElementRegistry::getBundledElements();
// => ['section', 'headline', 'starter_text', ...]

// Prüfen ob bundled
ElementRegistry::isBundledElement('starter_text');
// => true

// Alle Pfade (core, klxm_elements, custom)
ElementRegistry::getElementPaths();
// => ['core' => '/path/...', 'klxm_elements' => '/path/...']

// Elemente aus Pfad
ElementRegistry::getElementsFromPath('klxm_elements');
// => ['cards', 'hero_banner', ...]

// Alle verfügbaren Elemente
ElementRegistry::getAllElements();
// => [... externe + ggf. mitgelieferte (abhängig vom effektiven Modus) ...]

// Element-Config laden
ElementRegistry::getElementConfig('starter_text');
// => ['label' => 'Text', 'fields' => [...], ...]
```

**Registrierbar via Extension Point:**
```php
rex_extension::register('BUILDER_ELEMENT_PATHS',
    function($ep) {
        $paths = $ep->getSubject() ?? [];
        $paths['my_addon'] = rex_path::addon('my_addon', 'elements/');
        return $paths;
    }
);
```

#### 4. TemplateEngine
Framework-Dispatch für Templates.

```php
use FriendsOfREDAXO\Builder\TemplateEngine;

// Template mit Framework
TemplateEngine::render('wrapper', $data, 'bootstrap');
// → Lädt: elements/wrapper/templates/bootstrap.php (oder Fallback)

// Fragment rendern
TemplateEngine::renderFragment('ycb_elements/wrapper', $data, 'uikit');

// Prüfen ob vorhanden
if (TemplateEngine::hasTemplate('cards', 'tailwind')) {
    // ...
}

// Verfügbare Frameworks
TemplateEngine::getAvailableFrameworks();
// => ['uikit', 'bootstrap', 'plain']
```

### Extension Points Übersicht

| Extension Point | Parameter | Return | Zweck |
|-----------------|-----------|--------|-------|
| `BUILDER_FRAMEWORK_OPTIONS` | `framework`, `option_type` | `array` \| `string` | Framework-spezifische Optionen |
| `BUILDER_EDITOR_PROFILES` | `element`, `field` | `string` | Editor-Profile pro Element |
| `BUILDER_BUNDLED_ELEMENTS` | — | `array` | Bundled Element-Keys |
| `BUILDER_ELEMENT_PATHS` | — | `array` | Pfade zu Element-Verzeichnissen |

Hinweise zur Mode-Semantik:

- `replace` blendet mitgelieferte Elemente aus, externe Pfade bleiben aktiv.
- `merge` lädt externe Pfade plus mitgelieferte Elemente.
- Wenn ein registrierter Provider `replace` signalisiert, gilt effektiv `replace`.
- Einzelne mitgelieferte Elemente können über die Settings-Whitelist `replace_keep_core_elements` als Ausnahme freigegeben werden.

Vollständige Dokumentation der Extension Points: [DEV.md](DEV.md)

## 🧠 System Prompt Context

If you want to feed this context to an AI, you can copy the following block:

> **Builder Context:**
> 1. **Structure**: Elements are folders in `elements/{key}/`.
> 2. **Config**: `config.php` returns a PHP array defining fields (text, textarea, be_media, repeater, etc.).
> 3. **Templates**: `templates/bootstrap.php` is the default output. Use `$elementData['fieldname']` to access values.
> 4. **Schema**: Follow the `element-config.schema.json` for field definitions.
> 5. **Config Classes**: FrameworkConfig, EditorConfig, ElementRegistry, TemplateEngine for framework-agnostic configuration.
