<?php

$addon = rex_addon::get('builder');

$content = '';

$tutorialPath = $addon->getPath('TUTORIAL.md');
if (is_readable($tutorialPath)) {
    [$toc, $tutorialContent] = rex_markdown::factory()->parseWithToc(rex_file::require($tutorialPath), 2, 3, [
        rex_markdown::SOFT_LINE_BREAKS => false,
        rex_markdown::HIGHLIGHT_PHP => true,
    ]);
    $fragment = new rex_fragment();
    $fragment->setVar('content', $tutorialContent, false);
    $fragment->setVar('toc', $toc, false);
    $content .= $fragment->parse('core/page/docs.php');
} else {
    $content .= rex_view::info('TUTORIAL.md nicht gefunden.');
}

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('tutorial'), false);
$fragment->setVar('body', '<div class="builder-docs-page">' . $content . '</div>', false);
echo $fragment->parse('core/page/section.php');
