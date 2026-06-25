<?php

use FriendsOfREDAXO\Builder\SmartLinkView;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$headline = trim((string) ($elementData['headline'] ?? ''));
$intro = trim((string) ($elementData['intro'] ?? ''));
$items = $elementData['items'] ?? [];
$showPreview = !empty($elementData['show_preview']);
$columns = max(1, (int) ($elementData['columns'] ?? 3));

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if ($items === []) {
    return;
}

$sectionClass = trim(StarterConfig::mapBg($sectionBg, 'bootstrap') . ' ' . StarterConfig::mapPadding($sectionPadding, 'bootstrap'));
if ($sectionLight) {
    $sectionClass = trim($sectionClass . ' text-white');
}
$containerClass = trim(StarterConfig::mapContainer($containerWidth, 'bootstrap'));
$col = (int) floor(12 / max(1, min(4, $columns)));
if ($col < 3) {
    $col = 3;
}
?>
<?php if ($enableSection): ?><section<?= $sectionClass !== '' ? ' class="' . rex_escape($sectionClass) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div<?= $containerClass !== '' ? ' class="' . rex_escape($containerClass) . '"' : '' ?>><?php endif; ?>

<?php if ($headline !== ''): ?><h2 class="mb-3"><?= rex_escape($headline) ?></h2><?php endif; ?>
<?php if ($intro !== ''): ?><p class="lead"><?= nl2br(rex_escape($intro)) ?></p><?php endif; ?>

<div class="row g-3">
    <?php foreach ($items as $item): ?>
    <?php
    $title = trim((string) ($item['title'] ?? ''));
    $text = trim((string) ($item['text'] ?? ''));
    $resolved = SmartLinkView::resolveSingle($item['link'] ?? '', $title);
    $previewData = SmartLinkView::resolvePreview($item['link'] ?? '');
    if ($resolved === null) {
        continue;
    }
    $typeMeta = SmartLinkView::getTypeMeta($resolved['type']);
    $target = $resolved['is_external'] ? ' target="_blank" rel="noopener"' : '';
    ?>
    <div class="col-12 col-md-6 col-lg-<?= rex_escape((string) $col) ?>">
        <article class="card h-100">
            <?php if ($showPreview && $previewData !== null): ?>
                <?php if ($previewData['kind'] === 'video'): ?>
                <video class="card-img-top" src="<?= rex_escape($previewData['src']) ?>" controls preload="metadata"></video>
                <?php else: ?>
                <img class="card-img-top" src="<?= rex_escape($previewData['src']) ?>" alt="" loading="lazy">
                <?php endif; ?>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <strong><?= rex_escape($title !== '' ? $title : $resolved['label']) ?></strong>
                    <span class="badge text-bg-secondary"><?= rex_escape($typeMeta['label']) ?></span>
                </div>
                <?php if ($text !== ''): ?><p class="card-text"><?= nl2br(rex_escape($text)) ?></p><?php endif; ?>
                <a class="mt-auto" href="<?= rex_escape($resolved['href']) ?>"<?= $target ?>><?= rex_escape($resolved['label']) ?></a>
            </div>
        </article>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
