# Builder

Page und Contentbuilder für REDAXO. Modular und erweiterbar.

Komplexe Layouts oder Datenstrukturen visuell gestalten. Mit Builder ist das möglich.

👋 Aber erstmal vorweg: Es ist kein Out-of-the-Box Pagebuilder. Das meiste muss man selbst beisteuern. Ihr entscheided was es kann und welche Elemente es geben wird. Zum Start gibt es ein paar Demo-Elemente mit denen Ihr spielen könnt. 
Es ist vollständig unabhängig von irgendwelchen Frameworks in der Ausgabe. 

## Leichter Einstieg

Für einen leichten Einstieg empfehlen wir direkt am Anfang:

1. Starter-Addon: https://github.com/FriendsOfREDAXO/builder_starter
	Hinweis: Das Starter-Addon ist aktuell nicht im REDAXO-Installer verfügbar. Bitte manuell von GitHub herunterladen oder über das ZIP-Installer-Addon als ZIP-Datei installieren.
2. Anschließend das Tutorial in diesem Addon lesen und Schritt für Schritt nachbauen (`TUTORIAL.md`).


## Features

- Page- und Content-Builder für REDAXO-Module und YForm-Felder
- Modulare Elemente mit konfigurierbaren Feldern, Gruppen und Einstellungen
- Settings-Modal mit gruppierten Fieldsets und konfigurierbarem Akkordeon-Verhalten
- Framework-Templates für UIkit, Bootstrap und Plain HTML bei den mitgelieferten bereits dabei. 
- Erweiterbar über eigene Elemente in Projekt- oder Addon-Pfaden
- Klare Extension-Point-Schnittstellen für Framework-, Theme- und Editor-Integration
- Medienausgabe über Media Manager mit virtuellen Typen (`cb_<preset>__<width>`)
- Automatische Integration von `media_negotiator` für moderne Bildformate, wenn vorhanden
- Repeater- und Nested-Elemente für komplexe Inhaltsstrukturen 😀
- Rollen- und Sichtbarkeitssteuerung über Feldkonfiguration 😀
- Modul-Generator im Backend zur schnellen REDAXO-Modulerstellung ⭐️
- Geführte YForm-Listen-Profile auf eigener Verwaltungsseite
- Profiltypen für Listen: Kontakte, News, Events, Produkte, Slides/Teaser und freie Listen
- Slides-/Teaser-Layout im Element `yform_list` (UIkit, Bootstrap und Plain)

## Einsatzbereiche

- Landingpages mit flexiblen Abschnittslayouts
- Datengetriebene Seiten mit YForm-Tabellen
- Wiederverwendbare Inhaltsbausteine für Redaktionen
- Projektübergreifende Komponentenbibliotheken
- Unterschiedliche Ausgaben möglich  z.B. HTML, JSON-LD, E-Mail-HTML, CSV was auch immer ihr wollt. 

## Architekturüberblick

- Kernaddon: `builder`
- Elementdefinitionen: `elements/<element_key>/config.php`
- Ausgabe-Templates: `elements/<element_key>/templates/*.php`
- Feldtypen und Rendering: `lib/`, `lib/fields/`, `assets/content-builder.js`
- Schema-Validierung: `element-config.schema.json` und `schema/element-config.schema.json`

## Erweiterbarkeit

Builder ist auf Erweiterung ausgelegt:

- Eigene Elementpfade über `BUILDER_ELEMENT_PATHS`
- Eigene Presets über `BUILDER_MEDIA_TYPE_PRESETS`
- Theme-Provider über `BUILDER_THEME_*`
- Framework-Optionen über `BUILDER_FRAMEWORK_OPTIONS`
- Editorprofile über `BUILDER_EDITOR_PROFILES`

## Feldattribute

Elementfelder in `elements/<element_key>/config.php` unterstützen gemeinsame und feldtypspezifische Konfigurationen.

Gemeinsame Attribute:

- `label` Beschriftung im Backend-Editor
- `notice` Hilfetext unter dem Feld
- `default` Standardwert für neue Elemente
- `perm` Rollenbasierte Sichtbarkeit (`admin`, `editor|power`, `['editor', 'admin']`)
- `visible_if` Feld nur bei erfüllter Bedingung anzeigen

Beispiel `visible_if`:

```php
'subtitle' => [
	'type' => 'text',
	'label' => 'Untertitel',
	'visible_if' => ['show_subtitle' => '1'],
]
```

### Beliebige HTML-Attribute via `attributes`

Für die Feldtypen `text`, `textarea`, `checkbox`, `select`, `choice` kann ein `attributes`-Array gesetzt werden.

```php
'text' => [
	'type' => 'textarea',
	'label' => 'Text',
	'rows' => 8,
	'attributes' => [
		'maxlength' => '500',
		'data-autoresize' => 'true',
		'aria-describedby' => 'hint-text',
		'style' => 'background:#fffbe6; border-color:#f0c040;',
	],
]
```

Hinweise:

- Alle Attributschlüssel und Werte werden serverseitig escapt.
- Gesperrte Attribute (werden ignoriert): `name`, `type`, `value`, `checked`, `selected`, `id`, `rows`
- `attributes` wirkt auf das Backend-Formularfeld. Die Frontend-Ausgabe steuerst du weiterhin über das Template.

### Feldtypspezifische Attribute (Auszug)

- `text`: `placeholder`, `attributes`
- `textarea`: `rows`, `attributes`
- `checkbox`: `default`, `attributes`
- `select`: `options`, `default`, `attributes`
- `choice`: `choices`, `default`, `multiple`, `selectpicker`, `choice_colors`, `choice_icons`, `attributes`
- `be_media`: `allowed_types`
- `cke5`/`tinymce`: `profile`, `rows`
- `repeater`: `add_label`, `view`, `grid_columns`, `fields`, `item_modal`

### YForm-Value-Optionen

Für das YForm-Feld `content_builder` kannst du zusätzliche Feld-Optionen direkt in der YForm-Definition setzen. Besonders nützlich ist `wrapper_max_width` für eine optionale maximale Breite des Wrappers.

Beispiel:

```php
'wrapper_max_width' => '1140px',
```

Alternativ wird auch `max_width` akzeptiert. Wenn einer der beiden Werte gesetzt ist, erhält der Wrapper der Klasse `.yform-content-builder` ein Inline-`max-width` plus automatische Zentrierung.

Für Module ist dieselbe Option in der PHP-API beschrieben: siehe [API.md](API.md) im Abschnitt zur Full Builder API.

## Medienmodell

- Basistyp: `content_builder`
- Virtuelle Typen: `cb_<preset>__<width>`
- Preset-Auflösung zur Laufzeit über `MEDIA_MANAGER_FILTERSET`
- Optionaler `negotiator`-Effekt als letzter Effekt in der Kette

## Installation

1. Addon `builder` installieren und aktivieren.
2. Pflicht-Abhängigkeit sicherstellen: `media_manager`.
3. Optionale Addons je nach Einsatz:
	- `yform` für YForm-Felder und YForm-Listen-Profile
	- `focuspoint` für Focuspoint-Cropping (ohne Focuspoint mit Crop-Fallback)
	- `media_negotiator` für moderne Bildformate
4. Für die Demo und textbasierte Starter-Elemente wird ein installiertes `tinymce`-Addon empfohlen.
5. In den Einstellungen Framework und Elementquellen konfigurieren.
6. Optional Module über `Builder -> Module` generieren.

## YForm-Listen-Profile

Die Profile für das Element `yform_list` werden über eine eigene Seite gepflegt:

- `Builder -> YForm-Listen-Profile`

Beim Anlegen eines Profils wird zuerst der gewünschte Listentyp ausgewählt. Danach werden passende Felder und sinnvolle Defaults gesetzt, damit nicht alle Optionen manuell konfiguriert werden müssen.

Funktionen der YForm-Listen-Profile:

- Profilbezogene Feldzuordnung für Titel, Teaser, Bilder, Linkparameter und Sortierung
- Typabhängige Zusatzfelder für Kontakte (z. B. Rolle, Telefon, E-Mail)
- Typabhängige Zusatzfelder für Produkte (Preis, alter Preis, Währung, Badge, Verfügbarkeit)
- Vorgabewerte für sinnvolle Layouts je Profiltyp (z. B. `slides` für Teaser-/Produktdarstellung)
- Einheitliche Ausgabe über die mitgelieferten Templates für UIkit, Bootstrap und Plain

Verfügbare Profiltypen:

- Freie Liste
- Kontakte
- News / Artikel
- Events / Termine
- Produkte
- Slides / Teaser-Slider

## Ausblick

Geplant ist ein eigener Modus **YForm NoCode**, mit dem nicht nur Listen, sondern auch Detailseiten im Builder ohne eigene Template-Programmierung aufgebaut werden können.

Zielbild:

- Layout weiterhin visuell im Builder erstellen (wie bisher)
- Statt statischer Inhalte optional je Feld eine YForm-Spalte binden
- Umschaltbar pro Feld: **manuell** oder **dynamisch aus Tabelle**
- Tabellenkontext und Datensatzquelle zentral definieren (z. B. fester Datensatz, URL-Parameter, Listenkontext)
- Medien- und Textfelder gleich bedienen, aber wahlweise mit Spalten-Mapping statt freier Eingabe

Damit wird ein Workflow möglich, der dem bekannten CMS-Pattern aus Webflow ähnelt: Struktur und Design bleiben frei, der Content wird pro Elementfeld an Datenquellen gekoppelt.

## Dokumentation

- API: [API.md](API.md)
- Entwicklerleitfaden: [DEV.md](DEV.md)
- Schema-Referenz: [SCHEMA.md](SCHEMA.md)
- Tutorial: [TUTORIAL.md](TUTORIAL.md)
- Änderungen: [CHANGELOG.md](CHANGELOG.md)

## Autor

[Friends of REDAXO ](https://github.com/skerbis](https://friendsofredaxo.github.io)

## Lizenz

[MIT Lizenz](LICENSE.md)

## Credits

**Projekt-Lead**

[Thomas Skerbis](https://github.com/skerbis)

**Sponsored by**

[KLXM Crossmedia](https://klxm.de)
