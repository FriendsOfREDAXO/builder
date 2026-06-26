# Developer Guide – Builder v1.0.0-beta3+

Umfassender Guide für die Entwicklung mit dem Framework-agnostischen Content Builder System.

## 🧩 Begriffe im Gesamtsystem

- **Core-Elemente**: Basis-Bausteine im Haupt-Addon `builder`
- **Starter-Elemente**: Demo-Bausteine im Haupt-Addon `builder`
- **Projekt-Elemente**: Produktive externe Bausteine in Addons wie `klxm_elements`
- **Modul-Erstellung**: Bleibt zentral auf `index.php?page=builder/modules`

## MediaManager-Verantwortung

- `builder` legt nur den Basistyp `content_builder` an.
- Alle konkreten Ausgaben laufen über virtuelle Typen im Format `cb_<preset>__<width>`.
- Die Auflösung erfolgt zentral über `MEDIA_MANAGER_FILTERSET` in `MediaManagerFilterset::apply()`.
- Externe Addons registrieren Presets über `BUILDER_MEDIA_TYPE_PRESETS` statt eigene statische MM-Typen in `install.php` anzulegen.
- Wenn `media_negotiator` verfügbar ist, wird `negotiator` als letzter Effekt ergänzt; der Cache-Key wird über `MEDIA_MANAGER_INIT` erweitert.

### Wie virtuelle Bildtypen funktionieren

1. Templates erzeugen keine statischen MM-Typnamen mehr, sondern virtuelle Typen im Schema `cb_<preset>__<width>`.
2. `MediaTypeRegistry::parseVirtualType()` zerlegt den Typ in Preset + Zielbreite.
3. `MediaManagerFilterset::apply()` liest das passende Preset und mappt auf den Effekt `content_builder`.
4. Der Effekt erhält `ratio`, `mode` und normalisierte `width` als Parameter.
5. Optional wird `negotiator` als letzter Effekt angehängt.

Kurzbeispiel:

- Virtueller Typ: `cb_klxm_card_16_9__900`
- Auflösung: Preset `klxm_card_16_9`, Width `900`
- Normalisierung: z. B. auf `1200`, falls nur `[400,800,1200,1600]` erlaubt
- Effektkette: `content_builder` (+ optional `negotiator`)

### ResponsiveImage für Templates

Für responsive Bildausgaben mit virtuellen Typen steht die Klasse `FriendsOfREDAXO\Builder\Media\ResponsiveImage` bereit.

Ziel:

- zentrale Erzeugung von `src`, `srcset` und `sizes`
- optionale Mobile-Art-Direction über `<picture>` / `<source>`
- weniger duplizierte HTML- und Width-Logik in Templates

Beispiel:

```php
use FriendsOfREDAXO\Builder\Media\ResponsiveImage;

$markup = ResponsiveImage::forFile($image)
    ->withDesktopPreset('starter_cards_16_9')
    ->withMobilePreset('starter_cards_4_3')
    ->withWidths([400, 800, 1200, 1600])
    ->withContainerWidth('uk-container')
    ->withColumns(3, 2, 1)
    ->toPictureTag([
        'alt' => $alt,
        'class' => 'uk-width-1-1',
        'loading' => 'lazy',
    ]);

echo $markup;
```

API-Überblick:

- Daten: `toImage()`, `toPicture()`
- HTML: `toImageTag()`, `toPictureTag()`

Hinweis:

- `ResponsiveImage` nutzt intern weiterhin virtuelle Typen über `MediaTypeRegistry::buildVirtualType(...)`.
- Es werden keine zusätzlichen statischen Media-Manager-Typen angelegt.

### Preset-Kontrakt

Ein Preset hat folgende Struktur:

```php
[
    'ratio' => '16_9'|'21_9'|'4_3'|'1_1'|'3_2'|'3_4'|'original',
    'mode' => 'focuspoint'|'resize',
    'widths' => [400, 800, 1200, 1600],
    'default_width' => 1200,
]
```

Empfehlungen:

- Ratio-Crops immer mit `mode = focuspoint`.
- `original` nur mit `mode = resize`.
- Preset-Namen addonspezifisch prefixen, z. B. `klxm_card_16_9`.

### Presets registrieren (einfacher Weg, ohne direkten Extension-Point-Code)

Wenn du den Umgang mit Extension Points vermeiden willst, nutze direkt die Registry-Methoden:

```php
use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

if (rex_addon::get('builder')->isAvailable()) {
    MediaTypeRegistry::registerPresets([
        'klxm_card_16_9' => [
            'ratio' => '16_9',
            'mode' => 'focuspoint',
            'widths' => [400, 800, 1200, 1600],
            'default_width' => 1200,
        ],
        'klxm_card_original' => [
            'ratio' => 'original',
            'mode' => 'resize',
            'widths' => [400, 800, 1200],
            'default_width' => 1200,
        ],
    ]);
}
```

Einzelnes Preset:

```php
MediaTypeRegistry::registerPreset('klxm_teaser_4_3', [
    'ratio' => '4_3',
    'mode' => 'focuspoint',
    'widths' => [400, 800, 1200],
    'default_width' => 800,
]);
```

### Presets registrieren (klassisch per Extension Point)

Für dynamische Anpassungen oder projektweite Merges ist der Extension Point verfügbar:

```php
rex_extension::register('BUILDER_MEDIA_TYPE_PRESETS', static function (rex_extension_point $ep): array {
    $presets = $ep->getSubject();

    $presets['klxm_card_16_9'] = [
        'ratio' => '16_9',
        'mode' => 'focuspoint',
        'widths' => [400, 800, 1200, 1600],
        'default_width' => 1200,
    ];

    return $presets;
});
```

Hinweis:

- Die Registry-Methode ist für Addon-Entwickler der schnellste Einstieg.
- Der Extension Point ist sinnvoll, wenn du bestehende Presets anderer Addons gezielt verändern/erweitern möchtest.

## Element-CSS pro Framework

- Framework-basierte Templates sollen zuerst native Bootstrap/UIKit-Klassen verwenden.
- Eigenes CSS nur, wenn Funktion/Optik nicht sinnvoll nativ abbildbar ist.
- Element-spezifisches CSS liegt im Elementordner, z. B.:
    - `elements/<element>/assets/uikit.css`
    - `elements/<element>/assets/bootstrap.css`
    - optional `elements/<element>/assets/common.css`
- Das Backend lädt gemergtes Element-CSS automatisch über die API-Action `get_element_css`.
- Das Frontend bindet der Integrator bewusst selbst ein:
    - `/index.php?rex-api-call=content_builder&action=get_element_css&framework=uikit`
- Im Backend erfolgt der Request über den Backend-Controller (`/redaxo/index.php?...`), um relative Pfadprobleme zu vermeiden.
- Optional können einzelne Elemente per CSV gefiltert werden, z. B. `&elements=starter_cards,starter_media_split`.

## 📚 Inhaltsverzeichnis

1. [Architektur-Übersicht](#architektur-übersicht)
2. [Extension Points Referenz](#extension-points-referenz)
3. [Config-Klassen Nutzung](#config-klassen-nutzung)
4. [Framework Support](#framework-support)
5. [Eigene Elemente erstellen](#eigene-elemente-erstellen)
6. [Editor-Profile Setup](#editor-profile-setup)
7. [Element-Pfade registrieren](#element-pfade-registrieren)
8. [TemplateEngine nutzen](#templateengine-nutzen)
9. [Best Practices](#best-practices)
10. [Häufige Fragen](#häufige-fragen)

---

## Architektur-Übersicht

### System-Schichten

```
┌─────────────────────────────────────────────────────────┐
│ Backend UI (Element Editor, Slice Manager)              │
├─────────────────────────────────────────────────────────┤
│ Module / YForm Value-Klassen                            │
├─────────────────────────────────────────────────────────┤
│ Config-Klassen (FrameworkConfig, EditorConfig, etc.)    │
├─────────────────────────────────────────────────────────┤
│ Extension Points (registrierbare Hooks)                 │
├─────────────────────────────────────────────────────────┤
│ Element-Verzeichnisse (core, klxm_elements, custom)     │
├─────────────────────────────────────────────────────────┤
│ Templates (bootstrap.php, uikit.php, tailwind.php)      │
└─────────────────────────────────────────────────────────┘
```

### Daten-Fluss

```
Element Config (config.php)
  ↓
Feld-Typen & Konfiguration
  ↓
Extension Points (Framework-Optionen, Profiles)
  ↓
Config-Klassen (FrameworkConfig, EditorConfig)
  ↓
Rendering (TemplateEngine → Framework-Dispatch)
  ↓
Frontend Output
```

### YForm-Defaults für neue Elemente

Der YForm-Werttyp `content_builder` unterstützt projektweite Defaults direkt über Feldparameter.

Relevante Parameter in `getDefinitions()`:

- `default_enable_section`
- `default_enable_container`
- `element_defaults_json`

Interne Auflösung (Runtime):

1. `element_defaults_json` wird als Array geladen.
2. Falls `default_enable_section`/`default_enable_container` gesetzt sind, werden diese in `*` gemergt.
3. Ergebnis wird als JSON in `data-element-defaults` am Root-Container ausgegeben.
4. Die JS-Logik (`resolveElementDefaults`) merged `*` + typ-spezifische Defaults beim Anlegen eines neuen Slices.

Wichtig:

- Gilt nur beim Erzeugen neuer Elemente.
- Keine rückwirkende Mutation bereits gespeicherter Slice-Daten.

### Nesting-Steuerung (Self-Nesting)

Die Self-Nesting-Regel (Element X darf nicht in X eingefügt werden) kann auf drei Ebenen gesetzt werden.

1. Element-Config (`elements/<key>/config.php`)
    - `prevent_self_nesting` (`bool`)
    - oder `allow_self_nesting` (`bool`, invers)
2. Modul-Instanz (`Module::createWithValue(..., ['prevent_self_nesting' => ...])`)
    - CSV (`'columns,hero'`) oder Array (`['columns', 'hero']`)
3. YForm-Feldtyp `content_builder`
    - Feldoption `prevent_self_nesting` (Mehrfachauswahl)

Priorität zur Laufzeit:

1. Modul-Option
2. YForm-Option
3. Element-Config

Hinweise zur Implementierung:

- Die UI-Filterung (Dropdowns/Insert-Buttons) läuft in PHP-Renderer und JS konsistent über dieselbe Regel.
- Die Modul-Option wird in `ModuleBuilder::getAvailableElements()` als Override auf die geladenen Element-Configs angewendet.
- Die YForm-Option wird in `rex_yform_value_content_builder::getAvailableElements()` analog als Override angewendet.

---

## Extension Points Referenz

### BUILDER_FRAMEWORK_OPTIONS ⭐

Registriert Framework-spezifische Optionen (Backgrounds, Paddings, Containers).

**Aufruf-Kontext:**
- `framework`: Framework-Name ('uikit', 'bootstrap', 'plain', oder custom)
- `option_type`: Optionen-Typ ('backgrounds', 'paddings', 'containers', 'background_colors', 'css_prefix')

**Beispiel: Bootstrap Backgrounds hinzufügen**
```php
// In deinem Addon boot.php
rex_extension::register(
    'BUILDER_FRAMEWORK_OPTIONS',
    function(rex_extension_point $ep) {
        $framework = $ep->getParam('framework');
        $optionType = $ep->getParam('option_type');
        
        if ('bootstrap' === $framework && 'backgrounds' === $optionType) {
            return [
                '' => 'Keine',
                'bg-white' => 'Weiß',
                'bg-light' => 'Hell',
                'bg-dark' => 'Dunkel',
                'bg-primary' => 'Primary (Blau)',
                'bg-danger' => 'Danger (Rot)',
            ];
        }
        
        return $ep->getSubject();
    }
);
```

**Unterstützte Option-Types:**

| Type | Return | Zweck |
|------|--------|-------|
| `backgrounds` | `array [klasse => label]` | CSS-Klassen für Hintergrund-Auswahl |
| `paddings` | `array [klasse => label]` | Padding/Abstand-Klassen |
| `containers` | `array [klasse => label]` | Container-Breiten-Klassen |
| `background_colors` | `array [klasse => ['color' => hex, 'label' => str]]` | Hex-Farben für Color-Swatches |
| `css_prefix` | `string` | Framework CSS-Präfix (z.B. 'uk-', 'bs-') |

---

### BUILDER_EDITOR_PROFILES ⭐

Bestimmt Editor-Profil pro Element/Feld.

**Aufruf-Kontext:**
- `element`: Element-Key (z.B. 'starter_text')
- `field`: Feld-Name (z.B. 'text', 'description')

**Beispiel: Custom Editor-Profil pro Element**
```php
rex_extension::register(
    'BUILDER_EDITOR_PROFILES',
    function(rex_extension_point $ep) {
        $element = $ep->getParam('element');
        $field = $ep->getParam('field');
        
        // Spezielle Elemente mit custom Profilen
        if ('starter_callout' === $element && 'description' === $field) {
            return 'minimal'; // Nutze 'minimal' TinyMCE Profil
        }
        
        if ('my_element' === $element) {
            return 'full'; // Nutze 'full' CKE5 Profil
        }
        
        // Standard-Fallback
        return $ep->getSubject() ?? 'default';
    }
);
```

---

### BUILDER_BUNDLED_ELEMENTS ⭐

Definiert welche Elemente als "bundled" (im Haupt-Addon) gelten.

**Aufruf-Kontext:**
- Keine Parameter

**Beispiel: Custom Element zum Bundle hinzufügen**
```php
rex_extension::register(
    'BUILDER_BUNDLED_ELEMENTS',
    function(rex_extension_point $ep) {
        $bundled = $ep->getSubject() ?? [];
        
        // Nur wenn dein Addon verfügbar ist
        if (rex_addon::get('my_content_addon')->isAvailable()) {
            $bundled[] = 'my_custom_element';
        }
        
        return $bundled;
    }
);
```

---

### BUILDER_ELEMENT_PATHS ⭐

Registriert Verzeichnisse mit Element-Ordnern.

**Aufruf-Kontext:**
- Keine Parameter
- Return: `array [name => path]`

**Beispiel: Externen Element-Pfad registrieren**
```php
rex_extension::register(
    'BUILDER_ELEMENT_PATHS',
    function(rex_extension_point $ep) {
        $paths = $ep->getSubject() ?? [];
        
        // Element-Pfad vom eigenen Addon
        $paths['my_addon'] = rex_path::addon('my_addon', 'content_elements/');
        
        // Theme-Ordner ohne eigenes REDAXO-Addon
        // Hier setzt du den Key: theme
        $paths['theme'] = rex_path::base('theme/private/builder/theme_elements');
        
        return $paths;
    }
);
```

Hinweis: Der Schluessel `theme` bestimmt die spaetere Quelle in den Builder-Einstellungen.

---

## Config-Klassen Nutzung

### FrameworkConfig

```php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

// === In Element-Config ===
$backgrounds = FrameworkConfig::getBackgroundChoices('uikit');
$paddings = FrameworkConfig::getPaddingChoices('bootstrap');
$containers = FrameworkConfig::getContainerChoices('plain');
$colors = FrameworkConfig::getBackgroundColors('uikit');
$prefix = FrameworkConfig::getCssPrefix('bootstrap'); // 'bs-'

// === Direkt im Template ===
<?php
$bg = $elementData['section_bg'] ?? '';
$bgLabel = array_search($bg, 
    \FriendsOfREDAXO\Builder\Config\FrameworkConfig::getBackgroundChoices('uikit')
);
?>
<div class="<?= $bg ?>"><?= $bgLabel ?></div>
```

### EditorConfig

```php
use FriendsOfREDAXO\Builder\Config\EditorConfig;

// Profil abrufen
$profile = EditorConfig::getEditorProfile('starter_text', 'text');
// => 'default'

// Editor-Typ prüfen
$editorType = EditorConfig::getEditorTypeForProfile('default');
// => 'tinymce' oder 'ckeditor5'

// Alle Elemente-Profile
$all = EditorConfig::getElementProfiles();
// => ['starter_text' => 'default', ...]
```

### ElementRegistry

```php
use FriendsOfREDAXO\Builder\Config\ElementRegistry;

// Alle bundled Elements
$bundled = ElementRegistry::getBundledElements();
// => ['section', 'headline', 'starter_text', ...]

// Prüfen ob bundled
if (ElementRegistry::isBundledElement('starter_text')) {
    echo 'Ist bundled';
}

// Alle registrierten Pfade
$paths = ElementRegistry::getElementPaths();
// => ['core' => '/path/...', 'klxm_elements' => '/path/...', ...]

// Elemente aus Pfad auflisten
$klxmElements = ElementRegistry::getElementsFromPath('klxm_elements');
// => ['cards', 'hero_banner', ...]

// Alle verfügbaren Elemente (bundled + extern)
$all = ElementRegistry::getAllElements();

// Element-Konfiguration laden
$config = ElementRegistry::getElementConfig('starter_text');
// => ['label' => 'Text', 'icon' => 'fa-...', 'fields' => [...]]
```

### TemplateEngine

```php
use FriendsOfREDAXO\Builder\TemplateEngine;

// Template mit Framework rendern
$html = TemplateEngine::render('wrapper', [
    'content' => 'Hallo Welt',
    'bg' => 'uk-background-primary'
], 'uikit');

// Fragment rendern
$html = TemplateEngine::renderFragment('ycb_elements/wrapper', $data, 'bootstrap');

// Prüfen ob Template existiert
if (TemplateEngine::hasTemplate('cards', 'tailwind')) {
    $html = TemplateEngine::render('cards', $data, 'tailwind');
}

// Verfügbare Frameworks
$frameworks = TemplateEngine::getAvailableFrameworks();
// => ['uikit', 'bootstrap', 'plain']
```

---

## Framework Support

### Unterstützte Frameworks (Built-in)

| Framework | Starter-Elemente | Projekt-Elemente | Prefix |
|-----------|------------------|------------------|--------|
| **UIkit 3** | ✅ bootstrap.php + uikit.php | ✅ uikit.php | `uk-` |
| **Bootstrap** | ✅ bootstrap.php | ❌ (nur UIkit) | `bs-` |
| **Plain HTML** | ✅ plain.php | ❌ (nur UIkit) | — |

### Custom Framework hinzufügen (z.B. Tailwind)

**Step 1: Templates ergänzen**
```php
// In klxm_elements/elements/cards/templates/tailwind.php
<?php
/** @var array $elementData */
$columns = (int) ($elementData['columns'] ?? 3);
$colClass = match($columns) {
    1 => 'md:grid-cols-1',
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-3',
    4 => 'md:grid-cols-4',
    default => 'md:grid-cols-3'
};
?>
<div class="grid gap-4 <?= $colClass ?>">
    <!-- Card rendering -->
</div>
```

**Step 2: Framework-Optionen registrieren (boot.php)**
```php
rex_extension::register('BUILDER_FRAMEWORK_OPTIONS',
    function($ep) {
        if ('tailwind' !== $ep->getParam('framework')) {
            return $ep->getSubject();
        }
        
        $type = $ep->getParam('option_type');
        
        return match($type) {
            'backgrounds' => [
                '' => 'Keine',
                'bg-white' => 'Weiß',
                'bg-slate-100' => 'Slate Hell',
                'bg-slate-900' => 'Slate Dunkel',
            ],
            'paddings' => [
                '' => 'Standard',
                'p-0' => 'Keine',
                'p-4' => 'Klein (1rem)',
                'p-8' => 'Mittel (2rem)',
            ],
            'containers' => [
                'max-w-none' => 'Volle Breite',
                'max-w-4xl' => 'Standard (56rem)',
                'max-w-2xl' => 'Schmal (42rem)',
            ],
            'css_prefix' => '',  // Tailwind nutzt keine Präfixe
            default => $ep->getSubject(),
        };
    }
);
```

**Step 3: Verwendung in Element-Config**
```php
// elements/cards/config.php
$framework = 'tailwind'; // oder dynamisch ermitteln

$bgChoices = FrameworkConfig::getBackgroundChoices($framework);
$paddings = FrameworkConfig::getPaddingChoices($framework);

return [
    'label' => 'Cards',
    'fields' => [
        'bg' => [
            'type' => 'choice',
            'label' => 'Hintergrund',
            'choices' => $bgChoices,
        ],
        'padding' => [
            'type' => 'choice',
            'label' => 'Abstände',
            'choices' => $paddings,
        ],
    ],
];
```

### settings_modal mit Fieldsets und Akkordeon

Fuer laengere Einstellungen kann `settings_modal` in Gruppen aufgeteilt werden.

```php
'settings_modal' => [
    'label' => 'Layout & Sektion',
    'icon' => 'fa-cog',
    'accordion' => [
        // initial: first | all | none
        'initial' => 'first',
        // true = immer nur ein Bereich offen
        'single_open' => true,
        // true = geoeffneten Bereich wieder zuklappen duerfen
        'collapsible' => true,
    ],
    'fieldsets' => [
        'layout' => [
            'label' => 'Layout',
            'icon' => 'fa-th',
            'fields' => ['columns', 'gap', 'match_height'],
        ],
        'section' => [
            'label' => 'Sektion',
            'icon' => 'fa-window-maximize',
            'fields' => ['section_bg', 'section_padding'],
        ],
    ],
],
```

Hinweise:

- `fieldsets` und `fields` sind alternativ nutzbar.
- Ohne `accordion` gelten Defaults: `initial=first`, `single_open=true`, `collapsible=true`.
- In jedem Fieldset muessen die Eintraege in `fields` auf Keys unter Root-`fields` verweisen.

---

## Eigene Elemente erstellen

### Struktur

```
my_addon/
└── elements/
    └── my_element/
        ├── config.php
        ├── templates/
        │   ├── uikit.php
        │   ├── bootstrap.php
        │   └── plain.php
        └── lang/
            ├── de_de.lang
            └── en_gb.lang
```

### config.php Beispiel

```php
<?php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

return [
    'label' => 'Mein Element',
    'icon' => 'fa-star',
    'description' => 'Ein custom Content-Element',
    'version' => '1.0.0',
    'category' => 'content',
    
    'field_groups' => [
        'content' => [
            'label' => 'Inhalt',
            'icon' => 'fa-pencil',
            'fields' => ['headline', 'text'],
        ],
        'design' => [
            'label' => 'Design',
            'icon' => 'fa-paint-brush',
            'fields' => ['bg', 'padding'],
        ],
    ],
    
    'fields' => [
        'headline' => [
            'type' => 'text',
            'label' => 'Überschrift',
        ],
        'text' => [
            'type' => 'tinymce',
            'profile' => 'default',
            'label' => 'Text',
        ],
        'bg' => [
            'type' => 'choice',
            'label' => 'Hintergrund',
            'choices' => FrameworkConfig::getBackgroundChoices('uikit'),
        ],
        'padding' => [
            'type' => 'choice',
            'label' => 'Padding',
            'choices' => FrameworkConfig::getPaddingChoices('uikit'),
        ],
    ],
];
```

### Template Beispiel (uikit.php)

```php
<?php
/** @var array $elementData */

$headline = (string) ($elementData['headline'] ?? '');
$text = (string) ($elementData['text'] ?? '');
$bg = (string) ($elementData['bg'] ?? '');
$padding = (string) ($elementData['padding'] ?? '');

if (empty($headline) && empty($text)) {
    return; // Nichts zu rendern
}

$classes = ['my-element'];
if ($bg) $classes[] = $bg;
if ($padding) $classes[] = $padding;
?>

<div class="<?= implode(' ', $classes) ?>">
    <?php if ($headline): ?>
        <h2 class="uk-heading-line"><span><?= rex_escape($headline) ?></span></h2>
    <?php endif; ?>
    
    <?php if ($text): ?>
        <div class="uk-text-muted">
            <?= $text ?>
        </div>
    <?php endif; ?>
</div>
```

---

## Editor-Profile Setup

### Eigene TinyMCE Profile

```php
// In REDAXO Settings oder tinymce addon config
$addon_config['tinymce']['profiles']['minimal'] = [
    'toolbar' => 'bold italic link',
    'plugins' => 'link',
];

$addon_config['tinymce']['profiles']['full'] = [
    'toolbar' => 'formatselect | bold italic underline | link unlink | bullist numlist | image media',
    'plugins' => 'link image media table code',
];
```

### Profile in Element-Config verwenden

```php
// Automatisch per EditorConfig
$profile = EditorConfig::getEditorProfile('my_element', 'description');
// => 'default', 'minimal', 'full', etc.

return [
    'label' => 'My Element',
    'fields' => [
        'text' => [
            'type' => 'tinymce',
            'profile' => $profile,
            'label' => 'Text',
        ],
    ],
];
```

---

## Element-Pfade registrieren

### In boot.php

```php
// Externe Elemente registrieren
rex_extension::register(
    'BUILDER_ELEMENT_PATHS',
    function($ep) {
        $paths = $ep->getSubject() ?? [];
        
        // Theme-Ordner: Key ist 'theme'
        $paths['theme'] = rex_path::base('theme/private/builder/theme_elements');
        
        // Portfolio Addon mit Elements
        if (rex_addon::get('portfolio')->isAvailable()) {
            $paths['portfolio'] = rex_path::addon('portfolio', 'elements/');
        }
        
        return $paths;
    }
);
```

### Element-Mode steuern (merge vs replace)

```php
// In Provider-Addons via Extension Point
rex_extension::register('BUILDER_ELEMENT_MODE', static function (): string {
    return 'merge'; // oder 'replace'
});
```

**merge:**
- Externe Elemente aus registrierten Pfaden
- Plus mitgelieferte Elemente aus dem Haupt-Addon
- Use Case: Entwicklung mit mitgelieferten Start-/Core-Elementen und externen Elementen

**replace**:
- Externe Elemente aus registrierten Pfaden
- Mitgelieferte Elemente ausgeblendet
- Use Case: Produktive Instanz mit externen Projekt-Elementen

**Priorität:**
- Wenn mindestens ein registriertes Provider-Addon `replace` signalisiert, gilt effektiv `replace`.

**Ausnahmen im Replace-Modus:**
- Über die Setting-Whitelist `replace_keep_core_elements` können einzelne mitgelieferte Elemente gezielt freigegeben werden.
- Diese Ausnahmen bleiben auch sichtbar, wenn das eigene Addon in der AddOn-Quellauswahl nicht aktiv ist.

---

## TemplateEngine nutzen

### Direkt in Templates

```php
<?php
use FriendsOfREDAXO\Builder\TemplateEngine;

// Auto-Fallback zum bestmöglichen Framework
$html = TemplateEngine::render('wrapper', $data, 'tailwind');
// Falls tailwind.php nicht existiert → bootstrap.php → uikit.php → plain.php

// Fragment aus builder nutzen
$html = TemplateEngine::renderFragment('ycb_elements/wrapper', $data, 'uikit');
?>
```

### Mit Fallback-Logik

```php
<?php
use FriendsOfREDAXO\Builder\TemplateEngine;

// Render mit Default-Fallback
$framework = $GLOBALS['framework'] ?? 'uikit';
$template = TemplateEngine::hasTemplate('cards', $framework) 
    ? TemplateEngine::render('cards', $data, $framework)
    : TemplateEngine::render('cards', $data, 'plain');

echo $template;
?>
```

---

## Best Practices

### 1. Config-Klassen nutzen (nie hardcoden)

❌ **Falsch:**
```php
$backgrounds = [
    'uk-background-default' => 'Default',
    'uk-background-muted' => 'Muted',
];
```

✅ **Richtig:**
```php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

$backgrounds = FrameworkConfig::getBackgroundChoices('uikit');
// → Erlaubt Extension Point Überrides
```

### 2. Element-Registry für Pfade nutzen

❌ **Falsch:**
```php
$elementPaths = [
    rex_path::addon('builder', 'elements'),
    rex_path::addon('klxm_elements', 'elements'),
];
```

✅ **Richtig:**
```php
use FriendsOfREDAXO\Builder\Config\ElementRegistry;

$paths = ElementRegistry::getElementPaths();
// → Dynamisch, Extension Point aware, skalierbar
```

### 3. TemplateEngine für Rendering nutzen

❌ **Falsch:**
```php
$html = file_get_contents("templates/$framework.php");
```

✅ **Richtig:**
```php
use FriendsOfREDAXO\Builder\TemplateEngine;

$html = TemplateEngine::render('element', $data, $framework);
// → Sicherer, mit Fallbacks, Framework-aware
```

### 4. Extension Points für Customization

❌ **Falsch:**
```php
// Addon A hardcodet Styles
// Addon B versucht zu überschreiben → Konflikt!
```

✅ **Richtig:**
```php
// Alle Addons registrieren via Extension Point
rex_extension::register('BUILDER_FRAMEWORK_OPTIONS', function($ep) {
    // Custom Styles hinzufügen
    return $ep->getSubject();
});
```

### 5. Framework-agnostisch Template-Logik

❌ **Falsch (UIkit-spezifisch):**
```php
$classes = 'uk-' . $elementData['padding'];
```

✅ **Richtig:**
```php
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

$prefix = FrameworkConfig::getCssPrefix($framework);
$classes = $prefix . $elementData['padding'];
// → Funktioniert mit uk-, bs-, oder custom prefixes
```

---

## Häufige Fragen

### Wie integriere ich ein neues CSS-Framework?

1. **Erstelle Templates** in deinem Addon:
   ```
   my_theme/
   └── elements/
       └── my_element/
           └── templates/
               ├── bootstrap.php
               ├── tailwind.php
               └── custom.php
   ```

2. **Registriere Optionen** in boot.php (FrameworkConfig Extension Point)

3. **Nutze Config-Klassen** in config.php statt Hardcoding

4. **Registriere Element-Pfade** (ElementRegistry Extension Point)

### Kann ich mehrere Framework-Templates gleichzeitig nutzen?

Ja! TemplateEngine lädt automatisch das beste verfügbare Template:
1. Exaktes Framework-Match (z.B. tailwind.php)
2. Fallback Bootstrap (bootstrap.php)
3. Fallback UIkit (uikit.php)
4. Fallback Plain (plain.php)

### Wie überschreibe ich Bundled-Elemente?

Nutze die ElementRegistry:
```php
rex_extension::register(
    'BUILDER_BUNDLED_ELEMENTS',
    function($ep) {
        $bundled = $ep->getSubject() ?? [];
        // Element entfernen
        $bundled = array_diff($bundled, ['section']);
        return $bundled;
    }
);
```

### Können externe Addons ihre eigenen Editor-Profile setzen?

Ja! Via `BUILDER_EDITOR_PROFILES` Extension Point:
```php
rex_extension::register(
    'BUILDER_EDITOR_PROFILES',
    function($ep) {
        if ('my_element' === $ep->getParam('element')) {
            return 'custom_profile';
        }
        return $ep->getSubject();
    }
);
```

### Wie teste ich Framework-Options lokal?

```php
// In einem Debug-Template
use FriendsOfREDAXO\Builder\Config\FrameworkConfig;

echo '<pre>';
echo 'UIkit Backgrounds:';
var_dump(FrameworkConfig::getBackgroundChoices('uikit'));
echo 'Bootstrap Backgrounds:';
var_dump(FrameworkConfig::getBackgroundChoices('bootstrap'));
echo '</pre>';
```

---

## Links

- **API.md**: [Detaillierte API-Referenz](API.md)
- **SCHEMA.md**: [JSON Schema & Config-Klassen](SCHEMA.md)
- **TUTORIAL.md**: [Schritt-für-Schritt Element erstellen](TUTORIAL.md)
- **CHANGELOG.md**: [Version 3.1.0 Änderungen](CHANGELOG.md)
