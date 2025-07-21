<?php

namespace Alexplusde\Urlaub;

use rex_extension_point;
use rex_yrewrite_seo;
use rex_article;

/**
 * SEO-Klasse für URL2
 * Verwaltet SEO-Tags und Sitemap-Integration
 */
class Seo
{
    private ?array $urlData = null;
    private ?\rex_yform_manager_dataset $dataset = null;

    public function __construct(?array $urlData = null)
    {
        $this->urlData = $urlData ?: UrlManager::getCurrentUrlData();
        
        if ($this->urlData) {
            $this->dataset = UrlResolver::getDataset($this->urlData);
        }
    }

    /**
     * Holt alle SEO-Tags für die aktuelle URL
     */
    public function getTags(): array
    {
        if (!$this->urlData || !$this->dataset) {
            return [];
        }

        $tags = [];
        
        // Basis SEO-Daten
        if (!empty($this->urlData['seo_title'])) {
            $tags['title'] = $this->urlData['seo_title'];
        }
        
        if (!empty($this->urlData['seo_description'])) {
            $tags['description'] = $this->urlData['seo_description'];
        }
        
        if (!empty($this->urlData['seo_image'])) {
            $tags['image'] = $this->urlData['seo_image'];
            // Open Graph Image
            $tags['og:image'] = $this->getFullImageUrl($this->urlData['seo_image']);
        }

        // Canonical URL
        $tags['canonical'] = $this->getCanonicalUrl();
        
        // Open Graph Tags
        $tags['og:title'] = $tags['title'] ?? '';
        $tags['og:description'] = $tags['description'] ?? '';
        $tags['og:url'] = $tags['canonical'];
        $tags['og:type'] = 'article';
        
        // Twitter Card Tags
        $tags['twitter:card'] = 'summary_large_image';
        $tags['twitter:title'] = $tags['title'] ?? '';
        $tags['twitter:description'] = $tags['description'] ?? '';
        
        if (isset($tags['og:image'])) {
            $tags['twitter:image'] = $tags['og:image'];
        }

        return $tags;
    }

    /**
     * Generiert die Sitemap-Einträge für alle URL2-URLs
     */
    public static function getSitemap(): array
    {
        $sitemap = [];
        $sitemapUrls = UrlResolver::getSitemapUrls();
        
        foreach ($sitemapUrls as $urlData) {
            $entry = [
                'url' => \rex_yrewrite::getFullPath($urlData['url']),
                'lastmod' => self::formatSitemapDate($urlData),
                'changefreq' => 'weekly', // Standard-Wert
                'priority' => '0.8' // Standard-Wert
            ];
            
            // Profil-spezifische Sitemap-Einstellungen
            $profile = Profile::getRegisteredProfile($urlData['profile_name']);
            if ($profile) {
                // Hier könnten profil-spezifische changefreq/priority-Werte gesetzt werden
                // Das würde eine Erweiterung der Profile-Klasse erfordern
            }
            
            $sitemap[] = $entry;
        }
        
        return $sitemap;
    }

    /**
     * Formatiert das Änderungsdatum für die Sitemap
     */
    private static function formatSitemapDate(array $urlData): string
    {
        // Versuche verschiedene Datums-Felder zu finden
        $dateFields = ['modifyDate', 'updatedate', 'modified', 'updated_at'];
        
        foreach ($dateFields as $field) {
            if (!empty($urlData[$field])) {
                $timestamp = strtotime($urlData[$field]);
                if ($timestamp) {
                    return date('c', $timestamp); // ISO 8601 Format
                }
            }
        }
        
        // Fallback: aktuelles Datum
        return date('c');
    }

    /**
     * Holt die kanonische URL
     */
    private function getCanonicalUrl(): string
    {
        if (!$this->urlData) {
            return '';
        }
        
        return \rex_yrewrite::getFullPath($this->urlData['url']);
    }

    /**
     * Erstellt eine vollständige URL für ein Bild
     */
    private function getFullImageUrl(string $image): string
    {
        if (empty($image)) {
            return '';
        }
        
        // Wenn bereits eine vollständige URL
        if (strpos($image, 'http') === 0) {
            return $image;
        }
        
        // Media-Manager URL erstellen
        if (strpos($image, '/') === false) {
            // Nur Dateiname - über Media-Manager
            return \rex_url::frontend('images/url2_seo/' . $image);
        }
        
        // Relativer Pfad
        return \rex_url::frontend($image);
    }

    /**
     * Setzt SEO-Tags für YRewrite
     */
    public static function setYRewriteTags(rex_extension_point $ep): array
    {
        $tags = $ep->getSubject();
        
        if (!is_array($tags)) {
            $tags = [];
        }
        
        $seo = new self();
        $url2Tags = $seo->getTags();
        
        // URL2-Tags haben Priorität über Standard-Tags
        $tags = array_merge($tags, $url2Tags);
        
        return $tags;
    }

    /**
     * Behandelt YREWRITE_PREPARE Extension Point
     */
    public static function handleYRewritePrepare(rex_extension_point $ep): mixed
    {
        $urlData = UrlManager::getCurrentUrlData();
        
        if ($urlData) {
            // Artikel-ID und Sprach-ID für YRewrite setzen
            $ep->setParam('article_id', $urlData['article_id']);
            $ep->setParam('clang_id', $urlData['clang_id']);
            
            // SEO-Daten für späteren Gebrauch vorbereiten
            $seo = new self($urlData);
            $tags = $seo->getTags();
            
            // SEO-Tags in YRewrite-Session speichern (falls nötig)
            if (!empty($tags)) {
                foreach ($tags as $key => $value) {
                    $ep->setParam('seo_' . $key, $value);
                }
            }
        }
        
        return $ep->getSubject();
    }

    /**
     * Prüft ob ein Datensatz in der Sitemap erscheinen soll
     */
    public function shouldAppearInSitemap(): bool
    {
        if (!$this->urlData) {
            return false;
        }
        
        return (bool) $this->urlData['in_sitemap'];
    }

    /**
     * Holt den Title-Tag für die aktuelle Seite
     */
    public function getTitle(): string
    {
        return $this->urlData['seo_title'] ?? '';
    }

    /**
     * Holt die Meta-Description für die aktuelle Seite
     */
    public function getDescription(): string
    {
        return $this->urlData['seo_description'] ?? '';
    }

    /**
     * Holt das SEO-Bild für die aktuelle Seite
     */
    public function getImage(): string
    {
        return $this->urlData['seo_image'] ?? '';
    }
}
