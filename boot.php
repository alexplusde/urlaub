<?php

use Alexplusde\Urlaub\ProfileManager;
use Alexplusde\Urlaub\UrlManager;
use Alexplusde\Urlaub\Seo;
use Alexplusde\Urlaub\Generator;
use Alexplusde\Urlaub\ExtensionPointManager;
use rex_extension;
use rex_extension_point;

// Profile Manager initialisieren - lädt Profile aus DB und registriert Extension Point
ProfileManager::init();

// Extension Points nach dem Laden aller Pakete registrieren
rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) {
    
    // URL-Auflösung für YRewrite
    rex_extension::register('URL_REWRITE', function (rex_extension_point $ep) {
        return UrlManager::getRewriteUrl($ep);
    }, rex_extension::EARLY);
    
    // YRewrite-Vorbereitung
    rex_extension::register('YREWRITE_PREPARE', function (rex_extension_point $ep) {
        return Seo::handleYRewritePrepare($ep);
    }, rex_extension::EARLY);
    
    // Sitemap-Integration
    rex_extension::register('YREWRITE_SITEMAP', function (rex_extension_point $ep) {
        $sitemap = $ep->getSubject();
        if (is_array($sitemap)) {
            $sitemap = array_merge($sitemap, Seo::getSitemap());
        } else {
            $sitemap = Seo::getSitemap();
        }
        $ep->setSubject($sitemap);
        return $sitemap;
    }, rex_extension::EARLY);
    
    // SEO-Tags Integration
    rex_extension::register('YREWRITE_SEO_TAGS', function (rex_extension_point $ep) {
        return Seo::setYRewriteTags($ep);
    }, rex_extension::EARLY);
    
    // Backend: Extension Points für automatische URL-Generierung
    if (rex::isBackend() && rex::getUser() !== null) {
        $extensionPoints = [
            'ART_ADDED', 'ART_DELETED', 'ART_MOVED', 'ART_STATUS', 'ART_UPDATED',
            'CAT_ADDED', 'CAT_DELETED', 'CAT_MOVED', 'CAT_STATUS', 'CAT_UPDATED',
            'CLANG_ADDED', 'CLANG_DELETED', 'CLANG_UPDATED',
            'CACHE_DELETED',
            'REX_FORM_SAVED',
            'REX_YFORM_SAVED',
            'YFORM_DATA_ADDED', 'YFORM_DATA_DELETED', 'YFORM_DATA_UPDATED',
        ];

        foreach ($extensionPoints as $extensionPoint) {
            rex_extension::register($extensionPoint, function (rex_extension_point $ep) {
                $manager = new ExtensionPointManager($ep);
                
                if ($manager->shouldDeleteUrls()) {
                    // URLs löschen
                    if ($ep->getName() === 'YFORM_DATA_DELETED') {
                        UrlManager::handleYFormDelete($ep);
                    }
                } elseif ($manager->shouldRegenerateUrls()) {
                    // URLs regenerieren
                    if (in_array($ep->getName(), ['YFORM_DATA_ADDED', 'YFORM_DATA_UPDATED', 'REX_YFORM_SAVED'])) {
                        UrlManager::handleYFormChange($ep);
                    } else {
                        UrlManager::handleStructureChange($ep);
                    }
                }
            }, rex_extension::LATE);
        }
    }
    
}, rex_extension::EARLY);

// Beispiel für andere Addons: So können sie ihre Profile registrieren
/*
rex_extension::register('URL_PROFILES', function(rex_extension_point $ep) {
    $profiles = $ep->getSubject();

    // Beispiel: Events-Addon registriert ein Profil
    $profiles[] = [
        'key' => 'events',
        'tablename' => 'events',
        'query' => rex_yform_manager_table::get('events')->query()->where('status', 1),
        'seo_title' => 'getValue("title")',
        'seo_description' => 'getValue("description")',
        'seo_image' => 'getValue("image")',
        'addToSitemap' => true,
        'modifyDate' => 'getValue("updatedate")'
    ];

    return $profiles;
});
*/
