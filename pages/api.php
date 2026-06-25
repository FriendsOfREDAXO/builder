<?php

$addon = rex_addon::get('builder');

$content = '';

$apiPath = $addon->getPath('API.md');
if (is_readable($apiPath)) {
    [$toc, $apiContent] = rex_markdown::factory()->parseWithToc(rex_file::require($apiPath), 2, 3, [
        rex_markdown::SOFT_LINE_BREAKS => false,
        rex_markdown::HIGHLIGHT_PHP => true,
    ]);
    $fragment = new rex_fragment();
    $fragment->setVar('content', $apiContent, false);
    $fragment->setVar('toc', $toc, false);
    $content .= $fragment->parse('core/page/docs.php');
} else {
    $content .= rex_view::info('API.md nicht gefunden.');
}

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('api'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
