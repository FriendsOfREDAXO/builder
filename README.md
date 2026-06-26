# Builder

Page und Contentbuilder für REDAXO. Modular und erweiterbar.

Komplexe Layouts oder Datenstrukturen visuell gestalten. Mit Builder ist das möglich.

👋 Aber erstmal vorweg: Es ist kein Out-of-the-Box Pagebuilder. Das meiste muss man selbst beisteuern. Ihr entscheided was es kann und welche Elemente es geben wird. Zum Start gibt es ein paar Demo-Elemente mit denen Ihr spielen könnt. 
Es ist vollständig unabhängig von irgendwelchen Frameworks in der Ausgabe. 


## Features

- Page- und Content-Builder für REDAXO-Module und YForm-Felder
- Modulare Elemente mit konfigurierbaren Feldern, Gruppen und Einstellungen
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
