<?php

/**
 * Builder Dokumentation – mform-ähnliche Docs-Seite
 * Sidebar: Navigation + Suche + TOC | Main: gerendertes Markdown
 */

$addon = rex_addon::get('builder');

$backendLanguage = rex_i18n::getLanguage();
$readmeCandidates = [
    'README.' . $backendLanguage . '.md',
    'README.' . explode('_', $backendLanguage)[0] . '.md',
    'README.md',
];

$readmeFile = 'README.md';
foreach ($readmeCandidates as $candidate) {
    if (is_readable($addon->getPath($candidate))) {
        $readmeFile = $candidate;
        break;
    }
}

$builderDocPages = [
    'overview' => [
        'title' => rex_i18n::msg('builder_docs_section_overview'),
        'icon' => 'rex-icon fa-book',
        'file' => $readmeFile,
    ],
    'tutorial' => [
        'title' => rex_i18n::msg('builder_docs_section_tutorial'),
        'icon' => 'rex-icon fa-graduation-cap',
        'file' => 'TUTORIAL.md',
    ],
    'api' => [
        'title' => rex_i18n::msg('builder_docs_section_api'),
        'icon' => 'rex-icon fa-code-fork',
        'file' => 'API.md',
    ],
    'dev' => [
        'title' => rex_i18n::msg('builder_docs_section_dev'),
        'icon' => 'rex-icon fa-code',
        'file' => 'DEV.md',
    ],
    'schema' => [
        'title' => rex_i18n::msg('builder_docs_section_schema'),
        'icon' => 'rex-icon fa-sitemap',
        'file' => 'SCHEMA.md',
    ],
    'changelog' => [
        'title' => rex_i18n::msg('builder_docs_section_changelog'),
        'icon' => 'rex-icon fa-list',
        'file' => 'CHANGELOG.md',
    ],
];

$builderDocFileMap = [];
foreach ($builderDocPages as $key => $page) {
    $builderDocFileMap[strtolower((string) $page['file'])] = $key;
    $builderDocFileMap[strtolower(basename((string) $page['file']))] = $key;
}

$func = rex_request('func', 'string', 'overview');
$func = preg_replace('/^amp;/', '', $func);
if (!isset($builderDocPages[$func])) {
    $func = 'overview';
}

$q = rex_request('q', 'string', '');

$searchResults = [];
if ($q !== '') {
    foreach ($builderDocPages as $key => $page) {
        $raw = rex_file::get($addon->getPath((string) $page['file']));
        if (!is_string($raw) || $raw === '') {
            continue;
        }

        $pos = stripos($raw, $q);
        if ($pos === false) {
            continue;
        }

        $start = max(0, $pos - 70);
        $length = strlen($q) + 140;
        $snippet = mb_substr($raw, $start, $length);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if (($start + $length) < strlen($raw)) {
            $snippet .= '...';
        }

        $snippet = rex_escape($snippet);
        $snippet = preg_replace('/(' . preg_quote(rex_escape($q), '/') . ')/i', '<mark>$1</mark>', $snippet);

        $heading = '';
        $anchor = '';
        $preContent = substr($raw, 0, $pos);
        if (preg_match_all('/^#{1,6}\s+(.+)$/m', $preContent, $matches)) {
            $lastHeader = end($matches[1]);
            $heading = trim((string) $lastHeader);
            $anchor = rex_string::normalize($heading, '-');
        }

        $searchResults[$key] = $page;
        $searchResults[$key]['snippet'] = $snippet;
        $searchResults[$key]['heading'] = $heading;
        $searchResults[$key]['anchor'] = $anchor;
    }
}

$nav = '<ul class="nav nav-pills nav-stacked">';
foreach ($builderDocPages as $key => $page) {
    $active = ($q === '' && $key === $func) ? ' class="active"' : '';
    $nav .= '<li' . $active . '><a href="' . rex_url::currentBackendPage(['func' => $key]) . '"><i class="' . $page['icon'] . '"></i> ' . rex_escape((string) $page['title']) . '</a></li>';
}
$nav .= '</ul>';

$content = '';
$tocHtml = '';

if ($q !== '') {
    $content = '<h2>' . rex_i18n::msg('builder_docs_search_results_for') . ' <em>' . rex_escape($q) . '</em></h2>';

    if ($searchResults !== []) {
        $content .= '<div class="list-group">';
        foreach ($searchResults as $key => $page) {
            $url = rex_url::currentBackendPage(['func' => $key]);
            $title = rex_escape((string) $page['title']);
            if ((string) ($page['anchor'] ?? '') !== '') {
                $url .= '#' . $page['anchor'];
                $title .= ' <small class="text-muted"><i class="rex-icon fa-angle-right"></i> ' . rex_escape((string) ($page['heading'] ?? '')) . '</small>';
            }

            $content .= '<a href="' . $url . '" class="list-group-item">';
            $content .= '<h4 class="list-group-item-heading"><i class="' . $page['icon'] . '"></i> ' . $title . '</h4>';
            $content .= '<p class="list-group-item-text" style="color:#666;font-size:13px;margin-top:5px">' . $page['snippet'] . '</p>';
            $content .= '</a>';
        }
        $content .= '</div>';
    } else {
        $content .= rex_view::warning(rex_i18n::msg('builder_docs_no_results'));
    }
} else {
    $docFile = $addon->getPath((string) $builderDocPages[$func]['file']);

    if (is_readable($docFile)) {
        $md = rex_file::get($docFile);
        $md = is_string($md) ? $md : '';

        // Markdown-Querlinks zwischen den Builder-Dokumenten auf Backend-Routen auflösen
        $md = preg_replace_callback('/\[([^\]]+)\]\(([^)#]+\.md)(#[^)]+)?\)/i', static function ($matches) use ($builderDocFileMap) {
            $fileName = strtolower(basename(str_replace('\\\\', '/', (string) $matches[2])));
            if (!isset($builderDocFileMap[$fileName])) {
                return $matches[0];
            }

            $url = rex_url::currentBackendPage(['func' => $builderDocFileMap[$fileName]]);
            if (isset($matches[3])) {
                $url .= $matches[3];
            }

            return '[' . $matches[1] . '](' . $url . ')';
        }, $md);

        $parsed = rex_markdown::factory()->parse((string) $md, [
            rex_markdown::SOFT_LINE_BREAKS => false,
            rex_markdown::HIGHLIGHT_PHP => true,
        ]);

        $toc = [];
        $parsed = preg_replace_callback('/<h([1-6])>(.*?)<\/h\1>/s', static function ($matches) use (&$toc) {
            $level = (int) $matches[1];
            $text = strip_tags((string) $matches[2]);
            $id = rex_string::normalize($text, '-');
            $toc[] = ['level' => $level, 'text' => $text, 'id' => $id];

            return '<h' . $level . ' id="' . $id . '">' . $matches[2] . '</h' . $level . '>';
        }, (string) $parsed);

        if ($toc !== []) {
            $tocHtml = '<div class="panel panel-default" style="margin-top:20px">'
                . '<div class="panel-heading"><b>' . rex_i18n::msg('builder_docs_toc') . '</b></div>'
                . '<div class="panel-body" style="padding:10px">'
                . '<input type="text" id="builder-toc-filter" class="form-control input-sm" placeholder="' . rex_i18n::msg('builder_docs_toc_filter') . '...">'
                . '</div>'
                . '<div class="list-group" id="builder-toc-list" style="max-height:500px;overflow-y:auto">';

            foreach ($toc as $item) {
                $padding = (($item['level'] - 1) * 15) + 15;
                $style = 'padding-left:' . $padding . 'px;';
                if ($item['level'] === 1) {
                    $style .= 'font-weight:700;text-transform:uppercase;font-size:11px;letter-spacing:.5px;';
                } elseif ($item['level'] === 2) {
                    $style .= 'font-weight:700;font-size:13px;margin-top:5px;';
                } else {
                    $style .= 'font-size:13px;';
                }

                $tocHtml .= '<a href="#' . $item['id'] . '" class="list-group-item" style="' . $style . '">' . rex_escape((string) $item['text']) . '</a>';
            }

            $tocHtml .= '</div></div>';
        }

        $content = '<a id="builder-doc-top"></a><div class="rex-docs" style="display:block!important">' . $parsed . '</div>';
    } else {
        $content = rex_view::warning('Datei nicht gefunden: ' . rex_escape((string) $builderDocPages[$func]['file']));
    }
}

$searchForm = '<form action="' . rex_url::currentBackendPage() . '" method="get" style="margin-bottom:20px">'
    . '<input type="hidden" name="page" value="' . rex_escape(rex_be_controller::getCurrentPage()) . '">'
    . '<div class="input-group">'
    . '<input type="text" class="form-control" name="q" value="' . rex_escape($q) . '" placeholder="' . rex_i18n::msg('builder_docs_search_placeholder') . '...">'
    . '<span class="input-group-btn"><button class="btn btn-default" type="submit"><i class="rex-icon fa-search"></i></button></span>'
    . '</div></form>';

$sidebarBody = $searchForm . $nav;
if ($tocHtml !== '') {
    $sidebarBody .= $tocHtml;
}

$sidebarFragment = new rex_fragment();
$sidebarFragment->setVar('title', rex_i18n::msg('builder_docs'), false);
$sidebarFragment->setVar('body', $sidebarBody, false);
$sidebar = $sidebarFragment->parse('core/page/section.php');

$mainFragment = new rex_fragment();
$mainFragment->setVar('title', $q !== '' ? rex_i18n::msg('builder_docs_search_results') : (string) $builderDocPages[$func]['title'], false);
$mainFragment->setVar('body', $content, false);
$mainContent = $mainFragment->parse('core/page/section.php');

echo '<div class="row"><div class="col-md-3">' . $sidebar . '</div><div class="col-md-9">' . $mainContent . '</div></div>';

echo '<script>
(function () {
    var filter = document.getElementById("builder-toc-filter");
    if (!filter) {
        return;
    }

    filter.addEventListener("input", function () {
        var value = String(this.value || "").toLowerCase();
        document.querySelectorAll("#builder-toc-list .list-group-item").forEach(function (item) {
            var text = String(item.textContent || "").toLowerCase();
            item.style.display = value === "" || text.indexOf(value) !== -1 ? "" : "none";
        });
    });
})();
</script>';
