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
$columns = max(1, (int) ($elementData['columns'] ?? 3));

$sectionClass = trim(StarterConfig::mapBg($sectionBg, 'bootstrap') . ' ' . StarterConfig::mapPadding($sectionPadding, 'bootstrap'));
if ($sectionLight) {
    $sectionClass = trim($sectionClass . ' text-white');
}
$containerClass = trim(StarterConfig::mapContainer($containerWidth, 'bootstrap'));
$col = (int) floor(12 / max(1, min(4, $columns)));
if ($col < 3) {
    $col = 3;
}
$tel = static fn(string $v): string => preg_replace('/[^+\d]/', '', $v) ?? '';
?>
<?php if ($enableSection): ?><section<?= $sectionClass !== '' ? ' class="' . rex_escape($sectionClass) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div<?= $containerClass !== '' ? ' class="' . rex_escape($containerClass) . '"' : '' ?>><?php endif; ?>

<?php if ($headline !== ''): ?><h2 class="mb-3"><?= rex_escape($headline) ?></h2><?php endif; ?>
<?php if ($description !== ''): ?><p class="lead"><?= nl2br(rex_escape($description)) ?></p><?php endif; ?>

<?php if ($error !== null): ?>
<div class="alert alert-warning"><?= rex_escape((string) $error) ?></div>
<?php elseif ($items === []): ?>
<div class="alert alert-light">Keine Einträge.</div>
<?php elseif ($layout === 'slides'): ?>
<div id="rex-yfl-slides-<?= rex_escape((string) substr(md5(json_encode($items) ?: 'slides'), 0, 8)) ?>" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php foreach ($items as $index => $it): ?>
        <?php
        $title = rex_escape((string) ($it['title'] ?? ''));
        $teaser = rex_escape((string) ($it['teaser'] ?? ''));
        $href = $showLinks ? (string) ($it['href'] ?? '') : '';
        $img = ListRenderer::imgTag($it, 'd-block w-100');
        $product = (array) ($it['product'] ?? []);
        $currency = (string) ($product['currency'] ?? 'EUR');
        $price = ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
        $oldPrice = ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
        $badge = trim((string) ($product['badge'] ?? ''));
        $availability = trim((string) ($product['availability'] ?? ''));
        ?>
        <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
            <?php if ($img !== ''): ?><?= $img ?><?php endif; ?>
            <div class="carousel-caption d-none d-md-block" style="background:rgba(0,0,0,.45);border-radius:.5rem;padding:.8rem 1rem;">
                <?php if ($badge !== ''): ?><div class="mb-2"><span class="badge bg-primary"><?= rex_escape($badge) ?></span></div><?php endif; ?>
                <h5><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>" class="text-white text-decoration-none"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h5>
                <?php if ($teaser !== ''): ?><p class="mb-0"><?= $teaser ?></p><?php endif; ?>
                <?php if ($price !== ''): ?><p class="mb-0"><strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?></p><?php endif; ?>
                <?php if ($availability !== ''): ?><p class="mb-0"><small><?= rex_escape($availability) ?></small></p><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#rex-yfl-slides-<?= rex_escape((string) substr(md5(json_encode($items) ?: 'slides'), 0, 8)) ?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#rex-yfl-slides-<?= rex_escape((string) substr(md5(json_encode($items) ?: 'slides'), 0, 8)) ?>" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
<?php elseif ($layout === 'cards' || $layout === 'contact' || $layout === 'contact_compact'): ?>
<div class="row g-3">
    <?php foreach ($items as $it): ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $img = ListRenderer::imgTag($it, 'card-img-top');
    $contact = (array) ($it['contact'] ?? []);
    $product = (array) ($it['product'] ?? []);
    $role = trim((string) ($contact['role'] ?? ''));
    $phone = trim((string) ($contact['phone'] ?? ''));
    $mobile = trim((string) ($contact['mobile'] ?? ''));
    $email = trim((string) ($contact['email'] ?? ''));
    $currency = (string) ($product['currency'] ?? 'EUR');
    $price = ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
    $oldPrice = ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
    $badge = trim((string) ($product['badge'] ?? ''));
    $availability = trim((string) ($product['availability'] ?? ''));
    ?>
    <div class="col-12 col-md-6 col-lg-<?= rex_escape((string) $col) ?>">
        <div class="card h-100">
            <?php if ($badge !== ''): ?><div class="position-absolute top-0 end-0 p-2"><span class="badge bg-primary"><?= rex_escape($badge) ?></span></div><?php endif; ?>
            <?= $img ?>
            <div class="card-body">
                <h3 class="h5 card-title"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>" class="text-decoration-none"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h3>
                <?php if ($teaser !== ''): ?><p class="card-text"><?= $teaser ?></p><?php endif; ?>
                <?php if ($price !== ''): ?><p class="mb-1"><strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small class="text-muted" style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?></p><?php endif; ?>
                <?php if ($availability !== ''): ?><p class="small text-muted mb-2"><?= rex_escape($availability) ?></p><?php endif; ?>
                <?php if ($role !== ''): ?><div class="small text-muted"><?= rex_escape($role) ?></div><?php endif; ?>
                <?php if ($phone !== ''): ?><div><a href="tel:<?= rex_escape($tel($phone)) ?>"><?= rex_escape($phone) ?></a></div><?php endif; ?>
                <?php if ($mobile !== ''): ?><div><a href="tel:<?= rex_escape($tel($mobile)) ?>"><?= rex_escape($mobile) ?></a></div><?php endif; ?>
                <?php if ($email !== ''): ?><div><a href="mailto:<?= rex_escape($email) ?>"><?= rex_escape($email) ?></a></div><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<ul class="list-group list-group-flush">
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
    <li class="list-group-item px-0">
        <h4 class="h6 mb-1"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h4>
        <?php if ($teaser !== ''): ?><p class="mb-0"><?= $teaser ?></p><?php endif; ?>
        <?php if ($price !== ''): ?><p class="mb-0"><strong><?= rex_escape($price) ?></strong><?php if ($oldPrice !== ''): ?> <small class="text-muted" style="text-decoration:line-through;"><?= rex_escape($oldPrice) ?></small><?php endif; ?></p><?php endif; ?>
        <?php if ($availability !== ''): ?><p class="small text-muted mb-0"><?= rex_escape($availability) ?></p><?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
