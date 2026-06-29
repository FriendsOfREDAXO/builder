# Builder

Page und Contentbuilder für REDAXO. Modular und erweiterbar.

Komplexe Layouts oder Datenstrukturen visuell gestalten. Mit Builder ist das möglich.

## Redaktionshilfe

### Grundprinzip

Der Builder besteht aus einzelnen Elementen, die untereinander angeordnet werden.
Jedes Element bildet einen inhaltlichen Baustein, zum Beispiel:

- Überschrift
- Text
- Trenner
- Karten
- Spalten

Aus diesen Bausteinen wird der Inhalt Schritt für Schritt aufgebaut.

### So arbeitet ihr im Builder

#### 1. Neues Element hinzufügen

Über das Plus-Symbol kann an der gewünschten Stelle ein neues Element eingefügt werden.

Je nach Konfiguration stehen nur die Elemente zur Auswahl, die für diesen Bereich erlaubt sind.

#### 2. Element bearbeiten

Mit dem Stift-Symbol wird die Bearbeitung eines Elements geöffnet.

Dort lassen sich zum Beispiel anpassen:

- Texte
- Überschriften
- Bilder
- Abstände
- Hintergrundoptionen
- Layout-Einstellungen

Mit **Element übernehmen** werden die Änderungen in den Builder übernommen.

Wichtig: Dadurch ist das Element noch nicht endgültig gespeichert. Dauerhaft gespeichert wird erst, wenn das gesamte Formular oder der Datensatz gespeichert wird.

#### 3. Reihenfolge ändern

Mit den Pfeilen nach oben und unten können Elemente verschoben werden.

#### 4. Elemente löschen

Mit dem Papierkorb-Symbol wird ein Element entfernt.

### Arbeiten mit Spalten

Das Element **Spalten** enthält eigene Bereiche, in die wiederum weitere Elemente eingefügt werden können.

Wichtig dabei:

- Innerhalb von Spalten gelten dieselben Bearbeitungsregeln wie außerhalb.
- Auch dort können Elemente hinzugefügt, bearbeitet, verschoben, kopiert und gelöscht werden.
- Je nach Feld-Konfiguration können innerhalb von Spalten nur bestimmte Elemente erlaubt sein.

### Kopieren und Einfügen

Wenn Copy & Paste aktiviert ist, kann ein Element kopiert und an anderer Stelle wieder eingefügt werden.

Beim Kopieren wird der aktuelle Inhalt des Elements inklusive seiner Einstellungen übernommen. Das ist besonders nützlich, wenn ähnliche Abschnitte mehrfach gebraucht werden und nur kleine Anpassungen nötig sind.

Typischer Ablauf:

1. Element über das Kopieren-Symbol kopieren.
2. An der gewünschten Stelle das Plus-Menü öffnen.
3. **Element einfügen** auswählen.

Wichtig dabei:

- Das eingefügte Element ist zunächst eine Kopie des Originals und kann danach unabhängig weiterbearbeitet werden.
- Das Einfügen funktioniert auch innerhalb von Spalten, sofern der Zielbereich dieses Element erlaubt.
- Sichtbar und dauerhaft gespeichert wird die Kopie erst, wenn anschließend auch das gesamte Formular oder der Datensatz gespeichert wird.

### Kompaktmodus

Im Kompaktmodus wird der Builder platzsparender dargestellt. Das ist hilfreich bei langen Inhalten oder vielen verschachtelten Elementen.

### Speichern

Im Builder gibt es zwei Ebenen:

- **Element übernehmen**: übernimmt Änderungen nur in den aktuellen Builder-Inhalt.
- **Formular speichern** bzw. **Datensatz speichern**: speichert den gesamten Inhalt dauerhaft.

Wenn der Builder verlassen wird, ohne das gesamte Formular zu speichern, gehen ungespeicherte Änderungen verloren.

### Praktische Tipps

- Größere Inhalte Abschnitt für Abschnitt bearbeiten.
- Bei längeren Bearbeitungen regelmäßig den gesamten Datensatz speichern.
- Spalten nur dort einsetzen, wo wirklich ein mehrspaltiges Layout gebraucht wird.
- Nach Umbauten Reihenfolge und Abstände der Elemente prüfen.

### Tipps und Tricks

- Erst grob die Reihenfolge der Elemente aufbauen und Inhalte danach im Detail pflegen.
- Häufig genutzte Elemente lieber kopieren als neu anlegen, wenn Aufbau und Einstellungen ähnlich bleiben sollen.
- Bei langen Seiten den Kompaktmodus nutzen, um schneller durch den Inhalt zu navigieren.
- Nach dem Verschieben oder Einfügen kurz prüfen, ob Überschriften, Abstände und Spaltenaufteilung noch stimmig wirken.
- Bei verschachtelten Spalten zuerst die grobe Struktur anlegen und danach die Inhalte innerhalb der Spalten befüllen.
- Wenn ein Bereich unübersichtlich wird, lieber mehrere einfache Elemente verwenden als ein einzelnes Element zu stark zu überladen.
- Bilder und Texte möglichst direkt nach dem Einfügen eines Elements pflegen, damit der Aufbau später leichter nachvollziehbar bleibt.

### Wenn etwas nicht klappt

Wenn ein Element nicht wie erwartet reagiert oder eine Auswahl fehlt, liegt das oft an einer bewussten Einschränkung der Feld-Konfiguration.

In solchen Fällen:

- zuerst Eingabewerte prüfen
- dann den Datensatz einmal speichern und neu laden
- bei weiterhin bestehendem Problem die Administratoren informieren

Technische Hinweise, Installation, Starter-Addon und weiterführende Entwickler-Dokumentation stehen Administratoren auf einer separaten Doku-Seite zur Verfügung.
