<?php

namespace FriendsOfREDAXO\Builder\Api;

use FriendsOfREDAXO\Builder\ListProfiles;
use rex;
use rex_api_function;
use rex_api_result;
use rex_request;
use rex_response;

/**
 * API: Spalten einer YForm-Tabelle für die Profil-Konfiguration laden.
 *
 * Aufruf: /redaxo/index.php?rex-api-call=yform_list_columns&table=rex_news
 */
class ListColumnsApi extends rex_api_function
{
    protected $published = false;

    public function execute(): rex_api_result
    {
        if (!rex::isBackend() || !rex::getUser()) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::cleanOutputBuffers();
            rex_response::sendJson(['error' => 'forbidden']);
            exit;
        }

        rex_response::cleanOutputBuffers();

        $table = (string) rex_request('table', 'string', '');
        $columns = '' !== $table ? ListProfiles::collectColumns($table) : [];
        $urlProfiles = ListProfiles::collectUrlProfiles($table);

        rex_response::sendJson([
            'table' => $table,
            'columns' => $columns,
            'url_profiles' => $urlProfiles,
        ]);
        exit;
    }
}
