<?php
/**
 * Bootstrap Template for Columns Layout
 * 
 * @var array $elementData
 * @var string $framework
 */

$layout = $elementData['col_layout'] ?? '50_50';
$columnsData = $elementData['columns'] ?? [];

$rowClass = 'row';
$rowAttrs = '';
$colClasses = [
    '50_50' => ['col-sm-6', 'col-sm-6'],
    '66_33' => ['col-sm-8', 'col-sm-4'],
    '33_66' => ['col-sm-4', 'col-sm-8'],
    '33_33_33' => ['col-sm-4', 'col-sm-4', 'col-sm-4'],
    '25_50_25' => ['col-sm-3', 'col-sm-6', 'col-sm-3'],
    '50_25_25' => ['col-sm-6', 'col-sm-3', 'col-sm-3'],
    '25_25_50' => ['col-sm-3', 'col-sm-3', 'col-sm-6'],
    '25_25_25_25' => ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'],
    '25_75' => ['col-sm-3', 'col-sm-9'],
    '75_25' => ['col-sm-9', 'col-sm-3'],
];
$classes = $colClasses[$layout] ?? ['col-sm-6', 'col-sm-6'];
$styles = [];
$numCols = count($classes);

$isBackendEditMode = false;
if (rex::isBackend()) {
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $trace) {
        $function = $trace['function'] ?? '';
        $class = $trace['class'] ?? '';
        $file = $trace['file'] ?? '';
        if ($function === 'renderSliceBackend' || 
            $function === 'renderEditorSlice' || 
            $function === 'ajaxRenderSlice' || 
            ($function === 'renderSlice' && $class === 'FriendsOfREDAXO\Builder\Api\ContentBuilderApi') || 
            strpos($file, 'value.content_builder.tpl.php') !== false) {
            $isBackendEditMode = true;
            break;
        }
    }
}

if ($isBackendEditMode) {
    include __DIR__ . '/_backend.php';
    return;
}

// Frontend Output Rendering
$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? $elementData['background_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$sectionClasses = [];
$sectionStyle = '';

if ($enableSection) {
    $bgClass = StarterConfig::mapBg($sectionBg, 'bootstrap');
    if ($bgClass !== '') {
        $sectionClasses[] = $bgClass;
    }
    
    $paddingClass = StarterConfig::mapPadding($sectionPadding, 'bootstrap');
    if ($paddingClass !== '') {
        $sectionClasses[] = $paddingClass;
    }
    
    if ($sectionLight) {
        $sectionClasses[] = 'text-white';
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
        $sectionStyle = ' style="background-image: url(\'' . rex_escape($bgImageUrl) . '\'); background-size: cover; background-position: center;"';
    }
}

$sectionClassStr = implode(' ', $sectionClasses);
$containerClass = $enableContainer ? StarterConfig::mapContainer($containerWidth, 'bootstrap') : '';
?>
<?php if ($enableSection): ?>
<section<?= $sectionClassStr !== '' ? ' class="' . rex_escape($sectionClassStr) . '"' : '' ?><?= $sectionStyle !== '' || $hasVideo ? ' style="' . ($hasVideo ? 'position:relative;overflow:hidden;' : '') . rex_escape($sectionStyle) . '"' : '' ?>>
    <?php if ($hasVideo): ?>
        <video autoplay loop muted playsinline style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%); z-index: 0; object-fit: cover;">
            <source src="<?= rex_url::media($sectionBgImage) ?>" type="video/<?= $videoExt ?>">
        </video>
        <div style="position: relative; z-index: 1; width: 100%;">
    <?php endif; ?>
<?php endif; ?>

<?php if ($enableContainer && $containerClass !== ''): ?>
    <div class="<?= rex_escape($containerClass) ?>">
<?php endif; ?>

        <div class="<?= $rowClass ?>"<?= $rowAttrs ?>>
            <?php for ($i = 0; $i < $numCols; $i++): ?>
                <div class="<?= $classes[$i] ?>">
                    <?= \FriendsOfREDAXO\Builder\Helper::renderNestedSlices($columnsData[$i] ?? [], $framework) ?>
                </div>
            <?php endfor; ?>
        </div>

<?php if ($enableContainer && $containerClass !== ''): ?>
    </div>
<?php endif; ?>

<?php if ($enableSection): ?>
    <?php if ($hasVideo): ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
