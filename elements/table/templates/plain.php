<?php

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$tableData = $elementData['table_data'] ?? '';
$tableCaption = '';

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
        return 'text-align:right;';
    }
    if ($type === 'center') {
        return 'text-align:center;';
    }
    return '';
};

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain') . StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
?>
<?php if ($enableSection): ?>
<section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>>
<?php endif; ?>
<?php if ($enableContainer): ?>
<div style="<?= rex_escape($containerStyle) ?>">
<?php endif; ?>

<div style="overflow:auto;">
<table style="width:100%;border-collapse:collapse;">
    <?php if ($tableCaption !== ''): ?>
    <caption style="caption-side:top;text-align:left;padding:.5rem 0;font-weight:600;"><?= rex_escape($tableCaption) ?></caption>
    <?php endif; ?>
    <?php if ($tableHeadRows !== []): ?>
    <thead>
        <?php foreach ($tableHeadRows as $row): ?>
        <tr>
            <?php foreach ($row as $cellIndex => $cell): ?>
            <?php
            $colDef = is_array($tableCols[$cellIndex] ?? null) ? $tableCols[$cellIndex] : [];
            $headerType = (string) ($colDef['header_type'] ?? ($colDef['type'] ?? 'text'));
            $style = $alignStyle($headerType);
            ?>
            <th scope="col" style="border:1px solid #d8d8d8;padding:.55rem;<?= rex_escape($style) ?>"><?= rex_escape((string) $cell) ?></th>
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
            $style = $alignStyle($bodyType);
            ?>
            <?php if ($hasHeaderCol && $cellIndex === 0): ?>
            <th scope="row" style="border:1px solid #d8d8d8;padding:.55rem;<?= rex_escape($style) ?>"><?= rex_escape((string) $cell) ?></th>
            <?php else: ?>
            <td style="border:1px solid #d8d8d8;padding:.55rem;<?= rex_escape($style) ?>"><?= rex_escape((string) $cell) ?></td>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
</table>
</div>

<?php if ($enableContainer): ?>
</div>
<?php endif; ?>
<?php if ($enableSection): ?>
</section>
<?php endif; ?>
