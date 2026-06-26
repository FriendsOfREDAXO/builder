<?php

use FriendsOfREDAXO\Builder\Config\MediaTypeRegistry;

$addon = rex_addon::get('builder');
$selectedMedia = trim(rex_request('media_file', 'string', ''));
$currentPage = rex_be_controller::getCurrentPage();
$installCsrf = rex_csrf_token::factory('ycb_focuspoint_ratio_types_install');

$mediaManagerAvailable = rex_addon::get('media_manager')->isAvailable();
$focuspointAvailable = rex_addon::get('focuspoint')->isAvailable();
$pdfoutAvailable = rex_addon::get('pdfout')->isAvailable();

$presets = MediaTypeRegistry::getPresets();
ksort($presets, SORT_NATURAL | SORT_FLAG_CASE);

$totalPresets = count($presets);
$selectedMediaLabel = $selectedMedia !== '' ? $selectedMedia : '–';

/**
 * @param array<string, array{ratio: string, mode?: string, widths?: list<int>, default_width?: int}> $presetList
 * @return array<string, array{width_fr:int, height_fr:int, title:string, description:string}>
 */
$collectFocuspointRatioTypes = static function (array $presetList): array {
    $ratios = [];

    foreach ($presetList as $presetName => $presetConfig) {
        $mode = (string) ($presetConfig['mode'] ?? 'focuspoint');
        $ratio = trim((string) ($presetConfig['ratio'] ?? ''));

        if ($mode !== 'focuspoint' || $ratio === '' || $ratio === 'original') {
            continue;
        }

        if (preg_match('/^(?<w>[1-9][0-9]*)_(?<h>[1-9][0-9]*)$/', $ratio, $match) !== 1) {
            continue;
        }

        $widthFr = (int) $match['w'];
        $heightFr = (int) $match['h'];
        if ($widthFr < 1 || $heightFr < 1) {
            continue;
        }

        if (!isset($ratios[$ratio])) {
            $ratioHuman = $widthFr . ':' . $heightFr;
            $ratios[$ratio] = [
                'width_fr' => $widthFr,
                'height_fr' => $heightFr,
                'title' => 'CB Focuspoint ' . $ratioHuman,
                'description' => 'Kanonischer Ratio-Typ für Focuspoint-Preview (' . $ratioHuman . ', Preset: ' . $presetName . ')',
            ];
        }
    }

    ksort($ratios, SORT_NATURAL | SORT_FLAG_CASE);

    return $ratios;
};

$installMessage = '';
if (rex_post('ycb_action', 'string') === 'install_focuspoint_ratio_types') {
    if (!$installCsrf->isValid()) {
        $installMessage .= rex_view::warning(rex_i18n::msg('csrf_token_invalid'));
    } elseif (!$mediaManagerAvailable) {
        $installMessage .= rex_view::warning(rex_i18n::msg('builder_media_types_preview_install_requires'));
    } else {
        $ratioTypes = $collectFocuspointRatioTypes($presets);
        if ($ratioTypes === []) {
            $installMessage .= rex_view::info(rex_i18n::msg('builder_media_types_preview_install_no_ratios'));
        } else {
            $installWithTexts = rex_post('install_with_texts', 'bool', true);
            $installed = 0;
            $updated = 0;
            $effectsWritten = 0;

            $labelMap = $addon->getConfig('focuspoint_ratio_type_labels', []);
            if (!is_array($labelMap)) {
                $labelMap = [];
            }

            try {
                $sql = rex_sql::factory();

                foreach ($ratioTypes as $ratio => $ratioData) {
                    $typeName = 'cb_fp_ratio_' . $ratio;
                    $description = $installWithTexts ? (string) $ratioData['description'] : '';

                    $typeRows = $sql->getArray(
                        'SELECT id FROM ' . rex::getTable('media_manager_type') . ' WHERE name = :name LIMIT 1',
                        [':name' => $typeName]
                    );

                    if ($typeRows !== []) {
                        $typeId = (int) $typeRows[0]['id'];

                        $sql->setTable(rex::getTable('media_manager_type'));
                        $sql->setWhere(['id' => $typeId]);
                        $sql->setValue('status', 1);
                        if ($installWithTexts) {
                            $sql->setValue('description', $description);
                        }
                        $sql->addGlobalUpdateFields();
                        $sql->update();

                        ++$updated;
                    } else {
                        $sql->setTable(rex::getTable('media_manager_type'));
                        $sql->setValue('status', 1);
                        $sql->setValue('name', $typeName);
                        $sql->setValue('description', $description);
                        $sql->addGlobalCreateFields();
                        $sql->addGlobalUpdateFields();
                        $sql->insert();
                        $typeId = (int) $sql->getLastId();

                        ++$installed;
                    }

                    $effectName = 'focuspoint_fit';
                    $effectParameters = [
                        'rex_effect_focuspoint_fit' => [
                            'rex_effect_focuspoint_fit_meta' => 'med_focuspoint',
                            'rex_effect_focuspoint_fit_focus' => '50,50',
                            'rex_effect_focuspoint_fit_width' => (string) $ratioData['width_fr'] . 'fr',
                            'rex_effect_focuspoint_fit_height' => (string) $ratioData['height_fr'] . 'fr',
                            'rex_effect_focuspoint_fit_zoom' => '0%',
                        ],
                    ];

                    if (!$focuspointAvailable || !class_exists('rex_effect_focuspoint_fit')) {
                        $effectName = 'content_builder';
                        $effectParameters = [
                            'rex_effect_content_builder' => [
                                'rex_effect_content_builder_preset' => 'focuspoint_ratio_fallback',
                                'rex_effect_content_builder_ratio' => (string) $ratioData['width_fr'] . '_' . (string) $ratioData['height_fr'],
                                'rex_effect_content_builder_mode' => 'focuspoint',
                                'rex_effect_content_builder_width' => '1600',
                                'rex_effect_content_builder_allow_enlarge' => 'not_enlarge',
                            ],
                        ];
                    }

                    $effectRows = $sql->getArray(
                        'SELECT id, effect FROM ' . rex::getTable('media_manager_type_effect')
                        . ' WHERE type_id = :type_id AND effect IN (\'focuspoint_fit\', \'content_builder\')'
                        . ' ORDER BY priority ASC, id ASC',
                        [':type_id' => $typeId]
                    );

                    if ($effectRows !== []) {
                        $effectId = (int) $effectRows[0]['id'];
                        $sql->setTable(rex::getTable('media_manager_type_effect'));
                        $sql->setWhere(['id' => $effectId]);
                        $sql->setValue('effect', $effectName);
                        $sql->setValue('parameters', json_encode($effectParameters));
                        $sql->addGlobalUpdateFields();
                        $sql->update();

                        // Doppelte Alt-Effekte entfernen (z. B. focuspoint_fit + content_builder parallel).
                        for ($i = 1, $len = count($effectRows); $i < $len; ++$i) {
                            $obsoleteId = (int) ($effectRows[$i]['id'] ?? 0);
                            if ($obsoleteId <= 0) {
                                continue;
                            }

                            $sql->setQuery(
                                'DELETE FROM ' . rex::getTable('media_manager_type_effect') . ' WHERE id = :id',
                                [':id' => $obsoleteId]
                            );
                        }
                    } else {
                        $priorityRows = $sql->getArray(
                            'SELECT MAX(priority) AS max_priority FROM ' . rex::getTable('media_manager_type_effect') . ' WHERE type_id = :type_id',
                            [':type_id' => $typeId]
                        );
                        $priority = (int) ($priorityRows[0]['max_priority'] ?? 0) + 1;

                        $sql->setTable(rex::getTable('media_manager_type_effect'));
                        $sql->setValue('type_id', $typeId);
                        $sql->setValue('effect', $effectName);
                        $sql->setValue('parameters', json_encode($effectParameters));
                        $sql->setValue('priority', $priority);
                        $sql->addGlobalCreateFields();
                        $sql->addGlobalUpdateFields();
                        $sql->insert();
                    }

                    ++$effectsWritten;

                    if ($installWithTexts) {
                        $labelMap[$typeName] = [
                            'title' => (string) $ratioData['title'],
                            'description' => (string) $ratioData['description'],
                        ];
                    }
                }

                if ($installWithTexts) {
                    $addon->setConfig('focuspoint_ratio_type_labels', $labelMap);
                }

                rex_media_manager::deleteCache();

                $installMessage .= rex_view::success(
                    rex_i18n::msg('builder_media_types_preview_install_success', (string) $installed, (string) $updated, (string) $effectsWritten)
                );
            } catch (Throwable $e) {
                $installMessage .= rex_view::warning(
                    rex_i18n::msg('builder_media_types_preview_install_error', $e->getMessage())
                );
            }
        }
    }
}

$media = null;
if ($selectedMedia !== '') {
    $media = rex_media::get($selectedMedia);
}

$hero = '';
$hero .= '<section class="builder-hero">';
$hero .= '<div class="builder-hero__bg" aria-hidden="true">';
$hero .= '<span class="builder-hero__block builder-hero__block--a"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--b"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--c"></span>';
$hero .= '<span class="builder-hero__block builder-hero__block--d"></span>';
$hero .= '</div>';
$hero .= '<div class="builder-hero__content">';
$hero .= '<div class="builder-hero__logo" aria-hidden="true"></div>';
$hero .= '<div class="builder-hero__copy">';
$hero .= '<p class="builder-hero__kicker">Builder · Virtuelle Bildtypen</p>';
$hero .= '<h2 class="builder-hero__title">Virtuelle Medientypen live prüfen</h2>';
$hero .= '<p class="builder-hero__lead">Teste Presets, Ratio-Modi und Breiten direkt gegen eine ausgewählte Datei. So siehst du sofort, wie deine virtuellen Bildtypen im Frontend wirken.</p>';
$hero .= '<div class="builder-hero__chips">';
$hero .= '<span class="builder-chip">Media Manager</span>';
$hero .= '<span class="builder-chip">Focuspoint</span>';
$hero .= '<span class="builder-chip">Preset Preview</span>';
$hero .= '<span class="builder-chip">Virtual Types</span>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '<div class="builder-hero__stats">';
$hero .= '<div class="builder-stat"><strong>' . rex_escape((string) $totalPresets) . '</strong><span>Presets</span></div>';
$hero .= '<div class="builder-stat"><strong>' . ($mediaManagerAvailable ? 'Ja' : 'Nein') . '</strong><span>Media Manager</span></div>';
$hero .= '<div class="builder-stat"><strong>' . rex_escape($selectedMediaLabel) . '</strong><span>Aktuelle Datei</span></div>';
$hero .= '</div>';
$hero .= '</div>';
$hero .= '</section>';

echo '<div style="margin-bottom:16px;">' . $hero . '</div>';

$content = '';
$content .= '<p class="help-block">' . rex_i18n::msg('builder_media_types_preview_intro') . '</p>';

$formContent = '';
$formContent .= '<form method="get" action="' . rex_escape(rex_url::currentBackendPage()) . '" class="form-horizontal">';
$formContent .= '<input type="hidden" name="page" value="' . rex_escape($currentPage) . '">';
$formContent .= '<div class="form-group">';
$formContent .= '<label class="col-sm-2 control-label">' . rex_i18n::msg('builder_media_types_preview_select_image') . '</label>';
$formContent .= '<div class="col-sm-10">';
$formContent .= rex_var_media::getWidget(1, 'media_file', $selectedMedia);
$formContent .= '<p class="help-block">' . rex_i18n::msg('builder_media_types_preview_note') . '</p>';
$formContent .= '<button class="btn btn-primary" type="submit">' . rex_i18n::msg('builder_media_types_preview_show') . '</button>';
$formContent .= '</div>';
$formContent .= '</div>';
$formContent .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('elements', [[
    'label' => '',
    'field' => $formContent,
]], false);
$content .= $fragment->parse('core/form/form.php');

$content .= $installMessage;

$installForm = '';
$installForm .= '<form method="post" action="' . rex_escape(rex_url::currentBackendPage()) . '" class="form-horizontal" style="margin-top:10px;">';
$installForm .= '<input type="hidden" name="page" value="' . rex_escape($currentPage) . '">';
$installForm .= '<input type="hidden" name="ycb_action" value="install_focuspoint_ratio_types">';
$installForm .= $installCsrf->getHiddenField();
$installForm .= '<div class="form-group">';
$installForm .= '<label class="col-sm-2 control-label">Focuspoint</label>';
$installForm .= '<div class="col-sm-10">';
$installForm .= '<div class="checkbox" style="margin-top:0;">';
$installForm .= '<label><input type="checkbox" name="install_with_texts" value="1" checked> ' . rex_i18n::msg('builder_media_types_preview_install_with_texts') . '</label>';
$installForm .= '</div>';
$installForm .= '<button class="btn btn-default" type="submit"' . ((!$mediaManagerAvailable) ? ' disabled' : '') . '>' . rex_i18n::msg('builder_media_types_preview_install_button') . '</button>';
$installForm .= '<p class="help-block" style="margin-top:8px;">' . rex_i18n::msg('builder_media_types_preview_install_hint') . '</p>';
$installForm .= '</div>';
$installForm .= '</div>';
$installForm .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('elements', [[
    'label' => '',
    'field' => $installForm,
]], false);
$content .= $fragment->parse('core/form/form.php');

if ($selectedMedia === '') {
    $content .= rex_view::info(rex_i18n::msg('builder_media_types_preview_no_image'));
} elseif (!$media instanceof rex_media) {
    $content .= rex_view::warning(rex_i18n::msg('builder_media_types_preview_invalid_image'));
} elseif ($presets === []) {
    $content .= rex_view::info(rex_i18n::msg('builder_media_types_preview_no_presets'));
} else {
    $mediaMime = strtolower((string) $media->getType());
    $mediaFilename = strtolower((string) $media->getFileName());
    $isImage = str_starts_with($mediaMime, 'image/');
    $isPdf = $mediaMime === 'application/pdf' || rex_file::extension($mediaFilename) === 'pdf';

    if (!$isImage && !$isPdf) {
        $content .= rex_view::warning('Die ausgewählte Datei ist kein unterstütztes Vorschau-Medium (Bild oder PDF). MIME: ' . rex_escape((string) $media->getType()));
    } elseif ($isPdf && !$pdfoutAvailable) {
        $content .= rex_view::warning('PDF wurde gewählt, aber das AddOn pdfout ist nicht verfügbar. Vorschau-Bilder können daher nicht gerendert werden.');
    }

    $originalUrl = rex_url::media($selectedMedia);
    $meta = [];
    $title = trim((string) $media->getTitle());
    if ($title !== '') {
        $meta[] = rex_escape($title);
    }
    $meta[] = rex_escape((string) $media->getType());
    $meta[] = rex_escape(rex_formatter::bytes((int) $media->getSize()));

    $content .= '<div class="alert alert-success" style="margin-top:15px;">';
    $content .= '<strong>' . rex_i18n::msg('builder_media_types_preview_original') . ':</strong> ';
    $content .= rex_escape($selectedMedia) . ' <span class="text-muted">(' . implode(' | ', $meta) . ')</span>';
    $content .= '</div>';

    $content .= '<style>
.ycb-media-type-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; margin-top:16px; }
.ycb-media-type-card { border:1px solid #d8e1eb; border-radius:6px; background:#fff; overflow:hidden; }
.ycb-media-type-card-header { padding:10px 12px; border-bottom:1px solid #e8edf3; background:#f7f9fb; }
.ycb-media-type-card-title { margin:0 0 6px; font-size:14px; font-weight:700; }
.ycb-media-type-card-meta { margin:0; color:#5f6f83; font-size:12px; }
.ycb-media-type-item { padding:10px 12px; border-top:1px solid #edf1f6; }
.ycb-media-type-item:first-child { border-top:none; }
.ycb-media-type-label { margin-bottom:8px; font-size:12px; color:#5f6f83; font-weight:600; }
.ycb-media-type-image-wrap { border:1px solid #e2e8ef; border-radius:4px; overflow:hidden; background:#f8fafc; }
.ycb-media-type-image { display:block; width:100%; height:auto; }
.ycb-media-type-url { margin-top:6px; font-size:11px; line-height:1.25; word-break:break-all; }
</style>';

    $content .= '<div class="ycb-media-type-grid">';

    foreach ($presets as $presetName => $presetConfig) {
        $ratio = (string) ($presetConfig['ratio'] ?? 'original');
        $mode = (string) ($presetConfig['mode'] ?? 'focuspoint');
        $widths = $presetConfig['widths'] ?? [];
        if (!is_array($widths) || $widths === []) {
            $defaultWidth = (int) ($presetConfig['default_width'] ?? 1200);
            $widths = [$defaultWidth > 0 ? $defaultWidth : 1200];
        }

        $widths = array_values(array_unique(array_map(static fn ($value): int => max(1, (int) $value), $widths)));
        sort($widths, SORT_NUMERIC);

        $content .= '<article class="ycb-media-type-card">';
        $content .= '<header class="ycb-media-type-card-header">';
        $content .= '<h4 class="ycb-media-type-card-title">' . rex_escape($presetName) . '</h4>';
        $content .= '<p class="ycb-media-type-card-meta">';
        $content .= rex_i18n::msg('builder_media_types_preview_ratio') . ': <strong>' . rex_escape($ratio) . '</strong> | ';
        $content .= rex_i18n::msg('builder_media_types_preview_mode') . ': <strong>' . rex_escape($mode) . '</strong> | ';
        $content .= rex_i18n::msg('builder_media_types_preview_columns') . ': <strong>' . rex_escape((string) count($widths)) . '</strong>';
        $content .= '</p>';
        $content .= '</header>';

        foreach ($widths as $width) {
            $virtualType = MediaTypeRegistry::buildVirtualType($presetName, $width);
            $variantUrl = rex_media_manager::getUrl($virtualType, $selectedMedia);

            $content .= '<div class="ycb-media-type-item">';
            $content .= '<div class="ycb-media-type-label">';
            $content .= rex_i18n::msg('builder_media_types_preview_width') . ': ' . rex_escape((string) $width) . 'px | ';
            $content .= rex_i18n::msg('builder_media_types_preview_type') . ': <code>' . rex_escape($virtualType) . '</code>';
            $content .= '</div>';
            $content .= '<div class="ycb-media-type-image-wrap">';
            if ($isPdf && !$pdfoutAvailable) {
                $content .= '<div class="text-muted" style="padding:16px;">PDF-Preview erfordert pdfout.</div>';
            } else {
                $content .= '<img class="ycb-media-type-image" src="' . rex_escape($variantUrl) . '" alt="' . rex_escape($presetName . ' ' . $width) . '" loading="lazy">';
            }
            $content .= '</div>';
            $content .= '<div class="ycb-media-type-url"><a href="' . rex_escape($variantUrl) . '" target="_blank" rel="noopener">' . rex_escape($variantUrl) . '</a></div>';
            $content .= '</div>';
        }

        $content .= '</article>';
    }

    $content .= '</div>';
    $content .= '<p style="margin-top:18px;"><a class="btn btn-default" href="' . rex_escape($originalUrl) . '" target="_blank" rel="noopener">' . rex_i18n::msg('builder_media_types_preview_original') . ' öffnen</a></p>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('builder_media_types_preview'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
