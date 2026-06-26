<?php

namespace FriendsOfREDAXO\Builder;

use rex_escape;
use rex_logger;
use rex_media;
use rex_media_manager;
use rex_sql;
use rex_url;

/**
 * Server-seitiges Rendering der YForm-Listen-Element-Daten.
 *
 * Die Templates (uikit.php / plain.php) bauen den HTML-Wrapper und
 * delegieren das eigentliche Datenladen + Item-Rendering an diese Klasse.
 */
final class ListRenderer
{
    /**
     * Lädt die Datensätze gemäß Profil + Element-Overrides.
     *
     * @param array<string,mixed> $elementData
     * @return array{profile: ?array<string,mixed>, items: list<array<string,mixed>>, error: ?string, layout: string, limit: int}
     */
    public static function fetch(array $elementData): array
    {
        $profileId = (string) ($elementData['profile'] ?? '');
        if ('' === $profileId) {
            return self::err('Kein Profil gewählt.');
        }
        $profile = ListProfiles::get($profileId);
        if (null === $profile) {
            return self::err('Profil "' . $profileId . '" nicht gefunden.');
        }
        $tableName = (string) $profile['table'];
        if ('' === $tableName) {
            return self::err('Profil "' . $profileId . '" hat keine Tabelle.');
        }

        $allowedCols = ListProfiles::collectColumns($tableName);
        $titleCol = self::pickCol((string) $profile['title_field'], $allowedCols, 'name');
        $teaserCol = self::pickCol((string) $profile['teaser_field'], $allowedCols, '');
        $imageCol = self::pickCol((string) $profile['image_field'], $allowedCols, '');
        $sortCol = self::pickCol((string) $profile['sort_field'], $allowedCols, 'id');

        // Kontakt-Felder (nur fuer Layout=contact relevant, aber generisch verfuegbar).
        $firstnameCol = self::pickCol((string) ($profile['firstname_field'] ?? ''), $allowedCols, '');
        $freitextCol = self::pickCol((string) ($profile['freitext_field'] ?? ''), $allowedCols, '');
        $phoneCol = self::pickCol((string) ($profile['phone_field'] ?? ''), $allowedCols, '');
        $mobileCol = self::pickCol((string) ($profile['mobile_field'] ?? ''), $allowedCols, '');
        $emailCol = self::pickCol((string) ($profile['email_field'] ?? ''), $allowedCols, '');
        $priceCol = self::pickCol((string) ($profile['price_field'] ?? ''), $allowedCols, '');
        $oldPriceCol = self::pickCol((string) ($profile['old_price_field'] ?? ''), $allowedCols, '');
        $currencyCol = self::pickCol((string) ($profile['currency_field'] ?? ''), $allowedCols, '');
        $badgeCol = self::pickCol((string) ($profile['badge_field'] ?? ''), $allowedCols, '');
        $availabilityCol = self::pickCol((string) ($profile['availability_field'] ?? ''), $allowedCols, '');

        // Element-Overrides
        $limit = isset($elementData['limit']) ? (int) $elementData['limit'] : (int) $profile['default_limit'];
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > ListProfiles::MAX_LIMIT) {
            $limit = ListProfiles::MAX_LIMIT;
        }
        $layout = (string) ($elementData['layout'] ?? $profile['default_layout']);
        if (!in_array($layout, ListProfiles::ALLOWED_LAYOUTS, true)) {
            $layout = 'cards';
        }
        $sortDir = strtoupper((string) $profile['sort_dir']);
        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }
        $teaserLength = isset($elementData['teaser_length']) ? (int) $elementData['teaser_length'] : 160;
        if ($teaserLength < 30) {
            $teaserLength = 30;
        }
        if ($teaserLength > 800) {
            $teaserLength = 800;
        }

        // WHERE-Bedingungen ausschliesslich aus Profil-Filter (zentral verwaltet).
        // Element-Override entfaellt bewusst – Redakteure sollen nur Menge + Design waehlen.
        $filterRaw = (string) $profile['filter_default'];
        [$whereSql, $whereParams] = self::buildWhere($filterRaw, $allowedCols);

        $sql = rex_sql::factory();
        $cols = ['id'];
        if ('' !== $titleCol) {
            $cols[] = $titleCol;
        }
        if ('' !== $teaserCol) {
            $cols[] = $teaserCol;
        }
        if ('' !== $imageCol) {
            $cols[] = $imageCol;
        }
        foreach ([$firstnameCol, $freitextCol, $phoneCol, $mobileCol, $emailCol, $priceCol, $oldPriceCol, $currencyCol, $badgeCol, $availabilityCol] as $extraCol) {
            if ('' !== $extraCol && !in_array($extraCol, $cols, true)) {
                $cols[] = $extraCol;
            }
        }
        // Felder aus URL-Pattern dazu nehmen (z.B. {slug})
        foreach (self::extractPlaceholders((string) $profile['url_pattern']) as $ph) {
            if ('id' === $ph) {
                continue;
            }
            if (in_array($ph, $allowedCols, true) && !in_array($ph, $cols, true)) {
                $cols[] = $ph;
            }
        }
        $cols = array_values(array_unique($cols));

        $colsSql = implode(', ', array_map([$sql, 'escapeIdentifier'], $cols));
        $tableSql = $sql->escapeIdentifier($tableName);
        $sortSql = $sql->escapeIdentifier($sortCol);

        $query = 'SELECT ' . $colsSql . ' FROM ' . $tableSql;
        if ('' !== $whereSql) {
            $query .= ' WHERE ' . $whereSql;
        }
        $query .= ' ORDER BY ' . $sortSql . ' ' . $sortDir
            . ' LIMIT ' . $limit;

        try {
            $rows = $sql->getArray($query, $whereParams);
        } catch (Throwable $e) {
            rex_logger::logException($e);
            return self::err('Datenbankfehler beim Laden der Liste.');
        }

        $items = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $title = '' !== $titleCol ? (string) ($row[$titleCol] ?? '') : '';
            $teaser = '' !== $teaserCol ? (string) ($row[$teaserCol] ?? '') : '';
            $imageRaw = '' !== $imageCol ? (string) ($row[$imageCol] ?? '') : '';
            $contact = [
                'firstname' => '' !== $firstnameCol ? (string) ($row[$firstnameCol] ?? '') : '',
                'lastname' => $title,
                'role' => $teaser,
                'freitext' => '' !== $freitextCol ? (string) ($row[$freitextCol] ?? '') : '',
                'phone' => '' !== $phoneCol ? (string) ($row[$phoneCol] ?? '') : '',
                'mobile' => '' !== $mobileCol ? (string) ($row[$mobileCol] ?? '') : '',
                'email' => '' !== $emailCol ? (string) ($row[$emailCol] ?? '') : '',
            ];
            $product = [
                'price' => '' !== $priceCol ? (string) ($row[$priceCol] ?? '') : '',
                'old_price' => '' !== $oldPriceCol ? (string) ($row[$oldPriceCol] ?? '') : '',
                'currency' => '' !== $currencyCol ? (string) ($row[$currencyCol] ?? '') : 'EUR',
                'badge' => '' !== $badgeCol ? (string) ($row[$badgeCol] ?? '') : '',
                'availability' => '' !== $availabilityCol ? (string) ($row[$availabilityCol] ?? '') : '',
            ];
            $items[] = [
                'id' => $id,
                'title' => $title,
                'teaser' => self::truncateText($teaser, $teaserLength),
                'image' => self::resolveImage($imageRaw),
                'media_type' => (string) $profile['media_type'],
                'href' => self::buildHref($profile, $row),
                'contact' => $contact,
                'product' => $product,
                'raw' => $row,
            ];
        }

        return [
            'profile' => $profile,
            'items' => $items,
            'error' => null,
            'layout' => $layout,
            'limit' => $limit,
        ];
    }

    /**
     * Lädt eine vom Redakteur gepickte Liste konkreter Einträge.
     * `picks` enthält Strings im Format "profileId:entryId" – Reihenfolge wird beibehalten.
     *
     * @param list<string> $picks
     * @param array<string,mixed> $elementData
     * @return array{profile: ?array<string,mixed>, items: list<array<string,mixed>>, error: ?string, layout: string, limit: int}
     */
    public static function fetchPicked(array $picks, array $elementData): array
    {
        // Layout vom Element bestimmen (Default: contact_compact – das ist der typische Picker-Use-Case).
        $layout = (string) ($elementData['layout'] ?? 'contact_compact');
        if (!in_array($layout, ListProfiles::ALLOWED_LAYOUTS, true)) {
            $layout = 'contact_compact';
        }
        $teaserLength = isset($elementData['teaser_length']) ? (int) $elementData['teaser_length'] : 160;
        if ($teaserLength < 30) {
            $teaserLength = 30;
        }
        if ($teaserLength > 800) {
            $teaserLength = 800;
        }

        // Picks gruppieren: profileId => [entryIds...] – Original-Reihenfolge merken.
        $byProfile = [];
        $order = [];
        foreach ($picks as $idx => $p) {
            $parts = explode(':', $p, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$pid, $eidRaw] = $parts;
            if ('' === $pid || !ctype_digit($eidRaw)) {
                continue;
            }
            $eid = (int) $eidRaw;
            $byProfile[$pid][] = $eid;
            $order[$pid . ':' . $eid] = $idx;
        }
        if ([] === $byProfile) {
            return self::err('Keine Einträge gewählt.');
        }

        $itemsUnordered = [];
        $firstProfile = null;

        foreach ($byProfile as $pid => $ids) {
            $profile = ListProfiles::get($pid);
            if (null === $profile) {
                continue;
            }
            $tableName = (string) $profile['table'];
            if ('' === $tableName) {
                continue;
            }
            if (null === $firstProfile) {
                $firstProfile = $profile;
            }

            $allowedCols = ListProfiles::collectColumns($tableName);
            $titleCol = self::pickCol((string) $profile['title_field'], $allowedCols, 'name');
            $teaserCol = self::pickCol((string) $profile['teaser_field'], $allowedCols, '');
            $imageCol = self::pickCol((string) $profile['image_field'], $allowedCols, '');
            $firstnameCol = self::pickCol((string) ($profile['firstname_field'] ?? ''), $allowedCols, '');
            $freitextCol = self::pickCol((string) ($profile['freitext_field'] ?? ''), $allowedCols, '');
            $phoneCol = self::pickCol((string) ($profile['phone_field'] ?? ''), $allowedCols, '');
            $mobileCol = self::pickCol((string) ($profile['mobile_field'] ?? ''), $allowedCols, '');
            $emailCol = self::pickCol((string) ($profile['email_field'] ?? ''), $allowedCols, '');
            $priceCol = self::pickCol((string) ($profile['price_field'] ?? ''), $allowedCols, '');
            $oldPriceCol = self::pickCol((string) ($profile['old_price_field'] ?? ''), $allowedCols, '');
            $currencyCol = self::pickCol((string) ($profile['currency_field'] ?? ''), $allowedCols, '');
            $badgeCol = self::pickCol((string) ($profile['badge_field'] ?? ''), $allowedCols, '');
            $availabilityCol = self::pickCol((string) ($profile['availability_field'] ?? ''), $allowedCols, '');

            $cols = ['id'];
            foreach ([$titleCol, $teaserCol, $imageCol, $firstnameCol, $freitextCol, $phoneCol, $mobileCol, $emailCol, $priceCol, $oldPriceCol, $currencyCol, $badgeCol, $availabilityCol] as $c) {
                if ('' !== $c && !in_array($c, $cols, true)) {
                    $cols[] = $c;
                }
            }
            // URL-Pattern-Felder
            foreach (self::extractPlaceholders((string) $profile['url_pattern']) as $ph) {
                if ('id' !== $ph && in_array($ph, $allowedCols, true) && !in_array($ph, $cols, true)) {
                    $cols[] = $ph;
                }
            }

            $sql = rex_sql::factory();
            $colsSql = implode(', ', array_map([$sql, 'escapeIdentifier'], $cols));
            $tableSql = $sql->escapeIdentifier($tableName);
            // Sichere Integer-Liste – $ids stammen aus ctype_digit-geprüften Strings.
            $idList = implode(',', array_map('intval', $ids));
            $query = 'SELECT ' . $colsSql . ' FROM ' . $tableSql
                . ' WHERE id IN (' . $idList . ')';

            try {
                $rows = $sql->getArray($query);
            } catch (Throwable $e) {
                rex_logger::logException($e);
                continue;
            }
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                $title = '' !== $titleCol ? (string) ($row[$titleCol] ?? '') : '';
                $teaser = '' !== $teaserCol ? (string) ($row[$teaserCol] ?? '') : '';
                $imageRaw = '' !== $imageCol ? (string) ($row[$imageCol] ?? '') : '';
                $itemsUnordered[$pid . ':' . $id] = [
                    'id' => $id,
                    'title' => $title,
                    'teaser' => self::truncateText($teaser, $teaserLength),
                    'image' => self::resolveImage($imageRaw),
                    'media_type' => (string) $profile['media_type'],
                    'href' => self::buildHref($profile, $row),
                    'contact' => [
                        'firstname' => '' !== $firstnameCol ? (string) ($row[$firstnameCol] ?? '') : '',
                        'lastname' => $title,
                        'role' => $teaser,
                        'freitext' => '' !== $freitextCol ? (string) ($row[$freitextCol] ?? '') : '',
                        'phone' => '' !== $phoneCol ? (string) ($row[$phoneCol] ?? '') : '',
                        'mobile' => '' !== $mobileCol ? (string) ($row[$mobileCol] ?? '') : '',
                        'email' => '' !== $emailCol ? (string) ($row[$emailCol] ?? '') : '',
                    ],
                    'product' => [
                        'price' => '' !== $priceCol ? (string) ($row[$priceCol] ?? '') : '',
                        'old_price' => '' !== $oldPriceCol ? (string) ($row[$oldPriceCol] ?? '') : '',
                        'currency' => '' !== $currencyCol ? (string) ($row[$currencyCol] ?? '') : 'EUR',
                        'badge' => '' !== $badgeCol ? (string) ($row[$badgeCol] ?? '') : '',
                        'availability' => '' !== $availabilityCol ? (string) ($row[$availabilityCol] ?? '') : '',
                    ],
                    'raw' => $row,
                ];
            }
        }

        // In ursprünglicher Pick-Reihenfolge sortieren
        $items = [];
        $byOrder = $order;
        asort($byOrder);
        foreach (array_keys($byOrder) as $key) {
            if (isset($itemsUnordered[$key])) {
                $items[] = $itemsUnordered[$key];
            }
        }

        return [
            'profile' => $firstProfile,
            'items' => $items,
            'error' => null,
            'layout' => $layout,
            'limit' => count($items),
        ];
    }

    /**
     * Rendert ein Bild-Tag basierend auf Item-Daten und optionalem Mediamanager-Typ.
     *
     * @param array<string,mixed> $item
     */
    public static function imgTag(array $item, string $cssClass = '', int $width = 0): string
    {
        $img = (string) ($item['image'] ?? '');
        if ('' === $img) {
            return '';
        }
        $type = (string) ($item['media_type'] ?? '');

        if (1 === preg_match('#^https?://#i', $img)) {
            $src = $img;
        } elseif ('' !== $type) {
            // Mediamanager-Typ konfiguriert -> immer ueber rex_media_manager::getUrl ausliefern.
            $src = rex_media_manager::getUrl($type, $img);
        } else {
            $src = rex_url::media($img);
        }
        $alt = rex_escape((string) ($item['title'] ?? ''));
        $widthAttr = $width > 0 ? ' width="' . $width . '"' : '';
        $classAttr = '' !== $cssClass ? ' class="' . rex_escape($cssClass) . '"' : '';
        return '<img src="' . rex_escape($src) . '" alt="' . $alt . '"' . $classAttr . $widthAttr . ' loading="lazy">';
    }

    /**
     * @param list<string> $allowed
     */
    private static function pickCol(string $val, array $allowed, string $fallback): string
    {
        $val = trim($val);
        if ('' === $val) {
            return in_array($fallback, $allowed, true) ? $fallback : '';
        }
        return in_array($val, $allowed, true) ? $val : '';
    }

    /**
     * Baut WHERE-Klausel aus Zeilen "feld=wert" (key whitelisted, value gebunden).
     * Operatoren: =, !=, <, <=, >, >=, LIKE.
     *
     * @param list<string> $allowedCols
     * @return array{0:string, 1:array<int,scalar>}
     */
    private static function buildWhere(string $raw, array $allowedCols): array
    {
        $clauses = [];
        $params = [];
        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }
            if (!preg_match('/^([a-zA-Z0-9_]+)\s*(=|!=|<=|>=|<|>|LIKE)\s*(.+)$/i', $line, $m)) {
                continue;
            }
            $field = $m[1];
            $op = strtoupper($m[2]);
            $value = trim($m[3]);
            // Quotes entfernen, falls vorhanden
            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!in_array($field, $allowedCols, true)) {
                continue;
            }
            $sql = rex_sql::factory();
            $clauses[] = $sql->escapeIdentifier($field) . ' ' . $op . ' ?';
            $params[] = self::resolveDatePlaceholder($value);
        }
        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Ersetzt Datums-Platzhalter (NOW, TODAY, TODAY+N, TODAY-N, NOW+Nh, NOW-Nh)
     * durch konkrete MySQL-kompatible Datums-/Zeit-Strings.
     */
    private static function resolveDatePlaceholder(string $value): string
    {
        $upper = strtoupper(trim($value));

        if ('NOW' === $upper) {
            return date('Y-m-d H:i:s');
        }
        if ('TODAY' === $upper) {
            return date('Y-m-d');
        }
        if (preg_match('/^TODAY\s*([+-])\s*(\d+)$/', $upper, $m)) {
            $sign = $m[1];
            $days = (int) $m[2];
            $ts = strtotime(($sign === '-' ? '-' : '+') . $days . ' days');
            return false !== $ts ? date('Y-m-d', $ts) : $value;
        }
        if (preg_match('/^NOW\s*([+-])\s*(\d+)\s*([HMD])$/', $upper, $m)) {
            $sign = $m[1];
            $count = (int) $m[2];
            $unit = ['H' => 'hours', 'M' => 'minutes', 'D' => 'days'][$m[3]];
            $ts = strtotime(($sign === '-' ? '-' : '+') . $count . ' ' . $unit);
            return false !== $ts ? date('Y-m-d H:i:s', $ts) : $value;
        }
        return $value;
    }

    /**
     * @return list<string>
     */
    private static function extractPlaceholders(string $pattern): array
    {
        if ('' === $pattern) {
            return [];
        }
        if (!preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $m)) {
            return [];
        }
        return array_values(array_unique($m[1]));
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $row
     */
    private static function buildHref(array $profile, array $row): string
    {
        $id = (int) ($row['id'] ?? 0);
        $tableName = (string) ($profile['table'] ?? '');

        // 1. virtual_urls Addon
        if (!empty($profile['use_virtual_urls']) && $id > 0 && '' !== $tableName
            && ListProfiles::hasVirtualUrls()
        ) {
            try {
                $vUrl = \FriendsOfRedaxo\VirtualUrl\VirtualUrlsHelper::getUrl($tableName, $id);
                if (null !== $vUrl && '' !== $vUrl) {
                    return $vUrl;
                }
            } catch (Throwable) {
                // Fallback
            }
        }

        // 2. Url-Addon Profil
        $urlProfile = trim((string) ($profile['url_profile'] ?? ''));
        if ('' !== $urlProfile && $id > 0 && function_exists('rex_getUrl')) {
            try {
                $url = rex_getUrl('', '', [$urlProfile => $id]);
                if ('' !== $url) {
                    return $url;
                }
            } catch (Throwable) {
                // Fallback auf Pattern
            }
        }

        // 3. URL-Pattern Fallback
        return self::buildUrl((string) ($profile['url_pattern'] ?? ''), $row);
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function buildUrl(string $pattern, array $row): string
    {
        if ('' === $pattern) {
            return '';
        }
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', static function (array $m) use ($row): string {
            $key = $m[1];
            return rawurlencode((string) ($row[$key] ?? ''));
        }, $pattern) ?? '';
    }

    private static function truncateText(string $text, int $len): string
    {
        $clean = trim(strip_tags($text));
        if ('' === $clean) {
            return '';
        }
        return mb_strlen($clean) > $len ? mb_substr($clean, 0, $len - 1) . '…' : $clean;
    }

    /**
     * Formatiert Preiswerte robust als Textausgabe.
     */
    public static function formatPrice(string $value, string $currency = 'EUR'): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $normalized = str_replace([' ', "\xc2\xa0"], '', $value);
        $normalized = str_replace(',', '.', $normalized);
        if (!is_numeric($normalized)) {
            return $value;
        }

        $number = (float) $normalized;
        $formatted = number_format($number, 2, ',', '.');

        $currency = strtoupper(trim($currency));
        if ($currency === '') {
            $currency = 'EUR';
        }

        return $formatted . ' ' . $currency;
    }

    private static function resolveImage(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return '';
        }
        if (str_contains($value, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
            $value = [] !== $parts ? $parts[0] : '';
        }
        if ('' === $value) {
            return '';
        }
        if (1 === preg_match('#^https?://#i', $value)) {
            return $value;
        }
        if (null === rex_media::get($value)) {
            return '';
        }
        return $value;
    }

    /**
     * @return array{profile: null, items: list<array<string,mixed>>, error: string, layout: string, limit: int}
     */
    private static function err(string $msg): array
    {
        /** @var list<array<string,mixed>> $items */
        $items = [];
        return [
            'profile' => null,
            'items' => $items,
            'error' => $msg,
            'layout' => 'cards',
            'limit' => 0,
        ];
    }
}
