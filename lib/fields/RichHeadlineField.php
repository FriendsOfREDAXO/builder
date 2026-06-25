<?php

namespace FriendsOfREDAXO\Builder\Fields;

use rex_escape;

/**
 * Kombiniertes Eingabefeld fuer semantische Ueberschriften.
 */
class RichHeadlineField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'rich_headline';
    }

    /**
     * @param array<string, mixed> $fieldConfig
     * @param array<string, mixed> $sliceData
     */
    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = (string) ($fieldConfig['label'] ?? $fieldName);
        $notice = isset($fieldConfig['notice']) ? (string) $fieldConfig['notice'] : null;

        $data = [
            'eyebrow' => '',
            'text' => '',
            'highlight' => '',
            'subline' => '',
            'tag' => 'h2',
        ];

        if (is_array($value)) {
            $data = array_merge($data, array_intersect_key($value, $data));
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $data = array_merge($data, array_intersect_key($decoded, $data));
            }
        }

        $allowedTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        if (!in_array((string) $data['tag'], $allowedTags, true)) {
            $data['tag'] = 'h2';
        }

        $id = 'cb_rich_headline_' . uniqid();
        $baseName = rex_escape($fieldName);

        $this->openFormGroup();
        $this->renderLabel($label);

        echo '<fieldset class="cb-rich-headline" aria-describedby="' . rex_escape($id . '_help') . '">';
        echo '<div class="row" style="margin-left:-5px;margin-right:-5px;">';

        echo '<div class="col-sm-3" style="padding-left:5px;padding-right:5px;">';
        echo '<label for="' . rex_escape($id . '_eyebrow') . '">' . rex_escape($this->t('builder_rich_headline_eyebrow_label', 'Eyebrow')) . '</label>';
        echo '<input id="' . rex_escape($id . '_eyebrow') . '" type="text" class="form-control" name="' . $baseName . '[eyebrow]" value="' . rex_escape((string) $data['eyebrow']) . '" placeholder="' . rex_escape($this->t('builder_rich_headline_eyebrow_placeholder', 'z. B. Kategorie')) . '">';
        echo '</div>';

        echo '<div class="col-sm-5" style="padding-left:5px;padding-right:5px;">';
        echo '<label for="' . rex_escape($id . '_text') . '">' . rex_escape($this->t('builder_rich_headline_text_label', 'Ueberschrift')) . '</label>';
        echo '<input id="' . rex_escape($id . '_text') . '" type="text" class="form-control" name="' . $baseName . '[text]" value="' . rex_escape((string) $data['text']) . '" required>';
        echo '</div>';

        echo '<div class="col-sm-4" style="padding-left:5px;padding-right:5px;">';
        echo '<label for="' . rex_escape($id . '_tag') . '">' . rex_escape($this->t('builder_rich_headline_tag_label', 'Tag')) . '</label>';
        echo '<select id="' . rex_escape($id . '_tag') . '" class="form-control" name="' . $baseName . '[tag]">';
        foreach ($allowedTags as $tag) {
            $selected = $data['tag'] === $tag ? ' selected' : '';
            echo '<option value="' . rex_escape($tag) . '"' . $selected . '>' . strtoupper($tag) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</div>';
        echo '<div class="row" style="margin-top:8px;margin-left:-5px;margin-right:-5px;">';

        echo '<div class="col-sm-6" style="padding-left:5px;padding-right:5px;">';
        echo '<label for="' . rex_escape($id . '_highlight') . '">' . rex_escape($this->t('builder_rich_headline_highlight_label', 'Highlight-Teil (optional)')) . '</label>';
        echo '<input id="' . rex_escape($id . '_highlight') . '" type="text" class="form-control" name="' . $baseName . '[highlight]" value="' . rex_escape((string) $data['highlight']) . '" placeholder="' . rex_escape($this->t('builder_rich_headline_highlight_placeholder', 'Wort in der Ueberschrift markieren')) . '">';
        echo '</div>';

        echo '<div class="col-sm-6" style="padding-left:5px;padding-right:5px;">';
        echo '<label for="' . rex_escape($id . '_subline') . '">' . rex_escape($this->t('builder_rich_headline_subline_label', 'Subline (optional)')) . '</label>';
        echo '<input id="' . rex_escape($id . '_subline') . '" type="text" class="form-control" name="' . $baseName . '[subline]" value="' . rex_escape((string) $data['subline']) . '">';
        echo '</div>';

        echo '</div>';
        echo '<p id="' . rex_escape($id . '_help') . '" class="help-block" style="margin-top:8px;">' . rex_escape($this->t('builder_rich_headline_help', 'H1 nur einmal pro Seite verwenden und die Reihenfolge H1 bis H6 sauber einhalten.')) . '</p>';
        echo '</fieldset>';

        $this->closeFormGroup($notice);
    }

    private function t(string $key, string $fallback): string
    {
        $msg = \rex_i18n::rawMsg($key);

        return $msg !== $key ? $msg : $fallback;
    }
}
