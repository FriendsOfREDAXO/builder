<?php
/**
 * Plain HTML/CSS Template for Columns Layout
 * 
 * @var array $elementData
 * @var string $framework
 */

$layout = $elementData['col_layout'] ?? '50_50';
$columnsData = $elementData['columns'] ?? [];

$rowClass = 'cb-plain-row';
$rowAttrs = ' style="display: flex; flex-wrap: wrap; margin-left: -15px; margin-right: -15px;"';
$colClasses = [
    '50_50' => ['cb-plain-col-50', 'cb-plain-col-50'],
    '66_33' => ['cb-plain-col-66', 'cb-plain-col-33'],
    '33_66' => ['cb-plain-col-33', 'cb-plain-col-66'],
    '33_33_33' => ['cb-plain-col-33', 'cb-plain-col-33', 'cb-plain-col-33'],
    '25_50_25' => ['cb-plain-col-25', 'cb-plain-col-50', 'cb-plain-col-25'],
    '50_25_25' => ['cb-plain-col-50', 'cb-plain-col-25', 'cb-plain-col-25'],
    '25_25_50' => ['cb-plain-col-25', 'cb-plain-col-25', 'cb-plain-col-50'],
    '25_25_25_25' => ['cb-plain-col-25', 'cb-plain-col-25', 'cb-plain-col-25', 'cb-plain-col-25'],
    '25_75' => ['cb-plain-col-25', 'cb-plain-col-75'],
    '75_25' => ['cb-plain-col-75', 'cb-plain-col-25'],
];
$colStyles = [
    '50_50' => ['flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box;'],
    '66_33' => ['flex: 0 0 66.666%; max-width: 66.666%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 33.333%; max-width: 33.333%; padding: 0 15px; box-sizing: border-box;'],
    '33_66' => ['flex: 0 0 33.333%; max-width: 33.333%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 66.666%; max-width: 66.666%; padding: 0 15px; box-sizing: border-box;'],
    '33_33_33' => ['flex: 0 0 33.333%; max-width: 33.333%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 33.333%; max-width: 33.333%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 33.333%; max-width: 33.333%; padding: 0 15px; box-sizing: border-box;'],
    '25_50_25' => ['flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;'],
    '50_25_25' => ['flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;'],
    '25_25_50' => ['flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box;'],
    '25_25_25_25' => ['flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;'],
    '25_75' => ['flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 75%; max-width: 75%; padding: 0 15px; box-sizing: border-box;'],
    '75_25' => ['flex: 0 0 75%; max-width: 75%; padding: 0 15px; box-sizing: border-box;', 'flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box;'],
];
$classes = $colClasses[$layout] ?? ['cb-plain-col-50', 'cb-plain-col-50'];
$styles = $colStyles[$layout] ?? [];
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

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain');
$sectionStyle .= StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');

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
?>
<?php if ($enableSection): ?>
<section<?= $sectionStyle !== '' || $hasVideo ? ' style="' . ($hasVideo ? 'position:relative;overflow:hidden;' : '') . rex_escape($sectionStyle) . '"' : '' ?>>
    <?php if ($hasVideo): ?>
        <video autoplay loop muted playsinline style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%); z-index: 0; object-fit: cover;">
            <source src="<?= rex_url::media($sectionBgImage) ?>" type="video/<?= $videoExt ?>">
        </video>
        <div style="position: relative; z-index: 1; width: 100%;">
    <?php endif; ?>
<?php endif; ?>

<?php if ($enableContainer): ?>
    <div style="<?= rex_escape($containerStyle) ?>">
<?php endif; ?>

        <div class="<?= $rowClass ?>"<?= $rowAttrs ?>>
            <?php for ($i = 0; $i < $numCols; $i++): ?>
                <?php
                $styleAttr = isset($styles[$i]) ? ' style="' . $styles[$i] . '"' : '';
                ?>
                <div class="<?= $classes[$i] ?>"<?= $styleAttr ?>>
                    <?= \FriendsOfREDAXO\Builder\Helper::renderNestedSlices($columnsData[$i] ?? [], $framework) ?>
                </div>
            <?php endfor; ?>
        </div>

<?php if ($enableContainer): ?>
    </div>
<?php endif; ?>

<?php if ($enableSection): ?>
    <?php if ($hasVideo): ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
