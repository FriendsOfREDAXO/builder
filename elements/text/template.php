<?php
$content = $values['content'] ?? '';
if ($content !== '') {
    echo '<div class="builder-text-block">' . $content . '</div>';
}
