# 🚀 Einsteiger-Tutorial: Dein erstes Content-Element

Willkommen! In diesem Tutorial erstellen wir **Schritt für Schritt** dein erstes eigenes Element für den Builder.

Wir bauen eine **„Team-Box"** (Bild, Name, Jobtitel).

📖 Weitere Referenzen: [API-Dokumentation](API.md) · [README](README.md)

---

## 📋 Vorbereitung

1. Stelle sicher, dass das Addon **Builder** installiert und aktiviert ist.
2. Wir arbeiten im Dateisystem. Du brauchst Zugriff auf deine REDAXO-Installation (per FTP oder VS Code).

---

## Schritt 1: Der richtige Ordner

REDAXO braucht einen Ort für deine eigenen Elemente. Der beste Platz ist das **Project-Addon** (oder ein eigenes Theme-Addon).

1. Gehe in den Ordner: `redaxo/src/addons/project/`
2. Erstelle dort einen Ordner namens `elements` (falls nicht vorhanden).
3. Erstelle darin einen Ordner für dein neues Element: `team_member`

Deine Struktur sollte so aussehen:
```text
redaxo/src/addons/project/elements/team_member/
```

Optional fuer Uebersetzungen pro Element:

```text
redaxo/src/addons/project/elements/team_member/lang/de_de.lang
redaxo/src/addons/project/elements/team_member/lang/en_gb.lang
```

Diese Sprachdateien werden automatisch geladen, sobald das Element verwendet wird.

---

## Schritt 2: Die Konfiguration (`config.php`)

REDAXO muss wissen, welche Felder dein Element hat. Das definieren wir in der `config.php`.

Erstelle die Datei: `redaxo/src/addons/project/elements/team_member/config.php`

Kopiere diesen Code hinein:

```php
<?php
return [
    // Der Name, der im Backend angezeigt wird
    'label' => 'Team Mitglied',
    
    // Ein Icon (von FontAwesome)
    'icon' => 'fa-user',
    
    // Die Eingabefelder
    'fields' => [
        'name' => [
            'type' => 'text',
            'label' => 'Name des Mitarbeiters',
        ],
        'job' => [
            'type' => 'text',
            'label' => 'Jobtitel',
        ],
        'photo' => [
            'type' => 'be_media', // Das REDAXO Medien-Widget
            'label' => 'Profilfoto',
        ]
    ]
];
```

---

## Schritt 2.1: Mehrsprachigkeit (i18n)

Wenn dein Element mehrsprachig sein soll, lege pro Element einen `lang`-Ordner an:

```text
redaxo/src/addons/project/elements/team_member/lang/de_de.lang
redaxo/src/addons/project/elements/team_member/lang/en_gb.lang
```

Beispielinhalt:

```ini
# de_de.lang
team_member_label = Team Mitglied
team_member_field_name = Name des Mitarbeiters
team_member_field_job = Jobtitel
team_member_field_photo = Profilfoto
```

```ini
# en_gb.lang
team_member_label = Team Member
team_member_field_name = Employee name
team_member_field_job = Job title
team_member_field_photo = Profile photo
```

In `config.php` nutzt du dann statt fixer Texte Uebersetzungskeys.

```php
<?php
use FriendsOfREDAXO\Builder\Helper;

$_ci = Helper::elementTranslator('team_member');

return [
    'label' => $_ci('label', 'Team Mitglied'),
    'icon' => 'fa-user',
    'fields' => [
        'name' => ['type' => 'text', 'label' => $_ci('field_name', 'Name des Mitarbeiters')],
        'job' => ['type' => 'text', 'label' => $_ci('field_job', 'Jobtitel')],
        'photo' => ['type' => 'be_media', 'label' => $_ci('field_photo', 'Profilfoto')],
    ],
];
```

Der `lang`-Ordner wird beim Laden des Elements automatisch eingebunden.

Wenn du direkt in bestehende Elemente schauen willst, nutze diese Referenzen im Addon:

1. `redaxo/src/addons/builder/elements/cards/config.php`
2. `redaxo/src/addons/builder/elements/cards/lang/de_de.lang`
3. `redaxo/src/addons/builder/elements/cards/lang/en_gb.lang`
4. `redaxo/src/addons/builder/elements/smart_link_showcase/config.php`
5. `redaxo/src/addons/builder/elements/smart_link_showcase/lang/de_de.lang`
6. `redaxo/src/addons/builder/elements/smart_link_showcase/lang/en_gb.lang`
7. `redaxo/src/addons/builder/elements/smart_links_multi_showcase/config.php`
8. `redaxo/src/addons/builder/elements/smart_links_multi_showcase/lang/de_de.lang`
9. `redaxo/src/addons/builder/elements/smart_links_multi_showcase/lang/en_gb.lang`

---

## Schritt 3: Die Ausgabe (`templates/bootstrap.php`)

Jetzt bestimmen wir, wie das Element aussieht (HTML).

1. Erstelle im Ordner `team_member` einen Unterordner `templates`.
2. Erstelle darin die Datei `bootstrap.php`.

Pfad: `redaxo/src/addons/project/elements/team_member/templates/bootstrap.php`

Kopiere diesen Code hinein:

```php
<?php
// 1. Daten aus den Variablen holen (mit Fallback, falls leer)
$name = $elementData['name'] ?? '';
$job = $elementData['job'] ?? '';
$photo = $elementData['photo'] ?? '';
?>

<!-- 2. Das HTML Gerüst -->
<div class="panel panel-default">
    <div class="panel-body text-center">
        
        <!-- Foto anzeigen (wenn eines ausgewählt wurde) -->
        <?php if ($photo): ?>
            <img src="<?= rex_url::media($photo) ?>" 
                 alt="<?= rex_escape($name) ?>" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 15px;">
        <?php endif; ?>

        <!-- Name und Job -->
        <h3><?= rex_escape($name) ?></h3>
        <p class="text-muted"><?= rex_escape($job) ?></p>
        
    </div>
</div>
```

🎉 **Glückwunsch!** Dein Element ist fertig programmiert.

### Schritt 3.1: Bilder über virtuelle Media-Manager-Typen ausgeben

Für Pixelbilder sollte die Ausgabe über virtuelle Typen laufen, nicht über statische Typnamen.

Beispiel im Template:

```php
<?php
use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

$photoUrl = '';
if ($photo !== '') {
    $type = MediaTypeRegistry::buildVirtualType('starter_cards_1_1', 800);
    $photoUrl = rex_media_manager::getUrl($type, $photo);
}
?>
```

So bleibt die Verarbeitung zentral über den `content_builder`-Effekt und ist kompatibel zur optionalen Format-Negotiation.

---

## Schritt 4: Element nutzen (Variante A: Im Modul)

Du möchtest dieses Element nun in einem REDAXO-Modul verwenden, damit Redakteure es auf einer Seite einfügen können.

1. Gehe im REDAXO Backend zu **Module**.
2. Erstelle ein neues Modul "Team Box".

**Eingabe (Input):**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

// Wir rufen unser Element 'team_member' auf.
// 'bootstrap' sorgt dafür, dass es im Backend hübsch aussieht.
echo Module::createByValueId('team_member', 1, 'bootstrap')->renderInput();
?>
```

**Ausgabe (Output):**
```php
<?php
use FriendsOfREDAXO\Builder\Module;

// Hier geben wir das Element aus.
// Du kannst hier auch 'uikit' oder 'tailwind' angeben, wenn du dafür Templates angelegt hast.
echo Module::createByValueId('team_member', 1, 'bootstrap')->renderOutput();
?>
```

Die alte Schreibweise mit `create('team_member', 'REX_VALUE[1]', 'bootstrap')` funktioniert weiterhin.

3. Speichere das Modul und füge es in einem Artikel (Slice) hinzu. Du solltest nun dein Formular sehen!

---

## Schritt 5: Element nutzen (Variante B: In YForm)

Du kannst das Element auch in einer YForm-Tabelle (z.B. für News oder Produkte) nutzen.

1. Gehe zum **YForm Table Manager**.
2. Wähle deine Tabelle.
3. Füge ein neues Feld hinzu.
4. Wähle den Typ: `content_builder`.
5. Bei "Erlaubte Elemente" kannst du nun dein "Team Mitglied" auswählen (oder leer lassen für alle).

**Frontend Ausgabe (PHP):**
```php
<?php
use FriendsOfREDAXO\Builder\Helper;

// A) Datensatz ist bereits vorhanden
echo Helper::outputDataset($dataset, 'mein_content_feld', 'bootstrap');

// B) Direkt ueber Tabelle + ID
echo Helper::outputDatasetById('rex_news', 42, 'mein_content_feld', 'bootstrap');

// C) YORM-Abfrage mit where-Bedingungen
$news = \Project\Model\News::query()
    ->where('status', 1)
    ->where('clang_id', rex_clang::getCurrentId())
    ->where('slug', 'mein-artikel')
    ->findOne();

if ($news !== null) {
    echo Helper::outputDataset($news, 'mein_content_feld', 'bootstrap');
}
?>
```

---

## 💡 Tipps für Profis (die es werden wollen)

*   **Eigene Icons:** Du kannst alle [FontAwesome 4.7 Icons](https://fontawesome.com/v4/icons/) nutzen.
*   **Mehr Felder:** Schau in die [API.md](API.md), welche Feldtypen es noch gibt (z. B. `textarea`, `choice`, `repeater`, `radio_image`, `color_swatches`).
*   **Frameworks:** Wenn du Tailwind nutzt, erstelle einfach eine `templates/tailwind.php` und nutze Tailwind-Klassen statt Bootstrap-Klassen.

Viel Erfolg beim Bauen! 🚀

---

# 🎓 Teil 2: Praxis-Workshop Repeater-Element

Jetzt bauen wir ein echtes, praxisnahes Repeater-Element: ein **Team-Grid** mit beliebig vielen Karten.

## Ziel

Wir erstellen `project/elements/team_grid/` mit:

1. Repeater für beliebig viele Team-Mitglieder
2. Eigenem Bearbeitungs-Modal pro Repeater-Eintrag
3. Trigger-Feldern (zeigen Felder nur bei bestimmten Optionen)
4. Layout-Einstellungen in Tabs
5. Optionalem Settings-Modal

---

## 1. Ordnerstruktur

```text
redaxo/src/addons/project/elements/team_grid/
├── config.php
└── templates/
    ├── bootstrap.php
    └── uikit.php
```

---

## 2. Komplette `config.php` (mit Repeater)

```php
<?php

return [
    'label' => 'Team Grid',
    'icon' => 'fa-users',
    'description' => 'Team-Mitglieder als Karten mit Repeater',
    'category' => 'content',

    'field_groups' => [
        'content' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['headline', 'intro', 'items'],
        ],
        'layout' => [
            'label' => 'Layout',
            'icon' => 'fa-columns',
            'fields' => ['columns', 'card_style', 'show_social'],
        ],
    ],

    'settings_modal' => [
        'label' => 'Erweiterte Optionen',
        'icon' => 'fa-sliders',
        'fields' => ['section_padding', 'section_bg'],
    ],

    'fields' => [
        'headline' => [
            'type' => 'text',
            'label' => 'Überschrift',
            'default' => 'Unser Team',
        ],
        'intro' => [
            'type' => 'tinymce',
            'label' => 'Einleitung',
            'profile' => 'default',
            'notice' => 'Optionaler Einführungstext über dem Grid.',
        ],

        'items' => [
            'type' => 'repeater',
            'label' => 'Team-Mitglieder',
            'add_label' => 'Mitglied hinzufügen',
            'item_label' => 'Mitglied',
            'collapsed' => false,
            'fields' => [
                'name' => [
                    'type' => 'text',
                    'label' => 'Name',
                ],
                'role' => [
                    'type' => 'text',
                    'label' => 'Rolle',
                ],
                'photo' => [
                    'type' => 'be_media',
                    'label' => 'Foto',
                ],
                'bio' => [
                    'type' => 'textarea',
                    'label' => 'Kurzbeschreibung',
                ],
                'profile_type' => [
                    'type' => 'choice',
                    'label' => 'Profil-Link Typ',
                    'choices' => [
                        '' => 'Kein Link',
                        'url' => 'Externe URL',
                        'email' => 'E-Mail',
                    ],
                    'default' => '',
                    'trigger_after' => 'profile_link|profile_mail',
                ],
                'profile_link' => [
                    'type' => 'text',
                    'label' => 'Profil-URL',
                    'show_if' => ['profile_type' => 'url'],
                ],
                'profile_mail' => [
                    'type' => 'text',
                    'label' => 'Profil-E-Mail',
                    'show_if' => ['profile_type' => 'email'],
                ],
                'social_xing' => [
                    'type' => 'text',
                    'label' => 'Xing URL',
                ],
                'social_linkedin' => [
                    'type' => 'text',
                    'label' => 'LinkedIn URL',
                ],
            ],
            'item_modal' => [
                'label' => 'Details',
                'icon' => 'fa-id-card-o',
                'fields' => ['bio', 'profile_type', 'profile_link', 'profile_mail', 'social_xing', 'social_linkedin'],
            ],
        ],

        'columns' => [
            'type' => 'choice',
            'label' => 'Spalten (Desktop)',
            'choices' => [
                '2' => '2 Spalten',
                '3' => '3 Spalten',
                '4' => '4 Spalten',
            ],
            'default' => '3',
        ],
        'card_style' => [
            'type' => 'choice',
            'label' => 'Karten-Stil',
            'choices' => [
                'default' => 'Standard',
                'primary' => 'Primary',
                'secondary' => 'Secondary',
                'muted' => 'Muted',
            ],
            'default' => 'default',
        ],
        'show_social' => [
            'type' => 'checkbox',
            'label' => 'Social-Links anzeigen',
            'default' => true,
        ],

        'section_padding' => [
            'type' => 'choice',
            'label' => 'Section Padding',
            'choices' => [
                '' => 'Standard',
                'uk-section-small' => 'Klein',
                'uk-section-large' => 'Groß',
            ],
            'default' => '',
        ],
        'section_bg' => [
            'type' => 'choice',
            'label' => 'Section Hintergrund',
            'choices' => [
                '' => 'Keiner',
                'uk-section-muted' => 'Muted',
                'uk-section-primary' => 'Primary',
            ],
            'default' => '',
        ],
    ],
];
```

---

## 3. Template (`templates/bootstrap.php`)

```php
<?php
$headline = (string) ($elementData['headline'] ?? '');
$intro = (string) ($elementData['intro'] ?? '');
$items = $elementData['items'] ?? [];
$columns = (string) ($elementData['columns'] ?? '3');
$showSocial = !empty($elementData['show_social']);

$colClass = 'col-md-4';
if ($columns === '2') {
    $colClass = 'col-md-6';
} elseif ($columns === '4') {
    $colClass = 'col-md-3';
}
?>

<section class="team-grid">
    <?php if ($headline !== ''): ?>
        <h2><?= rex_escape($headline) ?></h2>
    <?php endif; ?>

    <?php if ($intro !== ''): ?>
        <div class="team-grid-intro"><?= $intro ?></div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($items as $item): ?>
            <?php
            $name = (string) ($item['name'] ?? '');
            $role = (string) ($item['role'] ?? '');
            $photo = (string) ($item['photo'] ?? '');
            $bio = (string) ($item['bio'] ?? '');
            $profileType = (string) ($item['profile_type'] ?? '');
            $profileLink = (string) ($item['profile_link'] ?? '');
            $profileMail = (string) ($item['profile_mail'] ?? '');
            $xing = (string) ($item['social_xing'] ?? '');
            $linkedin = (string) ($item['social_linkedin'] ?? '');
            ?>

            <div class="<?= rex_escape($colClass) ?>" style="margin-bottom:24px;">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <?php if ($photo !== ''): ?>
                            <img src="<?= rex_url::media($photo) ?>" alt="<?= rex_escape($name) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:50%;margin-bottom:12px;">
                        <?php endif; ?>

                        <?php if ($name !== ''): ?>
                            <h3 style="margin-top:0;"><?= rex_escape($name) ?></h3>
                        <?php endif; ?>
                        <?php if ($role !== ''): ?>
                            <p class="text-muted"><?= rex_escape($role) ?></p>
                        <?php endif; ?>
                        <?php if ($bio !== ''): ?>
                            <p><?= nl2br(rex_escape($bio)) ?></p>
                        <?php endif; ?>

                        <?php if ($profileType === 'url' && $profileLink !== ''): ?>
                            <p><a href="<?= rex_escape($profileLink) ?>" target="_blank" rel="noopener">Profil ansehen</a></p>
                        <?php elseif ($profileType === 'email' && $profileMail !== ''): ?>
                            <p><a href="mailto:<?= rex_escape($profileMail) ?>">E-Mail senden</a></p>
                        <?php endif; ?>

                        <?php if ($showSocial && ($xing !== '' || $linkedin !== '')): ?>
                            <p style="margin-top:10px;">
                                <?php if ($xing !== ''): ?>
                                    <a href="<?= rex_escape($xing) ?>" target="_blank" rel="noopener">Xing</a>
                                <?php endif; ?>
                                <?php if ($xing !== '' && $linkedin !== ''): ?> · <?php endif; ?>
                                <?php if ($linkedin !== ''): ?>
                                    <a href="<?= rex_escape($linkedin) ?>" target="_blank" rel="noopener">LinkedIn</a>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
```

Tipp: Für UIkit kannst du die gleiche Logik in `templates/uikit.php` mit `uk-card`, `uk-grid` und `uk-width-*` übernehmen.

---

## 4. Warum dieses Repeater-Beispiel wichtig ist

Du nutzt hier bereits mehrere wichtige Builder-Mechaniken:

1. `repeater` für dynamische Listen
2. `item_modal` für aufgeräumte Bearbeitung pro Eintrag
3. `trigger_after` + `show_if` für bedingte Felder
4. `field_groups` für klare Schritte/Tabs
5. `settings_modal` für selten genutzte Optionen

Damit deckst du bereits 80 % realer Kundenanforderungen ab.

---

## 5. Weitere Funktionen, die du direkt nutzen kannst

### A) Feldtypen kombinieren

- `tinymce` mit `'profile' => 'default'`
- `choice` mit `choices`, optional `choice_icons` oder `choice_colors`
- `be_media` für Bilder/Dateien
- `be_link` für interne REDAXO-Links
- `radio_image` für visuelle Layout-Auswahl

### B) Trigger und Sichtbarkeit

Nutze in Feldern:

```php
'show_if' => ['layout' => 'grid']
```

Und im auslösenden Feld:

```php
'trigger_after' => 'columns|gap'
```

### C) Modul-Generator verwenden

Backend-Seite „Module“ erzeugt dir die passenden Module automatisch. Das ist ideal, wenn du viele Elemente ausrollst und konsistent halten willst.

### D) `doform2026` als Formular-Basis

Wenn du Formulare brauchst, nutze `doform2026` als Referenz für:

1. Schrittweise Konfiguration mit `field_groups`
2. Repeater-Feldkonzepte für Formularfelder
3. Mail-Konfiguration inkl. Reply-To-Logik
4. TinyMCE-Einsatz in Einleitungstexten

---

## 6. Qualitäts-Check vor Go-Live

1. Konfiguration lädt ohne PHP-Fehler?
2. Template gibt nur erwartetes HTML aus?
3. `rex_escape()` überall bei User-Eingaben?
4. Upload-/Media-Felder funktionieren in Redaktions-Workflows?
5. Darstellung in Bootstrap und/oder UIkit geprüft?
6. Rexstan für betroffene Dateien laufen lassen?

---

# 🎁 Bonus: Spicken erlaubt!

Das Addon liefert viele fertige Elemente mit, die fast alles im Alltag abdecken:

*   **Section / Container** – Visuelle Abschnitte mit Hintergrundfarbe/-bild
*   **Text & Bild** – mit verschiedenen Layouts
*   **Accordion** – Aufklappbare Inhaltsblöcke / Tabs
*   **Headline** – H1-H6, Farbe, Ausrichtung
*   **Divider** – Trennlinien, mehrere Styles
*   **Cards Grid** – Repeater-basiert, Farb- und Layout-Auswahl
*   **Slideshow** – Bild-/Video-Slideshow
*   **Gallery** – Grid & Masonry, Mixed Media
*   **Hero Banner** – Fullscreen-Banner mit Call-to-Action
*   **Feature Grid** – Icon-Feature-Liste
*   **Moving Tiles** – Parallax-Tiles mit alternierenden Layouts
*   **Testimonial** – Zitate mit Autor und Bild
*   **Timeline** – Zeitstrahl-Element
*   **Downloads** – Dateiliste aus dem Mediapool
*   **Countdown** – Countdown bis zu einem Datum
*   **Table** – Einfache Tabellen-Ausgabe
*   **YForm-Liste** – Datensätze aus YForm-Tabellen
*   **Kontakt-Picker** – Einzelne Kontakte aus YForm-Profilen
*   **Forcal-Termine** – Veranstaltungen aus forcal
*   **DoForm2026** – Formular-Generator mit Mail, Sicherheit und Multi-Step
*   **Starter-Elemente** (`starter_text`, `starter_headline`, `starter_media_split`, `starter_gallery`, `starter_cards`, `starter_callout`) – reduzierte Vorlagen für schnellen Einstieg

### Der beste Weg zu lernen

Schau dir an, wie diese Elemente gebaut sind im Ordner:

`redaxo/src/addons/builder/elements/`

**Pro-Tipp:**
Wenn du ein Element brauchst, das fast zu deinem Ziel passt:

1. Ordner in `project/elements/` kopieren
2. Umbenennen
3. `config.php` und Template anpassen

So sparst du viel Zeit und lernst direkt von funktionierendem Code.
