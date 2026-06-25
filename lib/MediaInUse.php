<?php

namespace FriendsOfREDAXO\Builder;

use rex_extension;
use rex_extension_point;
use rex_sql;
use rex_yform_manager;
use rex_yform_manager_field;
use rex_yform_manager_table;

class MediaInUse
{
    /**
     * Prüft, ob eine Mediapool-Datei in content_builder-Feldern verwendet wird.
     *
     * @param rex_extension_point<mixed> $ep
     *
     * @return mixed
     */
    public static function isMediaInUse(rex_extension_point $ep): mixed
    {
        $params = $ep->getParams();
        $warning = $ep->getSubject();
        if (!is_array($warning)) {
            $warning = (array) $warning;
        }

        $filename = trim((string) ($params['filename'] ?? ''));
        if ($filename === '') {
            return $warning;
        }

        $sql = rex_sql::factory();
        $fields = $sql->getArray(
            'SELECT `table_name`, `name` FROM `' . rex_yform_manager_field::table() . '` WHERE `type_id` = "value" AND `type_name` = "content_builder"'
        );

        // Erweiterbar halten (analog zu mform).
        /** @phpstan-ignore-next-line argument.type (template covariance limitation in rex_extension_point) */
        $fields = rex_extension::registerPoint(new rex_extension_point('YFORM_MEDIA_IS_IN_USE', $fields));

        if (!is_array($fields) || count($fields) === 0) {
            return $warning;
        }

        $messages = '';
        $likePattern = $sql->escape('%' . $filename . '%');

        foreach ($fields as $field) {
            $tableName = (string) ($field['table_name'] ?? '');
            $columnName = (string) ($field['name'] ?? '');
            if ($tableName === '' || $columnName === '') {
                continue;
            }

            $rows = $sql->getArray(
                'SELECT `id` FROM `' . $tableName . '` WHERE ' . $sql->escapeIdentifier($columnName) . ' LIKE ' . $likePattern
            );
            if (!is_array($rows) || count($rows) === 0) {
                continue;
            }

            $table = rex_yform_manager_table::get($tableName);
            if (!$table instanceof rex_yform_manager_table) {
                continue;
            }

            $tableLabel = (string) $table->getName();
            if ($tableLabel === '') {
                $tableLabel = $table->getTableName();
            }

            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $editUrl = rex_yform_manager::url($table->getTableName(), $id);

                $messages .= '<li><a href="' . $editUrl . '">'
                    . rex_escape($tableLabel) . ' [id=' . $id . ']</a></li>';
            }
        }

        if ($messages !== '') {
            $warning[] = 'YForm Content Builder<br /><ul>' . $messages . '</ul>';
        }

        return $warning;
    }
}
