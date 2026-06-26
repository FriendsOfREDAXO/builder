<?php
/**
 * YForm-Liste – UIkit Template
 *
 * @var array<string,mixed> $elementData
 */

if (!class_exists(\FriendsOfREDAXO\Builder\ListRenderer::class)) {
    return;
}

$result = \FriendsOfREDAXO\Builder\ListRenderer::fetch($elementData);

$headline = (string) ($elementData['headline'] ?? '');
$description = (string) ($elementData['description'] ?? '');
$showLinks = !isset($elementData['show_links']) || !empty($elementData['show_links']);

$layout = $result['layout'];
$items = $result['items'];
$error = $result['error'];

// Section-/Container-Wrapping (analog zu anderen Elementen)
$sectionBg = $elementData['section_bg'] ?? '';
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = $elementData['section_padding'] ?? '';
$containerWidth = $elementData['container_width'] ?? 'uk-container';
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_bg_image', $sectionBgImage, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
$wrapperClose->setVar('container_width', $containerWidth, false);

$columns = (string) ($elementData['columns'] ?? '3');
$columnsTablet = (string) ($elementData['columns_tablet'] ?? '2');
$columnsMobile = (string) ($elementData['columns_mobile'] ?? '1');
$gap = (string) ($elementData['gap'] ?? 'medium');

echo $wrapper->parse('ycb_elements/wrapper.php');

if ('' !== $headline) {
    echo '<h2 class="uk-heading-line uk-margin-medium-bottom"><span>' . rex_escape($headline) . '</span></h2>';
}
if ('' !== $description) {
    echo '<div class="uk-margin-medium-bottom uk-text-lead">' . nl2br(rex_escape($description)) . '</div>';
}

if (null !== $error) {
    echo '<div class="uk-alert uk-alert-warning" uk-alert><p>' . rex_escape($error) . '</p></div>';
} elseif ([] === $items) {
    echo '<div class="uk-alert uk-alert-default" uk-alert><p>Keine Einträge.</p></div>';
} else {
    if ('cards' === $layout) {
        $colClass = 'uk-child-width-1-' . rex_escape($columnsMobile)
            . ' uk-child-width-1-' . rex_escape($columnsTablet) . '@s'
            . ' uk-child-width-1-' . rex_escape($columns) . '@m';

        echo '<div class="' . $colClass . '" uk-grid uk-height-match="target: > div > .uk-card">';
        foreach ($items as $it) {
            $title = rex_escape((string) $it['title']);
            $teaser = rex_escape((string) $it['teaser']);
            $href = $showLinks ? (string) $it['href'] : '';
            $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'uk-card-media-top');
            $product = (array) ($it['product'] ?? []);
            $currency = (string) ($product['currency'] ?? 'EUR');
            $price = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
            $oldPrice = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
            $badge = trim((string) ($product['badge'] ?? ''));
            $availability = trim((string) ($product['availability'] ?? ''));
            $titleHtml = '' !== $href
                ? '<a href="' . rex_escape($href) . '" class="uk-link-reset">' . $title . '</a>'
                : $title;
            echo '<div>'
                . '<div class="uk-card uk-card-default">'
                . ('' !== $badge ? '<div class="uk-position-top-right uk-padding-small"><span class="uk-label">' . rex_escape($badge) . '</span></div>' : '')
                . $img
                . '<div class="uk-card-body">'
                . '<h3 class="uk-card-title uk-margin-remove-bottom">' . $titleHtml . '</h3>'
                . ('' !== $teaser ? '<p class="uk-margin-small-top">' . $teaser . '</p>' : '')
                . ('' !== $price ? '<p class="uk-margin-small-top uk-margin-remove-bottom"><strong>' . rex_escape($price) . '</strong>' . ('' !== $oldPrice ? ' <span class="uk-text-meta" style="text-decoration:line-through;">' . rex_escape($oldPrice) . '</span>' : '') . '</p>' : '')
                . ('' !== $availability ? '<p class="uk-text-meta uk-margin-remove-top">' . rex_escape($availability) . '</p>' : '')
                . '</div>'
                . '</div>'
                . '</div>';
        }
        echo '</div>';
    } elseif ('list' === $layout) {
        $isContactList = false;
        foreach ($items as $it) {
            $c = (array) ($it['contact'] ?? []);
            if ('' !== trim((string) ($c['firstname'] ?? ''))
                || '' !== trim((string) ($c['phone'] ?? ''))
                || '' !== trim((string) ($c['mobile'] ?? ''))
                || '' !== trim((string) ($c['email'] ?? ''))
                || '' !== trim((string) ($c['role'] ?? ''))
            ) {
                $isContactList = true;
                break;
            }
        }
        if ($isContactList) {
            $tel = static fn(string $v): string => preg_replace('/[^+\d]/', '', $v) ?? '';
            echo '<div class="uk-overflow-auto">';
            echo '<table class="uk-table uk-table-middle uk-table-divider uk-table-responsive rex-yfl-contact-table">';
            echo '<thead><tr>'
                . '<th class="uk-table-shrink"></th>'
                . '<th>Name</th>'
                . '<th>Funktion</th>'
                . '<th>Telefon</th>'
                . '<th>E-Mail</th>'
                . '<th>Mobil</th>'
                . '</tr></thead><tbody>';
            foreach ($items as $it) {
                $contact = (array) ($it['contact'] ?? []);
                $first = trim((string) ($contact['firstname'] ?? ''));
                $last = trim((string) ($contact['lastname'] ?? $it['title'] ?? ''));
                $role = trim((string) ($contact['role'] ?? ''));
                $phone = trim((string) ($contact['phone'] ?? ''));
                $mobile = trim((string) ($contact['mobile'] ?? ''));
                $email = trim((string) ($contact['email'] ?? ''));
                $name = trim($first . ' ' . $last);
                if ('' === $name) {
                    $name = (string) $it['title'];
                }
                $href = $showLinks ? (string) $it['href'] : '';
                $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'uk-border-circle uk-preserve-width');
                $nameHtml = '' !== $href
                    ? '<a href="' . rex_escape($href) . '" class="uk-link-reset">' . rex_escape($name) . '</a>'
                    : rex_escape($name);
                echo '<tr>'
                    . '<td class="uk-table-shrink">' . $img . '</td>'
                    . '<td><strong>' . $nameHtml . '</strong></td>'
                    . '<td>' . ('' !== $role ? rex_escape($role) : '&mdash;') . '</td>'
                    . '<td>' . ('' !== $phone ? '<a href="tel:' . rex_escape($tel($phone)) . '">' . rex_escape($phone) . '</a>' : '&mdash;') . '</td>'
                    . '<td>' . ('' !== $email ? '<a href="mailto:' . rex_escape($email) . '">' . rex_escape($email) . '</a>' : '&mdash;') . '</td>'
                    . '<td>' . ('' !== $mobile ? '<a href="tel:' . rex_escape($tel($mobile)) . '">' . rex_escape($mobile) . '</a>' : '&mdash;') . '</td>'
                    . '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<ul class="uk-list uk-list-divider rex-yfl-list">';
            foreach ($items as $it) {
                $title = rex_escape((string) $it['title']);
                $teaser = rex_escape((string) $it['teaser']);
                $href = $showLinks ? (string) $it['href'] : '';
                $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'rex-yfl-thumb', 80);
                $product = (array) ($it['product'] ?? []);
                $currency = (string) ($product['currency'] ?? 'EUR');
                $price = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
                $oldPrice = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
                $availability = trim((string) ($product['availability'] ?? ''));
                $titleHtml = '' !== $href
                    ? '<a href="' . rex_escape($href) . '">' . $title . '</a>'
                    : $title;
                echo '<li>'
                    . '<div class="uk-flex uk-flex-middle" style="gap:1em;">'
                    . ('' !== $img ? '<div style="flex:0 0 auto;width:80px;">' . $img . '</div>' : '')
                    . '<div class="uk-flex-1">'
                    . '<h4 class="uk-margin-remove">' . $titleHtml . '</h4>'
                    . ('' !== $teaser ? '<p class="uk-margin-remove uk-text-meta">' . $teaser . '</p>' : '')
                        . ('' !== $price ? '<p class="uk-margin-remove"><strong>' . rex_escape($price) . '</strong>' . ('' !== $oldPrice ? ' <span class="uk-text-meta" style="text-decoration:line-through;">' . rex_escape($oldPrice) . '</span>' : '') . '</p>' : '')
                        . ('' !== $availability ? '<p class="uk-margin-remove uk-text-meta">' . rex_escape($availability) . '</p>' : '')
                    . '</div>'
                    . '</div>'
                    . '</li>';
            }
            echo '</ul>';
        }
    } elseif ('slides' === $layout) {
        echo '<div uk-slider="finite: true; autoplay: true; autoplay-interval: 5500">';
        echo '<div class="uk-position-relative">';
        echo '<div class="uk-slider-container">';
        echo '<ul class="uk-slider-items uk-child-width-1-1">';
        foreach ($items as $it) {
            $title = rex_escape((string) $it['title']);
            $teaser = rex_escape((string) $it['teaser']);
            $href = $showLinks ? (string) $it['href'] : '';
            $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'uk-width-1-1', 1200);
            $product = (array) ($it['product'] ?? []);
            $currency = (string) ($product['currency'] ?? 'EUR');
            $price = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['price'] ?? ''), $currency);
            $oldPrice = \FriendsOfREDAXO\Builder\ListRenderer::formatPrice((string) ($product['old_price'] ?? ''), $currency);
            $badge = trim((string) ($product['badge'] ?? ''));
            $availability = trim((string) ($product['availability'] ?? ''));
            $titleHtml = '' !== $href
                ? '<a href="' . rex_escape($href) . '" class="uk-link-reset">' . $title . '</a>'
                : $title;

            echo '<li>';
            echo '<article class="uk-card uk-card-default">';
            if ('' !== $img) {
                echo '<div class="uk-card-media-top">' . $img . '</div>';
            }
            echo '<div class="uk-card-body">';
            if ('' !== $badge) {
                echo '<div class="uk-margin-small-bottom"><span class="uk-label">' . rex_escape($badge) . '</span></div>';
            }
            echo '<h3 class="uk-card-title">' . $titleHtml . '</h3>';
            if ('' !== $teaser) {
                echo '<p class="uk-text-meta uk-margin-remove-bottom">' . $teaser . '</p>';
            }
            if ('' !== $price) {
                echo '<p class="uk-margin-small-top uk-margin-remove-bottom"><strong>' . rex_escape($price) . '</strong>';
                if ('' !== $oldPrice) {
                    echo ' <span class="uk-text-meta" style="text-decoration:line-through;">' . rex_escape($oldPrice) . '</span>';
                }
                echo '</p>';
            }
            if ('' !== $availability) {
                echo '<p class="uk-text-meta uk-margin-remove-top">' . rex_escape($availability) . '</p>';
            }
            echo '</div>';
            echo '</article>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '<a class="uk-position-center-left uk-position-small uk-hidden-hover" href="#" uk-slidenav-previous uk-slider-item="previous"></a>';
        echo '<a class="uk-position-center-right uk-position-small uk-hidden-hover" href="#" uk-slidenav-next uk-slider-item="next"></a>';
        echo '</div>';
        echo '<ul class="uk-slider-nav uk-dotnav uk-flex-center uk-margin"></ul>';
        echo '</div>';
    } elseif ('contact' === $layout) {
        $colClass = 'uk-child-width-1-' . rex_escape($columnsMobile)
            . ' uk-child-width-1-' . rex_escape($columnsTablet) . '@s'
            . ' uk-child-width-1-' . rex_escape($columns) . '@m';
        echo '<div class="' . $colClass . '" uk-grid uk-height-match="target: > div > .rex-yfl-contact">';
        foreach ($items as $it) {
            $contact = (array) ($it['contact'] ?? []);
            $first = trim((string) ($contact['firstname'] ?? ''));
            $last = trim((string) ($contact['lastname'] ?? $it['title'] ?? ''));
            $freitext = trim((string) ($contact['freitext'] ?? ''));
            $role = trim((string) ($contact['role'] ?? ''));
            $phone = trim((string) ($contact['phone'] ?? ''));
            $mobile = trim((string) ($contact['mobile'] ?? ''));
            $email = trim((string) ($contact['email'] ?? ''));
            $name = trim($first . ' ' . $last);
            $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'rex-yfl-contact-avatar uk-border-circle');
            $tel = static fn(string $v): string => preg_replace('/[^+\d]/', '', $v) ?? '';

            echo '<div>';
            echo '<div class="uk-card uk-card-default uk-card-body uk-text-center rex-yfl-contact">';
            if ('' !== $img) {
                echo '<div class="uk-margin-small-bottom" style="display:inline-block;">' . $img . '</div>';
            }
            echo '<h3 class="uk-card-title uk-margin-remove-bottom">' . rex_escape($name) . '</h3>';
            if ('' !== $freitext) {
                echo '<div class="uk-text-meta">' . rex_escape($freitext) . '</div>';
            }
            if ('' !== $role) {
                echo '<div class="uk-margin-small-top"><strong>' . rex_escape($role) . '</strong></div>';
            }
            $meta = [];
            if ('' !== $phone) {
                $meta[] = '<li><span uk-icon="receiver"></span> <a href="tel:' . rex_escape($tel($phone)) . '">' . rex_escape($phone) . '</a></li>';
            }
            if ('' !== $mobile) {
                $meta[] = '<li><span uk-icon="tablet"></span> <a href="tel:' . rex_escape($tel($mobile)) . '">' . rex_escape($mobile) . '</a></li>';
            }
            if ('' !== $email) {
                $meta[] = '<li><span uk-icon="mail"></span> <a href="mailto:' . rex_escape($email) . '">' . rex_escape($email) . '</a></li>';
            }
            if ([] !== $meta) {
                echo '<ul class="uk-list uk-margin-small-top rex-yfl-contact-meta">' . implode('', $meta) . '</ul>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    } elseif ('contact_compact' === $layout) {
        $colClass = 'uk-child-width-1-' . rex_escape($columnsMobile)
            . ' uk-child-width-1-' . rex_escape($columnsTablet) . '@s'
            . ' uk-child-width-1-' . rex_escape($columns) . '@m';
        echo '<div class="' . $colClass . '" uk-grid uk-height-match="target: > div > .uk-card">';
        foreach ($items as $it) {
            $contact = (array) ($it['contact'] ?? []);
            $first = trim((string) ($contact['firstname'] ?? ''));
            $last = trim((string) ($contact['lastname'] ?? $it['title'] ?? ''));
            $freitext = trim((string) ($contact['freitext'] ?? ''));
            $role = trim((string) ($contact['role'] ?? ''));
            $phone = trim((string) ($contact['phone'] ?? ''));
            $mobile = trim((string) ($contact['mobile'] ?? ''));
            $email = trim((string) ($contact['email'] ?? ''));
            $name = trim($first . ' ' . $last);
            $img = \FriendsOfREDAXO\Builder\ListRenderer::imgTag($it, 'uk-border-circle uk-preserve-width');
            $tel = static fn(string $v): string => preg_replace('/[^+\d]/', '', $v) ?? '';
            $hrefMain = $showLinks ? (string) $it['href'] : '';
            $nameHtml = '' !== $hrefMain
                ? '<a href="' . rex_escape($hrefMain) . '" class="uk-link-reset">' . rex_escape($name) . '</a>'
                : rex_escape($name);

            echo '<div>';
            echo '<div class="uk-card uk-card-default">';
            echo '<div class="uk-card-header">';
            echo '<div class="uk-grid-small uk-flex-middle" uk-grid>';
            if ('' !== $img) {
                echo '<div class="uk-width-auto">' . $img . '</div>';
            }
            echo '<div class="uk-width-expand">';
            echo '<h3 class="uk-card-title uk-margin-remove-bottom">' . $nameHtml . '</h3>';
            if ('' !== $role) {
                echo '<p class="uk-text-meta uk-margin-remove-top">' . rex_escape($role) . '</p>';
            }
            if ('' !== $freitext) {
                echo '<p class="uk-text-meta uk-margin-remove">' . rex_escape($freitext) . '</p>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
            $hasMeta = '' !== $phone || '' !== $mobile || '' !== $email;
            if ($hasMeta) {
                echo '<div class="uk-card-body">';
                echo '<ul class="uk-list uk-margin-remove rex-yfl-contact-meta">';
                if ('' !== $phone) {
                    echo '<li><span uk-icon="receiver"></span> <a href="tel:' . rex_escape($tel($phone)) . '">' . rex_escape($phone) . '</a></li>';
                }
                if ('' !== $mobile) {
                    echo '<li><span uk-icon="tablet"></span> <a href="tel:' . rex_escape($tel($mobile)) . '">' . rex_escape($mobile) . '</a></li>';
                }
                if ('' !== $email) {
                    echo '<li><span uk-icon="mail"></span> <a href="mailto:' . rex_escape($email) . '">' . rex_escape($email) . '</a></li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    } else {
        echo '<ul class="uk-list rex-yfl-compact">';
        foreach ($items as $it) {
            $title = rex_escape((string) $it['title']);
            $href = $showLinks ? (string) $it['href'] : '';
            $titleHtml = '' !== $href
                ? '<a href="' . rex_escape($href) . '">' . $title . '</a>'
                : $title;
            echo '<li>' . $titleHtml . '</li>';
        }
        echo '</ul>';
    }
}

echo $wrapperClose->parse('ycb_elements/wrapper.php');
