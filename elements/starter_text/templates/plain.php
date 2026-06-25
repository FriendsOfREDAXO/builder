<?php
$text = (string) ($elementData['text'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if (trim(strip_tags($text)) === '') {
    return;
}

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain');
$sectionStyle .= StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
?>
<?php if ($enableSection): ?>
<section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>>
<?php endif; ?>
<?php if ($enableContainer): ?>
    <div style="<?= rex_escape($containerStyle) ?>">
<?php endif; ?>
        <?php if ($text !== ''): ?>
            <div><?= $text ?></div>
        <?php endif; ?>
<?php if ($enableContainer): ?>
    </div>
<?php endif; ?>
<?php if ($enableSection): ?>
</section>
<?php endif; ?>
