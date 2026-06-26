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

// Helper: Generiert Modul-Code für ein Element
function generateModuleCode(string $elementKey, string $framework, int $valueId = 1): string
{
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

    return <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;
/**
 * Modul: Full Builder
 * Typ: Full Builder
 */





\$builder = Module::createWithValue({$valueId}, null, [
    'framework' => '{$framework}',
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

    return <<<PHP
<?php
use FriendsOfREDAXO\Builder\Module;

\$builder = Module::createWithValue({$valueId}, Module::getSliceValueForModule({$valueId}), [
    'framework' => '{$framework}',
    'allowed_elements' => {$allowedElementsCode},
]);

echo \$builder->renderOutput();
?>
PHP;
}

// Bestehende Module aktualisieren (alle yfcb_* Module neu generieren)
if (rex_post('update_all_modules', 'bool')) {
    $moduleMode = rex_post('module_mode', 'string', 'single');
    $framework = rex_post('framework', 'string', 'uikit');
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
                'SELECT id, `key`, `name` FROM ' . rex::getTable('module') . ' WHERE `key` LIKE :prefix',
                [':prefix' => 'yfcb_%']
            );

            while ($sql->hasNext()) {
                $moduleKey = (string) $sql->getValue('key');
                $moduleName = (string) $sql->getValue('name');
                // Elementname aus Key ableiten: yfcb_cards → cards
                $elementKey = substr($moduleKey, 5);
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

// Module erstellen
if (rex_post('create_modules', 'bool')) {
    $moduleMode = rex_post('module_mode', 'string', 'single');
    $selectedElements = rex_post('elements', 'array', []);
    $framework = rex_post('framework', 'string', 'uikit');
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
    
    if ($moduleMode === 'full') {
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
        $category = isset($config['category']) ? trim((string) $config['category']) : 'allgemein';
        if ($category === '' || $category === '-') {
            $category = 'allgemein';
        }

        $elements[$dir] = $label;
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

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', 'Module erstellen/aktualisieren', false);

$content = '';
$content .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';

// Modus-Auswahl
$content .= '<div class="form-group">';
$content .= '<label for="module_mode"><strong>Modus</strong></label>';
$content .= '<select class="form-control" id="module_mode" name="module_mode">';
$content .= '<option value="single">Einzelmodul pro Element</option>';
$content .= '<option value="full">Ein Full-Builder-Modul</option>';
$content .= '</select>';
$content .= '<small class="help-block">Im Einzelmodus wird für jedes ausgewählte Element ein Modul erzeugt. Im Full-Builder-Modus wird ein einziges Modul erzeugt; die Auswahl unten begrenzt dabei optional die erlaubten Elemente.</small>';
$content .= '</div>';

// Full-Builder Modulname/Key
$content .= '<div class="row">';
$content .= '<div class="col-sm-6">';
$content .= '<div class="form-group">';
$content .= '<label for="full_module_name"><strong>Full-Builder Modulname</strong></label>';
$content .= '<input class="form-control" id="full_module_name" name="full_module_name" value="Builder">';
$content .= '</div>';
$content .= '</div>';
$content .= '<div class="col-sm-6">';
$content .= '<div class="form-group">';
$content .= '<label for="full_module_key"><strong>Full-Builder Modul-Key</strong></label>';
$content .= '<input class="form-control" id="full_module_key" name="full_module_key" value="yfcb_builder">';
$content .= '<small class="help-block">Wird nur im Full-Builder-Modus verwendet.</small>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';

// Framework-Auswahl
$content .= '<div class="form-group">';
$content .= '<label for="framework"><strong>Framework</strong></label>';
$content .= '<select class="form-control" id="framework" name="framework">';
$content .= '<option value="uikit">UIkit</option>';
$content .= '<option value="bootstrap">Bootstrap</option>';
$content .= '</select>';
$content .= '<small class="help-block">Wähle das Framework, das du in deinen Modulen verwenden möchtest.</small>';
$content .= '</div>';

// REX_VALUE Slot Auswahl
$content .= '<div class="form-group">';
$content .= '<label for="value_id"><strong>REX_VALUE Slot</strong></label>';
$content .= '<select class="form-control" id="value_id" name="value_id">';
for ($i = 1; $i <= 20; ++$i) {
    $selected = (1 === $i) ? ' selected="selected"' : '';
    $content .= '<option value="' . $i . '"' . $selected . '>REX_VALUE[' . $i . ']</option>';
}
$content .= '</select>';
$content .= '<small class="help-block">Legt fest, in welchem VALUE-Feld das Modul seine JSON-Daten speichert und lädt.</small>';
$content .= '</div>';

// Elemente auswählen
$content .= '<div class="form-group">';
$content .= '<label><strong>Elemente auswählen</strong></label>';
$content .= '<p class="help-block">Im Einzelmodus werden dafür einzelne REDAXO-Module erzeugt. Im Full-Builder-Modus dient die Auswahl als erlaubte Elementliste.</p>';
$content .= '<p class="help-block"><span class="label label-success">intern</span> aus builder · <span class="label label-info">extern</span> aus registrierten Addons/Pfaden</p>';
$content .= '<div class="builder-modules-picker">';

if (empty($elements)) {
    $content .= '<p class="builder-modules-picker__empty">Keine Elemente gefunden.</p>';
} else {
    $content .= '<div class="form-group builder-modules-picker__actions">';
    $content .= '<button type="button" class="btn btn-xs btn-default" onclick="document.querySelectorAll(\'.element-checkbox\').forEach(c => c.checked = true);">Alle auswählen</button>';
    $content .= ' ';
    $content .= '<button type="button" class="btn btn-xs btn-default" onclick="document.querySelectorAll(\'.element-checkbox\').forEach(c => c.checked = false);">Alle abwählen</button>';
    $content .= '</div>';
    
    foreach ($elementsByCategory as $category => $categoryElements) {
        $content .= '<div class="builder-modules-picker__category">';
        $content .= '<strong class="builder-modules-picker__category-title">' . rex_escape($category) . '</strong> ';
        $content .= '<span class="label label-default">' . count($categoryElements) . '</span>';
        $content .= '</div>';

        foreach ($categoryElements as $elementKey => $elementLabel) {
            $moduleKey = 'yfcb_' . $elementKey;
            $sourceInfo = $elementSourceMeta[$elementKey] ?? ['is_external' => false, 'source' => 'core'];
            $isExternal = (bool) ($sourceInfo['is_external'] ?? false);
            $source = (string) ($sourceInfo['source'] ?? 'core');
            $badgeClass = $isExternal ? 'label-info' : 'label-success';
            $badgeText = $isExternal ? 'extern' : 'intern';
            $content .= '<div class="checkbox builder-modules-picker__item">';
            $content .= '<label>';
            $content .= '<input type="checkbox" class="element-checkbox" name="elements[]" value="' . rex_escape($elementKey) . '">';
            $content .= ' <strong>' . rex_escape($elementLabel) . '</strong> ';
            $content .= '<span class="label ' . $badgeClass . '">' . $badgeText . '</span> ';
            $content .= '<small class="builder-modules-picker__source">(' . rex_escape($source) . ')</small> ';
            $content .= '<small class="builder-modules-picker__key">(Key: ' . rex_escape($moduleKey) . ')</small>';
            $content .= '</label>';
            $content .= '</div>';
        }
    }
}

$content .= '</div>';
$content .= '</div>';

// Buttons
$content .= '<div class="form-group">';
$content .= '<button type="submit" name="create_modules" value="1" class="btn btn-primary">';
$content .= '<i class="fa fa-plus"></i> Module erstellen';
$content .= '</button>';
$content .= ' ';
$content .= '<button type="submit" name="update_all_modules" value="1" class="btn btn-default">';
$content .= '<i class="fa fa-refresh"></i> Bestehende Module aktualisieren';
$content .= '</button>';
$content .= '<p class="help-block">Im Einzelmodus werden alle vorhandenen <code>yfcb_*</code>-Module aktualisiert. Im Full-Builder-Modus wird nur das konfigurierte Full-Builder-Modul aktualisiert.</p>';
$content .= '</div>';

$content .= '</form>';

$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// Info-Box
$infoContent = '<p><i class="fa fa-info-circle"></i> ';
$infoContent .= '<strong>So funktioniert es:</strong><br>';
$infoContent .= '1. Wähle die gewünschten Elemente aus<br>';
$infoContent .= '2. Wähle den Modus (Einzelmodule oder Full Builder)<br>';
$infoContent .= '3. Wähle dein Framework (UIkit oder Bootstrap)<br>';
$infoContent .= '4. Klicke auf "Module erstellen"<br>';
$infoContent .= '5. Die Module werden automatisch in der REDAXO-Datenbank angelegt und sind sofort einsatzbereit<br>';
$infoContent .= '<br>';
$infoContent .= '<strong>Module Key Format:</strong> Einzelmodule verwenden <code>yfcb_[element-name]</code> (z.B. yfcb_cards). Für den Full Builder kannst du Key und Name frei festlegen.<br>';
$infoContent .= 'Du kannst die Module in deinen REDAXO-Seiten verwenden, indem du sie in dein Seitenlayout einbindest.';
$infoContent .= '</p>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', 'Info', false);
$fragment->setVar('body', $infoContent, false);
echo $fragment->parse('core/page/section.php');
