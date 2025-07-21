<?php

namespace Alexplusde\Urlaub;

use rex_sql;
use rex_yform_manager_dataset;
use rex_string;

// Nimmt Profile entgegen, geht die Datensätze durch und generiert passende URLs

class Generator extends rex_sql
{
    const TABLE_NAME = 'urlaub_generator';

    public function __construct()
    {
        parent::__construct();
        $this->setTable(self::TABLE_NAME);
    }

    /**
     * Generiert URLs für alle registrierten Profile
     */
    public function generateAllUrls(): void
    {
        $profiles = Profile::getAllRegisteredProfiles();
        
        foreach ($profiles as $profile) {
            $this->generateUrlsForProfile($profile);
        }
    }

    /**
     * Generiert URLs für ein spezifisches Profil
     */
    public function generateUrlsForProfile(Profile $profile): void
    {
        $query = $profile->getQuery();
        if (!$query) {
            return;
        }

        // Alle existierenden URLs für dieses Profil löschen
        $this->clearUrlsForProfile($profile);

        // Datensätze aus der Query abrufen
        $datasets = $query->find();

        foreach ($datasets as $dataset) {
            $this->generateUrlForDataset($profile, $dataset);
        }
    }

    /**
     * Generiert eine URL für einen einzelnen Datensatz
     */
    public function generateUrlForDataset(Profile $profile, rex_yform_manager_dataset $dataset): void
    {
        // SEO-Daten aus Dataset extrahieren
        $seoTitle = $this->extractSeoData($profile, $dataset, 'seo_title');
        $seoDescription = $this->extractSeoData($profile, $dataset, 'seo_description');
        $seoImage = $this->extractSeoData($profile, $dataset, 'seo_image');
        
        // URL aus Titel generieren
        $url = $this->generateUrl($seoTitle, $profile->getKey(), $dataset->getId());
        $urlHash = md5($url);

        // Prüfen ob Dataset zur Sitemap hinzugefügt werden soll
        $inSitemap = $profile->shouldAddToSitemap($dataset);

        // In Datenbank speichern
        $sql = rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setValue('profile_id', $profile->getId());
        $sql->setValue('profile_name', $profile->getKey());
        $sql->setValue('article_id', $profile->getArticleId() ?? 0);
        $sql->setValue('clang_id', $profile->getClangId() ?? 1);
        $sql->setValue('tablename', $profile->getTableName());
        $sql->setValue('dataset_id', $dataset->getId());
        $sql->setValue('url', $url);
        $sql->setValue('url_hash', $urlHash);
        $sql->setValue('seo_title', $seoTitle);
        $sql->setValue('seo_description', $seoDescription);
        $sql->setValue('seo_image', $seoImage);
        $sql->setValue('in_sitemap', (int)$inSitemap);
        $sql->insert();
    }

    /**
     * Extrahiert SEO-Daten aus einem Dataset basierend auf Profil-Konfiguration
     */
    private function extractSeoData(Profile $profile, rex_yform_manager_dataset $dataset, string $type): string
    {
        switch ($type) {
            case 'seo_title':
                $method = $profile->getSeoTitle();
                break;
            case 'seo_description':
                $method = $profile->getSeoDescription();
                break;
            case 'seo_image':
                $method = $profile->getSeoImage();
                break;
            default:
                return '';
        }

        return (string) $profile->executeSeoMethod($dataset, $method);
    }

    /**
     * Generiert eine SEO-freundliche URL
     */
    private function generateUrl(string $title, string $profileKey, int $datasetId): string
    {
        if (empty($title)) {
            $title = 'item-' . $datasetId;
        }

        // URL-freundlich machen
        $url = rex_string::normalize($title, '-', '-');
        $url = strtolower($url);
        
        // Profil-Präfix hinzufügen
        $url = $profileKey . '/' . $url;
        
        // Eindeutigkeit sicherstellen
        $url = $this->ensureUniqueUrl($url, $datasetId);
        
        return $url;
    }

    /**
     * Stellt sicher, dass die URL eindeutig ist
     */
    private function ensureUniqueUrl(string $baseUrl, int $excludeDatasetId = 0): string
    {
        $url = $baseUrl;
        $counter = 1;
        
        while ($this->urlExists($url, $excludeDatasetId)) {
            $url = $baseUrl . '-' . $counter;
            $counter++;
        }
        
        return $url;
    }

    /**
     * Prüft ob eine URL bereits existiert
     */
    private function urlExists(string $url, int $excludeDatasetId = 0): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT id FROM ' . self::TABLE_NAME . ' WHERE url = ? AND dataset_id != ?', [$url, $excludeDatasetId]);
        return $sql->getRows() > 0;
    }

    /**
     * Löscht alle URLs für ein Profil
     */
    public function clearUrlsForProfile(Profile $profile): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setWhere(['profile_id' => $profile->getId()]);
        $sql->delete();
    }

    /**
     * Löscht eine spezifische URL
     */
    public function deleteUrl(int $id): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setWhere(['id' => $id]);
        $sql->delete();
    }

    /**
     * Holt alle generierten URLs für ein Profil
     */
    public function getUrlsForProfile(Profile $profile): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::TABLE_NAME . ' WHERE profile_id = ? ORDER BY url', [$profile->getId()]);
        return $sql->getArray();
    }

    /**
     * Holt eine URL basierend auf Tabelle und Dataset-ID
     */
    public function getUrlByDataset(string $tablename, int $datasetId): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::TABLE_NAME . ' WHERE tablename = ? AND dataset_id = ?', [$tablename, $datasetId]);
        
        if ($sql->getRows() > 0) {
            return $sql->getRow();
        }
        
        return null;
    }

    /**
     * Holt alle URLs die zur Sitemap hinzugefügt werden sollen
     */
    public function getSitemapUrls(): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::TABLE_NAME . ' WHERE in_sitemap = 1 ORDER BY url');
        return $sql->getArray();
    }

    /**
     * Aktualisiert eine einzelne URL
     */
    public function updateUrl(int $id, array $data): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        
        foreach ($data as $field => $value) {
            $sql->setValue($field, $value);
        }
        
        $sql->setWhere(['id' => $id]);
        $sql->update();
    }
}
