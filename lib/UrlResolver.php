<?php

namespace Alexplusde\Urlaub;

use rex_sql;
use rex_yform_manager_dataset;

/**
 * URL-Resolver für YRewrite Integration
 * Diese Klasse stellt Methoden zur Verfügung, um URLs aufzulösen
 * und die entsprechenden Datensätze zu finden
 */
class UrlResolver
{
    /**
     * Löst eine URL auf und gibt die entsprechenden Daten zurück
     */
    public static function resolve(string $url): ?array
    {
        $generator = new Generator();
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . Generator::TABLE_NAME . ' WHERE url = ?', [$url]);
        
        if ($sql->getRows() > 0) {
            return $sql->getRow();
        }
        
        return null;
    }

    /**
     * Holt alle URLs für die Sitemap
     */
    public static function getSitemapUrls(): array
    {
        $generator = new Generator();
        return $generator->getSitemapUrls();
    }

    /**
     * Holt den Datensatz für eine aufgelöste URL
     */
    public static function getDataset(array $urlData): ?rex_yform_manager_dataset
    {
        $tablename = $urlData['tablename'];
        $datasetId = $urlData['dataset_id'];
        
        try {
            $table = \rex_yform_manager_table::get($tablename);
            if ($table) {
                return $table->query()->findId($datasetId);
            }
        } catch (\Exception $e) {
            // Tabelle existiert nicht oder Datensatz nicht gefunden
        }
        
        return null;
    }

    /**
     * Erstellt eine URL für einen Datensatz
     */
    public static function getUrlForDataset(string $tablename, int $datasetId): ?string
    {
        $generator = new Generator();
        $urlData = $generator->getUrlByDataset($tablename, $datasetId);
        
        return $urlData['url'] ?? null;
    }

    /**
     * Regeneriert URLs für ein spezifisches Profil
     */
    public static function regenerateUrlsForProfile(string $profileKey): bool
    {
        $profile = Profile::getRegisteredProfile($profileKey);
        
        if (!$profile) {
            return false;
        }
        
        $generator = new Generator();
        $generator->generateUrlsForProfile($profile);
        
        return true;
    }

    /**
     * Regeneriert alle URLs
     */
    public static function regenerateAllUrls(): void
    {
        ProfileManager::generateAllUrls();
    }

    /**
     * Prüft ob eine URL bereits existiert
     */
    public static function urlExists(string $url): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT id FROM ' . Generator::TABLE_NAME . ' WHERE url = ?', [$url]);
        return $sql->getRows() > 0;
    }

    /**
     * Holt SEO-Daten für eine URL
     */
    public static function getSeoData(string $url): ?array
    {
        $urlData = self::resolve($url);
        
        if (!$urlData) {
            return null;
        }
        
        return [
            'title' => $urlData['seo_title'] ?? '',
            'description' => $urlData['seo_description'] ?? '',
            'image' => $urlData['seo_image'] ?? '',
            'canonical' => $url
        ];
    }
}
