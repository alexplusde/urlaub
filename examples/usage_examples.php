<?php

/**
 * Beispiel-Implementierung für andere Addons
 *
 * Diese Datei zeigt, wie andere Addons das URL2-System verwenden können,
 * um ihre eigenen URL-Profile zu registrieren.
 */

// 1. Einfache Registrierung über den Extension Point
rex_extension::register('URL_PROFILES', function (rex_extension_point $ep) {
    $profiles = $ep->getSubject();
    
    // Events-Addon registriert ein Profil
    $profiles[] = [
        'key' => 'events',
        'tablename' => 'events',
        'query' => rex_yform_manager_table::get('events')->query()->where('status', 1),
        'seo_title' => 'getValue("title")',
        'seo_description' => 'getValue("description")',
        'seo_image' => 'getValue("image")',
        'addToSitemap' => 'getValue("public")', // Methode, die true/false zurückgibt
        'modifyDate' => 'getValue("updatedate")'
    ];
    
    // News-Addon registriert ein weiteres Profil
    $profiles[] = [
        'key' => 'news',
        'tablename' => 'news',
        'query' => rex_yform_manager_table::get('news')->query()
                    ->where('status', 'online')
                    ->where('publish_date', '<=', date('Y-m-d H:i:s')),
        'seo_title' => 'getTitle()', // Custom-Methode
        'seo_description' => 'getValue("teaser")',
        'seo_image' => 'getImage()', // Custom-Methode
        'addToSitemap' => true, // Alle News in Sitemap
        'modifyDate' => 'getValue("updatedate")'
    ];
    
    return $profiles;
});

// 2. Direkte Registrierung über ProfileManager
use Alexplusde\Urlaub\ProfileManager;

// Produkte-Profil registrieren
ProfileManager::registerUrlProfile([
    'key' => 'products',
    'tablename' => 'products',
    'query' => rex_yform_manager_table::get('products')->query()
                ->where('active', 1)
                ->orderBy('sort'),
    'seo_title' => 'getValue("name")',
    'seo_description' => 'getValue("short_description")',
    'seo_image' => 'getValue("main_image")',
    'addToSitemap' => 'isPublic()', // Custom-Methode des Models
    'modifyDate' => 'getValue("modified")'
]);

// 3. Verwendung des UrlResolvers
use Alexplusde\Urlaub\UrlResolver;

// URL auflösen
$urlData = UrlResolver::resolve('events/mein-event-titel');
if ($urlData) {
    $dataset = UrlResolver::getDataset($urlData);
    // Weitere Verarbeitung...
}

// SEO-Daten für eine URL holen
$seoData = UrlResolver::getSeoData('news/aktuelle-nachricht');
if ($seoData) {
    // Meta-Tags setzen
    $title = $seoData['title'];
    $description = $seoData['description'];
    $image = $seoData['image'];
}

// Sitemap-URLs holen
$sitemapUrls = UrlResolver::getSitemapUrls();
foreach ($sitemapUrls as $urlData) {
    // Sitemap XML generieren
    echo '<url><loc>' . $urlData['url'] . '</loc></url>';
}

// 4. Integration mit YRewrite (bereits automatisch über boot.php aktiv)
// Die folgenden Extension Points werden automatisch registriert:

/*
// URL-Auflösung - wird automatisch registriert
rex_extension::register('URL_REWRITE', function (rex_extension_point $ep) {
    return \Alexplusde\Urlaub\UrlManager::getRewriteUrl($ep);
}, rex_extension::EARLY);

// Sitemap-Integration - wird automatisch registriert  
rex_extension::register('YREWRITE_SITEMAP', function (rex_extension_point $ep) {
    $sitemap = $ep->getSubject();
    if (is_array($sitemap)) {
        $sitemap = array_merge($sitemap, \Alexplusde\Urlaub\Seo::getSitemap());
    } else {
        $sitemap = \Alexplusde\Urlaub\Seo::getSitemap();
    }
    $ep->setSubject($sitemap);
    return $sitemap;
}, rex_extension::EARLY);

// SEO-Tags Integration - wird automatisch registriert
rex_extension::register('YREWRITE_SEO_TAGS', function (rex_extension_point $ep) {
    return \Alexplusde\Urlaub\Seo::setYRewriteTags($ep);
}, rex_extension::EARLY);
*/

// Manuelle Verwendung der URL2-SEO-Funktionen im Template:
use Alexplusde\Urlaub\UrlManager;
use Alexplusde\Urlaub\Seo;

// Prüfen ob aktuelle Seite eine URL2-Seite ist
if (UrlManager::isUrlaubPage()) {
    $dataset = UrlManager::getCurrentDataset();
    $urlData = UrlManager::getCurrentUrlData();
    
    // SEO-Objekt erstellen
    $seo = new Seo($urlData);
    
    // SEO-Daten holen
    echo '<title>' . htmlspecialchars($seo->getTitle()) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($seo->getDescription()) . '">';
    
    // Alle SEO-Tags holen
    $tags = $seo->getTags();
    foreach ($tags as $name => $content) {
        if (strpos($name, 'og:') === 0) {
            echo '<meta property="' . $name . '" content="' . htmlspecialchars($content) . '">';
        } elseif (strpos($name, 'twitter:') === 0) {
            echo '<meta name="' . $name . '" content="' . htmlspecialchars($content) . '">';
        } elseif ($name === 'canonical') {
            echo '<link rel="canonical" href="' . htmlspecialchars($content) . '">';
        } else {
            echo '<meta name="' . $name . '" content="' . htmlspecialchars($content) . '">';
        }
    }
}

// 5. Regenerierung der URLs nach Datensatz-Änderungen
rex_extension::register('YFORM_SAVED', function (rex_extension_point $ep) {
    $table = $ep->getParam('table');
    $dataset = $ep->getParam('dataset');
    
    // Prüfen ob für diese Tabelle ein URL-Profil existiert
    $profiles = \Alexplusde\Urlaub\Profile::getAllRegisteredProfiles();
    foreach ($profiles as $profile) {
        if ($profile->getTableName() === $table->getTableName()) {
            // URLs für dieses Profil regenerieren
            UrlResolver::regenerateUrlsForProfile($profile->getKey());
            break;
        }
    }
});
