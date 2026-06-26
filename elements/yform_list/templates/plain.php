<?php

/** @var array<string,mixed> $elementData */

use FriendsOfREDAXO\Builder\ListRenderer;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

if (!class_exists(ListRenderer::class)) {
    return;
}

$result = ListRenderer::fetch($elementData);
$headline = (string) ($elementData['headline'] ?? '');
$description = (string) ($elementData['description'] ?? '');
$showLinks = !isset($elementData['show_links']) || !empty($elementData['show_links']);
$layout = (string) $result['layout'];
$items = (array) $result['items'];
$error = $result['error'];

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain') . StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
?>
<?php if ($enableSection): ?><section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div style="<?= rex_escape($containerStyle) ?>"><?php endif; ?>

<?php if ($headline !== ''): ?><h2><?= rex_escape($headline) ?></h2><?php endif; ?>
<?php if ($description !== ''): ?><p><?= nl2br(rex_escape($description)) ?></p><?php endif; ?>

<?php if ($error !== null): ?>
<p><?= rex_escape((string) $error) ?></p>
<?php elseif ($items === []): ?>
<p>Keine Einträge.</p>
<?php elseif ($layout === 'slides'): ?>
<div style="display:flex;gap:1rem;overflow-x:auto;padding-bottom:.3rem;scroll-snap-type:x mandatory;">
    <?php foreach ($items as $it): ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $img = ListRenderer::imgTag($it, '', 640);
    $product = (array) ($it['product'] ?? []);
    $currency = (string) ($product['currency'] ?? 'EUR');
    $price = ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
    $oldPrice = ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
    $badge = trim((string) ($product['badge'] ?? ''));
    $availability = trim((string) ($product['availability'] ?? ''));
    ?>
    <article style="min-width:min(520px,90vw);scroll-snap-align:start;border:1px solid #ddd;border-radius:6px;overflow:hidden;">
        <?= $img ?>
        <div style="padding:.85rem;">
            <?php if ($badge !== ''): ?><div style="margin-bottom:.4rem;"><span style="display:inline-block;background:#0d6efd;color:#fff;border-radius:4px;padding:.1rem .45rem;font-size:.8rem;"><?= rex_escape($badge) ?></span></div><?php endif; ?>
            <h3 style="margin-top:0;"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h3>
            <?php if ($teaser !== ''): ?><p style="margin-bottom:0;"><?= $teaser ?></p><?php endif; ?>
            <?php if ($price !== ''): ?><p style="margin:.35rem 0 0;"><strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?></p><?php endif; ?>
            <?php if ($availability !== ''): ?><p style="margin:.2rem 0 0;color:#666;"><small><?= rex_escape($availability) ?></small></p><?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php elseif ($layout === 'cards' || $layout === 'contact' || $layout === 'contact_compact'): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
    <?php foreach ($items as $it): ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $img = ListRenderer::imgTag($it, '', 360);
    $product = (array) ($it['product'] ?? []);
    $currency = (string) ($product['currency'] ?? 'EUR');
    $price = ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
    $oldPrice = ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
    $badge = trim((string) ($product['badge'] ?? ''));
    $availability = trim((string) ($product['availability'] ?? ''));
    ?>
    <article style="border:1px solid #ddd;border-radius:6px;overflow:hidden;">
        <?= $img ?>
        <div style="padding:.85rem;">
            <?php if ($badge !== ''): ?><div style="margin-bottom:.4rem;"><span style="display:inline-block;background:#0d6efd;color:#fff;border-radius:4px;padding:.1rem .45rem;font-size:.8rem;"><?= rex_escape($badge) ?></span></div><?php endif; ?>
            <h3 style="margin-top:0;"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h3>
            <?php if ($teaser !== ''): ?><p style="margin-bottom:0;"><?= $teaser ?></p><?php endif; ?>
            <?php if ($price !== ''): ?><p style="margin:.35rem 0 0;"><strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?></p><?php endif; ?>
            <?php if ($availability !== ''): ?><p style="margin:.2rem 0 0;color:#666;"><small><?= rex_escape($availability) ?></small></p><?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php else: ?>
<ul>
    <?php foreach ($items as $it): ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $product = (array) ($it['product'] ?? []);
    $currency = (string) ($product['currency'] ?? 'EUR');
    $price = ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
    $oldPrice = ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
    $availability = trim((string) ($product['availability'] ?? ''));
    ?>
    <li>
        <strong><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></strong>
        <?php if ($teaser !== ''): ?> - <?= $teaser ?><?php endif; ?>
        <?php if ($price !== ''): ?> | <strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?><?php endif; ?>
        <?php if ($availability !== ''): ?> <small>(<?= rex_escape($availability) ?>)</small><?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
