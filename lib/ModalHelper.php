<?php

namespace FriendsOfREDAXO\Builder;

use rex_escape;
use rex_file;
use rex_i18n;
use rex_markdown;

/**
 * Gemeinsame Helper-Funktionen fuer Hilfe-Modal (help*.md) im Content Builder.
 */
class ModalHelper
{
    public static function createModalId(): string
    {
        return 'help_modal_' . uniqid();
    }

    public static function buildConfigForElementDir(string $elementDir, ?string $label = null): ?array
    {
        $helpFile = self::findHelpMarkdownFile($elementDir);
        if ($helpFile === null) {
            return null;
        }

        $markdown = rex_file::get($helpFile);
        if (!is_string($markdown) || trim($markdown) === '') {
            return null;
        }

        return [
            'label' => $label ?? self::getDefaultHelpLabel(),
            'icon' => 'fa-question-circle',
            'content' => rex_markdown::factory()->parse($markdown),
        ];
    }

    public static function renderButton(array $helpModalConfig, bool $toolbarButton = true): void
    {
        $modalId = $helpModalConfig['_modal_id'] ?? self::createModalId();
        $label = $helpModalConfig['label'] ?? self::getDefaultHelpLabel();
        $icon = $helpModalConfig['icon'] ?? 'fa-question-circle';

        if ($toolbarButton) {
            echo '<button type="button" class="btn btn-info" style="margin-left: 8px;" data-toggle="modal" data-target="#' . $modalId . '">';
        } else {
            echo '<div class="form-group">';
            echo '<button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#' . $modalId . '">';
        }

        echo '<i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label);
        echo '</button>';

        if (!$toolbarButton) {
            echo '</div>';
        }
    }

    public static function renderModal(array $helpModalConfig): void
    {
        $modalId = $helpModalConfig['_modal_id'] ?? self::createModalId();
        $label = $helpModalConfig['label'] ?? self::getDefaultHelpLabel();
        $icon = $helpModalConfig['icon'] ?? 'fa-question-circle';

        echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" style="text-align: left;">';
        echo '<div class="modal-dialog modal-lg" role="document">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>';
        echo '<h4 class="modal-title"><i class="fa ' . rex_escape($icon) . '"></i> ' . rex_escape($label) . '</h4>';
        echo '</div>';
        echo '<div class="modal-body rex-docs ycb-help-modal-content">';
        echo (string) ($helpModalConfig['content'] ?? '');
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private static function findHelpMarkdownFile(string $elementDir): ?string
    {
        $helpFilesByName = self::getAvailableHelpMarkdownFiles($elementDir);
        if ($helpFilesByName === []) {
            return null;
        }

        foreach (self::getHelpMarkdownCandidates() as $candidate) {
            if (isset($helpFilesByName[$candidate])) {
                return $helpFilesByName[$candidate];
            }
        }

        return reset($helpFilesByName) ?: null;
    }

    /**
     * @return array<string, string>
     */
    private static function getAvailableHelpMarkdownFiles(string $elementDir): array
    {
        $files = [];

        $entries = @scandir($elementDir);
        if (!is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $filename = (string) $entry;
            if (!preg_match('/^help.*\.md$/i', $filename)) {
                continue;
            }

            $path = rtrim($elementDir, '/') . '/' . $filename;
            if (!is_readable($path)) {
                continue;
            }

            $files[strtolower($filename)] = $path;
        }

        if ($files === []) {
            return [];
        }

        ksort($files);

        return $files;
    }

    /**
     * @return string[]
     */
    private static function getHelpMarkdownCandidates(): array
    {
        $language = strtolower((string) rex_i18n::getLanguage());
        $normalized = str_replace('-', '_', $language);
        $candidates = [];

        if ($normalized !== '') {
            $candidates[] = 'help_' . $normalized . '.md';

            if (strpos($normalized, '_') === false && strlen($normalized) === 2) {
                $candidates[] = 'help_' . $normalized . '_' . $normalized . '.md';
            }

            if (strpos($normalized, '_') !== false) {
                $parts = explode('_', $normalized);
                if (isset($parts[0]) && $parts[0] !== '') {
                    $candidates[] = 'help_' . $parts[0] . '.md';
                }
            }
        }

        $candidates[] = 'help_de_de.md';
        $candidates[] = 'help_de.md';
        $candidates[] = 'help.md';

        return array_values(array_unique($candidates));
    }

    private static function getDefaultHelpLabel(): string
    {
        $language = strtolower((string) rex_i18n::getLanguage());
        if (strpos($language, 'en') === 0) {
            return 'Help';
        }

        return 'Hilfe';
    }
}
