<?php

namespace Alexplusde\Urlaub;

use rex_extension_point;
use rex_yrewrite;
use rex_article;
use rex_clang;

/**
 * URL-Manager für die Integration mit YRewrite
 * Verwaltet URL-Auflösung und Rewrite-Logik
 */
class UrlManager
{
    private static ?array $resolvedUrl = null;
    private static ?array $articleParams = null;

    /**
     * Löst eine URL auf und gibt die entsprechenden Artikel-Parameter zurück
     * Wird von YRewrite über URL_REWRITE Extension Point aufgerufen
     */
    public static function getRewriteUrl(rex_extension_point $ep): mixed
    {
        $params = $ep->getParams();
        $url = $params['url'] ?? '';
        
        // Nur verarbeiten wenn es keine normale REDAXO-URL ist
        if (empty($url) || self::isRedaxoUrl($url)) {
            return $ep->getSubject();
        }

        // URL ohne führenden Slash normalisieren
        $cleanUrl = ltrim($url, '/');
        
        // URL auflösen
        $urlData = UrlResolver::resolve($cleanUrl);
        
        if (!$urlData) {
            return $ep->getSubject();
        }

        // Datensatz laden für zusätzliche Validierung
        $dataset = UrlResolver::getDataset($urlData);
        if (!$dataset) {
            return $ep->getSubject();
        }

        // Artikel-Parameter für YRewrite setzen
        self::$resolvedUrl = $urlData;
        self::$articleParams = [
            'article_id' => $urlData['article_id'],
            'clang_id' => $urlData['clang_id'],
            'dataset' => $dataset,
            'url_data' => $urlData
        ];

        // YRewrite informieren
        $ep->setParam('article_id', $urlData['article_id']);
        $ep->setParam('clang_id', $urlData['clang_id']);
        
        return $ep->getSubject();
    }

    /**
     * Gibt die Artikel-Parameter für die aufgelöste URL zurück
     * Wird von YRewrite für YREWRITE_PREPARE verwendet
     */
    public static function getArticleParams(): ?array
    {
        return self::$articleParams;
    }

    /**
     * Prüft ob es sich um eine normale REDAXO-URL handelt
     */
    private static function isRedaxoUrl(string $url): bool
    {
        // Normale Artikel-URLs, Media-Manager, etc.
        if (preg_match('/^(media|assets|redaxo)\//', $url)) {
            return true;
        }
        
        // Index-Seite
        if ($url === '' || $url === '/') {
            return true;
        }
        
        return false;
    }

    /**
     * Erstellt eine URL für einen Datensatz
     */
    public static function createUrl(string $tablename, int $datasetId): ?string
    {
        return UrlResolver::getUrlForDataset($tablename, $datasetId);
    }

    /**
     * Holt die aktuelle aufgelöste URL-Daten
     */
    public static function getCurrentUrlData(): ?array
    {
        return self::$resolvedUrl;
    }

    /**
     * Prüft ob die aktuelle Seite eine URL2-URL ist
     */
    public static function isUrlaubPage(): bool
    {
        return self::$resolvedUrl !== null;
    }

    /**
     * Holt den Datensatz der aktuellen Seite
     */
    public static function getCurrentDataset(): ?\rex_yform_manager_dataset
    {
        if (!self::$articleParams || !isset(self::$articleParams['dataset'])) {
            return null;
        }
        
        return self::$articleParams['dataset'];
    }

    /**
     * Regeneriert URLs nach Struktur-Änderungen
     */
    public static function handleStructureChange(rex_extension_point $ep): void
    {
        // Bei Artikel/Kategorie-Änderungen URLs regenerieren
        $affectedProfiles = Profile::getAllRegisteredProfiles();
        
        foreach ($affectedProfiles as $profile) {
            // Prüfen ob das Profil von der Änderung betroffen ist
            $articleId = $profile->getArticleId();
            $changedArticleId = $ep->getParam('id') ?? $ep->getParam('article_id');
            
            if ($articleId && $articleId == $changedArticleId) {
                $generator = new Generator();
                $generator->generateUrlsForProfile($profile);
            }
        }
    }

    /**
     * Behandelt YForm-Datensatz-Änderungen
     */
    public static function handleYFormChange(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        $dataset = $ep->getParam('dataset');
        
        if (!$table || !$dataset) {
            return;
        }
        
        $tableName = $table->getTableName();
        
        // Alle Profile durchgehen und prüfen ob sie betroffen sind
        $profiles = Profile::getAllRegisteredProfiles();
        foreach ($profiles as $profile) {
            if ($profile->getTableName() === $tableName) {
                // Einzelnen Datensatz regenerieren
                $generator = new Generator();
                $generator->generateUrlForDataset($profile, $dataset);
            }
        }
    }

    /**
     * Löscht URLs wenn ein Datensatz gelöscht wird
     */
    public static function handleYFormDelete(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        $dataset = $ep->getParam('dataset');
        
        if (!$table || !$dataset) {
            return;
        }
        
        $tableName = $table->getTableName();
        $datasetId = $dataset->getId();
        
        // URL aus Generator-Tabelle löschen
        $sql = \rex_sql::factory();
        $sql->setQuery('DELETE FROM ' . Generator::TABLE_NAME . ' WHERE tablename = ? AND dataset_id = ?', 
                      [$tableName, $datasetId]);
    }
}
