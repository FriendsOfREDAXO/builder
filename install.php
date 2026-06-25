<?php

/**
 * Install-Script für Builder
 */

if (!rex_addon::get('yform')->isInstalled()) {
    throw new rex_functional_exception('Dieses Addon benötigt das "yform" Addon.');
}

if (!rex_addon::get('focuspoint')->isInstalled()) {
    throw new rex_functional_exception('Dieses Addon benötigt das "focuspoint" Addon.');
}

if (rex_addon::get('media_manager')->isAvailable()) {
    rex_media_manager::addEffect(rex_effect_content_builder::class);
    $mm = \FriendsOfREDAXO\Builder\MediaManagerHelper::factory();
    $mm->addType('content_builder', 'Builder: zentraler Medientyp für cb_* Ableitungen');
    $mm->addEffect('content_builder', 'content_builder', [
        'preset' => 'starter_cards_16_9',
        'ratio' => '16_9',
        'mode' => 'focuspoint',
        'width' => 1200,
        'allow_enlarge' => 'not_enlarge',
    ], 1);

    $mm->install();
}

$this->setProperty('install', true);
