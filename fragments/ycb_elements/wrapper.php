<?php

$mode = (string) $this->getVar('mode', 'open');
$enableSection = (bool) $this->getVar('enable_section', true);
$enableContainer = (bool) $this->getVar('enable_container', true);
$containerWidth = trim((string) $this->getVar('container_width', 'uk-container'));
$sectionBg = trim((string) $this->getVar('section_bg', ''));
$isContentEditPreview = 'content/edit' === rex_request('page', 'string', '');

if ('close' === $mode) {
    $sectionBgImage = trim((string) $this->getVar('section_bg_image', ''));
    $bgMediaExt = strtolower(pathinfo($sectionBgImage, PATHINFO_EXTENSION));
    $hasBackgroundVideo = '' !== $sectionBgImage
        && in_array($bgMediaExt, ['mp4', 'webm', 'ogg'], true)
        && !$isContentEditPreview
        && file_exists(rex_path::media($sectionBgImage));

    if ($enableContainer && '' !== $containerWidth) {
        echo '</div>';
    }

    if ($enableSection && $hasBackgroundVideo) {
        echo '</div>';
    }

    if ($enableSection) {
        echo '</section>';
    }

    return;
}

$sectionClasses = [];
if ($enableSection) {
    $sectionClasses[] = 'uk-section';

    if ('' !== $sectionBg && 'uk-background-transparent' !== $sectionBg) {
        $sectionClasses[] = $sectionBg;
    }

    $sectionPadding = trim((string) $this->getVar('section_padding', ''));
    if ('' !== $sectionPadding) {
        $sectionClasses[] = $sectionPadding;
    }

    if ((bool) $this->getVar('section_light', false)) {
        $sectionClasses[] = 'uk-light';
    }

    $sectionClassExtra = trim((string) $this->getVar('section_class_extra', ''));
    if ('' !== $sectionClassExtra) {
        $sectionClasses[] = $sectionClassExtra;
    }
}

$sectionBgImage = trim((string) $this->getVar('section_bg_image', ''));
$sectionStyle = '';
$backgroundVideoHtml = '';
$hasBackgroundVideo = false;

$appendStyle = static function (string $styleAttr, string $css): string {
    if ('' === $styleAttr) {
        return ' style="' . $css . '"';
    }

    return substr($styleAttr, 0, -1) . ' ' . $css . '"';
};

if ($enableSection && '' !== $sectionBgImage) {
    $bgMediaExt = strtolower(pathinfo($sectionBgImage, PATHINFO_EXTENSION));
    $videoExtensions = ['mp4', 'webm', 'ogg'];

    if (!$isContentEditPreview && in_array($bgMediaExt, $videoExtensions, true) && file_exists(rex_path::media($sectionBgImage))) {
        $hasBackgroundVideo = true;
        $sectionClasses[] = 'uk-cover-container';
        $sectionClasses[] = 'uk-position-relative';
        $videoSrc = rex_url::media($sectionBgImage);
        $backgroundVideoHtml = '<video class="uk-cover" autoplay loop muted playsinline uk-cover><source src="'
            . rex_escape($videoSrc)
            . '" type="video/'
            . rex_escape($bgMediaExt)
            . '"></video>';
    } else {
        $sectionClasses[] = 'uk-background-cover';
        $bgImageUrl = rex_media_manager::getUrl('content_slideshow', $sectionBgImage);
        $sectionStyle = ' style="background-image: url(\'' . rex_escape($bgImageUrl) . '\'); background-size: cover; background-position: center;"';
    }
}

if ($enableSection && !$hasBackgroundVideo && '' === $sectionBgImage && rex::isBackend() && '' !== $sectionBg && 'uk-background-transparent' !== $sectionBg) {
    $previewColor = '';

    $themeBackgrounds = \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::getBackgroundOptions('uikit');
    if (is_array($themeBackgrounds) && isset($themeBackgrounds[$sectionBg]) && is_array($themeBackgrounds[$sectionBg])) {
        $themeColor = $themeBackgrounds[$sectionBg]['color'] ?? '';
        if (is_string($themeColor) && '' !== trim($themeColor)) {
            $previewColor = trim($themeColor);
        }
    }

    if ('' === $previewColor) {
        $previewMap = [
            'muted' => '#f8f8f8',
            'primary' => '#1e87f0',
            'secondary' => '#222222',
            'uk-section-default' => '#ffffff',
            'uk-section-muted' => '#f8f8f8',
            'uk-section-primary' => '#1e87f0',
            'uk-section-secondary' => '#222222',
            'uk-background-default' => '#ffffff',
            'uk-background-muted' => '#f8f8f8',
            'uk-background-primary' => '#1e87f0',
            'uk-background-secondary' => '#222222',
        ];

        $previewColor = $previewMap[$sectionBg] ?? '';
    }

    if ('' !== $previewColor) {
        $sectionStyle = $appendStyle($sectionStyle, 'background-color: ' . rex_escape($previewColor) . ';');
    }
}

if ($enableSection) {
    $sectionId = trim((string) $this->getVar('section_id', ''));
    $sectionIdAttr = '';
    if ('' !== $sectionId) {
        $sectionIdAttr = ' id="' . rex_escape($sectionId) . '"';
    }

    $sectionAttrExtra = trim((string) $this->getVar('section_attr_extra', ''));
    if ('' !== $sectionAttrExtra) {
        $sectionAttrExtra = ' ' . $sectionAttrExtra;
    }

    echo '<section class="' . rex_escape(implode(' ', $sectionClasses)) . '"' . $sectionIdAttr . $sectionAttrExtra . $sectionStyle . '>';
    if ($hasBackgroundVideo) {
        echo $backgroundVideoHtml;
        echo '<div class="uk-position-relative">';
    }
}

if ($enableContainer && '' !== $containerWidth) {
    echo '<div class="' . rex_escape($containerWidth) . '">';
}
