<?php
$headline = (string) ($elementData['headline'] ?? '');
$text = (string) ($elementData['text'] ?? '');
$position = (string) ($elementData['media_position'] ?? 'left');
$mediaFile = (string) ($elementData['media_file'] ?? '');
$mediaAlt = (string) ($elementData['media_alt'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if ($headline === '' && trim(strip_tags($text)) === '' && $mediaFile === '') {
    return;
}

$ext = strtolower((string) pathinfo($mediaFile, PATHINFO_EXTENSION));
$isVideo = in_array($ext, ['mp4', 'webm', 'ogg'], true);

$mediaLabel = \FriendsOfREDAXO\Builder\MediaAltResolver::resolve($mediaFile, $mediaAlt, $headline);

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
        <div class="uk-grid-large uk-flex-middle" uk-grid>
            <?php if ($position === 'left'): ?>
                <div class="uk-width-1-2@m">
                    <?php if ($mediaFile !== ''): ?>
                        <?php if ($isVideo): ?>
                            <video controls playsinline aria-label="<?= rex_escape($mediaLabel !== '' ? $mediaLabel : 'Video') ?>" class="uk-width-1-1">
                                <source src="<?= rex_url::media($mediaFile) ?>" type="video/<?= rex_escape($ext) ?>">
                            </video>
                        <?php else: ?>
                            <img src="<?= rex_url::media($mediaFile) ?>" alt="<?= rex_escape($mediaLabel) ?>" class="uk-width-1-1" loading="lazy">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="uk-width-1-2@m">
                <?php if ($headline !== ''): ?>
                    <h3><?= rex_escape($headline) ?></h3>
                <?php endif; ?>
                <?php if ($text !== ''): ?>
                    <div><?= $text ?></div>
                <?php endif; ?>
            </div>
            <?php if ($position === 'right'): ?>
                <div class="uk-width-1-2@m">
                    <?php if ($mediaFile !== ''): ?>
                        <?php if ($isVideo): ?>
                            <video controls playsinline aria-label="<?= rex_escape($mediaLabel !== '' ? $mediaLabel : 'Video') ?>" class="uk-width-1-1">
                                <source src="<?= rex_url::media($mediaFile) ?>" type="video/<?= rex_escape($ext) ?>">
                            </video>
                        <?php else: ?>
                            <img src="<?= rex_url::media($mediaFile) ?>" alt="<?= rex_escape($mediaLabel) ?>" class="uk-width-1-1" loading="lazy">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
<?= $wrapperClose->parse('ycb_elements/wrapper.php') ?>
