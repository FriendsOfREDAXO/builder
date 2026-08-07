<?php

/**
 * Builder - Modul-Generator
 * Erstelle automatisch REDAXO-Module für deine Builder-Elemente
 */

$addon = rex_addon::get('builder');

/**
 * Liefert den Config-Pfad für einen Element-Key über alle registrierten Element-Pfade.
 */
function resolveElementConfigPath(string $elementKey): ?string
{
    $elementKey = trim($elementKey);
    if ($elementKey === '') {
        return null;
    }

    $paths = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementPaths();
    foreach ($paths as $basePath) {
        $configPath = rtrim((string) $basePath, '/') . '/' . $elementKey . '/config.php';
        if (is_file($configPath)) {
            return $configPath;
        }
    }

    return null;
}

/**
 * Lädt Sprachdateien aus allen registrierten Element-Pfaden.
 */
function loadElementLanguageDirectories(): void
{
    $paths = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementPaths();
    foreach ($paths as $basePath) {
        $basePath = rtrim((string) $basePath, '/');
        if (!is_dir($basePath)) {
            continue;
        }

        $dirs = scandir($basePath);
        if (!is_array($dirs)) {
            continue;
        }

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
                continue;
            }

            $langDir = $basePath . '/' . $dir . '/lang';
            if (is_dir($langDir)) {
                \rex_i18n::addDirectory($langDir);
            }
        }
    }
}

// WICHTIG: Lang-Dateien aller Elemente am Anfang laden damit Übersetzungen verfügbar sind
loadElementLanguageDirectories();

function normalizeFramework(string $framework): string
{
    $framework = trim($framework);

    return $framework !== '' ? $framework : 'uikit';
}

function isValidBuilderModuleKey(string $moduleKey): bool
{
    $moduleKey = trim($moduleKey);

    return (bool) preg_match('/^(?:yfcb_|cb_)[a-z0-9_]+$/i', $moduleKey);
}

// Helper: Generiert Modul-Code für ein Element
function generateModuleCode(string $elementKey, string $framework, int $valueId = 1): string
{
    $frameworkCode = var_export(normalizeFramework($framework), true);
    $config = [];
    $configPath = resolveElementConfigPath($elementKey);
    
    if (file_exists($configPath)) {
        $config = include $configPath;
    }
    
    $label = isset($config['label']) ? rex_escape($config['label']) : ucfirst($elementKey);
    
    $code = <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;
/**
 * Modul: {$label}
 * Element: {$elementKey}
 */

echo Module::createByValueId('{$elementKey}', {$valueId}, '{$framework}')->renderInput();
?>
PHP;

    $code = str_replace("'{$framework}'", $frameworkCode, $code);
    
    return $code;
}

/**
 * @param array<int, string> $allowedElements
 */
function exportAllowedElementsCode(array $allowedElements): string
{
    $normalizedElements = [];
    foreach ($allowedElements as $allowedElement) {
        $allowedElement = trim((string) $allowedElement);
        if ($allowedElement !== '') {
            $normalizedElements[] = $allowedElement;
        }
    }

    return var_export(array_values(array_unique($normalizedElements)), true);
}

/**
 * @param array<int, string> $allowedElements
 */
function generateFullBuilderInputCode(string $framework, int $valueId, array $allowedElements): string
{
    $allowedElementsCode = exportAllowedElementsCode($allowedElements);
    $frameworkCode = var_export(normalizeFramework($framework), true);

    return <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;
/**
 * Modul: Full Builder
 * Typ: Full Builder
 */





\$builder = Module::createWithValue({$valueId}, null, [
    'framework' => {$frameworkCode},
    'label' => rex_i18n::msg('builder_title'),
    'description' => rex_i18n::msg('builder_intro'),
    'allowed_elements' => {$allowedElementsCode},
]);

echo \$builder->getEditor();
?>
PHP;
}

/**
 * @param array<int, string> $allowedElements
 */
function generateFullBuilderOutputCode(string $framework, int $valueId, array $allowedElements): string
{
    $allowedElementsCode = exportAllowedElementsCode($allowedElements);
    $frameworkCode = var_export(normalizeFramework($framework), true);

    return <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;

\$builder = Module::createWithValue({$valueId}, Module::getSliceValueForModule({$valueId}), [
    'framework' => {$frameworkCode},
    'allowed_elements' => {$allowedElementsCode},
]);

echo \$builder->renderOutput();
?>
PHP;
}

// Bestehende Module aktualisieren (alle yfcb_* Module neu generieren)
if (rex_post('update_all_modules', 'bool')) {
    $moduleMode = rex_post('module_mode', 'string', 'single');
    $framework = normalizeFramework(rex_post('framework', 'string', 'uikit'));
    $valueId = rex_post('value_id', 'int', 1);
    $selectedElements = rex_post('elements', 'array', []);
    $fullModuleKey = trim(rex_post('full_module_key', 'string', 'yfcb_builder'));
    $fullModuleName = trim(rex_post('full_module_name', 'string', 'Builder'));
    if ($valueId < 1 || $valueId > 20) {
        $valueId = 1;
    }

    if ($moduleMode !== 'full') {
        $moduleMode = 'single';
    }

    if ($fullModuleKey === '') {
        $fullModuleKey = 'yfcb_builder';
    }

    if ($fullModuleName === '') {
        $fullModuleName = 'Builder';
    }

    if ($moduleMode === 'full' && !isValidBuilderModuleKey($fullModuleKey)) {
        echo rex_view::error('Ungültiger Modul-Key "' . rex_escape($fullModuleKey) . '": Erlaubt sind nur Keys im Format <code>yfcb_...</code> oder <code>cb_...</code> mit Buchstaben, Zahlen und Unterstrich.');
    } else {

    $updatedModules = [];
    $skippedModules = [];

    try {
        if ($moduleMode === 'full') {
            $existingSql = rex_sql::factory();
            $existingSql->setQuery('SELECT id FROM ' . rex::getTable('module') . ' WHERE `key` = :key', [':key' => $fullModuleKey]);

            if ($existingSql->getRows() === 0) {
                $skippedModules[] = $fullModuleName . ' (Modul nicht gefunden)';
            } else {
                $inputCode = generateFullBuilderInputCode($framework, $valueId, $selectedElements);
                $outputCode = generateFullBuilderOutputCode($framework, $valueId, $selectedElements);

                $updateSql = rex_sql::factory();
                $updateSql->setQuery(
                    'UPDATE ' . rex::getTable('module') . ' SET `name` = :name, `input` = :input, `output` = :output WHERE `key` = :key',
                    [
                        ':name' => $fullModuleName,
                        ':input' => $inputCode,
                        ':output' => $outputCode,
                        ':key' => $fullModuleKey,
                    ]
                );
                $updatedModules[] = $fullModuleName;
            }
        } else {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, `key`, `name` FROM ' . rex::getTable('module') . ' WHERE `key` LIKE :prefixYfcb OR `key` LIKE :prefixCb',
                [':prefixYfcb' => 'yfcb_%', ':prefixCb' => 'cb_%']
            );

            while ($sql->hasNext()) {
                $moduleKey = (string) $sql->getValue('key');
                $moduleName = (string) $sql->getValue('name');

                // Elementname aus Key ableiten: yfcb_cards -> cards, cb_cards -> cards
                if (str_starts_with($moduleKey, 'yfcb_')) {
                    $elementKey = substr($moduleKey, 5);
                } elseif (str_starts_with($moduleKey, 'cb_')) {
                    $elementKey = substr($moduleKey, 3);
                } else {
                    $skippedModules[] = $moduleName . ' (ungültiger Key)';
                    $sql->next();
                    continue;
                }

                $configPath = resolveElementConfigPath($elementKey);

                if (!file_exists($configPath)) {
                    $skippedModules[] = $moduleName . ' (Config nicht gefunden)';
                    $sql->next();
                    continue;
                }

                $inputCode = generateModuleCode($elementKey, $framework, $valueId);
                $outputCode = <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;

echo Module::create('{$elementKey}', Module::getSliceValueForModule({$valueId}), '{$framework}', {$valueId})->renderOutput();
?>
PHP;

                $updateSql = rex_sql::factory();
                $updateSql->setQuery(
                    'UPDATE ' . rex::getTable('module') . ' SET `input` = :input, `output` = :output WHERE `key` = :key',
                    [':input' => $inputCode, ':output' => $outputCode, ':key' => $moduleKey]
                );
                $updatedModules[] = $moduleName;
                $sql->next();
            }
        }
    } catch (Exception $e) {
        echo rex_view::error('Fehler beim Aktualisieren: ' . $e->getMessage());
    }

    if (!empty($updatedModules)) {
        $message = '<ul>';
        foreach ($updatedModules as $name) {
            $message .= '<li>' . rex_escape($name) . '</li>';
        }
        $message .= '</ul>';
        echo rex_view::success('Module aktualisiert: ' . $message);
    }
    if (!empty($skippedModules)) {
        $message = '<ul>';
        foreach ($skippedModules as $name) {
            $message .= '<li>' . rex_escape($name) . '</li>';
        }
        $message .= '</ul>';
        echo rex_view::warning('Übersprungen (Element-Config fehlt): ' . $message);
    }
    }
}

// Module erstellen
if (rex_post('create_modules', 'bool')) {
    $moduleMode = rex_post('module_mode', 'string', 'single');
    $selectedElements = rex_post('elements', 'array', []);
    $framework = normalizeFramework(rex_post('framework', 'string', 'uikit'));
    $valueId = rex_post('value_id', 'int', 1);
    $fullModuleKey = trim(rex_post('full_module_key', 'string', 'yfcb_builder'));
    $fullModuleName = trim(rex_post('full_module_name', 'string', 'Builder'));
    if ($valueId < 1 || $valueId > 20) {
        $valueId = 1;
    }

    if ($moduleMode !== 'full') {
        $moduleMode = 'single';
    }

    if ($fullModuleKey === '') {
        $fullModuleKey = 'yfcb_builder';
    }

    if ($fullModuleName === '') {
        $fullModuleName = 'Builder';
    }

    if ($moduleMode === 'full' && !isValidBuilderModuleKey($fullModuleKey)) {
        echo rex_view::error('Ungültiger Modul-Key "' . rex_escape($fullModuleKey) . '": Erlaubt sind nur Keys im Format <code>yfcb_...</code> oder <code>cb_...</code> mit Buchstaben, Zahlen und Unterstrich.');
    } elseif ($moduleMode === 'full') {
    
        $inputCode = generateFullBuilderInputCode($framework, $valueId, $selectedElements);
        $outputCode = generateFullBuilderOutputCode($framework, $valueId, $selectedElements);

        try {
            $existingSql = rex_sql::factory();
            $existingSql->setQuery('SELECT id FROM ' . rex::getTable('module') . ' WHERE `key` = :key', [':key' => $fullModuleKey]);

            if ($existingSql->getRows() > 0) {
                $updateSql = rex_sql::factory();
                $updateSql->setQuery(
                    'UPDATE ' . rex::getTable('module') . ' SET `name` = :name, `input` = :input, `output` = :output WHERE `key` = :key',
                    [
                        ':name' => $fullModuleName,
                        ':input' => $inputCode,
                        ':output' => $outputCode,
                        ':key' => $fullModuleKey,
                    ]
                );
            } else {
                $insertSql = rex_sql::factory();
                $insertSql->setQuery(
                    'INSERT INTO ' . rex::getTable('module') . ' (`key`, `name`, `input`, `output`) VALUES (:key, :name, :input, :output)',
                    [
                        ':key' => $fullModuleKey,
                        ':name' => $fullModuleName,
                        ':input' => $inputCode,
                        ':output' => $outputCode,
                    ]
                );
            }

            echo rex_view::success('Full-Builder-Modul erstellt/aktualisiert: ' . rex_escape($fullModuleName));
        } catch (Exception $e) {
            echo rex_view::error('Fehler beim Erstellen des Full-Builder-Moduls: ' . $e->getMessage());
        }
    } elseif (!empty($selectedElements)) {
        $createdModules = [];
        
        foreach ($selectedElements as $elementKey) {
            $elementKey = rex_escape($elementKey);
            $configPath = resolveElementConfigPath($elementKey);
            
            if (!file_exists($configPath)) {
                continue;
            }
            
            $config = include $configPath;
            $label = isset($config['label']) ? $config['label'] : ucfirst($elementKey);
            $moduleKey = 'yfcb_' . $elementKey;
            
            // Modul-Code generieren
            $inputCode = generateModuleCode($elementKey, $framework, $valueId);
            
            $outputCode = <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;

echo Module::create('{$elementKey}', Module::getSliceValueForModule({$valueId}), '{$framework}', {$valueId})->renderOutput();
?>
PHP;
            
            // In DB eintragen
            try {
                // Prüfe ob Modul existiert
                $existingSql = rex_sql::factory();
                $existingSql->setQuery('SELECT id FROM ' . rex::getTable('module') . ' WHERE `key` = ?', [$moduleKey]);
                
                if ($existingSql->getRows() > 0) {
                    // Update mit direktem SQL wegen 'key' Keyword
                    $updateSql = rex_sql::factory();
                    $updateSql->setQuery(
                        'UPDATE ' . rex::getTable('module') . ' SET `name` = :name, `input` = :input, `output` = :output WHERE `key` = :key',
                        [
                            ':name' => $label,
                            ':input' => $inputCode,
                            ':output' => $outputCode,
                            ':key' => $moduleKey
                        ]
                    );
                    $createdModules[] = $label . ' (aktualisiert)';
                } else {
                    // Insert mit direktem SQL wegen 'key' Keyword
                    $insertSql = rex_sql::factory();
                    $insertSql->setQuery(
                        'INSERT INTO ' . rex::getTable('module') . ' (`key`, `name`, `input`, `output`) VALUES (:key, :name, :input, :output)',
                        [
                            ':key' => $moduleKey,
                            ':name' => $label,
                            ':input' => $inputCode,
                            ':output' => $outputCode
                        ]
                    );
                    $createdModules[] = $label . ' (neu erstellt)';
                }
            } catch (Exception $e) {
                echo rex_view::error('Fehler beim Erstellen von Modul "' . $label . '": ' . $e->getMessage());
                continue;
            }
        }
        
        if (!empty($createdModules)) {
            $message = '<ul>';
            foreach ($createdModules as $module) {
                $message .= '<li>' . rex_escape($module) . '</li>';
            }
            $message .= '</ul>';
            echo rex_view::success('Module erstellt/aktualisiert: ' . $message);
        }
    }
}


// Alle verfügbaren Elemente laden (zentral über registrierte Element-Pfade)
$elements = [];
$elementsByCategory = [];
$elementSourceMeta = [];
$elementDescriptionMeta = [];
$elementVersionMeta = [];
$elementPaths = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementPaths();
$coreElementsPath = realpath(rex_path::addon('builder', 'elements')) ?: '';
foreach ($elementPaths as $pathKey => $basePath) {
    $basePath = rtrim((string) $basePath, '/');
    if (!is_dir($basePath)) {
        continue;
    }

    $resolvedBasePath = realpath($basePath) ?: $basePath;

    $dirs = scandir($basePath);
    if (!is_array($dirs)) {
        continue;
    }

    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || str_starts_with($dir, '.')) {
            continue;
        }

        // Ersten Treffer pro Element-Key verwenden (keine Duplikate)
        if (isset($elements[$dir])) {
            continue;
        }

        $configPath = $basePath . '/' . $dir . '/config.php';
        if (!is_file($configPath)) {
            continue;
        }

        $config = include $configPath;
        if (!is_array($config) || !isset($config['label'])) {
            continue;
        }

        $label = (string) $config['label'];
        $description = isset($config['description']) ? trim((string) $config['description']) : '';
        $version = isset($config['version']) ? trim((string) $config['version']) : '';
        $category = isset($config['category']) ? trim((string) $config['category']) : 'allgemein';
        if ($category === '' || $category === '-') {
            $category = 'allgemein';
        }

        $elements[$dir] = $label;
        $elementDescriptionMeta[$dir] = $description;
        $elementVersionMeta[$dir] = $version;
        $isExternal = $coreElementsPath === '' ? $pathKey !== 'core' : ($resolvedBasePath !== $coreElementsPath);
        $elementSourceMeta[$dir] = [
            'is_external' => $isExternal,
            'source' => (string) $pathKey,
        ];

        if (!isset($elementsByCategory[$category])) {
            $elementsByCategory[$category] = [];
        }
        $elementsByCategory[$category][$dir] = $label;
    }
}

// Sortiere Elemente
asort($elements);

$totalElements = count($elements);
$totalCategories = count($elementsByCategory);
$totalExternalElements = 0;
foreach ($elementSourceMeta as $sourceMeta) {
    if ((bool) ($sourceMeta['is_external'] ?? false)) {
        ++$totalExternalElements;
    }
}

// Sortiere Kategorien und Elemente in Kategorien
if (!empty($elementsByCategory)) {
    foreach ($elementsByCategory as $category => $categoryElements) {
        asort($categoryElements);
        $elementsByCategory[$category] = $categoryElements;
    }

    uksort($elementsByCategory, static function ($a, $b) {
        if ($a === 'allgemein') {
            return -1;
        }
        if ($b === 'allgemein') {
            return 1;
        }

        return strcasecmp((string) $a, (string) $b);
    });
}

// UI
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
$hero .= '<p class="builder-hero__kicker">Builder · Module</p>';
$hero .= '<h2 class="builder-hero__title">Module aus Builder-Elementen generieren</h2>';
$hero .= '<p class="builder-hero__lead">Erzeuge pro Element einzelne REDAXO-Module oder ein zentrales Full-Builder-Modul. Framework, erlaubte Elemente und Value-Slot steuerst du direkt hier.</p>';
$hero .= '<div class="builder-hero__chips">';
$hero .= '<span class="builder-chip">Single Module</span>';
$hero .= '<span class="builder-chip">Full Builder</span>';
$hero .= '<span class="builder-chip">UIkit / Bootstrap</span>';
$hero .= '<span class="builder-chip">YFCB Keys</span>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '<div class="builder-hero__stats">';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalElements) . '</strong><span>Elemente</span></div>';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalCategories) . '</strong><span>Kategorien</span></div>';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalExternalElements) . '</strong><span>Externe Quellen</span></div>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '</section>';

echo '<div style="margin-bottom:16px;">' . $hero . '</div>';
echo '<div style="margin:-4px 0 14px; text-align:right;"><button type="button" class="btn btn-default" data-toggle="modal" data-target="#builder-elements-overview-modal"><i class="fa fa-th-list"></i> Aktuell verfügbare Elemente und Infos</button></div>';

$currentFramework = normalizeFramework(rex_request('framework', 'string', 'uikit'));
$currentValueId = rex_request('value_id', 'int', 1);
if ($currentValueId < 1 || $currentValueId > 20) {
    $currentValueId = 1;
}

$currentFullModuleName = trim(rex_request('full_module_name', 'string', 'Builder'));
if ($currentFullModuleName === '') {
    $currentFullModuleName = 'Builder';
}

$currentFullModuleKey = trim(rex_request('full_module_key', 'string', 'yfcb_builder'));
if ($currentFullModuleKey === '') {
    $currentFullModuleKey = 'yfcb_builder';
}

$selectedElementsRaw = rex_request('elements', 'array', []);
$selectedElementsLookup = [];
foreach ($selectedElementsRaw as $selectedElementRaw) {
    $selectedElement = trim((string) $selectedElementRaw);
    if ($selectedElement !== '') {
        $selectedElementsLookup[$selectedElement] = true;
    }
}

$modulesPageView = 'all';
if (isset($builderModulesView) && is_string($builderModulesView)) {
    $modulesPageView = trim($builderModulesView);
} else {
    $modulesPageView = trim(rex_request('builder_modules_view', 'string', 'all'));
}
if (!in_array($modulesPageView, ['all', 'full', 'single'], true)) {
    $modulesPageView = 'all';
}

$showFullPanel = $modulesPageView === 'full';
$showSinglePanel = $modulesPageView === 'single';

$builderModulesOverviewUrl = rex_url::backendPage('builder/modules');
$builderModulesFullUrl = rex_url::backendPage('builder/modules_builder');
$builderModulesSingleUrl = rex_url::backendPage('builder/modules_single');

$updatableModules = [];
try {
    $moduleSql = rex_sql::factory();
    $moduleSql->setQuery(
        'SELECT `key`, `name` FROM ' . rex::getTable('module') . ' WHERE `key` LIKE :prefixYfcb OR `key` LIKE :prefixCb ORDER BY `name` ASC',
        [':prefixYfcb' => 'yfcb_%', ':prefixCb' => 'cb_%']
    );

    while ($moduleSql->hasNext()) {
        $updatableModules[] = [
            'key' => (string) $moduleSql->getValue('key'),
            'name' => (string) $moduleSql->getValue('name'),
        ];
        $moduleSql->next();
    }
} catch (Exception $e) {
    $updatableModules = [];
}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);

$buildElementPicker = static function (string $checkboxClass, string $selectAllId, string $deselectAllId, array $selectedLookup) use ($elementsByCategory, $elementSourceMeta): string {
    $html = '';
    $html .= '<div class="builder-modules-picker">';

    if ($elementsByCategory === []) {
        $html .= '<p class="builder-modules-picker__empty">Keine Elemente gefunden.</p>';
        $html .= '</div>';

        return $html;
    }

    $html .= '<div class="form-group builder-modules-picker__actions">';
    $html .= '<button type="button" id="' . rex_escape($selectAllId) . '" class="btn btn-xs btn-default">Alle auswählen</button>';
    $html .= ' ';
    $html .= '<button type="button" id="' . rex_escape($deselectAllId) . '" class="btn btn-xs btn-default">Alle abwählen</button>';
    $html .= '</div>';

    foreach ($elementsByCategory as $category => $categoryElements) {
        $html .= '<div class="builder-modules-picker__category">';
        $html .= '<strong class="builder-modules-picker__category-title">' . rex_escape((string) $category) . '</strong> ';
        $html .= '<span class="label label-default">' . count($categoryElements) . '</span>';
        $html .= '</div>';

        foreach ($categoryElements as $elementKey => $elementLabel) {
            $moduleKey = 'yfcb_' . $elementKey;
            $sourceInfo = $elementSourceMeta[$elementKey] ?? ['is_external' => false, 'source' => 'core'];
            $isExternal = (bool) ($sourceInfo['is_external'] ?? false);
            $source = (string) ($sourceInfo['source'] ?? 'core');
            $badgeClass = $isExternal ? 'label-info' : 'label-success';
            $badgeText = $isExternal ? 'extern' : 'intern';
            $isSelected = isset($selectedLookup[$elementKey]);

            $html .= '<div class="checkbox builder-modules-picker__item">';
            $html .= '<label>';
            $html .= '<input type="checkbox" class="' . rex_escape($checkboxClass) . '" name="elements[]" value="' . rex_escape($elementKey) . '"' . ($isSelected ? ' checked="checked"' : '') . '>';
            $html .= ' <strong>' . rex_escape((string) $elementLabel) . '</strong> ';
            $html .= '<span class="label ' . $badgeClass . '">' . $badgeText . '</span> ';
            $html .= '<small class="builder-modules-picker__source">(' . rex_escape($source) . ')</small> ';
            $html .= '<small class="builder-modules-picker__key">(Key: ' . rex_escape($moduleKey) . ')</small>';
            $html .= '</label>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';

    return $html;
};

$sectionTitle = 'Module erstellen - klar getrennte Modi';
if ($modulesPageView === 'full') {
    $sectionTitle = 'Buildermodul (Full Builder)';
} elseif ($modulesPageView === 'single') {
    $sectionTitle = 'Einzelmodule pro Element';
}
$fragment->setVar('title', $sectionTitle, false);

$content = '';
$content .= '<div class="btn-group" role="group" aria-label="Modulbereiche" style="margin:0 0 14px;">';
$content .= '<a class="btn btn-default' . ($modulesPageView === 'all' ? ' active' : '') . '" href="' . rex_escape($builderModulesOverviewUrl) . '"><i class="fa fa-compass"></i> Übersicht</a>';
$content .= '<a class="btn btn-default' . ($modulesPageView === 'full' ? ' active' : '') . '" href="' . rex_escape($builderModulesFullUrl) . '"><i class="fa fa-cubes"></i> Buildermodul</a>';
$content .= '<a class="btn btn-default' . ($modulesPageView === 'single' ? ' active' : '') . '" href="' . rex_escape($builderModulesSingleUrl) . '"><i class="fa fa-th-large"></i> Einzelmodule</a>';
$content .= '</div>';

$content .= $modulesPageView === 'all' ? '<p class="help-block">Diese Übersichtsseite enthält bewusst keine Formulare mehr. Öffne bitte den gewünschten Bereich über die Buttons oben.</p>' : '';

if ($modulesPageView === 'all') {
    $content .= '<div class="row" style="margin-top:8px;">';
    $content .= '<div class="col-sm-6">';
    $content .= '<div class="panel panel-primary">';
    $content .= '<div class="panel-heading"><strong>Buildermodul (Full Builder)</strong></div>';
    $content .= '<div class="panel-body">';
    $content .= '<p>Ein zentrales Modul mit frei definierter erlaubter Elementliste für den redaktionellen Einsatz.</p>';
    $content .= '<p><a class="btn btn-primary" href="' . rex_escape($builderModulesFullUrl) . '"><i class="fa fa-cubes"></i> Zur Buildermodul-Seite</a></p>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '<div class="col-sm-6">';
    $content .= '<div class="panel panel-default">';
    $content .= '<div class="panel-heading"><strong>Einzelmodule</strong></div>';
    $content .= '<div class="panel-body">';
    $content .= '<p>Pro ausgewähltem Element wird ein eigenes REDAXO-Modul erstellt oder aktualisiert.</p>';
    $content .= '<p><a class="btn btn-default" href="' . rex_escape($builderModulesSingleUrl) . '"><i class="fa fa-th-large"></i> Zur Einzelmodul-Seite</a></p>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</div>';
}

if ($showFullPanel) {
$content .= '<div class="panel panel-primary">';
$content .= '<div class="panel-heading"><strong>Bereich A · Buildermodul (Full Builder)</strong></div>';
$content .= '<div class="panel-body">';
$content .= '<p class="help-block">Nutze diesen Bereich, wenn Redakteure in einem einzigen Modul mehrere Builder-Elemente kombinieren sollen.</p>';
$content .= '<form action="' . rex_url::currentBackendPage() . '" method="post" id="builder-modules-form-full">';
$content .= '<input type="hidden" name="module_mode" value="full">';

$content .= '<div class="row" id="full-builder-fields">';
$content .= '<div class="col-sm-6">';
$content .= '<div class="form-group">';
$content .= '<label for="full_module_name"><strong>Full-Builder Modulname</strong></label>';
$content .= '<input class="form-control" id="full_module_name" name="full_module_name" value="' . rex_escape($currentFullModuleName) . '">';
$content .= '</div>';
$content .= '</div>';
$content .= '<div class="col-sm-6">';
$content .= '<div class="form-group">';
$content .= '<label for="full_module_key"><strong>Full-Builder Modul-Key</strong></label>';
$content .= '<input class="form-control" id="full_module_key" name="full_module_key" value="' . rex_escape($currentFullModuleKey) . '" pattern="^(yfcb_|cb_).+" required="required">';
$content .= '<small class="help-block">Wird nur im Full-Builder-Modus verwendet. Muss mit <code>yfcb_</code> oder <code>cb_</code> beginnen.</small>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';

$content .= '<div id="full-builder-existing-module" class="form-group">';
$content .= '<label for="builder-full-existing-module-preset"><strong>Vorhandenes Buildermodul übernehmen (optional)</strong></label>';
if ($updatableModules === []) {
    $content .= '<select class="form-control" id="builder-full-existing-module-preset" disabled="disabled">';
    $content .= '<option value="">Keine bestehenden yfcb_*/cb_*-Module gefunden</option>';
    $content .= '</select>';
    $content .= '<small class="help-block">Sobald ein bestehendes yfcb_*- oder cb_*-Modul vorhanden ist, kannst du hier Key und Name direkt übernehmen.</small>';
} else {
    $content .= '<select class="form-control" id="builder-full-existing-module-preset">';
    $content .= '<option value="">Bitte vorhandenes Modul auswählen …</option>';
    foreach ($updatableModules as $updatableModule) {
        $moduleName = $updatableModule['name'];
        $moduleKey = $updatableModule['key'];
        $content .= '<option value="' . rex_escape($moduleKey) . '" data-module-key="' . rex_escape($moduleKey) . '" data-module-name="' . rex_escape($moduleName) . '">';
        $content .= rex_escape($moduleName . ' (' . $moduleKey . ')');
        $content .= '</option>';
    }
    $content .= '</select>';
    $content .= '<small class="help-block">Auswahl übernimmt Modul-Key und Modulname in die beiden Felder oben.</small>';
}
$content .= '</div>';

// Framework-Auswahl
$content .= '<div class="form-group">';
$content .= '<label for="framework"><strong>Framework</strong></label>';
$content .= '<input class="form-control" id="framework" name="framework" list="builder-framework-options" value="' . rex_escape($currentFramework) . '">';
$content .= '<datalist id="builder-framework-options">';
$content .= '<option value="uikit">';
$content .= '<option value="bootstrap">';
$content .= '<option value="plain">';
$content .= '</datalist>';
$content .= '<small class="help-block">Vorschläge: uikit, bootstrap, plain. Du kannst hier auch einen freien Template-Key eintragen.</small>';
$content .= '</div>';

// REX_VALUE Slot Auswahl
$content .= '<div class="form-group">';
$content .= '<label for="value_id"><strong>REX_VALUE Slot</strong></label>';
$content .= '<select class="form-control" id="value_id" name="value_id">';
for ($i = 1; $i <= 20; ++$i) {
    $selected = ($currentValueId === $i) ? ' selected="selected"' : '';
    $content .= '<option value="' . $i . '"' . $selected . '>REX_VALUE[' . $i . ']</option>';
}
$content .= '</select>';
$content .= '<small class="help-block">Legt fest, in welchem VALUE-Feld das Modul seine JSON-Daten speichert und lädt.</small>';
$content .= '</div>';

$content .= '<div class="form-group">';
$content .= '<label><strong>Erlaubte Elemente für das Buildermodul</strong></label>';
$content .= '<p class="help-block">Nur diese Elemente sind später im Full-Builder-Modul auswählbar.</p>';
$content .= '<p class="help-block"><span class="label label-success">intern</span> aus builder · <span class="label label-info">extern</span> aus registrierten Addons/Pfaden</p>';
$content .= $buildElementPicker('builder-element-checkbox-full', 'builder-elements-select-all-full', 'builder-elements-deselect-all-full', $selectedElementsLookup);
$content .= '</div>';

$content .= '<div class="form-group">';
$content .= '<button type="submit" name="create_modules" value="1" class="btn btn-primary">';
$content .= '<i class="fa fa-plus"></i> Buildermodul erstellen/aktualisieren';
$content .= '</button>';
$content .= ' ';
$content .= '<button type="submit" name="update_all_modules" value="1" class="btn btn-default">';
$content .= '<i class="fa fa-refresh"></i> Bestehendes Buildermodul aktualisieren';
$content .= '</button>';
$content .= '<p class="help-block">Aktualisieren wirkt hier nur auf den angegebenen Full-Builder-Modul-Key.</p>';
$content .= '</div>';

$content .= '</form>';
$content .= '</div>';
$content .= '</div>';
}

if ($showSinglePanel) {
$content .= '<div class="panel panel-default">';
$content .= '<div class="panel-heading"><strong>Bereich B · Einzelmodule pro Element</strong></div>';
$content .= '<div class="panel-body">';
$content .= '<p class="help-block">Nutze diesen Bereich, wenn jedes Element als eigenes REDAXO-Modul zur Verfügung stehen soll.</p>';
$content .= '<form action="' . rex_url::currentBackendPage() . '" method="post" id="builder-modules-form-single">';
$content .= '<input type="hidden" name="module_mode" value="single">';

$content .= '<div class="form-group">';
$content .= '<label for="framework_single"><strong>Framework</strong></label>';
$content .= '<input class="form-control" id="framework_single" name="framework" list="builder-framework-options-single" value="' . rex_escape($currentFramework) . '">';
$content .= '<datalist id="builder-framework-options-single">';
$content .= '<option value="uikit">';
$content .= '<option value="bootstrap">';
$content .= '<option value="plain">';
$content .= '</datalist>';
$content .= '<small class="help-block">Vorschläge: uikit, bootstrap, plain. Du kannst hier auch einen freien Template-Key eintragen.</small>';
$content .= '</div>';

$content .= '<div class="form-group">';
$content .= '<label for="value_id_single"><strong>REX_VALUE Slot</strong></label>';
$content .= '<select class="form-control" id="value_id_single" name="value_id">';
for ($i = 1; $i <= 20; ++$i) {
    $selected = ($currentValueId === $i) ? ' selected="selected"' : '';
    $content .= '<option value="' . $i . '"' . $selected . '>REX_VALUE[' . $i . ']</option>';
}
$content .= '</select>';
$content .= '<small class="help-block">Legt fest, in welchem VALUE-Feld das Einzelmodul seine JSON-Daten speichert und lädt.</small>';
$content .= '</div>';

$content .= '<div class="form-group">';
$content .= '<label><strong>Elemente für Einzelmodule</strong></label>';
$content .= '<p class="help-block">Für jedes ausgewählte Element wird ein eigenes Modul mit Key <code>yfcb_[element]</code> erstellt oder aktualisiert.</p>';
$content .= '<p class="help-block"><span class="label label-success">intern</span> aus builder · <span class="label label-info">extern</span> aus registrierten Addons/Pfaden</p>';
$content .= $buildElementPicker('builder-element-checkbox-single', 'builder-elements-select-all-single', 'builder-elements-deselect-all-single', $selectedElementsLookup);
$content .= '</div>';

$content .= '<div class="form-group">';
$content .= '<button type="submit" name="create_modules" value="1" class="btn btn-primary">';
$content .= '<i class="fa fa-plus"></i> Einzelmodule erstellen/aktualisieren';
$content .= '</button>';
$content .= ' ';
$content .= '<button type="submit" name="update_all_modules" value="1" class="btn btn-default">';
$content .= '<i class="fa fa-refresh"></i> Alle bestehenden Einzelmodule aktualisieren';
$content .= '</button>';
$content .= '<p class="help-block">Aktualisieren wirkt hier auf alle vorhandenen <code>yfcb_*</code>- und <code>cb_*</code>-Module.</p>';
$content .= '</div>';

$content .= '</form>';
$content .= '</div>';
$content .= '</div>';
}

$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

$overviewModalBody = '';
$overviewModalBody .= '<div class="builder-overview-modal">';
$overviewModalBody .= '<p class="help-block">Übersicht der aktuell registrierten Builder-Elemente inklusive Quelle und Modul-Key.</p>';

if ($elements === []) {
    $overviewModalBody .= '<p class="text-muted">Keine Elemente gefunden.</p>';
} else {
    $overviewModalBody .= '<p style="margin:0 0 12px;">';
    $overviewModalBody .= '<strong>Elemente:</strong> ' . rex_escape((string) $totalElements) . ' · ';
    $overviewModalBody .= '<strong>Kategorien:</strong> ' . rex_escape((string) $totalCategories) . ' · ';
    $overviewModalBody .= '<strong>Externe Quellen:</strong> ' . rex_escape((string) $totalExternalElements);
    $overviewModalBody .= '</p>';

    foreach ($elementsByCategory as $category => $categoryElements) {
        $overviewModalBody .= '<div class="panel panel-default" style="margin-bottom:10px;">';
        $overviewModalBody .= '<div class="panel-heading" style="padding:8px 12px;">';
        $overviewModalBody .= '<strong>' . rex_escape((string) $category) . '</strong> ';
        $overviewModalBody .= '<span class="label label-default">' . rex_escape((string) count($categoryElements)) . '</span>';
        $overviewModalBody .= '</div>';
        $overviewModalBody .= '<div class="panel-body" style="padding:10px 12px;">';

        foreach ($categoryElements as $elementKey => $elementLabel) {
            $moduleKey = 'yfcb_' . $elementKey;
            $elementDescription = trim((string) ($elementDescriptionMeta[$elementKey] ?? ''));
            $elementVersion = trim((string) ($elementVersionMeta[$elementKey] ?? ''));
            $sourceInfo = $elementSourceMeta[$elementKey] ?? ['is_external' => false, 'source' => 'core'];
            $isExternal = (bool) ($sourceInfo['is_external'] ?? false);
            $source = (string) ($sourceInfo['source'] ?? 'core');
            $sourceTypeText = $isExternal ? 'extern' : 'intern';
            $sourceTypeBadgeClass = $isExternal ? 'label-info' : 'label-success';

            $overviewModalBody .= '<div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid #eef2f5;">';
            $overviewModalBody .= '<div style="flex:1 1 auto;min-width:0;"><strong>' . rex_escape((string) $elementLabel) . '</strong>';
            if ($elementDescription !== '') {
                $overviewModalBody .= '<div class="text-muted" style="font-size:12px;line-height:1.4;margin-top:2px;">' . rex_escape($elementDescription) . '</div>';
            }
            $overviewModalBody .= '<div class="text-muted" style="font-size:12px;line-height:1.5;margin-top:3px;">';
            $overviewModalBody .= '<strong>Version:</strong> ' . rex_escape($elementVersion !== '' ? $elementVersion : '-') . '<br>';
            $overviewModalBody .= '<strong>Modul-Key:</strong> <code>' . rex_escape($moduleKey) . '</code>';
            $overviewModalBody .= '</div>';
            $overviewModalBody .= '</div>';
            $overviewModalBody .= '<div style="flex:0 0 auto;white-space:nowrap;text-align:right;align-self:flex-start;">';
            $overviewModalBody .= '<span class="label ' . $sourceTypeBadgeClass . '" style="margin-right:4px;">' . rex_escape($sourceTypeText) . '</span>';
            $overviewModalBody .= '<span class="label label-default">' . rex_escape($source) . '</span>';
            $overviewModalBody .= '</div>';
            $overviewModalBody .= '</div>';
        }

        $overviewModalBody .= '</div>';
        $overviewModalBody .= '</div>';
    }
}

$overviewModalBody .= '</div>';

$overviewModal = '';
$overviewModal .= '<div class="modal fade" id="builder-elements-overview-modal" tabindex="-1" role="dialog" aria-labelledby="builder-elements-overview-modal-label">';
$overviewModal .= '<div class="modal-dialog modal-lg" role="document">';
$overviewModal .= '<div class="modal-content">';
$overviewModal .= '<div class="modal-header">';
$overviewModal .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
$overviewModal .= '<h4 class="modal-title" id="builder-elements-overview-modal-label">Aktuell verfügbare Elemente und Infos</h4>';
$overviewModal .= '</div>';
$overviewModal .= '<div class="modal-body" style="max-height:70vh;overflow:auto;">' . $overviewModalBody . '</div>';
$overviewModal .= '<div class="modal-footer">';
$overviewModal .= '<button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>';
$overviewModal .= '</div>';
$overviewModal .= '</div>';
$overviewModal .= '</div>';
$overviewModal .= '</div>';

echo $overviewModal;

// Info-Box
$infoContent = '<p><i class="fa fa-info-circle"></i> ';
$infoContent .= '<strong>So funktioniert es:</strong><br>';
$infoContent .= '1. Entscheide zuerst zwischen Bereich A (Buildermodul) und Bereich B (Einzelmodule)<br>';
$infoContent .= '2. Konfiguriere im gewählten Bereich Framework, Value-Slot und Elemente<br>';
$infoContent .= '3. Klicke auf den passenden Erstellen- oder Aktualisieren-Button des Bereichs<br>';
$infoContent .= '4. Die Module werden automatisch in der REDAXO-Datenbank angelegt und sind sofort einsatzbereit<br>';
$infoContent .= '<br>';
$infoContent .= '<strong>Module Key Format:</strong> Einzelmodule verwenden <code>yfcb_[element-name]</code> (z.B. yfcb_cards). Für das Buildermodul kannst du Key und Name frei festlegen.<br>';
$infoContent .= 'Du kannst die Module in deinen REDAXO-Seiten verwenden, indem du sie in dein Seitenlayout einbindest.';
$infoContent .= '</p>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', 'Info', false);
$fragment->setVar('body', $infoContent, false);
echo $fragment->parse('core/page/section.php');
