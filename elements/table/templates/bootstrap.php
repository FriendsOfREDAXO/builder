<?php

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$tableData = $elementData['table_data'] ?? '';
$tableCaption = '';
$tableHover = !empty($elementData['table_hover']);
$tableResponsive = (string) ($elementData['table_responsive'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$tableRows = [];
$tableHeadRows = [];
$tableCols = [];
$hasHeaderCol = false;

$data = json_decode((string) $tableData, true);
if (is_array($data) && isset($data['rows']) && is_array($data['rows'])) {
    $rows = $data['rows'];
    $hasHeaderRow = (bool) ($data['has_header_row'] ?? true);
    $hasHeaderCol = (bool) ($data['has_header_col'] ?? false);
    $tableCols = is_array($data['cols'] ?? null) ? $data['cols'] : [];
    if (is_string($data['caption'] ?? null)) {
        $tableCaption = $data['caption'];
    }

    $normalizedRows = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $normalizedRows[] = array_values($row);
        }
    }

    if ($hasHeaderRow && $normalizedRows !== []) {
        $tableHeadRows[] = array_shift($normalizedRows);
    }
    $tableRows = $normalizedRows;
}

if (empty($tableHeadRows) && empty($tableRows)) {
    return;
}

$alignStyle = static function (string $type): string {
    if ($type === 'number') {
        return ' style="text-align:right;"';
    }
    if ($type === 'center') {
        return ' style="text-align:center;"';
    }
    return '';
};

$sectionClass = trim(StarterConfig::mapBg($sectionBg, 'bootstrap') . ' ' . StarterConfig::mapPadding($sectionPadding, 'bootstrap'));
if ($sectionLight) {
    $sectionClass = trim($sectionClass . ' text-white');
}
$containerClass = trim(StarterConfig::mapContainer($containerWidth, 'bootstrap'));

$tableClass = 'table';
if ($tableHover) {
    $tableClass .= ' table-hover';
}
?>
<?php if ($enableSection): ?>
<section<?= $sectionClass !== '' ? ' class="' . rex_escape($sectionClass) . '"' : '' ?>>
<?php endif; ?>
<?php if ($enableContainer): ?>
<div<?= $containerClass !== '' ? ' class="' . rex_escape($containerClass) . '"' : '' ?>>
<?php endif; ?>

<?php if ($tableResponsive === ''): ?>
<div class="table-responsive">
<?php endif; ?>
<table class="<?= rex_escape($tableClass) ?>">
    <?php if ($tableCaption !== ''): ?>
    <caption><?= rex_escape($tableCaption) ?></caption>
    <?php endif; ?>
    <?php if ($tableHeadRows !== []): ?>
    <thead>
        <?php foreach ($tableHeadRows as $row): ?>
        <tr>
            <?php foreach ($row as $cellIndex => $cell): ?>
            <?php
            $colDef = is_array($tableCols[$cellIndex] ?? null) ? $tableCols[$cellIndex] : [];
            $headerType = (string) ($colDef['header_type'] ?? ($colDef['type'] ?? 'text'));
            ?>
            <th scope="col"<?= $alignStyle($headerType) ?>><?= rex_escape((string) $cell) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </thead>
    <?php endif; ?>
    <?php if ($tableRows !== []): ?>
    <tbody>
        <?php foreach ($tableRows as $row): ?>
        <tr>
            <?php foreach ($row as $cellIndex => $cell): ?>
            <?php
            $colDef = is_array($tableCols[$cellIndex] ?? null) ? $tableCols[$cellIndex] : [];
            $bodyType = (string) ($colDef['type'] ?? 'text');
            ?>
            <?php if ($hasHeaderCol && $cellIndex === 0): ?>
            <th scope="row"<?= $alignStyle($bodyType) ?>><?= rex_escape((string) $cell) ?></th>
            <?php else: ?>
            <td<?= $alignStyle($bodyType) ?>><?= rex_escape((string) $cell) ?></td>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
</table>
<?php if ($tableResponsive === ''): ?>
</div>
<?php endif; ?>

<?php if ($enableContainer): ?>
</div>
<?php endif; ?>
<?php if ($enableSection): ?>
</section>
<?php endif; ?>
