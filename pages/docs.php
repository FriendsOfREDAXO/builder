<?php

$addon = rex_addon::get('builder');

$content = '';

// Sprachspezifische README zuerst, dann Fallback auf README.md
$readmePath = null;
$localizedPath = $addon->getPath('README.' . rex_i18n::getLanguage() . '.md');
if (is_readable($localizedPath)) {
    $readmePath = $localizedPath;
} elseif (is_readable($addon->getPath('README.md'))) {
    $readmePath = $addon->getPath('README.md');
}

if ($readmePath !== null) {
    [$readmeToc, $readmeContent] = rex_markdown::factory()->parseWithToc(rex_file::require($readmePath), 2, 3, [
        rex_markdown::SOFT_LINE_BREAKS => false,
        rex_markdown::HIGHLIGHT_PHP => true,
    ]);
    $fragment = new rex_fragment();
    $fragment->setVar('content', $readmeContent, false);
    $fragment->setVar('toc', $readmeToc, false);
    $content .= $fragment->parse('core/page/docs.php');
} else {
    $content .= rex_view::info('Keine README.md gefunden.');
}

$fragment = new rex_fragment();
$fragment->setVar('title', 'Dokumentation', false);
$fragment->setVar('body', '<div class="builder-docs-page">' . $content . '</div>', false);
echo $fragment->parse('core/page/section.php');
