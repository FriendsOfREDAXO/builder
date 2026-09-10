# Changelog

Alle wesentlichen Änderungen an diesem Projekt werden hier dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [1.2.0-beta.2] - 2026-09-10

### Added

- **`MediaAltResolver::resolve()` erkennt jetzt "dekorativ" markierte Mediapool-Bilder.** Prüft sowohl das klassische, optionale `mediaplace`-Metainfo-Feld `med_alt_decorative` als auch dessen neueres JSON-Metadaten-System (eigener Alt-Feld-Widget-Typ) und liefert für als dekorativ markierte Bilder immer ein leeres `alt`-Attribut (WCAG-korrekt) – gewinnt bewusst gegen jeden manuellen oder aufgelösten Alt-Text. Zusätzlich liest `resolve()` jetzt auch `mediaplace`s eigenes, sprachrichtiges JSON-Alt-Feld (vorher nur das klassische `med_alt`-Metainfo-Feld). `mediaplace` bleibt für `builder` komplett optional – ohne das Addon oder ohne die jeweiligen Metainfo-Felder verhält sich `resolve()` unverändert wie zuvor. Vorher war diese Erkennung nur in einzelnen externen Addon-Elementen (z. B. `ncss`) lokal nachgebaut; jetzt profitieren alle Elemente mit einem `be_media`-Feld automatisch davon, ohne eigene Dekorativ-Logik zu duplizieren. Betrifft `lib/MediaAltResolver.php`.

### Documentation

- **`MediaAltResolver` erstmals dokumentiert** – vollständige Referenz (Prioritätsreihenfolge der Alt-Text-Auflösung, Dekorativ-Erkennung, Beispielaufruf) in `API.md` (Abschnitt „Helper-Klassen") und `SCHEMA.md` (Abschnitt „Media-Output-Konvention"), plus ein Hinweis direkt beim `be_media`-Feldtyp in `API.md`. War bisher trotz produktiver Nutzung durch mehrere externe Addon-Elemente nirgends beschrieben.
- **Neuer vollständiger Abschnitt „Vollständiges Integrationsbeispiel: Eigenes Addon mit eigenen Elementen" in `DEV.md`.** Die einzelnen Extension Points (`BUILDER_ELEMENT_PATHS`, `BUILDER_ELEMENT_MODE`, `BUILDER_MEDIA_TYPE_PRESETS`, freies `framework`-Textfeld) waren bereits einzeln referenziert, aber es gab kein durchgängiges Beispiel, wie ein externes Addon sie zu einem vollständigen, additiven Element-Set kombiniert (reales Referenzbeispiel: `ncss`s 17 eigene Elemente). Deckt außerdem zwei bislang undokumentierte Fallstricke ab: Element-*Labels* haben (anders als Element-*Keys*) keinen echten Namensraum-Mechanismus (Kollisionsgefahr im Element-Picker), und ein in `fields` definiertes, aber in keiner `field_groups`-Gruppe gelistetes Feld wird im Backend-Formular kommentarlos gar nicht gerendert.

### Fix

- **Kompaktmodus aus den Addon-Einstellungen galt nur für das YForm-Feld, nicht für das Builder-Modul auf der Struktur-Seite.** `ModuleBuilder` setzt die Klasse `compact-mode` jetzt wie das YForm-Template aus der Einstellung (per Option `compact_mode` übersteuerbar) und bekommt denselben Kompaktmodus-Schalter je Redakteur (localStorage). In beiden Editoren zeigt die Checkbox jetzt den wirksamen Zustand: ohne eigene Wahl gilt die Einstellung, eine eigene Wahl übersteuert sie. Betrifft `lib/ModuleBuilder.php`, `ytemplates/bootstrap/value.content_builder.tpl.php`, `assets/content-builder.css`.
- **Linkmap-/Medienpool-Auswahl landete im falschen Feld, wenn mehrere Elemente nacheinander eingefügt wurden.** Die Zähler für die generierten Widget-IDs (`REX_LINK_1001`, `REX_MEDIA_1` …) starteten in jedem AJAX-Request („Element hinzufügen") wieder bei derselben Basis, weil sie in `$GLOBALS` nur innerhalb eines Requests eindeutig sind. Zwei be_link-/be_media-Felder aus zwei Requests bekamen dadurch identische IDs; `getElementById()` traf dann immer nur das erste, meist unsichtbare Duplikat und das sichtbare Feld blieb leer. Die Zähler starten jetzt je Request auf einer zufälligen Basis (`FieldAbstract::counterBase()`, `rex_yform_value_content_builder::getNextMediaCounter()`). Reproduziert mit dem Original-Element `starter_cards`. Betrifft `lib/fields/FieldAbstract.php`, `lib/rex_yform_value_content_builder.php`.
- **Markdown-Querlinks mit Anker-Fragment (`[Text](Datei.md#anker)`) zwischen den Builder-Doku-Seiten (Seite „Dokumentation" im Backend) navigierten stumm zur Übersichtsseite statt zum Ziel.** `pages/docs.php` schreibt solche Links vor dem Markdown-Rendering auf interne Backend-Routen um (`rex_url::currentBackendPage(['func' => ...])`) – diese Methode liefert bereits HTML-escapte Query-Parameter (`&amp;` statt `&`) zurück, was beim direkten Einsetzen in Markdown zu doppeltem Escaping führte (`rex_markdown::parse()` escapt beim Rendern selbst nochmal): im gerenderten `href` stand am Ende `&amp;amp;func=...`, für den Browser ein anderer Parametername als `func` – die Navigation landete deshalb immer auf der Standard-Sektion. Betraf JEDEN Cross-Datei-Link in den Doku-Seiten mit mehr als einem Query-Parameter, nicht nur neue Anker-Links (per echtem Test auch bei einem bereits lange bestehenden `[DEV.md](DEV.md)`-Link ohne Anker reproduziert). Fix: die von `currentBackendPage()` zurückgegebene URL wird vor dem Einsetzen ins Markdown einmal zurück-dekodiert (`&amp;` → `&`), damit nur noch das abschließende `rex_markdown::parse()` sie escaped. Betrifft `pages/docs.php`.

## [1.2.0-beta.1] - 2026-09-08

### Added

- Neuer Extension Point `BUILDER_SLICE_PREVIEW_HTML`, an DREI Stellen, die einen Slice als Backend-Vorschau rendern: `Helper::renderSliceBackend()` (verschachtelte Slices, z. B. innerhalb eines `columns`-Elements), `ModuleBuilder::renderEditorSlice()` (das normale Top-Level-Slice-Formular, `.slice-rendered`) UND `ContentBuilderApi::renderSlice()` (der tatsächliche AJAX-Live-Preview-Endpunkt, der bei jeder Formulareingabe im Slice-Editor feuert – in der Praxis der meistgenutzte der drei Pfade, siehe `assets/content-builder.js` `renderSlice()`). Alle drei puffern ihr Template-Output jetzt statt direkt zu `echo`en und lassen es durch diesen Extension Point laufen, bevor es ausgegeben wird. Andere Addons können damit z. B. fremdes CSS-Framework-Markup (Bootstrap, ncss, ...) im Backend-Preview in ein eigenes Element (z. B. per Shadow DOM) einbetten, ohne `builder` selbst zu verändern. Betrifft `lib/Helper.php`, `lib/ModuleBuilder.php`, `lib/Api/ContentBuilderApi.php`.
- **Theme-Kontext (Einstellungen > Content Builder, „table_themes"-Zuordnung) wird jetzt auch im AJAX-Live-Preview gesetzt.** Neue zentrale Methode `ThemeProviderBridge::applyThemeContextForTable(string $tableName)` kapselt die Tabelle-zu-Theme-Auflösung; `ContentBuilderApi::renderSlice()` ruft sie jetzt auf, bevor ein Element-Template inkludiert wird. Der Tabellenname kommt als neuer `table_name`-Parameter vom Client mit (`data-table-name`-Attribut im `content_builder`-Formular-Wrapper, `getTemplateVars()`/Template ergänzt) – vorher hatte dieser Endpunkt gar keinen Bezug zur aktuellen YForm-Tabelle und Addons wie ncss konnten ihr zugewiesenes Theme nie sehen. Betrifft `lib/Api/ContentBuilderApi.php`, `lib/Config/ThemeProviderBridge.php`, `lib/rex_yform_value_content_builder.php` (`getTemplateVars()`), `ytemplates/bootstrap/value.content_builder.tpl.php`, `assets/content-builder.js`.

### Changed

- **Framework-Feld im `content_builder`-YForm-Feldtyp ist jetzt ein freies Textfeld statt einer festen `choice`-Auswahl** (`bootstrap`/`uikit`/`tailwind`/`plain`). Der Wert steuert nur, welcher `templates/<framework>.php`-Dateiname pro Element zuerst gesucht wird (Fallback-Kette in `renderSlice()`: `<framework>` → `plain` → `uikit` → `bootstrap`) – war aber technisch nie auf diese vier Werte beschränkt, `ThemeProviderBridge::normalizeFramework()` (Extension Point `BUILDER_FRAMEWORK_NORMALIZE`) akzeptiert bereits jeden String. Andere Addons mit eigenen `templates/<eigener-name>.php`-Dateien in ihren Elementen (z. B. ein CSS-Framework-Bundle wie ncss) können ihren Framework-Namen jetzt direkt im Feld eintragen, ohne dass `builder` ihn erst in einer festen Liste kennen muss. Bestehende gespeicherte Werte bleiben unverändert kompatibel (beide Feldtypen speichern denselben String). Betrifft `lib/rex_yform_value_content_builder.php` (`getDefinitions()`).

## [1.1.1] - 2026-09-02

> **Voraussetzung: [MediaPlace](https://github.com/FriendsOfREDAXO/mediaplace) ≥ 2.0.0.** Wer MediaPlace einsetzt, sollte vor diesem Update auf Version 2.0.0 oder neuer aktualisieren (siehe "Fix" unten) – mit einer älteren MediaPlace-Version wird das Overlay sonst nicht mehr erkannt und es öffnet sich wieder der klassische Medienpool-Popup.

### Fix

- **MediaPlace-Erkennung nutzte noch das alte globale JS-Objekt `MP3`:** `window.rex5MediaplaceBridge` in `assets/js/mediaplace-bridge.js` prüfte auf `MP3.open()`/`MP3.openFile()`, MediaPlace hat sein globales Overlay-Objekt seit Version 2.0.0 aber in `MP` umbenannt. Dadurch öffnete sich bei aktuellem MediaPlace wieder der klassische Medienpool-Popup statt des Overlays. Auf `MP` aktualisiert.

## [1.1.0] - 2026-08-28

### Added

- MediaPlace-Weiche für alle Medien-Picker (`be_media`-Feld, SmartLinkField-Medienauswahl): Ist das separate `mediaplace`-Addon (FriendsOfREDAXO) installiert und aktiv (`window.MP3`), öffnet sich dessen modernes Overlay statt des klassischen Medienpool-Popups – gleiches Muster wie bei `mform` (siehe dessen `assets/js/mediaplace-bridge.js`), eigene, unabhängige Bridge-Datei `assets/js/mediaplace-bridge.js`. Ohne MediaPlace bleibt das klassische Popup unverändert die Voreinstellung.

### Fix

- `BUILDER_FIELDS`-Extension-Point war wirkungslos: `FieldRegistry::ensureInitialized()` rief `rex_extension::registerPoint()` auf, schrieb den Rückgabewert aber nie zurück in die interne Feldliste. Dadurch kamen über diesen Extension Point registrierte/überschriebene Feldtypen (siehe `API.md`, Abschnitt „Per Extension Point“) nie an. Rückgabewert wird jetzt wieder `self::$fields` zugewiesen (`lib/fields/FieldRegistry.php`).
- Element "Bild" (`elements/image/config.json`): Feld `media` nutzte den Typ `media`, für den kein Feldtyp registriert war und das Feld daher still auf ein reines Text-Eingabefeld zurückfiel. Auf den vorhandenen, registrierten Typ `be_media` umgestellt.

## [1.0.3] - 2026-08-09

### Changed

- UX im Content-Builder verbessert: kurze, klare und mehrsprachige Button-Bezeichnungen für Element- und Formularspeicherung.
- Hinweistexte für Bearbeitungs- und Speicherfluss vereinheitlicht und i18n-fähig gemacht.
- Fixierte YForm-Speicherleiste auf Positionierung reduziert, damit das Original-Design erhalten bleibt.

### Added

- Neues i18n-Mapping (`BUILDER_I18N`) für clientseitige Hinweise und Bestätigungsdialoge im Builder-JavaScript.

### Behavior

- Element-Speichern vor Formular-Speichern wird im Workflow nun klar erzwungen: Solange ein Element-Editor offen ist, bleibt Formular-Speichern gesperrt und zeigt eine eindeutige Meldung.
- Nested-Editor-Workflow verallgemeinert: Das Freigeben von Speichern/Übernehmen nach „Element speichern“ funktioniert jetzt hierarchiebasiert für alle verschachtelten Elemente, inklusive zukünftiger eigener Columns-ähnlicher Typen.

## [1.0.1] - 2026-08-07

### Changed

- Modulseite aufgeteilt: eigene Bereiche für Buildermodul (Full Builder) und Einzelmodule inkl. klarer Übersichtsseite als Einstieg.
- Demo-Flow verschlankt: direkter Einstieg in den Editor, kürzere Hinweistexte und fokussierter Button "Eigene Elemente testen".
- Demo-Elementauswahl über expliziten Test-Button statt klassischem Formular-Submit, um irreführende Navigation in die Struktur zu vermeiden.

### Fix

- `content_builder`-Feld: Standard-Datenbanktyp von `text` (max. 65.535 Byte) auf `mediumtext` (max. 16 MB) umgestellt. Bei umfangreichen eingefügten Inhalten (z. B. Word-Paste) konnte die JSON-Slice-Struktur beim Speichern abgeschnitten werden, wodurch das Feld nach dem erneuten Öffnen leer erschien.

## [1.0.0-beta8] - 2026-07-23


### Repeater-Modals und Farb-UX

- JS refactored

## [1.0.0-beta7] - 2026-07-12

### Repeater-Modals und Farb-UX

- Neues Feld `color` im Builder ergänzt (kompakter Color-Picker mit Hex-Anzeige).
- Repeater-Modals unterstützen jetzt `modal_size` (`sm`, `md`, `lg`, `xl`, `full`).
- Grundlage für breitere, element-spezifische Optionen-Modals (z. B. im ECharts-Element) geschaffen.

## [1.0.0-beta3] - 2026-06-26

### YForm-Listen-Profile und Doku

- Profilverwaltung um Produkt-Mappings (Preis, alter Preis, Währung, Badge, Verfügbarkeit) erweitert.
- Templates für UIkit, Bootstrap und Plain um Produktausgabe ergänzt.
- Eigene Profilseite für YForm-Listen mit Hero-Einleitung und verbessertem Styling.
- README um Funktionsbeschreibung und Ausblick zum Modus "YForm NoCode" ergänzt.

## [1.0.0-beta2] - 2026-06-25

### Demo-Editor und Layouts

- Builder-Demo als echter Editor mit vorgefüllten Demo- und Default-Elementen.
- Spaltenlayout mit verschachtelter Struktur in der Demo ergänzt.
- Unterschiedliche Hintergründe für äußere und innere Layout-Ebenen.

## [1.0.0-beta1] - 2026-06-25

### 1st Beta

- Erstveröffentlichung des Addons `builder`.
- Page- und Contentbuilder für REDAXO mit modularen Elementen.
- Integration für YForm und REDAXO-Module.
- Erweiterbarkeit über Extension Points und externe Elementpfade.
- Framework-Templates für UIkit, Bootstrap und Plain HTML.
- Medienmodell mit `content_builder` und virtuellen Typen `cb_<preset>__<width>`.
- Dokumentation in `README.md`, `API.md`, `DEV.md`, `SCHEMA.md` und `TUTORIAL.md`.
