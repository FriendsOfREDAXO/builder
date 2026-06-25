<?php
$media = $values['media'] ?? '';
$alt   = rex_escape($values['alt'] ?? '');
$align = $values['align'] ?? 'center';

$alignClass = 'text-center';
if ($align === 'left') {
    $alignClass = 'text-left';
} elseif ($align === 'right') {
    $alignClass = 'text-right';
}

if ($media !== '') {
    echo '<div class="builder-image-block ' . $alignClass . '" style="margin-bottom: 20px;">';
    echo '<img src="' . rex_url::media($media) . '" alt="' . $alt . '" class="img-responsive" style="max-width:100%; height:auto; display:inline-block; border-radius: 4px;">';
    echo '</div>';
}
