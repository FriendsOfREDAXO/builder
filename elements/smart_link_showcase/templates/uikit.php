<?php

/**
 * Smart-Link Showcase - UIkit Template
 *
 * @var array $elementData
 */

$headline = trim((string) ($elementData['headline'] ?? ''));
$intro = trim((string) ($elementData['intro'] ?? ''));
$items = $elementData['items'] ?? [];
$showPreview = !empty($elementData['show_preview']);

$columns = (int) ($elementData['columns'] ?? 3);
$columnsTablet = (int) ($elementData['columns_tablet'] ?? 2);
$columnsMobile = (int) ($elementData['columns_mobile'] ?? 1);
$gap = (string) ($elementData['gap'] ?? 'medium');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if ($items === []) {
    return;
}

$gridClasses = ['uk-grid', 'uk-grid-match'];
if ($gap !== 'medium') {
    $gridClasses[] = 'uk-grid-' . $gap;
}
$gridClasses[] = 'uk-child-width-1-' . max(1, $columnsMobile);
$gridClasses[] = 'uk-child-width-1-' . max(1, $columnsTablet) . '@s';
$gridClasses[] = 'uk-child-width-1-' . max(1, $columns) . '@m';

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
    <?php if ($headline !== ''): ?>
        <h2 class="uk-heading-divider"><?= rex_escape($headline) ?></h2>
    <?php endif; ?>

    <?php if ($intro !== ''): ?>
        <div class="uk-text-lead uk-margin-bottom"><?= nl2br(rex_escape($intro)) ?></div>
    <?php endif; ?>

    <div class="<?= rex_escape(implode(' ', $gridClasses)) ?>" uk-grid>
        <?php foreach ($items as $item): ?>
            <?php
            $title = trim((string) ($item['title'] ?? ''));
            $text = trim((string) ($item['text'] ?? ''));
            $resolved = \FriendsOfREDAXO\Builder\SmartLinkView::resolveSingle($item['link'] ?? '', $title);
            $previewData = \FriendsOfREDAXO\Builder\SmartLinkView::resolvePreview($item['link'] ?? '');

            if ($resolved === null) {
                continue;
            }

            $typeMeta = \FriendsOfREDAXO\Builder\SmartLinkView::getTypeMeta($resolved['type']);
            $target = $resolved['is_external'] ? ' target="_blank" rel="noopener"' : '';
            ?>
            <div>
                <article class="uk-card uk-card-default uk-card-small uk-box-shadow-small uk-overflow-hidden uk-height-1-1">
                    <div class="uk-card-media-top uk-flex uk-flex-middle uk-flex-center uk-background-muted" style="aspect-ratio:16 / 9;">
                        <?php if ($showPreview && $previewData !== null): ?>
                            <?php if ($previewData['kind'] === 'video'): ?>
                                <video src="<?= rex_escape($previewData['src']) ?>" controls preload="metadata" style="width:100%;height:100%;display:block;object-fit:cover;"></video>
                            <?php else: ?>
                                <img src="<?= rex_escape($previewData['src']) ?>" alt="" loading="lazy" style="width:100%;height:100%;display:block;object-fit:cover;">
                            <?php endif; ?>
                        <?php else: ?>
                            <span uk-icon="icon: <?= rex_escape($typeMeta['uikit_icon']) ?>; ratio: 2"></span>
                        <?php endif; ?>
                    </div>

                    <div class="uk-card-body uk-flex uk-flex-column uk-height-1-1">
                        <div class="uk-flex uk-flex-middle uk-flex-between uk-margin-small-bottom">
                            <div class="uk-flex uk-flex-middle uk-flex-nowrap">
                                <span class="uk-margin-small-right" uk-icon="icon: <?= rex_escape($typeMeta['uikit_icon']) ?>"></span>
                                <strong><?= rex_escape($title !== '' ? $title : $resolved['label']) ?></strong>
                            </div>
                            <span class="uk-label"><?= rex_escape($typeMeta['label']) ?></span>
                        </div>

                        <?php if ($text !== ''): ?>
                            <p class="uk-text-small"><?= nl2br(rex_escape($text)) ?></p>
                        <?php endif; ?>

                        <a class="uk-button uk-button-text uk-margin-top-auto" href="<?= rex_escape($resolved['href']) ?>"<?= $target ?>>
                            <?= rex_escape($resolved['label']) ?>
                            <span uk-icon="chevron-right"></span>
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?= $wrapperClose->parse('ycb_elements/wrapper.php') ?>
