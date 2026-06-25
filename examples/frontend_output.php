<?php

use FriendsOfREDAXO\Builder\Helper;

/**
 * Beispiel: Frontend-Ausgabe von Content Builder Slices
 *
 * 1) Ein Datensatz (YORM + where):
 *
 * $dataset = \Project\Model\ContentPage::query()
 *     ->where('status', 1)
 *     ->where('clang_id', rex_clang::getCurrentId())
 *     ->where('slug', 'startseite')
 *     ->findOne();
 *
 * if ($dataset !== null) {
 *     echo Helper::outputDataset($dataset, 'content_builder', 'bootstrap');
 * }
 *
 * 2) Mehrere Datensaetze (YORM + where + limit):
 *
 * $items = \Project\Model\ContentPage::query()
 *     ->where('status', 1)
 *     ->where('category_id', 5)
 *     ->orderBy('prio', 'asc')
 *     ->limit(10)
 *     ->find();
 *
 * foreach ($items as $item) {
 *     echo Helper::outputDataset($item, 'content_builder', 'bootstrap');
 * }
 *
 * 3) Einzeiler ueber Tabelle + ID:
 *
 * echo Helper::outputDatasetById('rex_content_pages', 42, 'content_builder', 'bootstrap');
 *
 * 4) Einzeiler wenn Datensatz bereits vorhanden ist:
 *
 * echo Helper::outputDataset($dataset, 'content_builder', 'bootstrap');
 */

$framework = 'bootstrap'; // oder 'uikit', 'plain'

// Optional: Wenn im Scope bereits ein YORM-Datensatz vorhanden ist.
if (isset($dataset) && is_object($dataset) && method_exists($dataset, 'getValue')) {
	echo Helper::outputDataset($dataset, 'content_builder', $framework);
}

// Optional: Im YForm-Context (z.B. value Callback).
if (isset($this) && is_object($this) && method_exists($this, 'getValue')) {
	echo Helper::outputRaw($this->getValue('content_builder'), $framework);
}
