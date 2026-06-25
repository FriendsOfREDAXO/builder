---
name: yform-content-builder
description: Entwicklungshilfe fuer das REDAXO Addon builder mit Fokus auf Element-Configs, Feldtypen, Rendering und Datenintegritaet.
---

# Skill: Builder

## Wann nutzen

Nutze diesen Skill, wenn du im Addon `builder` arbeitest und mindestens eines davon anfasst:

- `lib/rex_yform_value_content_builder.php`
- `lib/Module.php`, `lib/ModuleBuilder.php`, `lib/Helper.php`
- `assets/content-builder.js`
- `elements/*/config.php`
- `elements/*/templates/*.php`
- Doku-Dateien wie `README.md`, `API.md`, `SCHEMA.md`, `DEV.md`

## Ziele

- Kompatible Erweiterungen fuer YForm-Werte und Modul-Integration liefern.
- Verhalten fuer neue Slices verbessern, ohne gespeicherte Daten stillschweigend umzuschreiben.
- Element-Overrides (project/data/addon) und merge/replace-Modus respektieren.

## Arbeitsregeln

1. REDAXO-Konventionen beachten.
2. Keine inline-JavaScript-Snippets in PHP-echo-Strings einbauen.
3. Bei API- oder Config-Erweiterungen immer Doku aktualisieren:
   - `README.md`
   - `API.md`
   - `SCHEMA.md`
   - `DEV.md`
4. Neue Config-Keys in Schema-Dateien spiegeln:
   - `element-config.schema.json`
   - `schema/element-config.schema.json`
5. UI-Logik sowohl in PHP-Renderpfaden als auch in `assets/content-builder.js` konsistent halten.

## Besondere Vorsicht

- `allowed_elements` darf die Elementliste nicht unbrauchbar machen: bei ungueltigen/stale Keys auf verfuegbare Elemente zurueckfallen.
- Verschachtelte Slices serialisieren nur im korrekten Scope, um Datenverlust zu vermeiden.
- Legacy-HTML-Migration darf bestehende Inhalte nur kontrolliert umwandeln.

## Schnellcheck vor Abschluss

- Syntax plausibel (PHP/JSON/JS).
- Keine neuen offensichtlichen Diagnosen.
- Doku und Code sprechen dieselbe Sprache (gleiche Optionen, gleiche Prioritaeten).
