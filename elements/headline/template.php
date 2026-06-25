<?php
$text = rex_escape($values['text'] ?? '');
$tag  = $values['tag'] ?? 'h2';

$allowedTags = ['h1', 'h2', 'h3', 'h4'];
if (!in_array($tag, $allowedTags, true)) {
    $tag = 'h2';
}

if ($text !== '') {
    echo '<' . $tag . ' class="builder-headline">' . $text . '</' . $tag . '>';
}
