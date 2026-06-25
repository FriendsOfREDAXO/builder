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

$bgMap = [
    'uk-background-default' => '#ffffff',
    'uk-background-muted' => '#f7f7f7',
    'uk-background-primary' => '#1e87f0',
    'uk-background-secondary' => '#222222',
];
$paddingMap = [
    'uk-padding-remove' => '0',
    'uk-padding-small' => '18px 0',
    'uk-padding' => '35px 0',
    'uk-padding-large' => '55px 0',
];
$sectionStyle = '';
if (isset($bgMap[$sectionBg])) {
    $sectionStyle .= 'background:' . $bgMap[$sectionBg] . ';';
}
if (isset($paddingMap[$sectionPadding])) {
    $sectionStyle .= 'padding:' . $paddingMap[$sectionPadding] . ';';
}
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}

$containerClass = 'container';
if ($containerWidth === '') {
    $containerClass = '';
} elseif (strpos($containerWidth, 'expand') !== false || strpos($containerWidth, 'xlarge') !== false) {
    $containerClass = 'container-fluid';
}
?>
<section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>>
    <?php if ($containerClass !== ''): ?><div class="<?= $containerClass ?>"><?php endif; ?>
        <div class="row">
            <?php if ($position === 'left'): ?>
                <div class="col-sm-6">
                    <?php if ($mediaFile !== ''): ?>
                        <?php if ($isVideo): ?>
                            <div class="embed-responsive embed-responsive-16by9">
                                <video controls playsinline aria-label="<?= rex_escape($mediaLabel !== '' ? $mediaLabel : 'Video') ?>" class="embed-responsive-item">
                                    <source src="<?= rex_url::media($mediaFile) ?>" type="video/<?= rex_escape($ext) ?>">
                                </video>
                            </div>
                        <?php else: ?>
                            <img src="<?= rex_url::media($mediaFile) ?>" alt="<?= rex_escape($mediaLabel) ?>" class="img-responsive" loading="lazy">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="col-sm-6">
                <?php if ($headline !== ''): ?><h3><?= rex_escape($headline) ?></h3><?php endif; ?>
                <?php if ($text !== ''): ?><div><?= $text ?></div><?php endif; ?>
            </div>
            <?php if ($position === 'right'): ?>
                <div class="col-sm-6">
                    <?php if ($mediaFile !== ''): ?>
                        <?php if ($isVideo): ?>
                            <div class="embed-responsive embed-responsive-16by9">
                                <video controls playsinline aria-label="<?= rex_escape($mediaLabel !== '' ? $mediaLabel : 'Video') ?>" class="embed-responsive-item">
                                    <source src="<?= rex_url::media($mediaFile) ?>" type="video/<?= rex_escape($ext) ?>">
                                </video>
                            </div>
                        <?php else: ?>
                            <img src="<?= rex_url::media($mediaFile) ?>" alt="<?= rex_escape($mediaLabel) ?>" class="img-responsive" loading="lazy">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php if ($containerClass !== ''): ?></div><?php endif; ?>
</section>
