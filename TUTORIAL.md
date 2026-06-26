# Builder Tutorial

Dieses Tutorial ist auf einen schnellen und realistischen Einstieg ausgelegt:

1. Start mit dem Starter-Addon
2. Übernahme ins project-Addon
3. Aufbau eines eigenen Addons

So kommst du schnell produktiv, ohne später in eine Sackgasse zu laufen.

## Bevor du startest

Voraussetzungen:

- Addon builder ist installiert und aktiviert
- Addon media_manager ist installiert und aktiviert
- Optional, aber empfohlen: tinymce für Textfelder

Wichtig zum Starter-Addon:

- Repository: https://github.com/FriendsOfREDAXO/builder_starter
- builder_starter ist aktuell nicht im REDAXO-Installer gelistet
- Installation daher manuell per Download oder über das ZIP-Installer-Addon

## Empfohlener Weg

### Phase 1: Sofort loslegen mit builder_starter

1. builder_starter installieren
2. In Builder Einstellungen die Elementquelle builder_starter aktivieren
3. Im Backend die Demo-Elemente öffnen und verstehen
4. Erst danach eigene Elemente ableiten

Damit bekommst du sofort lauffähige Beispiele für:

- einfache Text-Elemente
- Medienfelder
- Repeater
- Smart Link
- virtuelle Bildtypen

### Phase 2: In dein Projekt überführen

Danach entscheidest du, wo deine produktiven Elemente leben sollen:

1. project-Addon
2. eigenes Addon

Beides ist korrekt. Die Entscheidung ist organisatorisch.

## Variante A: project-Addon

Gut, wenn die Elemente nur in genau diesem Projekt genutzt werden.

Empfohlene Struktur:

```text
redaxo/src/addons/project/elements/
  hero/
    config.php
    templates/
      plain.php
      bootstrap.php
      uikit.php
    lang/
      de_de.lang
      en_gb.lang
```

Registrierung in redaxo/src/addons/project/boot.php:

```php
<?php

rex_extension::register('BUILDER_ELEMENT_PATHS', static function (rex_extension_point $ep) {
    $paths = $ep->getSubject();
    if (!is_array($paths)) {
        $paths = [];
    }

    $paths['project'] = rex_path::addon('project', 'elements');

    return $paths;
});

rex_extension::register('BUILDER_ELEMENT_MODE', static function () {
    return 'merge';
});
```

### Sonderfall Theme-Ordner (ohne eigenes REDAXO-Addon)

Wenn ihr das Theme-Addon nutzt und eure Elemente im Theme-Ordner pflegt, nutzt diesen Aufbau:

1. `theme/private/builder/theme_elements`

Beispiel in `theme/private/inc/functions.php`:

```php
<?php

if (rex_addon::get('builder')->isAvailable()) {
    rex_extension::register('BUILDER_ELEMENT_PATHS', static function (rex_extension_point $ep) {
        $paths = $ep->getSubject();
        if (!is_array($paths)) {
            $paths = [];
        }

        // Hier setzt du den Key: 'theme'
        $paths['theme'] = rex_path::base('theme/private/builder/theme_elements');

        return $paths;
    });
}
```

Wichtig: Der Key wird in genau dieser Zeile gesetzt:

```php
$paths['theme'] = rex_path::base('theme/private/builder/theme_elements');
```

Optional kannst du den Modus ebenfalls im Theme anmelden:

```php
rex_extension::register('BUILDER_ELEMENT_MODE', static function (): string {
    return 'merge';
});
```

## Variante B: eigenes Addon

Gut, wenn du Elemente zwischen Projekten wiederverwenden oder veröffentlichen möchtest.

Empfohlene Struktur:

```text
redaxo/src/addons/dein_addon/
  boot.php
  install.php
  package.yml
  elements/
    hero/
      config.php
      templates/
        plain.php
        bootstrap.php
        uikit.php
      lang/
        de_de.lang
        en_gb.lang
```

Registrierung in redaxo/src/addons/dein_addon/boot.php:

```php
<?php

$addonKey = 'dein_addon';

rex_extension::register('BUILDER_ELEMENT_PATHS', static function (rex_extension_point $ep) use ($addonKey) {
    $paths = $ep->getSubject();
    if (!is_array($paths)) {
        $paths = [];
    }

    $paths[$addonKey] = rex_path::addon($addonKey, 'elements');

    return $paths;
});

rex_extension::register('BUILDER_ELEMENT_MODE', static function () {
    return 'merge';
});
```

## Merge oder Replace

- merge: Core-Elemente plus deine Elemente
- replace: nur deine Elemente

Für den Start fast immer merge nutzen. replace nur, wenn du bewusst einen komplett eigenen Elementkatalog willst.

## Erstes eigenes Element auf Basis von builder_starter

Der schnellste Weg ist Kopieren und Anpassen.

Beispiel:

1. Nimm aus builder_starter das Element text_block als Vorlage
2. Kopiere in project/elements/teaser_text oder in dein eigenes Addon
3. Passe Label, Felder und Templates an

Beispiel für config.php:

```php
<?php

use FriendsOfREDAXO\Builder\Helper;

$_i18n = Helper::elementTranslator('teaser_text');

return [
    'label' => $_i18n('label', 'Teaser Text'),
    'icon' => 'fa-align-left',
    'category' => 'content',
    'fields' => [
        'headline' => [
            'type' => 'text',
            'label' => $_i18n('field_headline', 'Überschrift'),
        ],
        'text' => [
            'type' => 'textarea',
            'label' => $_i18n('field_text', 'Text'),
            'rows' => 6,
            'attributes' => [
                'maxlength' => '800',
                'data-editor' => 'simple',
            ],
        ],
    ],
];
```

Beispiel für templates/bootstrap.php:

```php
<?php

$headline = (string) ($elementData['headline'] ?? '');
$text = (string) ($elementData['text'] ?? '');
?>

<section class="panel panel-default">
    <div class="panel-body">
        <?php if ($headline !== ''): ?>
            <h3><?= rex_escape($headline) ?></h3>
        <?php endif; ?>

        <?php if ($text !== ''): ?>
            <div><?= nl2br(rex_escape($text)) ?></div>
        <?php endif; ?>
    </div>
</section>
```

## Virtuelle Bildtypen richtig nutzen

Wenn dein Element Bilder rendert, nutze virtuelle Typen statt harter Typnamen.

```php
<?php

use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

$media = (string) ($elementData['image'] ?? '');
$url = '';

if ($media !== '') {
    $type = MediaTypeRegistry::buildVirtualType('bstarter_hero_16_9', 1440);
    $url = rex_media_manager::getUrl($type, $media);
}
```

## Modul-Einsatz

Input:

```php
<?php

use FriendsOfREDAXO\Builder\Module;

echo Module::createByValueId('teaser_text', 1, 'bootstrap')->renderInput();
```

Output:

```php
<?php

use FriendsOfREDAXO\Builder\Module;

echo Module::createByValueId('teaser_text', 1, 'bootstrap')->renderOutput();
```

## YForm-Einsatz

Ein content_builder Feld in YForm anlegen und das Element freigeben.

Frontend-Ausgabe:

```php
<?php

use FriendsOfREDAXO\Builder\Helper;

echo Helper::outputDataset($dataset, 'content', 'bootstrap');
```

## Troubleshooting

Wenn dein Element nicht erscheint:

1. Elementquelle in Builder Einstellungen aktiviert?
2. Pfad in BUILDER_ELEMENT_PATHS korrekt?
3. Modus merge oder replace passend gesetzt?
4. config.php liefert ein gültiges Array?

## Nächste Schritte

1. Ein Starter-Element vollständig kopieren und umbauen
2. Ein eigenes Repeater-Element ergänzen
3. Für jedes produktive Element drei Templates pflegen: plain, bootstrap, uikit
4. Sprachdateien pro Element einführen

## Weiterführende Dokumente

- README.md
- API.md
- DEV.md
- SCHEMA.md
- CHANGELOG.md
