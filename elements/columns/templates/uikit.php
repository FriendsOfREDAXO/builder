<?php
/**
 * UIkit Template for Columns Layout
 * 
 * @var array $elementData
 * @var string $framework
 */

$layout = $elementData['col_layout'] ?? '50_50';
$columnsData = $elementData['columns'] ?? [];

$rowClass = 'uk-grid uk-grid-margin';
$rowAttrs = ' data-uk-grid';
$colClasses = [
    '50_50' => ['uk-width-1-2@m', 'uk-width-1-2@m'],
    '66_33' => ['uk-width-2-3@m', 'uk-width-1-3@m'],
    '33_66' => ['uk-width-1-3@m', 'uk-width-2-3@m'],
    '33_33_33' => ['uk-width-1-3@m', 'uk-width-1-3@m', 'uk-width-1-3@m'],
    '25_50_25' => ['uk-width-1-4@m', 'uk-width-1-2@m', 'uk-width-1-4@m'],
    '50_25_25' => ['uk-width-1-2@m', 'uk-width-1-4@m', 'uk-width-1-4@m'],
    '25_25_50' => ['uk-width-1-4@m', 'uk-width-1-4@m', 'uk-width-1-2@m'],
    '25_25_25_25' => ['uk-width-1-4@m', 'uk-width-1-4@m', 'uk-width-1-4@m', 'uk-width-1-4@m'],
    '25_75' => ['uk-width-1-4@m', 'uk-width-3-4@m'],
    '75_25' => ['uk-width-3-4@m', 'uk-width-1-4@m'],
];
$classes = $colClasses[$layout] ?? ['uk-width-1-2@m', 'uk-width-1-2@m'];
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
?>
<?= $wrapper->parse('ycb_elements/wrapper.php') ?>
<div class="<?= $rowClass ?>"<?= $rowAttrs ?>>
    <?php for ($i = 0; $i < $numCols; $i++): ?>
        <div class="<?= $classes[$i] ?>">
            <?= \FriendsOfREDAXO\Builder\Helper::renderNestedSlices($columnsData[$i] ?? [], $framework) ?>
        </div>
    <?php endfor; ?>
</div>
<?= $wrapperClose->parse('ycb_elements/wrapper.php') ?>
