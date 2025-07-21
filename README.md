# URL2 - SEO-freundliche URLs für REDAXO

Das URL2-Addon ermöglicht es, SEO-freundliche URLs für Datensätze aus YForm-Tabellen zu generieren. Das neue System basiert auf URL-Profilen, die programmatisch von anderen Addons registriert werden können.

## Konzept

### URL-Profile

Ein URL-Profil definiert, wie URLs für eine bestimmte Datenquelle generiert werden sollen:

- **key**: Eindeutiger Schlüssel/Namespace für das Profil
- **tablename**: Name der YForm-Tabelle (ohne rex_-Präfix)
- **query**: YForm-Query-Objekt zur Einschränkung der Datensätze
- **seo_title**: Methode zur Ermittlung des Titels
- **seo_description**: Methode zur Ermittlung der Beschreibung  
- **seo_image**: Methode zur Ermittlung des Bildes
- **addToSitemap**: Boolean oder Methode zur Sitemap-Entscheidung
- **modifyDate**: Methode zur Ermittlung des Änderungsdatums

### Kernklassen

#### Profile

Repräsentiert ein URL-Profil mit allen Konfigurationen und Methoden.

#### ProfileManager

Verwaltet alle registrierten Profile und stellt den Extension Point zur Verfügung.

#### Generator

Generiert URLs basierend auf den Profilen und speichert sie in der Datenbank.

#### UrlResolver

Löst URLs auf und stellt Methoden für YRewrite-Integration zur Verfügung.

## Verwendung

### 1. Profile registrieren

#### Über Extension Point (empfohlen)

```php
rex_extension::register('URL_PROFILES', function(rex_extension_point $ep) {
    $profiles = $ep->getSubject();
    
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
```

#### Direkt über ProfileManager

```php
use Alexplusde\Urlaub\ProfileManager;

ProfileManager::registerUrlProfile([
    'key' => 'products',
    'tablename' => 'products',
    'query' => rex_yform_manager_table::get('products')->query()->where('active', 1),
    'seo_title' => 'getValue("name")',
    'seo_description' => 'getValue("short_description")',
    'seo_image' => 'getValue("main_image")',
    'addToSitemap' => 'isPublic()',
    'modifyDate' => 'getValue("modified")'
]);
```

### 2. URLs generieren

```php
use Alexplusde\Urlaub\ProfileManager;

// Alle URLs regenerieren
ProfileManager::generateAllUrls();

// URLs für spezifisches Profil regenerieren
use Alexplusde\Urlaub\UrlResolver;
UrlResolver::regenerateUrlsForProfile('events');
```

### 3. URLs auflösen

```php
use Alexplusde\Urlaub\UrlResolver;

// URL auflösen
$urlData = UrlResolver::resolve('events/mein-event-titel');
if ($urlData) {
    $dataset = UrlResolver::getDataset($urlData);
    // Datensatz verarbeiten...
}

// SEO-Daten holen
$seoData = UrlResolver::getSeoData('events/mein-event-titel');
if ($seoData) {
    $title = $seoData['title'];
    $description = $seoData['description'];
    $image = $seoData['image'];
}
```

### 4. Sitemap-Integration

```php
use Alexplusde\Urlaub\UrlResolver;

$sitemapUrls = UrlResolver::getSitemapUrls();
foreach ($sitemapUrls as $urlData) {
    echo '<url>';
    echo '<loc>' . htmlspecialchars($urlData['url']) . '</loc>';
    echo '<lastmod>' . date('c', strtotime($urlData['modifyDate'])) . '</lastmod>';
    echo '</url>';
}
```

### 5. YRewrite-Integration

```php
// URLs automatisch auflösen
rex_extension::register('YREWRITE_RESOLVE', function(rex_extension_point $ep) {
    $url = $ep->getParam('url');
    
    $urlData = UrlResolver::resolve($url);
    if ($urlData) {
        $ep->setParam('article_id', $urlData['article_id']);
        $ep->setParam('clang_id', $urlData['clang_id']);
    }
    
    return $ep->getSubject();
});
```

## Methodenformate

### SEO-Methoden

Für `seo_title`, `seo_description` und `seo_image` können folgende Formate verwendet werden:

- **Einfache Getter**: `getValue("fieldname")`
- **Custom-Methoden**: `getTitle()`, `getImage()`
- **Methoden mit Parametern**: `getValue("title")`, `getResizedImage("medium")`

### Sitemap-Entscheidung

Für `addToSitemap` können verwendet werden:

- **Boolean**: `true` oder `false`
- **Feldwert**: `getValue("public")`
- **Methode**: `isPublic()`

### Änderungsdatum

Für `modifyDate` können verwendet werden:

- **Feldwert**: `getValue("updatedate")`
- **Methode**: `getLastModified()`
- **Statischer Wert**: `2025-01-20 10:30:00`

## Datenbank-Schema

### url2_profile

- `id` - Primary Key
- `key` - Eindeutiger Schlüssel
- `namespace` - Namespace
- `table_name` - YForm-Tabelle
- `query` - JSON-Query
- `seo_title`, `seo_description`, `seo_image` - SEO-Methoden
- `addToSitemap` - Sitemap-Einstellung
- `modifyDate` - Änderungsdatum-Methode

### url2_generator

- `id` - Primary Key
- `profile_id` - Verweis auf Profil
- `profile_name` - Profil-Schlüssel
- `article_id`, `clang_id` - REDAXO-Artikel
- `tablename` - YForm-Tabelle
- `dataset_id` - Datensatz-ID
- `url` - Generierte URL
- `url_hash` - URL-Hash für Performance
- `seo_title`, `seo_description`, `seo_image` - SEO-Daten
- `in_sitemap` - Sitemap-Flag

## Extension Points

### URL_PROFILES

Ermöglicht anderen Addons die Registrierung von URL-Profilen.

```php
rex_extension::register('URL_PROFILES', function(rex_extension_point $ep) {
    $profiles = $ep->getSubject();
    // Profile hinzufügen...
    return $profiles;
});
```

## Automatische URL-Regenerierung

```php
// Nach Datensatz-Änderungen URLs aktualisieren
rex_extension::register('YFORM_SAVED', function(rex_extension_point $ep) {
    $table = $ep->getParam('table');
    
    // Prüfen ob URL-Profil für diese Tabelle existiert
    $profiles = \Alexplusde\Urlaub\Profile::getAllRegisteredProfiles();
    foreach ($profiles as $profile) {
        if ($profile->getTableName() === $table->getTableName()) {
            UrlResolver::regenerateUrlsForProfile($profile->getKey());
            break;
        }
    }
});
```
