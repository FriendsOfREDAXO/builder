<?php
$headline = (string) ($elementData['headline'] ?? '');
$text = (string) ($elementData['text'] ?? '');
$position = (string) ($elementData['media_position'] ?? 'left');
$mediaFile = (string) ($elementData['media_file'] ?? '');
$mediaAlt = (string) ($elementData['media_alt'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);

if ($headline === '' && trim(strip_tags($text)) === '' && $mediaFile === '') {
    return;
}

$ext = strtolower((string) pathinfo($mediaFile, PATHINFO_EXTENSION));
$isVideo = in_array($ext, ['mp4', 'webm', 'ogg'], true);

$mediaLabel = \FriendsOfREDAXO\Builder\MediaAltResolver::resolve($mediaFile, $mediaAlt, $headline);

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain');
$sectionStyle .= StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
$mediaHtml = '';
if ($mediaFile !== '') {
    if ($isVideo) {
        $videoAriaLabel = $mediaLabel !== '' ? $mediaLabel : 'Video';
        $mediaHtml = '<video controls playsinline aria-label="' . rex_escape($videoAriaLabel) . '" style="max-width:100%;height:auto;"><source src="' . rex_url::media($mediaFile) . '" type="video/' . rex_escape($ext) . '"></video>';
    } else {
        $mediaHtml = '<img src="' . rex_url::media($mediaFile) . '" alt="' . rex_escape($mediaLabel) . '" loading="lazy" style="max-width:100%;height:auto;">';
    }
}
?>
<section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>>
    <div style="<?= rex_escape($containerStyle) ?>">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px;">
            <?php if ($position === 'left'): ?>
                <div style="flex:1 1 320px;"><?= $mediaHtml ?></div>
            <?php endif; ?>
            <div style="flex:1 1 320px;">
                <?php if ($headline !== ''): ?><h3 style="margin-top:0;"><?= rex_escape($headline) ?></h3><?php endif; ?>
                <?php if ($text !== ''): ?><div><?= $text ?></div><?php endif; ?>
            </div>
            <?php if ($position === 'right'): ?>
                <div style="flex:1 1 320px;"><?= $mediaHtml ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
