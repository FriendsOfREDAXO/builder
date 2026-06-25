<?php
/**
 * UIkit Template für Section Element
 * @var array $elementData
 * @var string $closeType 'open', 'close', or null
 */

// Theme Override: Falls gesetzt, Theme-Provider Kontext anpassen
$themeOverride = $elementData['theme_override'] ?? '';
if (!empty($themeOverride)) {
    \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::setTheme((string) $themeOverride);
}

$label = $elementData['label'] ?? '';
$bgColor = $elementData['background_color'] ?? 'none';
$bgImage = $elementData['background_image'] ?? '';
$paddingTop = $elementData['padding_top'] ?? 'medium';
$paddingBottom = $elementData['padding_bottom'] ?? 'medium';
$container = $elementData['container'] ?? 'container';
$textAlign = $elementData['text_align'] ?? '';
$customClass = $elementData['custom_class'] ?? '';
$customId = $elementData['custom_id'] ?? '';

// Grid-Optionen
$gridEnabled        = !empty($elementData['grid_enabled']);
$gridChildWidth     = $elementData['grid_child_width'] ?? '1-3';
$gridChildWidthTab  = $elementData['grid_child_width_tablet'] ?? '1-2';
$gridChildWidthMob  = $elementData['grid_child_width_mobile'] ?? '1-1';
$gridGap            = $elementData['grid_gap'] ?? '';
$gridMatch          = !empty($elementData['grid_match']);
$gridDivider        = !empty($elementData['grid_divider']);

// Padding-Mapping für UIkit
$paddingMap = [
    'none' => '',
    'small' => 'uk-section-small',
    'medium' => 'uk-section',
    'large' => 'uk-section-large',
    'xlarge' => 'uk-section-xlarge'
];

// Background-Klassen für UIkit
$bgClasses = [
    'none' => '',
    'transparent' => '',
    'muted' => 'uk-section-muted',
    'primary' => 'uk-section-primary',
    'secondary' => 'uk-section-secondary',
    // Legacy-Werte aus älteren Konfigurationen
    'light' => 'uk-section-muted',
    'dark' => 'uk-section-secondary',
    'white' => ''
];

$classes = [];

$sectionPaddingClass = '';
$sectionBgClass = '';
$sectionClassExtra = '';

// Section class
if (isset($paddingMap[$paddingTop]) && isset($paddingMap[$paddingBottom])) {
    // Bei unterschiedlichen Paddings: Basis verwenden
    if ($paddingTop === $paddingBottom && !empty($paddingMap[$paddingTop])) {
        $sectionPaddingClass = $paddingMap[$paddingTop];
        $classes[] = $sectionPaddingClass;
    } else {
        $classes[] = 'uk-section';
    }
}

// Background
if (!empty($bgImage)) {
    $classes[] = 'uk-background-cover uk-background-center-center';
} elseif (isset($bgClasses[$bgColor]) && $bgClasses[$bgColor]) {
    $sectionBgClass = $bgClasses[$bgColor];
    $classes[] = $sectionBgClass;
} elseif (is_string($bgColor) && str_starts_with($bgColor, 'uk-')) {
    // ThemeBuilder-Klassen (z.B. uk-background-*) direkt durchreichen
    $sectionBgClass = $bgColor;
    $classes[] = $sectionBgClass;
}

// Text Align
if ($textAlign) {
    $sectionClassExtra = trim($sectionClassExtra . ' ' . 'uk-text-' . $textAlign);
    $classes[] = 'uk-text-' . $textAlign;
}

// Custom Class
if ($customClass) {
    $sectionClassExtra = trim($sectionClassExtra . ' ' . $customClass);
    $classes[] = $customClass;
}

$classString = implode(' ', $classes);

// Style für Hintergrundbild
$style = '';
if ($bgImage) {
    $style = ' style="background-image: url(' . rex_url::media($bgImage) . ');"';
}

// ID-Attribut
$idAttr = $customId ? ' id="' . rex_escape($customId) . '"' : '';

// Container-Klasse für UIkit
$containerClass = 'uk-container';
if ($container === 'container-fluid') {
    $containerClass = 'uk-container uk-container-expand';
}

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', true, false);
$wrapper->setVar('enable_container', $container !== 'none', false);
$wrapper->setVar('section_bg', $sectionBgClass, false);
$wrapper->setVar('section_bg_image', $bgImage, false);
$wrapper->setVar('section_padding', $sectionPaddingClass, false);
$wrapper->setVar('container_width', $containerClass, false);
$wrapper->setVar('section_light', false, false);
$wrapper->setVar('section_id', $customId, false);
$wrapper->setVar('section_class_extra', $sectionClassExtra, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', true, false);
$wrapperClose->setVar('enable_container', $container !== 'none', false);
$wrapperClose->setVar('section_bg_image', $bgImage, false);
$wrapperClose->setVar('container_width', $containerClass, false);

// Wenn closeType gesetzt ist, nur öffnen oder schließen
if (isset($closeType)) {
    if ($closeType === 'close') {
        // Grid-Wrapper schließen wenn aktiviert
        if ($gridEnabled) {
            echo '        </div>' . "\n"; // uk-grid
        }
        echo $wrapperClose->parse('ycb_elements/wrapper.php') . "\n";
        return;
    }
    
    if ($closeType === 'open') {
        echo $wrapper->parse('ycb_elements/wrapper.php') . "\n";

        // Grid-Wrapper öffnen wenn aktiviert
        if ($gridEnabled) {
            $gridClasses = ['uk-grid'];
            if ($gridGap) {
                $gridClasses[] = 'uk-grid-' . $gridGap;
            }
            if ($gridMatch) {
                $gridClasses[] = 'uk-grid-match';
            }
            if ($gridDivider) {
                $gridClasses[] = 'uk-grid-divider';
            }
            // uk-child-width für mobile, tablet, desktop
            if ($gridChildWidthMob && $gridChildWidthMob !== '1-1') {
                $gridClasses[] = 'uk-child-width-' . $gridChildWidthMob;
            } else {
                $gridClasses[] = 'uk-child-width-1-1';
            }
            $gridClasses[] = 'uk-child-width-' . $gridChildWidthTab . '@s';
            $gridClasses[] = 'uk-child-width-' . $gridChildWidth . '@m';

            echo '        <div class="' . implode(' ', $gridClasses) . '" uk-grid>' . "\n";
        }

        return;
    }
}

// Fallback: Normaler Output (sollte nicht verwendet werden)
?>
<!-- Section: <?= rex_escape($label ?: 'Unbenannt') ?> -->
<?= $wrapper->parse('ycb_elements/wrapper.php') ?>
        <!-- Nachfolgende Elemente werden hier eingefügt -->
<?= $wrapperClose->parse('ycb_elements/wrapper.php') ?>
