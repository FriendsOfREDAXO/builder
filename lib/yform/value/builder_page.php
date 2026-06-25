<?php

/**
 * YForm Value: Page Builder.
 *
 * Provides the visual layout designer as a YForm field.
 *
 * @package builder
 */
class rex_yform_value_builder_page extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ($this->needsOutput() && $this->isViewable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                'value.builder_page.tpl.php',
                [
                    'value' => $this->getValue(),
                ]
            );
        }
    }

    public function getDescription(): string
    {
        return 'builder_page|name|label';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'builder_page',
            'values' => [
                'name'  => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
            ],
            'description' => 'Visueller Page-Builder zur Anordnung strukturierter Layouts und Widgets.',
            'db_type'     => ['mediumtext', 'text'],
            'famous'      => true,
        ];
    }
}
