<?php

namespace FriendsOfREDAXO\Builder;

use rex_addon;
use rex_sql;
use rex_yform_manager_table;
use Throwable;

/**
 * YForm-Listen-Profile fuer das builder-Addon.
 *
 * Profile werden zentral in den Addon-Einstellungen verwaltet
 * (Config-Schluessel `yform_list_profiles`, JSON). Im Element waehlt der
 * Redakteur nur das Profil + Anzahl + Filter + Layout.
 *
 * Profil-Schema:
 *   id              string   eindeutig (Slug, z.B. "news", "products")
 *   label           string   Anzeigename im Element-Dropdown
 *   table           string   YForm-Tabellenname (rex_*)
 *   title_field     string   Spalte fuer Titel
 *   teaser_field    string   Spalte fuer Anriss (optional)
 *   image_field     string   Spalte fuer Mediapool-Datei (optional)
 *   sort_field      string   Sortierspalte
 *   sort_dir        string   ASC|DESC
 *   url_pattern     string   z.B. "/news/?id={id}" oder "{slug}" - Platzhalter {id} und {feldname}
 *   default_limit   int      Default-Anzahl Eintraege
 *   default_layout  string   cards|list|compact
 *   filter_default  string   optionaler WHERE-Snippet (key=value pro Zeile, KEINE Roh-SQL)
 */
final class ListProfiles
{
    public const CONFIG_KEY = 'yform_list_profiles';

    public const ALLOWED_LAYOUTS = ['cards', 'list', 'compact', 'contact', 'contact_compact'];
    public const MAX_LIMIT = 200;

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function getAll(): array
    {
        $raw = (string) rex_addon::get('builder')->getConfig(self::CONFIG_KEY, '');
        if ('' === $raw) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $id => $cfg) {
            $id = (string) $id;
            if (!is_array($cfg) || '' === $id) {
                continue;
            }
            $out[$id] = self::normalize($id, $cfg);
        }
        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function get(string $id): ?array
    {
        $all = self::getAll();
        return $all[$id] ?? null;
    }

    /**
     * @param array<string,array<string,mixed>|mixed> $profiles
     */
    public static function save(array $profiles): void
    {
        $clean = [];
        foreach ($profiles as $id => $cfg) {
            $id = (string) $id;
            if ('' === $id || !preg_match('/^[a-z0-9_]+$/i', $id)) {
                continue;
            }
            if (!is_array($cfg)) {
                continue;
            }
            $clean[$id] = self::normalize($id, $cfg);
        }
        rex_addon::get('builder')->setConfig(
            self::CONFIG_KEY,
            json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        );
    }

    /**
     * Liefert Profile als Choices fuer die Element-Konfiguration.
     *
     * @return array<string,string>
     */
    public static function getChoices(): array
    {
        $out = [];
        foreach (self::getAll() as $id => $p) {
            $out[$id] = ($p['label'] ?? $id) . ' (' . ($p['table'] ?? '?') . ')';
        }
        return $out;
    }

    /**
     * Liefert die verfuegbaren Spalten einer Tabelle (System + YForm).
     *
     * @return list<string>
     */
    public static function collectColumns(string $tableName): array
    {
        $cols = [];
        if ('' === $tableName) {
            return $cols;
        }
        // YForm-Wertfelder
        if (class_exists(rex_yform_manager_table::class)) {
            try {
                $table = rex_yform_manager_table::get($tableName);
                if (null !== $table) {
                    foreach ($table->getValueFields() as $field) {
                        $name = (string) $field->getName();
                        if ('' !== $name) {
                            $cols[] = $name;
                        }
                    }
                    foreach (array_keys($table->getColumns()) as $col) {
                        $cols[] = (string) $col;
                    }
                }
            } catch (Throwable) {
                // ignore
            }
        }
        // SQL-Fallback fuer Tabellen ohne YForm-Definition
        if ([] === $cols) {
            try {
                $sql = rex_sql::factory();
                foreach ($sql->getArray('SHOW COLUMNS FROM ' . $sql->escapeIdentifier($tableName)) as $row) {
                    $cols[] = (string) $row['Field'];
                }
            } catch (Throwable) {
                // ignore
            }
        }
        $cols = array_values(array_unique($cols));
        sort($cols);
        return $cols;
    }

    /**
     * Prueft, ob das Url-Addon verfuegbar ist.
     */
    public static function hasUrlAddon(): bool
    {
        return rex_addon::get('url')->isAvailable() && class_exists(\Url\Profile::class);
    }

    /**
     * Prueft, ob das virtual_urls-Addon mit Helper verfuegbar ist.
     */
    public static function hasVirtualUrls(): bool
    {
        return rex_addon::get('virtual_urls')->isAvailable()
            && class_exists(\FriendsOfRedaxo\VirtualUrl\VirtualUrlsHelper::class);
    }

    /**
     * Liefert Url-Addon-Profile, optional gefiltert nach Tabellenname.
     *
     * @return list<array{namespace:string, label:string, table:string}>
     */
    public static function collectUrlProfiles(string $tableName = ''): array
    {
        if (!self::hasUrlAddon()) {
            return [];
        }
        $out = [];
        try {
            $profiles = '' !== $tableName
                ? \Url\Profile::getByTableName($tableName)
                : \Url\Profile::getAll();
            foreach ($profiles as $p) {
                $ns = (string) $p->getNamespace();
                if ('' === $ns) {
                    continue;
                }
                $out[] = [
                    'namespace' => $ns,
                    'label' => $ns . ' (' . $p->getTableName() . ')',
                    'table' => (string) $p->getTableName(),
                ];
            }
        } catch (Throwable) {
            // ignore
        }
        return $out;
    }

    /**
     * Liefert Profile, die als Kontakt-Profile konfiguriert sind
     * (mind. firstname_field gesetzt – sinnvoll für den Picker).
     *
     * @return array<string,array<string,mixed>>
     */
    public static function getContactProfiles(): array
    {
        $out = [];
        foreach (self::getAll() as $id => $p) {
            if ('' !== trim((string) ($p['firstname_field'] ?? ''))) {
                $out[$id] = $p;
            }
        }
        return $out;
    }

    /**
     * Liefert Picker-Choices über alle Kontakt-Profile.
     * Key:   "profileId:entryId"
     * Label: "[Profil-Label] Vorname Nachname"
     *
     * @return array<string,string>
     */
    public static function getContactPickerChoices(int $maxPerProfile = 500): array
    {
        $out = [];
        foreach (self::getContactProfiles() as $pid => $p) {
            $tableName = (string) $p['table'];
            if ('' === $tableName) {
                continue;
            }
            $allowed = self::collectColumns($tableName);
            $titleCol = in_array((string) $p['title_field'], $allowed, true)
                ? (string) $p['title_field'] : '';
            $firstCol = in_array((string) $p['firstname_field'], $allowed, true)
                ? (string) $p['firstname_field'] : '';
            try {
                $sql = rex_sql::factory();
                $cols = ['id'];
                if ('' !== $firstCol) {
                    $cols[] = $firstCol;
                }
                if ('' !== $titleCol) {
                    $cols[] = $titleCol;
                }
                $colsSql = implode(', ', array_map([$sql, 'escapeIdentifier'], $cols));
                $rows = $sql->getArray(
                    'SELECT ' . $colsSql . ' FROM ' . $sql->escapeIdentifier($tableName)
                    . ' ORDER BY ' . $sql->escapeIdentifier('' !== $titleCol ? $titleCol : 'id') . ' ASC'
                    . ' LIMIT ' . $maxPerProfile,
                );
            } catch (Throwable) {
                continue;
            }
            $label = trim((string) ($p['label'] ?? $pid));
            foreach ($rows as $row) {
                $eid = (int) ($row['id'] ?? 0);
                if ($eid < 1) {
                    continue;
                }
                $first = '' !== $firstCol ? (string) ($row[$firstCol] ?? '') : '';
                $last = '' !== $titleCol ? (string) ($row[$titleCol] ?? '') : '';
                $name = trim($first . ' ' . $last);
                if ('' === $name) {
                    $name = '#' . $eid;
                }
                $out[$pid . ':' . $eid] = '[' . $label . '] ' . $name;
            }
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $cfg
     * @return array<string,mixed>
     */
    private static function normalize(string $id, array $cfg): array
    {
        $sortDir = strtoupper((string) ($cfg['sort_dir'] ?? 'DESC'));
        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }
        $layout = (string) ($cfg['default_layout'] ?? 'cards');
        if (!in_array($layout, self::ALLOWED_LAYOUTS, true)) {
            $layout = 'cards';
        }
        $limit = (int) ($cfg['default_limit'] ?? 6);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }
        $mediaType = trim((string) ($cfg['media_type'] ?? ''));
        // Kontakt-Layout: Default-MediaManager-Typ "avatar" (Cropping wird ueber den
        // MM-Typ gesteuert, nicht im Code). Nur wenn der Redakteur nichts gesetzt hat.
        if (str_starts_with($layout, 'contact') && '' === $mediaType) {
            $mediaType = 'avatar';
        }
        return [
            'id' => $id,
            'label' => trim((string) ($cfg['label'] ?? $id)),
            'table' => trim((string) ($cfg['table'] ?? '')),
            'title_field' => trim((string) ($cfg['title_field'] ?? 'name')),
            'teaser_field' => trim((string) ($cfg['teaser_field'] ?? '')),
            'image_field' => trim((string) ($cfg['image_field'] ?? '')),
            'sort_field' => trim((string) ($cfg['sort_field'] ?? 'id')),
            'sort_dir' => $sortDir,
            'url_pattern' => trim((string) ($cfg['url_pattern'] ?? '')),
            'url_profile' => trim((string) ($cfg['url_profile'] ?? '')),
            'use_virtual_urls' => (bool) ($cfg['use_virtual_urls'] ?? false),
            'default_limit' => $limit,
            'default_layout' => $layout,
            'filter_default' => trim((string) ($cfg['filter_default'] ?? '')),
            'media_type' => $mediaType,
            // Kontakt-spezifische Feld-Mappings (nur fuer Layout=contact relevant).
            'firstname_field' => trim((string) ($cfg['firstname_field'] ?? '')),
            'freitext_field' => trim((string) ($cfg['freitext_field'] ?? '')),
            'phone_field' => trim((string) ($cfg['phone_field'] ?? '')),
            'mobile_field' => trim((string) ($cfg['mobile_field'] ?? '')),
            'email_field' => trim((string) ($cfg['email_field'] ?? '')),
        ];
    }
}
