<?php

use FriendsOfREDAXO\Builder\SmartLink;
use FriendsOfREDAXO\Builder\SmartLinkView;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$headline = trim((string) ($elementData['headline'] ?? ''));
$intro = trim((string) ($elementData['intro'] ?? ''));
$showPreview = (bool) ($elementData['show_preview'] ?? false);
$rawLinks = $elementData['links'] ?? '';

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$links = SmartLink::normalize($rawLinks, true);
if ($links === []) {
    return;
}

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain') . StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
?>
<?php if ($enableSection): ?><section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div style="<?= rex_escape($containerStyle) ?>"><?php endif; ?>

<?php if ($headline !== ''): ?><h2><?= rex_escape($headline) ?></h2><?php endif; ?>
<?php if ($intro !== ''): ?><p><?= nl2br(rex_escape($intro)) ?></p><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
    <?php foreach ($links as $linkItem): ?>
    <?php
    $resolved = SmartLinkView::resolveSingle(['items' => [$linkItem]]);
    if ($resolved === null) {
        continue;
    }
    $previewData = SmartLinkView::resolvePreview(['items' => [$linkItem]]);
    $typeMeta = SmartLinkView::getTypeMeta($resolved['type']);
    $target = $resolved['is_external'] ? ' target="_blank" rel="noopener"' : '';
    ?>
    <article style="border:1px solid #ddd;border-radius:6px;overflow:hidden;">
        <?php if ($showPreview && $previewData !== null): ?>
            <?php if ($previewData['kind'] === 'video'): ?>
            <video src="<?= rex_escape($previewData['src']) ?>" controls preload="metadata" style="width:100%;display:block;"></video>
            <?php else: ?>
            <img src="<?= rex_escape($previewData['src']) ?>" alt="" loading="lazy" style="width:100%;display:block;">
            <?php endif; ?>
        <?php endif; ?>
        <div style="padding:.9rem;">
            <div style="display:flex;justify-content:space-between;gap:.5rem;">
                <strong><?= rex_escape($resolved['label']) ?></strong>
                <span style="font-size:.8rem;color:#666;"><?= rex_escape($typeMeta['label']) ?></span>
            </div>
            <a href="<?= rex_escape($resolved['href']) ?>"<?= $target ?>><?= rex_escape($resolved['label']) ?></a>
        </div>
    </article>
    <?php endforeach; ?>
</div>

<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
