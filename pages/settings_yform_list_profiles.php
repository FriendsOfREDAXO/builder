<?php

/**
 * YForm-Listen-Profile – Verwaltungs-UI
 *
 * Ein Profil definiert: Tabelle, Spalten, Sortierung, URL-Pattern.
 * Im Element wählt der Redakteur nur das Profil + Anzahl + Filter + Layout.
 */

$addon = rex_addon::get('builder');
$csrf = rex_csrf_token::factory('builder_profiles');

// JS-Datei + API-URL werden zentral in boot.php nur für diese Subseite eingebunden.

// ---- Verfügbare YForm-Tabellen ermitteln ----------------------------------
$availableTables = [];
if (class_exists(rex_yform_manager_table::class)) {
    try {
        foreach (rex_yform_manager_table::getAll() as $tbl) {
            $availableTables[$tbl->getTableName()] = $tbl->getName() . ' (' . $tbl->getTableName() . ')';
        }
    } catch (Throwable) {
        // ignore
    }
}
ksort($availableTables);

// ---- Aktion verarbeiten ---------------------------------------------------
$func = rex_request('func', 'string', '');
$message = '';

if ('' !== $func && !$csrf->isValid()) {
    $message = rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    $func = '';
}

$profiles = \FriendsOfREDAXO\Builder\ListProfiles::getAll();

if ('save' === $func) {
    $rawId = trim((string) rex_post('profile_id', 'string', ''));
    $origId = trim((string) rex_post('profile_orig_id', 'string', ''));
    if ('' === $rawId || !preg_match('/^[a-z0-9_]+$/i', $rawId)) {
        $message = rex_view::error('Profil-ID darf nur Buchstaben, Zahlen und _ enthalten.');
    } else {
        $newProfile = [
            'label' => trim((string) rex_post('label', 'string', '')),
            'table' => trim((string) rex_post('table', 'string', '')),
            'title_field' => trim((string) rex_post('title_field', 'string', '')),
            'teaser_field' => trim((string) rex_post('teaser_field', 'string', '')),
            'image_field' => trim((string) rex_post('image_field', 'string', '')),
            'sort_field' => trim((string) rex_post('sort_field', 'string', '')),
            'sort_dir' => strtoupper(trim((string) rex_post('sort_dir', 'string', 'DESC'))),
            'url_pattern' => trim((string) rex_post('url_pattern', 'string', '')),
            'url_profile' => trim((string) rex_post('url_profile', 'string', '')),
            'use_virtual_urls' => (bool) rex_post('use_virtual_urls', 'bool', false),
            'default_limit' => (int) rex_post('default_limit', 'int', 6),
            'default_layout' => trim((string) rex_post('default_layout', 'string', 'cards')),
            'filter_default' => trim((string) rex_post('filter_default', 'string', '')),
            'media_type' => trim((string) rex_post('media_type', 'string', '')),
            'firstname_field' => trim((string) rex_post('firstname_field', 'string', '')),
            'freitext_field' => trim((string) rex_post('freitext_field', 'string', '')),
            'phone_field' => trim((string) rex_post('phone_field', 'string', '')),
            'mobile_field' => trim((string) rex_post('mobile_field', 'string', '')),
            'email_field' => trim((string) rex_post('email_field', 'string', '')),
        ];
        // Bei Rename altes Profil entfernen
        if ('' !== $origId && $origId !== $rawId) {
            unset($profiles[$origId]);
        }
        $profiles[$rawId] = $newProfile;
        \FriendsOfREDAXO\Builder\ListProfiles::save($profiles);
        $profiles = \FriendsOfREDAXO\Builder\ListProfiles::getAll();
        $message = rex_view::success('Profil "' . rex_escape($rawId) . '" gespeichert.');
        // Im Edit-Modus bleiben, damit Spalten-Selects nach erstem Save erscheinen
        $_GET['edit'] = $rawId;
    }
}

if ('delete' === $func) {
    $delId = (string) rex_request('profile_id', 'string', '');
    if ('' !== $delId && isset($profiles[$delId])) {
        unset($profiles[$delId]);
        \FriendsOfREDAXO\Builder\ListProfiles::save($profiles);
        $message = rex_view::success('Profil "' . rex_escape($delId) . '" gelöscht.');
    }
}

if ('' !== $message) {
    echo $message;
}

// ---- Liste der Profile + Edit-Formulare -----------------------------------
$editId = (string) rex_request('edit', 'string', '');
$showAddForm = 'add' === rex_request('mode', 'string', '');

$listingHtml = '';
if ([] === $profiles) {
    $listingHtml .= '<p class="text-muted"><em>Noch keine Profile angelegt.</em></p>';
} else {
    $listingHtml .= '<table class="table table-striped table-hover">';
    $listingHtml .= '<thead><tr>'
        . '<th>ID</th><th>Label</th><th>Tabelle</th><th>Layout</th><th>URL-Pattern</th><th class="rex-table-action">Aktion</th>'
        . '</tr></thead><tbody>';
    foreach ($profiles as $pid => $p) {
        $editUrl = rex_url::currentBackendPage(['edit' => $pid]);
        $delUrl = rex_url::currentBackendPage(array_merge(
            ['func' => 'delete', 'profile_id' => $pid],
            $csrf->getUrlParams(),
        ));
        $listingHtml .= '<tr>'
            . '<td><code>' . rex_escape($pid) . '</code></td>'
            . '<td>' . rex_escape((string) $p['label']) . '</td>'
            . '<td><code>' . rex_escape((string) $p['table']) . '</code></td>'
            . '<td>' . rex_escape((string) $p['default_layout']) . '</td>'
            . '<td><small><code>' . rex_escape((string) $p['url_pattern']) . '</code></small></td>'
            . '<td class="rex-table-action">'
            . '<a class="btn btn-default btn-xs" href="' . $editUrl . '"><i class="rex-icon fa-edit"></i> Bearbeiten</a> '
            . '<a class="btn btn-delete btn-xs" href="' . $delUrl . '" data-confirm="Profil wirklich löschen?"><i class="rex-icon fa-trash"></i></a>'
            . '</td></tr>';
    }
    $listingHtml .= '</tbody></table>';
}

$listingHtml .= '<p style="margin-top:10px;">'
    . '<a class="btn btn-primary" href="' . rex_url::currentBackendPage(['mode' => 'add']) . '"><i class="rex-icon fa-plus"></i> Neues Profil anlegen</a>'
    . '</p>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', 'YForm-Listen-Profile', false);
$fragment->setVar('body', $listingHtml, false);
echo $fragment->parse('core/page/section.php');

// ---- Bearbeitungs-/Erstellungsformular -----------------------------------
$editing = null;
$origId = '';
if ('' !== $editId && isset($profiles[$editId])) {
    $editing = $profiles[$editId];
    $origId = $editId;
} elseif ($showAddForm) {
    $editing = [
        'id' => '',
        'label' => '',
        'table' => '',
        'title_field' => 'name',
        'teaser_field' => '',
        'image_field' => '',
        'sort_field' => 'id',
        'sort_dir' => 'DESC',
        'url_pattern' => '',
        'url_profile' => '',
        'use_virtual_urls' => false,
        'default_limit' => 6,
        'default_layout' => 'cards',
        'filter_default' => '',
        'media_type' => '',
        'firstname_field' => '',
        'freitext_field' => '',
        'phone_field' => '',
        'mobile_field' => '',
        'email_field' => '',
    ];
}

if (null === $editing) {
    return;
}

$columns = '' !== (string) $editing['table']
    ? \FriendsOfREDAXO\Builder\ListProfiles::collectColumns((string) $editing['table'])
    : [];

$selectField = static function (string $name, string $current, array $columns, bool $allowEmpty = true): string {
    $allowEmptyAttr = $allowEmpty ? '1' : '0';
    $html = '<select class="form-control" name="' . $name . '"'
        . ' data-yfl-column-select="1"'
        . ' data-current-value="' . rex_escape($current) . '"'
        . ' data-allow-empty="' . $allowEmptyAttr . '"'
        . ' data-server-filled="1">';
    if ([] === $columns) {
        // Noch keine Tabelle gewählt: JS füllt nach Auswahl. Trotzdem aktuellen Wert als Option behalten.
        if ($allowEmpty) {
            $html .= '<option value=""' . ('' === $current ? ' selected' : '') . '>— erst Tabelle wählen —</option>';
        } else {
            $html .= '<option value="">— erst Tabelle wählen —</option>';
        }
        if ('' !== $current) {
            $html .= '<option value="' . rex_escape($current) . '" selected>' . rex_escape($current) . '</option>';
        }
    } else {
        if ($allowEmpty) {
            $html .= '<option value=""' . ('' === $current ? ' selected' : '') . '>— optional —</option>';
        }
        if (!in_array($current, $columns, true) && '' !== $current) {
            $html .= '<option value="' . rex_escape($current) . '" selected>' . rex_escape($current) . ' (nicht in Tabelle)</option>';
        }
        foreach ($columns as $col) {
            $sel = ($col === $current) ? ' selected' : '';
            $html .= '<option value="' . rex_escape($col) . '"' . $sel . '>' . rex_escape($col) . '</option>';
        }
    }
    $html .= '</select>';
    return $html;
};

$tableSelect = '<select class="form-control" id="yfl-profile-table" name="table">';
$tableSelect .= '<option value="">— Tabelle wählen —</option>';
foreach ($availableTables as $tableName => $label) {
    $sel = ($tableName === (string) $editing['table']) ? ' selected' : '';
    $tableSelect .= '<option value="' . rex_escape($tableName) . '"' . $sel . '>' . rex_escape($label) . '</option>';
}
$tableSelect .= '</select>';

$layoutOptions = '';
foreach ([
    'cards' => 'Kacheln (Cards)',
    'list' => 'Liste mit Bild + Anriss',
    'compact' => 'Kompakt (nur Titel)',
    'contact' => 'Kontakt-Karten (Avatar, Name, Funktion, Telefon, E-Mail)',
    'contact_compact' => 'Kontakt kompakt (Card-Header mit Avatar – siehe UIkit "Card Header")',
] as $val => $lbl) {
    $sel = ($val === (string) $editing['default_layout']) ? ' selected' : '';
    $layoutOptions .= '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
}

$sortDirOptions = '';
foreach (['DESC' => 'Absteigend (neueste zuerst)', 'ASC' => 'Aufsteigend (älteste zuerst)'] as $val => $lbl) {
    $sel = ($val === (string) $editing['sort_dir']) ? ' selected' : '';
    $sortDirOptions .= '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
}

$formHtml = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$formHtml .= $csrf->getHiddenField();
$formHtml .= '<input type="hidden" name="func" value="save">';
$formHtml .= '<input type="hidden" name="profile_orig_id" value="' . rex_escape($origId) . '">';

$fields = [];

$fields[] = [
    'label' => '<label>Profil-ID</label>',
    'field' => '<input class="form-control" type="text" name="profile_id" value="' . rex_escape((string) ($editing['id'] ?? $origId)) . '" pattern="[a-z0-9_]+" required placeholder="z.B. news, products">',
    'note' => 'Eindeutige technische ID (Slug). Nur Kleinbuchstaben, Ziffern, Unterstrich.',
];
$fields[] = [
    'label' => '<label>Label (Anzeigename)</label>',
    'field' => '<input class="form-control" type="text" name="label" value="' . rex_escape((string) $editing['label']) . '" required placeholder="z.B. News">',
];
$fields[] = [
    'label' => '<label>YForm-Tabelle</label>',
    'field' => $tableSelect,
    'note' => 'Tabelle wählen – die Spalten-Auswahllisten unten werden automatisch aktualisiert.',
];

if ([] === $columns && '' !== (string) $editing['table']) {
    $fields[] = [
        'label' => '',
        'field' => '<div class="alert alert-info" style="margin:10px 0;">Tabelle <code>' . rex_escape((string) $editing['table']) . '</code> hat keine erkannten Spalten – bitte nach dem Speichern erneut prüfen.</div>',
    ];
}

if ([] !== $columns || '' !== (string) $editing['table'] || $showAddForm) {
    $fields[] = [
        'label' => '<label>Titel-Spalte</label>',
        'field' => $selectField('title_field', (string) $editing['title_field'], $columns, false),
    ];
    $fields[] = [
        'label' => '<label>Anriss-/Teaser-Spalte</label>',
        'field' => $selectField('teaser_field', (string) $editing['teaser_field'], $columns),
        'note' => 'Optional. HTML wird gestrippt und gekürzt.',
    ];
    $fields[] = [
        'label' => '<label>Bild-Spalte (Mediapool-Datei)</label>',
        'field' => $selectField('image_field', (string) $editing['image_field'], $columns),
        'note' => 'Optional. Akzeptiert Dateinamen, Komma-Liste oder absolute URL.',
    ];
    $fields[] = [
        'label' => '<label>Sortier-Spalte</label>',
        'field' => $selectField('sort_field', (string) $editing['sort_field'], $columns, false),
    ];

    // Kontakt-spezifische Feld-Mappings (relevant fuer Layout=contact).
    $fields[] = [
        'label' => '<hr><strong style="display:block;margin:8px 0;">Kontakt-Layout (optional)</strong>',
        'field' => '<p class="help-block" style="margin:0 0 12px;">Diese Feld-Mappings werden ausschliesslich beim Layout <code>Kontakt-Karten</code> ausgewertet. Der Cropping/Bildtyp wird ueber den Mediamanager-Typ unten gesteuert (Empfehlung: <code>avatar</code>).</p>',
    ];
    $fields[] = [
        'label' => '<label>Vorname-Spalte</label>',
        'field' => $selectField('firstname_field', (string) ($editing['firstname_field'] ?? ''), $columns),
        'note' => 'Optional. Die Titel-Spalte oben dient als Nachname.',
    ];
    $fields[] = [
        'label' => '<label>Freitext-Spalte</label>',
        'field' => $selectField('freitext_field', (string) ($editing['freitext_field'] ?? ''), $columns),
        'note' => 'Optional. Wird unter dem Namen angezeigt (z.B. Titel/Suffix).',
    ];
    $fields[] = [
        'label' => '<label>Telefon-Spalte</label>',
        'field' => $selectField('phone_field', (string) ($editing['phone_field'] ?? ''), $columns),
    ];
    $fields[] = [
        'label' => '<label>Mobil-Spalte</label>',
        'field' => $selectField('mobile_field', (string) ($editing['mobile_field'] ?? ''), $columns),
    ];
    $fields[] = [
        'label' => '<label>E-Mail-Spalte</label>',
        'field' => $selectField('email_field', (string) ($editing['email_field'] ?? ''), $columns),
    ];
}

$fields[] = [
    'label' => '<label>Sortier-Richtung</label>',
    'field' => '<select class="form-control" name="sort_dir">' . $sortDirOptions . '</select>',
];

// Virtual URLs (separates Addon) – einfache Checkbox
if (\FriendsOfREDAXO\Builder\ListProfiles::hasVirtualUrls()) {
    $checked = !empty($editing['use_virtual_urls']) ? ' checked' : '';
    $fields[] = [
        'label' => '<label>Virtual URLs</label>',
        'field' => '<label class="checkbox-inline" style="padding-top:7px;">'
            . '<input type="checkbox" name="use_virtual_urls" value="1"' . $checked . '> '
            . 'URLs über das <code>virtual_urls</code>-Addon erzeugen'
            . '</label>',
        'note' => 'Wenn aktiv, wird die Detail-URL via <code>VirtualUrlsHelper::getUrl($table, $id)</code> erzeugt. Hat Vorrang vor Url-Addon-Profil und Pattern.',
    ];
} else {
    $fields[] = [
        'label' => '',
        'field' => '<input type="hidden" name="use_virtual_urls" value="' . (!empty($editing['use_virtual_urls']) ? '1' : '0') . '">',
    ];
}

// URL-Profil (Url-Addon) – optional
if (\FriendsOfREDAXO\Builder\ListProfiles::hasUrlAddon()) {
    $urlProfiles = \FriendsOfREDAXO\Builder\ListProfiles::collectUrlProfiles((string) $editing['table']);
    $currentUrlProfile = (string) $editing['url_profile'];
    $upHtml = '<select class="form-control" id="yfl-url-profile" name="url_profile"'
        . ' data-current-value="' . rex_escape($currentUrlProfile) . '">';
    $upHtml .= '<option value=""' . ('' === $currentUrlProfile ? ' selected' : '') . '>— kein URL-Profil —</option>';
    $hasCurrent = false;
    foreach ($urlProfiles as $up) {
        $sel = ($up['namespace'] === $currentUrlProfile) ? ' selected' : '';
        if ($up['namespace'] === $currentUrlProfile) {
            $hasCurrent = true;
        }
        $upHtml .= '<option value="' . rex_escape($up['namespace']) . '"' . $sel . '>'
            . rex_escape($up['label']) . '</option>';
    }
    if ('' !== $currentUrlProfile && !$hasCurrent) {
        $upHtml .= '<option value="' . rex_escape($currentUrlProfile) . '" selected>'
            . rex_escape($currentUrlProfile) . ' (nicht für diese Tabelle)</option>';
    }
    $upHtml .= '</select>';
    $fields[] = [
        'label' => '<label>URL-Profil (Url-Addon)</label>',
        'field' => $upHtml,
        'note' => 'Wenn gesetzt, wird die Detail-URL über das gewählte Url-Addon-Profil erzeugt (' . '<code>rex_getUrl(\'\', \'\', [\'namespace\' =&gt; $id])</code>) – das URL-Pattern unten wird dann ignoriert.',
    ];
} else {
    $fields[] = [
        'label' => '<label>URL-Profil</label>',
        'field' => '<input type="hidden" name="url_profile" value="' . rex_escape((string) $editing['url_profile']) . '"><div class="help-block">Url-Addon nicht installiert – stattdessen URL-Pattern verwenden.</div>',
    ];
}

$fields[] = [
    'label' => '<label>URL-Pattern</label>',
    'field' => '<input class="form-control" type="text" name="url_pattern" value="' . rex_escape((string) $editing['url_pattern']) . '" placeholder="/news/?id={id}">',
    'note' => 'Fallback, wenn kein URL-Profil gewählt ist. Platzhalter: <code>{id}</code> sowie <code>{feldname}</code> jeder Spalte.',
];
$fields[] = [
    'label' => '<label>Mediamanager-Typ (für Bilder)</label>',
    'field' => '<input class="form-control" type="text" name="media_type" value="' . rex_escape((string) $editing['media_type']) . '" placeholder="z.B. card_16_9_w800 oder avatar">',
    'note' => 'Optional. Für Layout <code>Kontakt-Karten</code> empfohlen: <code>avatar</code> (Cropping/Format wird ausschliesslich über den MM-Typ gesteuert).',
];
$fields[] = [
    'label' => '<label>Default-Layout</label>',
    'field' => '<select class="form-control" name="default_layout">' . $layoutOptions . '</select>',
];
$fields[] = [
    'label' => '<label>Default-Anzahl Einträge</label>',
    'field' => '<input class="form-control" type="number" min="1" max="200" name="default_limit" value="' . rex_escape((string) $editing['default_limit']) . '">',
];
$fields[] = [
    'label' => '<label>Filter / WHERE-Bedingungen</label>',
    'field' => '<textarea class="form-control" name="filter_default" rows="5" placeholder="status = 1&#10;publish_date <= NOW&#10;expire_date >= NOW">'
        . rex_escape((string) $editing['filter_default']) . '</textarea>'
        . '<p class="help-block" style="margin-top:8px;">'
        . '<strong>Syntax:</strong> Pro Zeile eine Bedingung im Format <code>feld OP wert</code>. '
        . 'Bedingungen werden mit <code>AND</code> verknüpft.<br>'
        . '<strong>Operatoren:</strong> <code>=</code>, <code>!=</code>, <code>&lt;</code>, <code>&lt;=</code>, <code>&gt;</code>, <code>&gt;=</code>, <code>LIKE</code><br>'
        . '<strong>Datums-Platzhalter:</strong> <code>NOW</code>, <code>TODAY</code>, <code>TODAY+7</code>, <code>TODAY-30</code>, <code>NOW+1H</code>, <code>NOW-30M</code>, <code>NOW+2D</code><br>'
        . '<strong>Beispiele:</strong><br>'
        . '<code>status = 1</code> – nur veröffentlichte<br>'
        . '<code>publish_date &lt;= NOW</code> – Veröffentlichungsdatum erreicht<br>'
        . '<code>expire_date &gt;= TODAY</code> – noch nicht abgelaufen<br>'
        . '<code>category LIKE %news%</code> – Kategorie-Match'
        . '</p>',
    'note' => 'Diese Filter laufen auf <em>jedem</em> Auflistungs-Element mit diesem Profil – Redakteure können sie nicht überschreiben.',
];

$formElements = [];
foreach ($fields as $f) {
    $formElements[] = $f;
}
$ff = new rex_fragment();
$ff->setVar('elements', $formElements, false);
$formHtml .= $ff->parse('core/form/form.php');

$formHtml .= '<div class="rex-form-aligned">';
$formHtml .= '<button class="btn btn-save" type="submit">' . ('' === $origId ? 'Profil anlegen' : 'Profil speichern') . '</button> ';
$formHtml .= '<a class="btn btn-default" href="' . rex_url::currentBackendPage() . '">Abbrechen</a>';
$formHtml .= '</div>';
$formHtml .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', '' === $origId ? 'Neues Profil anlegen' : 'Profil bearbeiten: ' . rex_escape($origId), false);
$fragment->setVar('body', $formHtml, false);
echo $fragment->parse('core/page/section.php');
