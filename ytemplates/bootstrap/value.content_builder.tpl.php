<?php
/**
 * @var rex_yform_value_abstract $this
 * @var array $value
 * @var string $field_name
 * @var string $field_id
 * @var string $label
 * @var string $notice
 * @var bool $required
 * @var string $description
 * @var string $framework
 * @var array $available_elements
 * @var bool $legacy_mode_enabled
 * @var bool $legacy_is_active
 * @var string $legacy_html
 * @var string $legacy_cke5_profile
 * @var string $legacy_cke5_lang
 * @var array $legacy_editor_attributes
 * @var bool $legacy_migration_hint
 * @var string $legacy_migration_target
 * @var string $legacy_migration_field
 * @var string $wrapper_max_width
 * @var string $element_defaults_json
 */

$fieldClass = 'yform-content-builder';
if ($required) {
    $fieldClass .= ' required';
}

// Kompaktmodus aus Addon-Config laden
$addon = rex_addon::get('builder');
if ($addon->getConfig('compact_mode')) {
    $fieldClass .= ' compact-mode';
}

// Online/Offline-Toggle pro Slice optional (Addon-Config)
$enableOnlineToggle = (bool) $addon->getConfig('enable_online_toggle', false);
if ($enableOnlineToggle) {
    $fieldClass .= ' has-online-toggle';
}

$groupedAvailableElements = [];
foreach ($available_elements as $elementType => $config) {
    $category = trim((string) ($config['category'] ?? ''));
    if ('' === $category) {
        $category = 'sonstiges';
    }

    if (!isset($groupedAvailableElements[$category])) {
        $groupedAvailableElements[$category] = [];
    }

    $groupedAvailableElements[$category][$elementType] = $config;
}

$normalizeCategorySortKey = static function (string $category): array {
    $category = trim($category);
    if ($category === '') {
        return [9999, ''];
    }

    if (preg_match('/^\s*(\d{1,4})\s*(?:[:\-_.]{1,2}|::)?\s*(.*)$/u', $category, $matches) === 1) {
        $priority = (int) $matches[1];
        $label = trim((string) ($matches[2] ?? ''));
        if ($label !== '') {
            return [$priority, $label];
        }
    }

    return [9999, $category];
};

$formatCategoryLabel = static function (string $category) use ($normalizeCategorySortKey): string {
    [, $label] = $normalizeCategorySortKey($category);
    return ucfirst(str_replace('_', ' ', $label));
};

uksort(
    $groupedAvailableElements,
    static function (string $leftCategory, string $rightCategory) use ($normalizeCategorySortKey): int {
        $leftSortKey = $normalizeCategorySortKey($leftCategory);
        $rightSortKey = $normalizeCategorySortKey($rightCategory);

        if ($leftSortKey[0] !== $rightSortKey[0]) {
            return $leftSortKey[0] <=> $rightSortKey[0];
        }

        return strcasecmp($leftSortKey[1], $rightSortKey[1]);
    }
);

foreach ($groupedAvailableElements as &$elementsInCategory) {
    uasort(
        $elementsInCategory,
        static function (array $leftConfig, array $rightConfig): int {
            $leftLabel = trim((string) ($leftConfig['label'] ?? ''));
            $rightLabel = trim((string) ($rightConfig['label'] ?? ''));
            return strcasecmp($leftLabel, $rightLabel);
        }
    );
}
unset($elementsInCategory);

$legacyEditorId = 'yform_cb_legacy_editor_' . uniqid();
$legacyMigrateButtonId = 'yform_cb_legacy_migrate_' . uniqid();
$legacyNoticeId = 'yform_cb_legacy_notice_' . uniqid();
$legacyEditorAttributeParts = [];
foreach ($legacy_editor_attributes as $attrName => $attrValue) {
    if ($attrName === '' || strtolower((string) $attrName) === 'id') {
        continue;
    }

    $escapedName = rex_escape((string) $attrName);
    $escapedValue = rex_escape((string) $attrValue);
    $legacyEditorAttributeParts[] = $escapedName . '="' . $escapedValue . '"';
}

$legacyEditorAttributeString = implode(' ', $legacyEditorAttributeParts);
$builderValue = $legacy_is_active ? [] : $value;
$modernHiddenStyle = $legacy_is_active ? ' style="display: none;"' : '';
$wrapperStyle = '';
if (trim((string) $wrapper_max_width) !== '') {
    $wrapperStyle = ' style="max-width:' . rex_escape($wrapper_max_width) . ';"';
}
?>

<div class="form-group yform-element <?= $fieldClass ?>"<?= $wrapperStyle ?>
     data-framework="<?= $framework ?>"
    data-copy-paste="<?= $addon->getConfig('enable_copy_paste') ? '1' : '0' ?>"
     data-online-toggle="<?= $enableOnlineToggle ? '1' : '0' ?>"
     data-element-search="<?= $addon->getConfig('enable_element_search') ? '1' : '0' ?>"
     data-legacy-mode="<?= $legacy_is_active ? '1' : '0' ?>"
     data-available-elements='<?= rex_escape(json_encode($available_elements, JSON_UNESCAPED_UNICODE)) ?>'>
    <?php if ($label): ?>
        <label class="control-label" for="<?= $field_id ?>"><?= $label ?></label>
    <?php endif; ?>
    
    <?php if ($description): ?>
        <p class="help-block"><?= $description ?></p>
    <?php endif; ?>

    <?php if (!$legacy_is_active): ?>
        <div class="alert alert-info yform-cb-start-separator" style="margin-bottom: 12px; border-left: 4px solid #1b809e; display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
                <strong class="yform-cb-start-separator__title"><i class="builder-icon-logo yform-cb-start-logo" aria-hidden="true"></i> Content-Builder-Bereich</strong><br>
                <span>Ab hier beginnt der modulare Seiteninhalt.</span>
            </div>
            <label style="display:flex; align-items:center; gap:6px; margin:0; font-weight:normal; white-space:nowrap; cursor:pointer; flex-shrink:0;">
                <input type="checkbox" id="cb-compact-toggle-<?= rex_escape($field_id) ?>" style="cursor:pointer;" />
                <span style="font-size:13px;">Kompaktmodus</span>
            </label>
        </div>

        <div class="yform-cb-persist-status is-clean" data-cb-persist-status="clean" style="margin-bottom: 12px;">
            <div class="yform-cb-persist-row">
                <span class="yform-cb-persist-dot" aria-hidden="true"></span>
                <strong class="yform-cb-persist-text">Alle Änderungen sind im Datensatz gespeichert.</strong>
            </div>
            <div class="yform-cb-persist-hint">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <span>Element übernehmen speichert nur das aktuelle Element im Builder. Dauerhaft gespeichert wird erst beim Speichern des gesamten Formulars.</span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($legacy_is_active): ?>
        <div class="panel panel-default content-builder-legacy-panel" style="margin-bottom: 12px;">
            <div class="panel-body">
                <?php if ($legacy_migration_hint): ?>
                    <div id="<?= $legacyNoticeId ?>" class="alert alert-info yform-cb-legacy-notice" style="margin-bottom: 12px; padding: 8px 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                            <span>Sie bearbeiten aktuell einen älteren Inhalt. Wechseln Sie jetzt zum modernen Content-Builder.</span>
                            <button type="button" id="<?= $legacyMigrateButtonId ?>" class="btn btn-default btn-xs yform-cb-legacy-migrate">
                                <i class="fa fa-exchange"></i> Zum modernen Content-Builder wechseln
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="control-label" for="<?= $legacyEditorId ?>">Legacy HTML (Editor)</label>
                    <textarea
                        id="<?= $legacyEditorId ?>"
                        <?= $legacyEditorAttributeString !== '' ? $legacyEditorAttributeString : '' ?>><?= rex_escape($legacy_html) ?></textarea>
                    <input type="hidden"
                           name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>__legacy_migrate]"
                           value="0"
                           class="yform-cb-legacy-migrate-flag">
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="content-builder-slices"<?= $modernHiddenStyle ?>>
            <?php if (!empty($builderValue)): ?>
                <?php foreach ($builderValue as $index => $slice): ?>
                    <?php
                    $sliceId = $slice['id'] ?? 'slice_' . uniqid();
                    $sliceType = $slice['type'];
                    $elementData = $slice['data'] ?? [];
                    // Online/Offline-Status – Standard: online (true)
                    $sliceOnline = !isset($slice['online']) || $slice['online'] !== false;
                    
                    // Section-Element?
                    $isSection = ($sliceType === 'section');
                    
                    $addon = rex_addon::get('builder');
                    $elementPath = '';

                    if (isset($available_elements[$sliceType]['_path']) && is_string($available_elements[$sliceType]['_path'])) {
                        $candidate = (string) $available_elements[$sliceType]['_path'];
                        if (is_dir($candidate)) {
                            $elementPath = $candidate;
                        }
                    }

                    if ($elementPath === '') {
                        $customPaths = rex_extension::registerPoint(new rex_extension_point(
                            'BUILDER_ELEMENT_PATHS',
                            ['']
                        ));

                        foreach ($customPaths as $customPath) {
                            if ($customPath === '') {
                                continue;
                            }

                            $candidate = rtrim($customPath, '/\\') . '/' . $sliceType;
                            if (is_dir($candidate)) {
                                $elementPath = $candidate;
                                break;
                            }
                        }
                    }

                    if ($elementPath === '' && rex_addon::exists('project') && rex_addon::get('project')->isAvailable()) {
                        $candidate = rex_addon::get('project')->getPath('elements/' . $sliceType);
                        if (is_dir($candidate)) {
                            $elementPath = $candidate;
                        }
                    }

                    if ($elementPath === '') {
                        $candidate = $addon->getDataPath('elements/' . $sliceType);
                        if (is_dir($candidate)) {
                            $elementPath = $candidate;
                        }
                    }

                    if ($elementPath === '') {
                        $elementPath = $addon->getPath('elements/' . $sliceType);
                    }

                    $sliceToolbarConfig = $available_elements[$sliceType] ?? [];
                    $sliceToolbarLabel = (string) ($sliceToolbarConfig['label'] ?? $sliceType);
                    $sliceToolbarIcon = (string) ($sliceToolbarConfig['icon'] ?? 'fa-cube');

                    $templateFile = '';
                    foreach ([$framework, 'plain', 'uikit', 'bootstrap'] as $templateName) {
                        $candidate = $elementPath . '/templates/' . $templateName . '.php';
                        if (file_exists($candidate)) {
                            $templateFile = $candidate;
                            break;
                        }
                    }
                    ?>
                    
                    <div class="content-builder-slice <?= $isSection ? 'is-section' : '' ?> <?= $sliceOnline ? '' : 'is-offline' ?>" 
                         data-slice-id="<?= rex_escape($sliceId) ?>"
                         data-slice-type="<?= rex_escape($sliceType) ?>"
                         data-slice-index="<?= $index ?>"
                         data-slice-online="<?= $sliceOnline ? '1' : '0' ?>"
                         data-slice-data='<?= rex_escape(json_encode($elementData, JSON_UNESCAPED_UNICODE)) ?>'>
                        
                        <div class="slice-toolbar" data-element-name="<?= rex_escape($sliceToolbarLabel) ?>">
                            <span class="slice-label"><i class="fa <?= rex_escape($sliceToolbarIcon) ?>"></i><?= rex_escape($sliceToolbarLabel) ?></span>
                            <div class="btn-group btn-group-insert">
                                <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" title="<?= rex_i18n::msg('builder_element_add') ?>">
                                    <i class="fa fa-plus"></i>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <?php if ($addon->getConfig('enable_copy_paste')): ?>
                                        <li class="paste-slice-item" style="display: none;">
                                            <a href="#" class="btn-paste-slice" data-insert-after="<?= $index ?>">
                                                <i class="fa fa-clipboard"></i> <strong>Element einfügen</strong>
                                            </a>
                                        </li>
                                        <li role="separator" class="divider paste-slice-item" style="display: none;"></li>
                                    <?php endif; ?>
                                    <?php 
                                    $totalElementsSlice = 0;
                                    foreach ($groupedAvailableElements as $elementsInCategory) {
                                        $totalElementsSlice += count($elementsInCategory);
                                    }
                                    $enableSearchSlice = $addon->getConfig('enable_element_search');
                                    ?>
                                    <?php if ($enableSearchSlice && $totalElementsSlice >= 5): 
                                    ?>
                                        <li class="yform-cb-search-item">
                                            <div class="yform-cb-search-wrapper">
                                                <input type="text" 
                                                       class="yform-cb-element-search-input form-control input-sm" 
                                                       placeholder="<?= rex_i18n::msg('builder_element_search_placeholder') ?>"
                                                       style="margin: 0; width: 100%;">
                                            </div>
                                        </li>
                                        <li role="separator" class="divider"></li>
                                    <?php endif; ?>
                                    <?php $categoryIndex = 0; ?>
                                    <?php foreach ($groupedAvailableElements as $category => $elementsInCategory): ?>
                                        <?php if ($categoryIndex > 0): ?>
                                            <li role="separator" class="divider"></li>
                                        <?php endif; ?>
                                        <li class="dropdown-header"><?= rex_escape($formatCategoryLabel((string) $category)) ?></li>
                                        <?php foreach ($elementsInCategory as $elementType => $config): ?>
                                            <li>
                                                <?php $elementDescription = (string) ($config['description'] ?? ''); ?>
                                                <a href="#" class="btn-insert-slice" 
                                                   data-element-type="<?= rex_escape($elementType) ?>"
                                                   data-element-label="<?= rex_escape($config['label'] ?? $elementType) ?>"
                                                   <?php if ($elementDescription !== ''): ?>
                                                   data-toggle="tooltip"
                                                   data-placement="right"
                                                   data-container="body"
                                                   data-delay='{"show":700,"hide":120}'
                                                   title="<?= rex_escape($elementDescription) ?>"
                                                   <?php endif; ?>
                                                   data-insert-after="<?= $index ?>">
                                                    <i class="fa <?= rex_escape($config['icon'] ?? 'fa-cube') ?>"></i>
                                                    <?= rex_escape($config['label'] ?? $elementType) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php ++$categoryIndex; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-xs btn-default btn-slice-edit" title="<?= rex_i18n::msg('builder_element_edit') ?>">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <?php if ($addon->getConfig('enable_copy_paste')): ?>
                            <button type="button" class="btn btn-xs btn-default btn-slice-copy" title="Kopieren">
                                <i class="fa fa-copy"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-xs btn-default btn-slice-move-up" title="Nach oben verschieben">
                                <i class="fa fa-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-default btn-slice-move-down" title="Nach unten verschieben">
                                <i class="fa fa-arrow-down"></i>
                            </button>
                            <?php if ($enableOnlineToggle): ?>
                            <button type="button" class="btn btn-xs btn-default btn-slice-toggle-online" title="<?= $sliceOnline ? rex_i18n::msg('builder_element_set_offline') : rex_i18n::msg('builder_element_set_online') ?>">
                                <i class="fa <?= $sliceOnline ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-xs btn-danger btn-slice-delete" title="<?= rex_i18n::msg('builder_element_delete') ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        
                        <div class="slice-rendered">
                            <?php if ($isSection): ?>
                                <!-- Section-Label mit Hintergrund-Thumbnail -->
                                <div class="section-backend-label">
                                    <i class="fa fa-object-group"></i>
                                    <strong>Section:</strong> <?= rex_escape($elementData['label'] ?? 'Unbenannt') ?>
                                    <span class="section-info">
                                        <?php if (!empty($elementData['background_color']) && $elementData['background_color'] !== 'none'): ?>
                                            <span class="label label-default"><?= rex_escape($elementData['background_color']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($elementData['custom_id'])): ?>
                                            <span class="label label-info">#<?= rex_escape($elementData['custom_id']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <!-- Hintergrund-Thumbnail -->
                                    <?php
                                    $bgColor = $elementData['background_color'] ?? 'none';
                                    $bgImage = $elementData['background_image'] ?? '';
                                    $bgThumbnailClass = 'bg-' . ($bgColor ?: 'none');
                                    $bgThumbnailStyle = '';
                                    
                                    if ($bgImage) {
                                        if (is_numeric($bgImage)) {
                                            $media = rex_media::get($bgImage);
                                            if ($media instanceof rex_media) {
                                                $bgImage = $media->getFileName();
                                            }
                                        }
                                        $bgThumbnailStyle = ' style="background-image: url(' . rex_url::media($bgImage) . ');"';
                                    }
                                    ?>
                                    <span class="section-bg-thumbnail <?= $bgThumbnailClass ?>"<?= $bgThumbnailStyle ?>></span>
                                </div>
                            <?php else: ?>
                                <?php if ($templateFile !== ''): ?>
                                    <?php include $templateFile; ?>
                                <?php else: ?>
                                    <div class="alert alert-danger">Template not found: <?= rex_escape($sliceType) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="slice-edit-form" style="display: none;">
                            <!-- Wird per AJAX mit YForm-Formular gefüllt -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
    </div>

    <div class="content-builder-add"<?= $modernHiddenStyle ?>>
            <div class="btn-group btn-block">
                <button type="button" class="btn btn-default btn-block dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-plus"></i> <?= rex_i18n::msg('builder_element_add') ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <?php if ($addon->getConfig('enable_copy_paste')): ?>
                        <li class="paste-slice-item" style="display: none;">
                            <a href="#" class="btn-paste-slice" data-insert-after="end">
                                <i class="fa fa-clipboard"></i> <strong>Element einfügen</strong>
                            </a>
                        </li>
                        <li role="separator" class="divider paste-slice-item" style="display: none;"></li>
                    <?php endif; ?>
                    <?php 
                    $totalElements = 0;
                    foreach ($groupedAvailableElements as $elementsInCategory) {
                        $totalElements += count($elementsInCategory);
                    }
                    // DEBUG: Check if search is enabled
                    $enableSearch = $addon->getConfig('enable_element_search');
                    ?>
                    <!-- DEBUG: enable_element_search = <?= var_export($enableSearch, true) ?>, totalElements = <?= $totalElements ?> -->
                    <?php if ($enableSearch && $totalElements >= 5): 
                    ?>
                        <li class="yform-cb-search-item">
                            <div class="yform-cb-search-wrapper">
                                <input type="text" 
                                       class="yform-cb-element-search-input form-control input-sm" 
                                       placeholder="<?= rex_i18n::msg('builder_element_search_placeholder') ?>"
                                       style="margin: 0; width: 100%;">
                            </div>
                        </li>
                        <li role="separator" class="divider"></li>
                    <?php endif; ?>
                    <?php $categoryIndex = 0; ?>
                    <?php foreach ($groupedAvailableElements as $category => $elementsInCategory): ?>
                        <?php if ($categoryIndex > 0): ?>
                            <li role="separator" class="divider"></li>
                        <?php endif; ?>
                        <li class="dropdown-header"><?= rex_escape($formatCategoryLabel((string) $category)) ?></li>
                        <?php foreach ($elementsInCategory as $elementType => $config): ?>
                            <li>
                                <?php $elementDescription = (string) ($config['description'] ?? ''); ?>
                                <a href="#" class="btn-add-slice" 
                                   data-element-type="<?= rex_escape($elementType) ?>"
                                   data-element-label="<?= rex_escape($config['label'] ?? $elementType) ?>"
                                   <?php if ($elementDescription !== ''): ?>
                                   data-toggle="tooltip"
                                   data-placement="right"
                                   data-container="body"
                                   data-delay='{"show":700,"hide":120}'
                                   title="<?= rex_escape($elementDescription) ?>"
                                   <?php endif; ?>>
                                    <i class="fa <?= rex_escape($config['icon'] ?? 'fa-cube') ?>"></i>
                                    <?= rex_escape($config['label'] ?? $elementType) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <?php ++$categoryIndex; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
    </div>

        <input type="hidden" 
            name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>]" 
            class="content-builder-data" 
            value='<?= $legacy_is_active ? rex_escape($legacy_html) : rex_escape(json_encode($builderValue, JSON_UNESCAPED_UNICODE)) ?>'>
    
    <?php if ($notice): ?>
        <p class="help-block"><?= $notice ?></p>
    <?php endif; ?>

</div>
<script nonce="<?= rex_response::getNonce() ?>">
(function() {
    var toggleId = 'cb-compact-toggle-<?= rex_escape($field_id) ?>';
    var lsKey    = 'cb-compact-mode-<?= rex_escape($field_id) ?>';
    var toggle   = document.getElementById(toggleId);
    if (!toggle) return;
    var wrapper  = toggle.closest('.yform-content-builder');
    if (!wrapper) return;

    var saved = localStorage.getItem(lsKey) === '1';
    toggle.checked = saved;
    if (saved) wrapper.classList.add('compact-mode');

    toggle.addEventListener('change', function() {
        if (this.checked) {
            wrapper.classList.add('compact-mode');
            localStorage.setItem(lsKey, '1');
        } else {
            wrapper.classList.remove('compact-mode');
            localStorage.setItem(lsKey, '0');
        }
    });
}());
</script>

