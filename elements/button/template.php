<?php
$label    = rex_escape($values['label'] ?? '');
$style    = $values['style'] ?? 'btn-primary';
$link     = $values['link'] ?? '';
$external = $values['external_url'] ?? '';

$url = '';
if ($link !== '') {
    if (function_exists('rex_getUrl')) {
        $url = rex_getUrl((int) $link);
    } else {
        $url = 'index.php?article_id=' . (int) $link;
    }
} elseif ($external !== '') {
    $url = rex_escape($external);
}

if ($url !== '' && $label !== '') {
    echo '<div class="builder-button-block" style="margin-bottom: 20px;">';
    echo '<a href="' . $url . '" class="btn ' . rex_escape($style) . '">' . $label . '</a>';
    echo '</div>';
}
