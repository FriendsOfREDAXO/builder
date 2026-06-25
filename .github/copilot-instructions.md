# Copilot Instructions für builder

Diese Hinweise gelten nur innerhalb dieses Addons.

## Fokus (Release 1.0.0)

- REDAXO Addon `builder` stabil erweitern.
- Rückwärtskompatibilität beachten.
- Konfigurationen und Doku synchron halten.

## Coding-Regeln

1. Bevorzuge REDAXO-Core-APIs statt nativer Loesungen, wenn passende APIs existieren.
2. Halte Aenderungen klein und zielgerichtet; keine unnoetigen Refactorings.
3. Nutze PSR-12-kompatiblen Stil.
4. Escape Ausgaben in Templates konsequent (`rex_escape`).
5. Keine stillen Breaking Changes bei gespeicherten Builder-Daten.

## Pflicht bei Feature-Updates

Wenn neue Optionen/Keys eingefuehrt werden (z. B. Feld- oder Element-Config):

- Codepfade anpassen (PHP + ggf. JS)
- API-Doku erweitern (`API.md`)
- Nutzerdoku erweitern (`README.md`)
- Entwicklerdoku erweitern (`DEV.md`)
- Schema-Doku erweitern (`SCHEMA.md`)
- JSON-Schema aktualisieren (`element-config.schema.json`, `schema/element-config.schema.json`)

## Tests und Verifikation

- Nutze die in der Umgebung verfuegbaren Werkzeuge (lokal oder CI).
- Falls statische Analyse/Tests nicht verfuegbar sind, zumindest betroffene Dateien auf Diagnosen pruefen.
- Bei Aenderungen an `assets/content-builder.js` auf Serialisierung und Nesting-Verhalten achten.

## Spezifisch fuer Nesting-Regeln

- Self-Nesting immer konsistent aufloesen:
  1. Modul-Option
  2. YForm-Feldoption
  3. Element-Config
- Bestehende Inhalte nie automatisch umstrukturieren.

## Spezifisch fuer neue Elemente

Wenn du ein neues Element erstellst oder ein bestehendes Element strukturell erweiterst:

1. Verwende die Skill-Datei `.claude/skills/create-elements/SKILL.md` als verbindliche Checkliste.
2. Halte den element_key stabil (kein stilles Umbenennen bestehender Keys).
3. Liefere mindestens ein funktionsfaehiges Template plus Fallback-Strategie.
4. Ziehe neue Config-Keys immer in beiden Schema-Dateien nach.
5. Pruefe, dass Insert, Speichern, Reload und Rendern ohne Datenverlust funktionieren.
