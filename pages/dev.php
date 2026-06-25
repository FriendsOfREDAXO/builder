<?php

$addon = rex_addon::get('builder');

$content = '';

$devPath = $addon->getPath('DEV.md');
if (is_readable($devPath)) {
    [$toc, $devContent] = rex_markdown::factory()->parseWithToc(rex_file::require($devPath), 2, 3, [
        rex_markdown::SOFT_LINE_BREAKS => false,
        rex_markdown::HIGHLIGHT_PHP => true,
    ]);
    $fragment = new rex_fragment();
    $fragment->setVar('content', $devContent, false);
    $fragment->setVar('toc', $toc, false);
    $content .= $fragment->parse('core/page/docs.php');
} else {
    $content .= rex_view::info('DEV.md nicht gefunden.');
}

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('dev'), false);
$fragment->setVar('body', '<div class="builder-docs-page">' . $content . '</div>', false);
echo $fragment->parse('core/page/section.php');
