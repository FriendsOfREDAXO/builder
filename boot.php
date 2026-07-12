<?php

/**
 * YForm Content Builder
 * Slice-based Content Builder for YForm
 */

// === PHASE 1: Config-Klassen registrieren ===
require_once rex_path::addon('builder', 'lib/config/FrameworkConfig.php');
require_once rex_path::addon('builder', 'lib/config/EditorConfig.php');
require_once rex_path::addon('builder', 'lib/config/ElementRegistry.php');
require_once rex_path::addon('builder', 'lib/config/ElementModeResolver.php');
require_once rex_path::addon('builder', 'lib/config/MediaTypeRegistry.php');
require_once rex_path::addon('builder', 'lib/config/ThemeProviderBridge.php');
require_once rex_path::addon('builder', 'lib/MediaNegotiatorBridge.php');

// API-Klassen registrieren (namespaced, via rex_api_function::register)
rex_api_function::register('content_builder', \FriendsOfREDAXO\Builder\Api\ContentBuilderApi::class);
rex_api_function::register('yform_list_columns', \FriendsOfREDAXO\Builder\Api\ListColumnsApi::class);

if (rex_addon::get('yform')->isAvailable()) {
    rex_extension::register('MEDIA_IS_IN_USE', static function (rex_extension_point $ep) {
        return \FriendsOfREDAXO\Builder\MediaInUse::isMediaInUse($ep);
    });
}

if (rex_addon::get('media_manager')->isAvailable()) {
    rex_media_manager::addEffect(rex_effect_content_builder::class);
    rex_extension::register('MEDIA_MANAGER_FILTERSET', static function (rex_extension_point $ep): array {
        return \FriendsOfREDAXO\Builder\MediaManagerFilterset::apply($ep);
    }, rex_extension::EARLY);
    rex_extension::register('MEDIA_MANAGER_INIT', static function (rex_extension_point $ep): void {
        \FriendsOfREDAXO\Builder\MediaNegotiatorBridge::adjustCachePath($ep);
    }, rex_extension::EARLY);
}

// Theme-Provider Integration: konfiguriertes Backend-Theme anwenden
if (rex::isBackend()) {
    $addon = rex_addon::get('builder');
    $assetUrl = static function (string $assetPath) use ($addon): string {
        $url = $addon->getAssetsUrl($assetPath);
        $file = $addon->getAssetsPath($assetPath);
        if (is_file($file)) {
            $mtime = filemtime($file);
            if (false !== $mtime) {
                $url .= '?v=' . $mtime;
            }
        }

        return $url;
    };
    
    $configuredTheme = (string) $addon->getConfig('theme', '');
    if ($configuredTheme !== '') {
        \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::resetThemeContext();
        \FriendsOfREDAXO\Builder\Config\ThemeProviderBridge::setTheme($configuredTheme);
    }
    
    // Fieldset Akkordeon Assets laden
    rex_view::addCssFile($assetUrl('fieldset-accordion.css'));
    rex_view::addJsFile($assetUrl('fieldset-accordion.js'));
}

if (rex::isBackend() && rex_addon::get('focuspoint')->isAvailable()) {
    rex_extension::register('FOCUSPOINT_PREVIEW_SELECT', static function (rex_extension_point $ep): array {
        $subject = $ep->getSubject();
        if (!is_array($subject)) {
            return $subject;
        }

        $labelMap = rex_addon::get('builder')->getConfig('focuspoint_ratio_type_labels', []);
        if (!is_array($labelMap) || $labelMap === []) {
            return $subject;
        }

        foreach ($subject as $typeName => $typeData) {
            if (!isset($labelMap[$typeName]) || !is_array($typeData)) {
                continue;
            }

            $entry = $labelMap[$typeName];
            if (is_array($entry)) {
                $title = trim((string) ($entry['title'] ?? ''));
                $description = trim((string) ($entry['description'] ?? ''));
                if ($title !== '') {
                    $typeData['label'] = $description !== '' ? $title . ' - ' . $description : $title;
                    $subject[$typeName] = $typeData;
                }
            } elseif (is_string($entry) && trim($entry) !== '') {
                $typeData['label'] = trim($entry);
                $subject[$typeName] = $typeData;
            }
        }

        return $subject;
    });
}

// Extension Points registrieren
rex_extension::register('PACKAGES_INCLUDED', function() {
    if (rex_addon::get('yform')->isAvailable() && class_exists('rex_yform')) {
        // Templates nur registrieren, wenn YForm vorhanden ist.
        rex_yform::addTemplatePath(rex_path::addon('builder', 'ytemplates'));
    }
});

// Assets für Backend einbinden
if (rex::isBackend()) {
    $addon = rex_addon::get('builder');
    $assetUrl = static function (string $assetPath) use ($addon): string {
        $url = $addon->getAssetsUrl($assetPath);
        $file = $addon->getAssetsPath($assetPath);
        if (is_file($file)) {
            $mtime = filemtime($file);
            if (false !== $mtime) {
                $url .= '?v=' . $mtime;
            }
        }

        return $url;
    };

    $backendApiUrl = static function (array $params): string {
        return rex_url::backendController($params, false);
    };

    if (rex_be_controller::getCurrentPagePart(1) === 'builder') {
        rex_extension::register('PAGE_TITLE', static function (rex_extension_point $ep) {
            return '<i class="builder-icon-logo"></i> ' . $ep->getSubject();
        }, rex_extension::EARLY);
    }

    rex_view::addCssFile($assetUrl('css/builder-brand.css'));
    rex_view::addCssFile($assetUrl('content-builder.css'));
    rex_view::addCssFile($assetUrl('content-builder-dark.css'));
    rex_view::addCssFile($assetUrl('divider.css'));
    rex_view::addCssFile($assetUrl('cards.css'));
    rex_view::addCssFile($backendApiUrl([
        'rex-api-call' => 'content_builder',
        'action' => 'get_element_css',
        'framework' => 'uikit',
    ]));
    rex_view::addCssFile($backendApiUrl([
        'rex-api-call' => 'content_builder',
        'action' => 'get_element_css',
        'framework' => 'bootstrap',
    ]));
    rex_view::addJsFile($assetUrl('content-builder.js'));
    rex_view::addJsFile($assetUrl('media-browser.js'));
    rex_view::addJsFile($assetUrl('field-widgets.js'));

    // YForm Manager Assets laden (für YFormPickerField)
    if (rex_addon::get('yform')->isAvailable()) {
        rex_view::addJsFile(rex_addon::get('yform')->getAssetsUrl('widget.js'));
        rex_view::addJsFile(rex_addon::get('yform')->getAssetsUrl('manager.js'));
    }

    // YForm-Listen-Profile: AJAX-Spaltenlader nur auf den relevanten Subseiten laden.
    if (in_array(rex_be_controller::getCurrentPage(), ['builder/settings', 'builder/settings_yform_list_profiles'], true)) {
        rex_view::addCssFile($assetUrl('yform_list_profiles.css'));
        rex_view::addJsFile($assetUrl('yform_list_profiles.js'));
        rex_view::setJsProperty('YFL_API_URL', rex_url::backendController([
            'rex-api-call' => 'yform_list_columns',
        ]));
    }

    if ('builder/demo' === rex_be_controller::getCurrentPage()) {
        rex_view::addJsFile($assetUrl('demo.js'));
    }

    if ('builder/modules' === rex_be_controller::getCurrentPage()) {
        rex_view::addJsFile($assetUrl('js/modules-page.js'));
    }
}

// Assets für Frontend einbinden (CSS für Elemente)
if (!rex::isBackend()) {
    rex_view::addCssFile(rex_addon::get('builder')->getAssetsUrl('divider.css'));
    rex_view::addCssFile(rex_addon::get('builder')->getAssetsUrl('cards.css'));
}

