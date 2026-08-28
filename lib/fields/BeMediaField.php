<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_addon;
use rex_escape;
use rex_i18n;
use rex_media_manager;
use rex_path;
use rex_url;
use FriendsOfREDAXO\Builder\Helper;

/**
 * REDAXO Backend Media Widget
 */
class BeMediaField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'be_media';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        // Berechtigungsprüfung: Feld nicht rendern wenn Berechtigung fehlt
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = $fieldConfig['label'] ?? $fieldName;
        $notice = $fieldConfig['notice'] ?? null;

        $mediaCounter = self::getNextMediaCounter();
        $inputId = 'REX_MEDIA_' . $mediaCounter;

        // Robuste Behandlung von allowed_types
        $types = '';
        $allowedTypes = [];
        if (isset($fieldConfig['allowed_types'])) {
            if (is_array($fieldConfig['allowed_types'])) {
                $allowedTypes = $fieldConfig['allowed_types'];
            } else {
                $allowedTypes = array_map('trim', explode(',', $fieldConfig['allowed_types']));
            }
            $types = implode(',', $allowedTypes);
        }

        $this->openFormGroup();
        $this->renderLabel($label);

        $wdgtClass = 'rex-js-widget rex-js-widget-media';

        echo '<div class="' . $wdgtClass . '">';
        echo '<div class="input-group">';
        echo '<input class="form-control content-builder-media-input" type="text" ';
        echo 'name="' . rex_escape($fieldName) . '" ';
        echo 'id="' . $inputId . '" ';
        echo 'value="' . rex_escape($value) . '" ';
        echo 'data-media-id="' . $mediaCounter . '" ';
        echo 'readonly />';
        echo '<span class="input-group-btn">';

        echo '<a href="#" class="btn btn-popup" ';
        echo 'data-cb-media-action="open" data-input-id="' . $inputId . '" data-cb-media-counter="' . $mediaCounter . '" data-cb-media-types="' . rex_escape($types) . '" ';
        echo 'title="' . rex_i18n::msg('var_media_open') . '">';
        echo '<i class="rex-icon fa fa-folder-open"></i></a>';

        echo '<a href="#" class="btn btn-popup" ';
        echo 'data-cb-media-action="view" data-input-id="' . $inputId . '" data-cb-media-counter="' . $mediaCounter . '" data-cb-media-types="' . rex_escape($types) . '" ';
        echo 'title="' . rex_i18n::msg('var_media_view') . '">';
        echo '<i class="rex-icon fa fa-eye"></i></a>';

        echo '<a href="#" class="btn btn-popup btn-delete-cb-media" ';
        echo 'data-input-id="' . $inputId . '" ';
        echo 'onclick="return false;" ';
        echo 'title="' . rex_i18n::msg('var_media_remove') . '">';
        echo '<i class="rex-icon fa fa-trash"></i></a>';

        echo '</span></div>';

        // Preview
        echo '<div class="content-builder-media-preview" data-input-id="' . $inputId . '">';
        if ($value) {
            $this->renderPreview($value);
        }
        echo '</div>';
        echo '</div>';

        $this->closeFormGroup($notice);
    }

    /**
     * Rendert die Medien-Vorschau
     */
    protected function renderPreview(string $value): void
    {
        $mediaPath = rex_path::media($value);
        $isImage = Helper::isImage($value);
        $isVideo = Helper::isVideo($value);

        if ($isImage && file_exists($mediaPath)) {
            $mediaUrl = rex_url::media($value);
            if (rex_addon::get('media_manager')->isAvailable()) {
                $mediaUrl = rex_media_manager::getUrl('builder_preview', $value);
            }
            echo '<div class="cb-media-preview-item">';
            echo '<div class="cb-media-container">';
            echo '<img src="' . $mediaUrl . '" alt="' . rex_escape($value) . '" />';
            echo '</div>';
            echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
            echo '</div>';
        } elseif ($isVideo && file_exists($mediaPath)) {
            $mediaUrl = rex_url::media($value);
            echo '<div class="cb-media-preview-item cb-media-video">';
            echo '<div class="cb-media-container">';
            echo '<video controls preload="metadata">';
            echo '<source src="' . $mediaUrl . '" />';
            echo '</video>';
            echo '</div>';
            echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
            echo '</div>';
        } else {
            echo '<div class="cb-media-preview-item cb-media-file">';
            echo '<i class="fa fa-file"></i>';
            echo '<span class="cb-media-filename">' . rex_escape($value) . '</span>';
            echo '</div>';
        }
    }
}
