<?php

/**
 * Builder - Übersicht
 */

$addon = rex_addon::get('builder');

// Verfügbare Elemente inkl. Metadaten zentral über registrierte Element-Pfade laden
$elements = [];
$elementPaths = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementPaths();

$resolveSourceLabel = static function ($pathKey, string $basePath): string {
	$key = is_string($pathKey) ? trim($pathKey) : '';
	if ($key === '' || is_numeric($key)) {
		$key = basename(rtrim($basePath, '/'));
	}

	if ($key === 'core' || $key === 'builder') {
		return 'core';
	}

	return $key;
};

foreach ($elementPaths as $pathKey => $basePath) {
	$basePath = rtrim((string) $basePath, '/');
	if (!is_dir($basePath)) {
		continue;
	}

	$sourceLabel = $resolveSourceLabel($pathKey, $basePath);

	$dirs = scandir($basePath);
	if (!is_array($dirs)) {
		continue;
	}

	// Sprachdateien aus allen registrierten Elementpfaden laden
	foreach ($dirs as $dir) {
		if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
			continue;
		}

		$langDir = $basePath . '/' . $dir . '/lang';
		if (is_dir($langDir)) {
			\rex_i18n::addDirectory($langDir);
		}
	}

	// Konfigurationen laden (erster Treffer pro Element-Key gewinnt)
	foreach ($dirs as $dir) {
		if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
			continue;
		}

		if (isset($elements[$dir])) {
			continue;
		}

		$configPath = $basePath . '/' . $dir . '/config.php';
		if (!is_file($configPath)) {
			continue;
		}

		$config = include $configPath;
		if (!is_array($config)) {
			continue;
		}

		$elements[$dir] = [
			'key' => $dir,
			'label' => (string) ($config['label'] ?? $dir),
			'description' => (string) ($config['description'] ?? ''),
			'icon' => (string) ($config['icon'] ?? 'fa-cube'),
			'category' => (string) ($config['category'] ?? '-'),
			'version' => (string) ($config['version'] ?? '-'),
			'source' => $sourceLabel,
		];
	}
}

$elements = array_values($elements);

usort($elements, static function (array $a, array $b): int {
	return strcasecmp($a['label'], $b['label']);
});

$groupedElements = [];
$versionMap = [];

foreach ($elements as $element) {
	$category = trim($element['category']);
	if ($category === '' || $category === '-') {
		$category = 'allgemein';
	}

	if (!isset($groupedElements[$category])) {
		$groupedElements[$category] = [];
	}
	$groupedElements[$category][] = $element;

	if ($element['version'] !== '' && $element['version'] !== '-') {
		$versionMap[$element['version']] = true;
	}
}

$categoryKeys = array_keys($groupedElements);
usort($categoryKeys, static function (string $a, string $b): int {
	if ($a === 'allgemein') {
		return -1;
	}
	if ($b === 'allgemein') {
		return 1;
	}

	return strcasecmp($a, $b);
});

$totalElements = count($elements);
$totalCategories = count($groupedElements);
$totalVersions = count($versionMap);

$hero = '';
$hero .= '<section class="builder-hero">';
$hero .= '<div class="builder-hero__bg" aria-hidden="true">';
$hero .= '<span class="builder-hero__block builder-hero__block--a"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--b"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--c"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--d"></span>';
$hero .= '</div>';
$hero .= '<div class="builder-hero__content">';
$hero .= '<div class="builder-hero__logo" aria-hidden="true"></div>';
$hero .= '<div class="builder-hero__copy">';
$hero .= '<p class="builder-hero__kicker">Builder ' . rex_escape($addon->getVersion()) . '</p>';
$hero .= '<h2 class="builder-hero__title">Page und Contentbuilder für REDAXO</h2>';
$hero .= '<p class="builder-hero__lead">Modular, visuell und erweiterbar. Komplexe Layouts und Datenstrukturen lassen sich direkt im Backend gestalten.</p>';
$hero .= '<div class="builder-hero__chips">';
$hero .= '<span class="builder-chip">YForm</span>';
$hero .= '<span class="builder-chip">Module</span>';
$hero .= '<span class="builder-chip">Media Manager</span>';
$hero .= '<span class="builder-chip">Extension Points</span>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '<div class="builder-hero__stats">';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalElements) . '</strong><span>Elemente</span></div>';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalCategories) . '</strong><span>Kategorien</span></div>';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalVersions) . '</strong><span>Versionen</span></div>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '</section>';

echo '<div style="margin-bottom:16px;">' . $hero . '</div>';

$listBody = '<div class="builder-main-overview">';
$listBody .= '<p class="help-block">Schneller Überblick über alle verfügbaren Content-Builder-Elemente mit Metadaten.</p>';

if ($elements === []) {
	$listBody .= rex_view::info('Keine Elemente gefunden.');
} else {
	$listBody .= '<div class="row builder-main-metrics">';
	$listBody .= '<div class="col-sm-4"><div class="builder-main-metric"><div class="builder-main-metric__value">' . rex_escape((string) $totalElements) . '</div><div class="builder-main-metric__label">Elemente</div></div></div>';
	$listBody .= '<div class="col-sm-4"><div class="builder-main-metric"><div class="builder-main-metric__value">' . rex_escape((string) $totalCategories) . '</div><div class="builder-main-metric__label">Kategorien</div></div></div>';
	$listBody .= '<div class="col-sm-4"><div class="builder-main-metric"><div class="builder-main-metric__value">' . rex_escape((string) $totalVersions) . '</div><div class="builder-main-metric__label">Verwendete Versionen</div></div></div>';
	$listBody .= '</div>';

	foreach ($categoryKeys as $categoryKey) {
		$categoryElements = $groupedElements[$categoryKey];
		$categoryTitle = $categoryKey;
		if ($categoryKey === 'allgemein') {
			$categoryTitle = 'Allgemein';
		}

		$listBody .= '<div class="builder-main-category">';
		$listBody .= '<h4 class="builder-main-category__title">' . rex_escape($categoryTitle) . '</h4>';
		$listBody .= '<span class="label label-default">' . rex_escape((string) count($categoryElements)) . '</span>';
		$listBody .= '</div>';

		$listBody .= '<div class="builder-main-list">';

		foreach ($categoryElements as $index => $element) {
			$description = $element['description'];
			if ($description === '') {
				$description = 'Keine Beschreibung hinterlegt.';
			}

			$itemClass = 'builder-main-item';
			if ($index > 0) {
				$itemClass .= ' is-not-first';
			}

			$listBody .= '<div class="' . $itemClass . '">';
			$listBody .= '<div class="row">';
			$listBody .= '<div class="col-sm-8">';
			$listBody .= '<div class="builder-main-title"><i class="fa ' . rex_escape($element['icon']) . '"></i> ' . rex_escape($element['label']) . '</div>';
			$listBody .= '<div class="builder-main-description">' . rex_escape($description) . '</div>';
			$listBody .= '</div>';
			$listBody .= '<div class="col-sm-4 builder-main-meta">';
			$sourceLabel = (string) ($element['source'] ?? '');
			$sourceBadgeClass = $sourceLabel === 'core' ? 'label-primary' : 'label-warning';
			if ($sourceLabel === '') {
				$sourceLabel = '-';
			}
			$listBody .= '<div class="builder-main-badges"><span class="label label-info">v' . rex_escape($element['version']) . '</span><span class="label label-default">' . rex_escape($element['key']) . '</span><span class="label ' . rex_escape($sourceBadgeClass) . '">' . rex_escape($sourceLabel) . '</span></div>';
			$listBody .= '<div class="builder-main-icon-name">' . rex_escape($element['icon']) . '</div>';
			$listBody .= '</div>';
			$listBody .= '</div>';
			$listBody .= '</div>';
		}

		$listBody .= '</div>';
	}
}

$listBody .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Element-Übersicht', false);
$fragment->setVar('body', $listBody, false);
echo $fragment->parse('core/page/section.php');
