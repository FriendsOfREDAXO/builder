<?php
use FriendsOfREDAXO\Builder\Starter\StarterConfig;
use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

$headline        = (string) ($elementData['headline'] ?? '');
$items           = $elementData['items'] ?? [];
$cardStyle       = (string) ($elementData['card_style'] ?? 'default');
$imageRatio      = (string) ($elementData['image_ratio'] ?? '16_9');
$imageRatioMobile = (string) ($elementData['image_ratio_mobile'] ?? '');
$columns         = max(1, min(6, (int) ($elementData['columns'] ?? 3)));
$gap             = (string) ($elementData['gap'] ?? 'medium');
$sectionBg       = (string) ($elementData['section_bg'] ?? '');
$sectionPadding  = (string) ($elementData['section_padding'] ?? '');
$containerWidth  = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight    = !empty($elementData['section_light']);
$enableSection   = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if (!is_array($items) || $items === []) {
    return;
}

$sectionStyle  = StarterConfig::mapBg($sectionBg, 'plain');
$sectionStyle .= StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');

$gapPx = $gap === 'collapse' ? '0' : ($gap === 'small' ? '8px' : ($gap === 'large' ? '24px' : '15px'));

$cardStyleMap = [
    'default'     => 'border:1px solid #ddd;background:#fff;',
    'primary'     => 'background:#1e87f0;color:#fff;',
    'secondary'   => 'background:#222;color:#fff;',
    'muted'       => 'background:#f8f8f8;',
    'hover'       => 'border:1px solid #ddd;background:#fff;',
    'transparent' => '',
];
$cardStyleAttr = $cardStyleMap[$cardStyle] ?? 'border:1px solid #ddd;background:#fff;';
$estimateContainerMaxPx = static function (string $container): int {
    if (str_contains($container, 'xsmall')) {
        return 640;
    }
    if (str_contains($container, 'small')) {
        return 900;
    }
    if (str_contains($container, 'xlarge')) {
        return 1600;
    }
    if (str_contains($container, 'large')) {
        return 1400;
    }
    if (str_contains($container, 'expand') || $container === '') {
        return 1920;
    }

    return 1200;
};
$containerMaxPx = $estimateContainerMaxPx($containerWidth);
$desktopPx = (int) max(220, round($containerMaxPx / max(1, $columns)));
$mobileArtDirectionActive = $imageRatioMobile !== '' && $imageRatioMobile !== $imageRatio;

$resolveLink = static function (array $item): array {
    $type   = (string) ($item['link_type'] ?? '');
    $target = (string) ($item['link_target'] ?? '');
    $text   = trim((string) ($item['link_text'] ?? 'Mehr erfahren'));
    if ($text === '') { $text = 'Mehr erfahren'; }
    if ($type === 'external') {
        return ['href' => (string) ($item['link_url'] ?? ''), 'target' => $target, 'text' => $text];
    }
    if ($type === 'internal') {
        $id = (int) ($item['link_internal'] ?? 0);
        return ['href' => $id > 0 ? rex_getUrl($id) : '', 'target' => $target, 'text' => $text];
    }
    if (!empty($item['link_url'])) {
        return ['href' => (string) $item['link_url'], 'target' => '', 'text' => $text];
    }
    return ['href' => '', 'target' => '', 'text' => $text];
};

/** @return array{src:string,srcset:string} */
$resolveImage = static function (string $image, string $ratio): array {
    if ($image === '') {
        return ['src' => '', 'srcset' => ''];
    }

    if (!rex_addon::get('media_manager')->isAvailable()) {
        return ['src' => rex_url::media($image), 'srcset' => ''];
    }

    $preset = 'starter_cards_' . $ratio;
    $widths = [400, 800, 1200, 1600];
    $src = rex_media_manager::getUrl(MediaTypeRegistry::buildVirtualType($preset, 1200), $image);
    $srcset = [];
    foreach ($widths as $w) {
        $srcset[] = rex_media_manager::getUrl(MediaTypeRegistry::buildVirtualType($preset, $w), $image) . ' ' . $w . 'w';
    }

    return ['src' => $src, 'srcset' => implode(', ', $srcset)];
};
?>
<?php if ($enableSection): ?><section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div style="<?= rex_escape($containerStyle) ?>"><?php endif; ?>
<?php if ($headline !== ''): ?><h3><?= rex_escape($headline) ?></h3><?php endif; ?>
<ul style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(<?= $columns ?>,minmax(0,1fr));gap:<?= rex_escape($gapPx) ?>;">
    <?php foreach ($items as $index => $item): ?>
        <?php
        $itemTitle = (string) ($item['title'] ?? '');
        $itemText  = (string) ($item['text'] ?? '');
        $link      = $resolveLink($item);
        $fallback  = $headline !== '' ? $headline . ' ' . ($index + 1) : 'Karte ' . ($index + 1);
        $imageAlt  = \FriendsOfREDAXO\Builder\MediaAltResolver::resolve((string) ($item['image'] ?? ''), '', $itemTitle !== '' ? $itemTitle : $fallback);
        $imageInfo = $resolveImage((string) ($item['image'] ?? ''), $imageRatio);
        $imageInfoMobile = $mobileArtDirectionActive
            ? $resolveImage((string) ($item['image'] ?? ''), $imageRatioMobile)
            : ['src' => '', 'srcset' => ''];
        ?>
        <li>
            <article style="height:100%;<?= rex_escape($cardStyleAttr) ?>">
                <?php if ($imageInfo['src'] !== ''): ?>
                    <picture>
                        <?php if ($mobileArtDirectionActive && $imageInfoMobile['src'] !== ''): ?>
                            <source
                                media="(max-width: 767px)"
                                <?= $imageInfoMobile['srcset'] !== '' ? 'srcset="' . rex_escape($imageInfoMobile['srcset']) . '"' : 'srcset="' . rex_escape($imageInfoMobile['src']) . '"' ?>
                                sizes="100vw"
                            >
                        <?php endif; ?>
                        <img
                            src="<?= rex_escape($imageInfo['src']) ?>"
                            <?= $imageInfo['srcset'] !== '' ? 'srcset="' . rex_escape($imageInfo['srcset']) . '"' : '' ?>
                            sizes="(min-width: 1200px) <?= $desktopPx ?>px, 100vw"
                            alt="<?= rex_escape($imageAlt) ?>"
                            loading="lazy"
                            style="max-width:100%;height:auto;display:block;"
                        >
                    </picture>
                <?php endif; ?>
                <div style="padding:12px;">
                    <?php if ($itemTitle !== ''): ?><h4 style="margin:0 0 8px;"><?= rex_escape($itemTitle) ?></h4><?php endif; ?>
                    <?php if (trim(strip_tags($itemText)) !== ''): ?><div style="margin:0 0 8px;"><?= $itemText ?></div><?php endif; ?>
                    <?php if ($link['href'] !== ''): ?>
                        <a href="<?= rex_escape($link['href']) ?>"
                           <?= $link['target'] !== '' ? 'target="' . rex_escape($link['target']) . '" rel="noopener"' : '' ?>
                           aria-label="<?= rex_escape($link['text'] . ($itemTitle !== '' ? ' – ' . $itemTitle : '')) ?>">
                            <?= rex_escape($link['text']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        </li>
    <?php endforeach; ?>
</ul>
<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
