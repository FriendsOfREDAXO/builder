<?php

/**
 * YForm Content Builder - Hauptseite
 */

$addon = rex_addon::get('builder');

echo rex_view::title($addon->i18n('title'));

// Subpages einbinden
rex_be_controller::includeCurrentPageSubPath();
