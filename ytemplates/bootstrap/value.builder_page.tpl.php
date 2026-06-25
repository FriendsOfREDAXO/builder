<?php

/**
 * YForm Template: value.builder_page.tpl.php
 *
 * @var rex_yform_value_builder_page $this
 * @psalm-scope-this rex_yform_value_builder_page
 */

use FriendsOfRedaxo\Builder\PageBuilder;

$fieldName = $this->getFieldName();
$fieldId   = $this->getFieldId();
$label     = $this->getLabel();
$value     = $this->getValue();

$classGroup = ['form-group', 'yform-element', 'builder-page-element'];
if ($this->getWarningClass() !== '') {
    $classGroup[] = $this->getWarningClass();
}
?>

<div class="<?= implode(' ', $classGroup) ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label"><?= rex_escape($label) ?></label>
    <?= PageBuilder::renderInput($fieldName, $value, $fieldId) ?>
</div>
