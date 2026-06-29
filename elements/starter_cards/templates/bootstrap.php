<?php
use FriendsOfREDAXO\Builder\Starter\StarterConfig;
use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

$headline    = (string) ($elementData['headline'] ?? '');
$items       = $elementData['items'] ?? [];
$cardStyle   = (string) ($elementData['card_style'] ?? 'default');
$imageRatio  = (string) ($elementData['image_ratio'] ?? '16_9');
$imageRatioMobile = (string) ($elementData['image_ratio_mobile'] ?? '');
$hidpiEnabled = !empty($elementData['hidpi_enabled']);
$columns     = (int) ($elementData['columns'] ?? 3);
$sectionBg   = (string) ($elementData['section_bg'] ?? '');
$sectionPadding  = (string) ($elementData['section_padding'] ?? '');
$containerWidth  = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight    = !empty($elementData['section_light']);
$enableSection   = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if (!is_array($items) || $items === []) { return; }

$sectionClasses = array_filter([
    StarterConfig::mapBg($sectionBg, 'bootstrap'),
    StarterConfig::mapPadding($sectionPadding, 'bootstrap'),
    $sectionLight ? 'text-white' : '',
]);
$sectionClassStr = implode(' ', $sectionClasses);
$containerClass  = StarterConfig::mapContainer($containerWidth, 'bootstrap');

$cardClassMap = [
    'default' => 'panel panel-default',
    'primary' => 'panel panel-primary',
    'secondary' => 'panel panel-info',
    'muted' => 'panel panel-default',
    'hover' => 'panel panel-default',
    'transparent' => '',
];
$cardClass = $cardClassMap[$cardStyle] ?? 'panel panel-default';

/** @param array<string,mixed> $item @return array{href:string,target:string,text:string} */
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

$colMap = [1 => 'col-sm-12', 2 => 'col-sm-6', 3 => 'col-sm-4', 4 => 'col-sm-3', 5 => 'col-sm-2', 6 => 'col-sm-2'];
$colClass = $colMap[$columns] ?? 'col-sm-4';
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
    if (str_contains($container, 'expand') || str_contains($container, 'fluid') || $container === '') {
        return 1920;
    }

    return 1170;
};
$containerMaxPx = $estimateContainerMaxPx($containerWidth);
$desktopPx = (int) max(220, round($containerMaxPx / max(1, $columns)));
$mobileArtDirectionActive = $imageRatioMobile !== '' && $imageRatioMobile !== $imageRatio;

/** @return array{src:string,srcset:string} */
$resolveImage = static function (string $image, string $ratio, bool $hidpiEnabled): array {
    if ($image === '') {
        return ['src' => '', 'srcset' => ''];
    }

    if (!rex_addon::get('media_manager')->isAvailable()) {
        return ['src' => rex_url::media($image), 'srcset' => ''];
    }

    $preset = 'starter_cards_' . $ratio;
    $widths = $hidpiEnabled ? [400, 800, 1200, 1600, 2000, 2400, 3200] : [400, 800, 1200, 1600];
    $src = rex_media_manager::getUrl(MediaTypeRegistry::buildVirtualType($preset, 1200), $image);
    $srcset = [];
    foreach ($widths as $w) {
        $srcset[] = rex_media_manager::getUrl(MediaTypeRegistry::buildVirtualType($preset, $w), $image) . ' ' . $w . 'w';
    }

    return ['src' => $src, 'srcset' => implode(', ', $srcset)];
};
?>
<?php if ($enableSection): ?>
<section<?= $sectionClassStr !== '' ? ' class="' . rex_escape($sectionClassStr) . '"' : '' ?>>
<?php endif; ?>
<?php if ($enableContainer && $containerClass !== ''): ?><div class="<?= rex_escape($containerClass) ?>"><?php endif; ?>
    <?php if ($headline !== ''): ?><h3><?= rex_escape($headline) ?></h3><?php endif; ?>
    <div class="row">
        <?php foreach ($items as $index => $item): ?>
            <?php
            $itemTitle = (string) ($item['title'] ?? '');
            $itemText  = (string) ($item['text'] ?? '');
            $link      = $resolveLink($item);
            $fallback  = $headline !== '' ? $headline . ' ' . ($index + 1) : 'Karte ' . ($index + 1);
            $imageAlt  = \FriendsOfREDAXO\Builder\MediaAltResolver::resolve((string) ($item['image'] ?? ''), '', $itemTitle !== '' ? $itemTitle : $fallback);
            $imageInfo = $resolveImage((string) ($item['image'] ?? ''), $imageRatio, $hidpiEnabled);
            $imageInfoMobile = $mobileArtDirectionActive
                ? $resolveImage((string) ($item['image'] ?? ''), $imageRatioMobile, $hidpiEnabled)
                : ['src' => '', 'srcset' => ''];
            ?>
            <div class="<?= rex_escape($colClass) ?>">
                <article class="<?= rex_escape($cardClass) ?>">
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
                                sizes="(min-width: 992px) <?= $desktopPx ?>px, 100vw"
                                alt="<?= rex_escape($imageAlt) ?>"
                                class="img-responsive"
                                loading="lazy"
                            >
                        </picture>
                    <?php endif; ?>
                    <div class="panel-body">
                        <?php if ($itemTitle !== ''): ?><h4><?= rex_escape($itemTitle) ?></h4><?php endif; ?>
                        <?php if (trim(strip_tags($itemText)) !== ''): ?><div><?= $itemText ?></div><?php endif; ?>
                        <?php if ($link['href'] !== ''): ?>
                            <a href="<?= rex_escape($link['href']) ?>"
                               class="btn btn-link"
                               <?= $link['target'] !== '' ? 'target="' . rex_escape($link['target']) . '" rel="noopener"' : '' ?>
                               aria-label="<?= rex_escape($link['text'] . ($itemTitle !== '' ? ' – ' . $itemTitle : '')) ?>">
                                <?= rex_escape($link['text']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php if ($enableContainer && $containerClass !== ''): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
