<?php

namespace Alexplusde\Urlaub;

use rex;
use rex_addon;
use rex_response;
use rex_extension;
use rex_extension_point;

/* Beispiel-Code aus Slack */
if (rex_addon::get('yrewrite')->isAvailable() && !rex::isSafeMode()) {
    rex_extension::register('PACKAGES_INCLUDED', function () {
        $urlPath = trim($_SERVER['REQUEST_URI'], '/');
        $segments = explode('/', $urlPath);
        // Prüfen, ob die URL mit "aaabbb" beginnt und eine ID enthält
        if ($segments[0] === 'aaabbb' && isset($segments[1])) {
            $id = explode('?', $segments[1])[0]; // Falls Query-Parameter dran hängen, diese entfernen
            $unterkunftArticleId = 12854;
            $_REQUEST['object_id'] = $id; // Optional für rex_request()
            $structureAddon = rex_addon::get('structure');
            $structureAddon->setProperty('article_id', $unterkunftArticleId);
        } else {
            rex_response::send404();
        }
    });
}


/* Modifizierter Code aus URL-Addon: */
// https://github.com/tbaddade/redaxo_url/blob/560d1ed03e4c0bc37c247cff86735a92bfad6e07/boot.php#L82-L92
rex_extension::register('PACKAGES_INCLUDED', function (\rex_extension_point $epPackagesIncluded) {

    $blocked_article_ids = Urlaub::getAllArticleIds();

    // Artikel löschen deaktivieren, wenn Artikel in der Liste der blockierten Artikel ist
    rex_extension::register('OUTPUT_FILTER', function (\rex_extension_point $ep) use ($blocked_article_ids) {
        $subject = $ep->getSubject();

        foreach ($blocked_article_ids as $id) {
            $regexp = '@<a.*?href="index\.php\?page=structure[^>]*category-id=' . $id . '&[^>]*rex-api-call=category_delete[^>]*>([^&]*)<\/a>@';
            if (preg_match($regexp, $subject, $matches)) {
                $subject = str_replace($matches[0], '<span class="text-muted" title="' . rex_i18n::msg('url_generator_structure_disallow_to_delete_category') . '">' . $matches[1] . '</span>', $subject);
            }
            $regexp = '@<a[^>]*href="index\.php\?page=structure[^>]*article_id=' . $id . '&[^>]*rex-api-call=article_delete[^>]*>([^&]*)<\/a>@';
            if (preg_match($regexp, $subject, $matches)) {
                $subject = str_replace($matches[0], '<span class="text-muted" title="' . rex_i18n::msg('url_generator_structure_disallow_to_delete_article') . '">' . $matches[1] . '</span>', $subject);
            }
        }
        return $subject;
    });

    /*
        // Profilartikel - löschen nicht erlauben
        $rexApiCall = rex_request(rex_api_function::REQ_CALL_PARAM, 'string', '');
        if (($rexApiCall == 'category_delete' && in_array(rex_request('category-id', 'int'), $profileArticleIds)) ||
            ($rexApiCall == 'article_delete' && in_array(rex_request('article_id', 'int'), $profileArticleIds))) {
            $_REQUEST[rex_api_function::REQ_CALL_PARAM] = '';
            rex_extension::register('PAGE_TITLE_SHOWN', function (\rex_extension_point $ep) {
                $subject = $ep->getSubject();
                $ep->setSubject(rex_view::error(rex_i18n::msg('url_generator_rex_api_delete')).$subject);
            });
        } */

    rex_extension::register('URL_REWRITE', function (rex_extension_point $ep) {
        return Urlaub::getRewriteUrl($ep);
    }, rex_extension::EARLY);

    if (null !== Urlaub::getRewriter()) {
        rex_extension::register('YREWRITE_SITEMAP', function (rex_extension_point $ep) {
            $sitemap = (array) $ep->getSubject();
            $sitemap = array_merge($sitemap, Urlaub::getSitemap());
            $ep->setSubject($sitemap);
        }, rex_extension::EARLY);
    }
}, rex_extension::EARLY);
