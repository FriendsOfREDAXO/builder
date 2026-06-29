<?php
/**
 * Shared Backend Edit Template for Columns Layout
 * 
 * @var array $columnsData
 * @var string $rowClass
 * @var string $rowAttrs
 * @var array $classes
 * @var array $styles
 * @var int $numCols
 * @var string $framework
 */

$contextAvailableElements = isset($available_elements) && is_array($available_elements) ? $available_elements : [];

// Fallback: Wenn kein Kontext uebergeben wurde, alle registrierten Elemente laden.
if ($contextAvailableElements === []) {
    $elementKeys = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getAllElements();
    foreach ($elementKeys as $key) {
        $config = \FriendsOfREDAXO\Builder\Config\ElementRegistry::getElementConfig($key);
        if ($config !== null) {
            $config['type'] = $key;
            $config['key'] = $key;
            $contextAvailableElements[$key] = $config;
        }
    }
}

$available_elements = $contextAvailableElements;

$contextGroupedAvailableElements = isset($groupedAvailableElements) && is_array($groupedAvailableElements)
    ? $groupedAvailableElements
    : [];

if ($contextGroupedAvailableElements === []) {
    foreach ($available_elements as $elementType => $config) {
        $category = trim((string) ($config['category'] ?? ''));
        if ('' === $category) {
            $category = 'sonstiges';
        }
        $contextGroupedAvailableElements[$category][$elementType] = $config;
    }
}

$groupedAvailableElements = $contextGroupedAvailableElements;
$enableOnlineToggle = (bool) rex_addon::get('builder')->getConfig('enable_online_toggle', false);

$backendRowClass = $rowClass . ' content-builder-columns-row';

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? $elementData['background_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if ($framework === 'uikit') {
    $wrapper = new rex_fragment();
    $wrapper->setVar('enable_section', $enableSection, false);
    $wrapper->setVar('enable_container', $enableContainer, false);
    $wrapper->setVar('section_bg', $sectionBg, false);
    $wrapper->setVar('section_bg_image', $sectionBgImage, false);
    $wrapper->setVar('section_padding', $sectionPadding, false);
    $wrapper->setVar('container_width', $containerWidth, false);
    $wrapper->setVar('section_light', $sectionLight, false);

    $wrapperClose = new rex_fragment();
    $wrapperClose->setVar('mode', 'close', false);
    $wrapperClose->setVar('enable_section', $enableSection, false);
    $wrapperClose->setVar('enable_container', $enableContainer, false);
    $wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
    $wrapperClose->setVar('container_width', $containerWidth, false);

    echo $wrapper->parse('ycb_elements/wrapper.php');
} else {
    // bootstrap / plain
    $sectionClasses = [];
    $sectionStyle = '';

    if ($enableSection) {
        if ($framework === 'bootstrap') {
            $bgClass = \FriendsOfREDAXO\Builder\Starter\StarterConfig::mapBg($sectionBg, 'bootstrap');
            if ($bgClass !== '') {
                $sectionClasses[] = $bgClass;
            }
            $paddingClass = \FriendsOfREDAXO\Builder\Starter\StarterConfig::mapPadding($sectionPadding, 'bootstrap');
            if ($paddingClass !== '') {
                $sectionClasses[] = $paddingClass;
            }
            if ($sectionLight) {
                $sectionClasses[] = 'text-white';
            }
        } else {
            // plain
            $sectionStyle = \FriendsOfREDAXO\Builder\Starter\StarterConfig::mapBg($sectionBg, 'plain');
            $sectionStyle .= \FriendsOfREDAXO\Builder\Starter\StarterConfig::mapPadding($sectionPadding, 'plain');
            if ($sectionLight) {
                $sectionStyle .= 'color:#fff;';
            }
        }
    }

    $hasVideo = false;
    $videoExt = '';
    if ($enableSection && $sectionBgImage !== '') {
        $videoExt = strtolower(pathinfo($sectionBgImage, PATHINFO_EXTENSION));
        if (in_array($videoExt, ['mp4', 'webm', 'ogg'], true)) {
            $hasVideo = true;
        } else {
            $bgImageUrl = rex_media_manager::getUrl('content_slideshow', $sectionBgImage);
            $sectionStyle .= 'background-image: url(\'' . rex_escape($bgImageUrl) . '\'); background-size: cover; background-position: center;';
        }
    }

    $sectionClassStr = implode(' ', $sectionClasses);
    $containerClass = '';
    $containerStyle = '';
    if ($enableContainer) {
        if ($framework === 'bootstrap') {
            // Backend-Preview: nie fixe Bootstrap-Containerbreiten verwenden,
            // sonst kann das Columns-Layout ueber den Editor hinausragen.
            $containerClass = 'content-builder-backend-container';
        } else {
            // Plain-Preview ebenfalls auf Editorbreite begrenzen.
            $containerStyle = 'width:100%;max-width:100%;padding:0;box-sizing:border-box;';
        }
    }

    if ($enableSection) {
        echo '<section' . ($sectionClassStr !== '' ? ' class="' . rex_escape($sectionClassStr) . '"' : '') . ($sectionStyle !== '' || $hasVideo ? ' style="' . ($hasVideo ? 'position:relative;overflow:hidden;' : '') . rex_escape($sectionStyle) . '"' : '') . '>';
        if ($hasVideo) {
            echo '<video autoplay loop muted playsinline style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%); z-index: 0; object-fit: cover;">';
            echo '<source src="' . rex_url::media($sectionBgImage) . '" type="video/' . $videoExt . '">';
            echo '</video>';
            echo '<div style="position: relative; z-index: 1; width: 100%;">';
        }
    }

    if ($enableContainer) {
        echo '<div' . ($containerClass !== '' ? ' class="' . rex_escape($containerClass) . '"' : '') . ($containerStyle !== '' ? ' style="' . rex_escape($containerStyle) . '"' : '') . '>';
    }
}
?>
<div class="<?= $backendRowClass ?>"<?= $rowAttrs ?>>
    <?php for ($i = 0; $i < $numCols; $i++): ?>
        <?php
        $styleAttr = isset($styles[$i]) ? ' style="' . $styles[$i] . '"' : '';
        ?>
        <div class="<?= $classes[$i] ?> content-builder-column"<?= $styleAttr ?>>
            <div class="content-builder-column-slices" data-column-index="<?= $i ?>"><?php
                $colSlices = $columnsData[$i] ?? [];
                foreach ($colSlices as $index => $slice) {
                    echo \FriendsOfREDAXO\Builder\Helper::renderSliceBackend(
                        $slice,
                        $index,
                        $available_elements,
                        $groupedAvailableElements,
                        $framework,
                        $enableOnlineToggle
                    );
                }
            ?></div>
            <!-- Column Add Button Dropdown -->
            <div class="column-add-slice btn-group" style="margin-top: 10px;">
                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-plus"></i> Element hinzufügen
                </button>
                <ul class="dropdown-menu">
                    <?php if (rex_addon::get('builder')->getConfig('enable_copy_paste')): ?>
                        <li class="paste-slice-item" style="display: none;">
                            <a href="#" class="btn-paste-slice" data-insert-after="end">
                                <i class="fa fa-clipboard"></i> <strong>Element einfügen</strong>
                            </a>
                        </li>
                        <li role="separator" class="divider paste-slice-item" style="display: none;"></li>
                    <?php endif; ?>
                    <?php $categoryIndex = 0; ?>
                    <?php foreach ($groupedAvailableElements as $category => $elementsInCategory): ?>
                        <?php if ($categoryIndex > 0): ?>
                            <li role="separator" class="divider"></li>
                        <?php endif; ?>
                        <li class="dropdown-header"><?= rex_escape(ucfirst(str_replace('_', ' ', (string) $category))) ?></li>
                        <?php foreach ($elementsInCategory as $elementType => $config): ?>
                            <?php if (!\FriendsOfREDAXO\Builder\Helper::isSelfNestingBlocked('columns', (string) $elementType, $available_elements)): ?>
                                <li>
                                    <a href="#" class="btn-add-nested-slice" 
                                       data-element-type="<?= rex_escape($elementType) ?>"
                                       data-element-label="<?= rex_escape($config['label'] ?? $elementType) ?>">
                                        <i class="fa <?= rex_escape($config['icon'] ?? 'fa-cube') ?>"></i>
                                        <?= rex_escape($config['label'] ?? $elementType) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php ++$categoryIndex; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endfor; ?>
</div>
<?php
if ($framework === 'uikit') {
    echo $wrapperClose->parse('ycb_elements/wrapper.php');
} else {
    if ($enableContainer) {
        echo '</div>';
    }
    if ($enableSection) {
        if ($hasVideo) {
            echo '</div>';
        }
        echo '</section>';
    }
}
