---
name: create-elements
description: Verbindliche Regeln und Schrittfolge zum Erstellen neuer builder Elemente.
---

# Skill: Elemente erstellen

## Wann nutzen

Nutze diesen Skill immer dann, wenn neue Elemente unter elements angelegt oder bestehende Elemente strukturell erweitert werden.

## Ziel

Neue Elemente sollen konsistent, sauber dokumentiert und ohne Seiteneffekte auf bestehende Inhalte eingefuehrt werden.

## Pflichtstruktur pro Element

1. Ordner anlegen: elements/<element_key>
2. Pflichtdatei: elements/<element_key>/config.php
3. Mindestens ein Template unter elements/<element_key>/templates
4. Optional: Sprachdateien unter elements/<element_key>/lang

## Regeln fuer element_key

- Nur Kleinbuchstaben, Zahlen und Unterstriche.
- Stabil halten, da der Key in gespeicherten Slice-Daten vorkommt.
- Keine nachtraegliche Umbenennung ohne Migration.

## Regeln fuer config.php

- Muss ein gueltiges PHP-Array zurueckgeben.
- label, icon und fields klar definieren.
- category setzen, damit die Gruppierung im Editor stabil bleibt.
- Neue Root-Optionen immer im JSON-Schema nachziehen.
- Bei Nesting-Verhalten explizit setzen:
  - prevent_self_nesting bevorzugt
  - allow_self_nesting nur fuer Rueckwaertskompatibilitaet

## Regeln fuer Felder

- Feldnamen stabil halten, keine stillen Renames.
- Defaults sparsam und nachvollziehbar setzen.
- Bei choice/radio eindeutige values verwenden.
- Bei sichtbarkeitsabhaengigen Feldern visible_if statt ad-hoc JS bevorzugen.
- Berechtigungen ueber perm serverseitig steuern.

## Regeln fuer Templates

- Ausgaben konsequent escapen.
- Keine Funktionsdefinitionen im Template.
- Leere/ungueltige Werte defensiv abfangen.
- Framework-spezifische Klassen nur im passenden Template verwenden.
- Fallback ueber plain.php sicherstellen.

## Einfuehrungs-Checkliste

1. Element erscheint im Insert-Menue wie erwartet.
2. Formular speichert und laedt stabil.
3. Rendern funktioniert im Ziel-Framework und im Fallback.
4. Keine Datenverluste bei verschachtelten Slices.
5. Doku aktualisiert:
   - README.md
   - API.md
   - DEV.md
   - SCHEMA.md
6. Schema aktualisiert:
   - element-config.schema.json
   - schema/element-config.schema.json

## Nicht tun

- Keine Umgebungsdetails in Doku oder Instruktionen hart codieren.
- Keine Breaking Changes an bestehenden Slice-Strukturen ohne Migrationspfad.
- Keine duplizierte Logik in PHP und JS mit widerspruechlichem Verhalten.
