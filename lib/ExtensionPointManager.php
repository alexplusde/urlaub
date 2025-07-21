<?php

namespace Alexplusde\Urlaub;

use rex_extension_point;

/**
 * Extension Point Manager für URL2
 * Verwaltet alle Extension Points und deren Behandlung
 */
class ExtensionPointManager
{
    private rex_extension_point $extensionPoint;
    private string $extensionPointName;
    private array $params;

    public function __construct(rex_extension_point $extensionPoint)
    {
        $this->extensionPoint = $extensionPoint;
        $this->extensionPointName = $extensionPoint->getName();
        $this->params = $extensionPoint->getParams();
    }

    /**
     * Bestimmt ob eine URL-Regenerierung nötig ist
     */
    public function shouldRegenerateUrls(): bool
    {
        switch ($this->extensionPointName) {
            // YForm-Datenänderungen
            case 'YFORM_DATA_ADDED':
            case 'YFORM_DATA_UPDATED':
            case 'REX_YFORM_SAVED':
                return $this->isRelevantYFormChange();
                
            // Struktur-Änderungen
            case 'ART_ADDED':
            case 'ART_UPDATED':
            case 'ART_STATUS':
            case 'CAT_ADDED':
            case 'CAT_UPDATED':
            case 'CAT_STATUS':
                return $this->isRelevantStructureChange();
                
            // Sprach-Änderungen
            case 'CLANG_ADDED':
            case 'CLANG_UPDATED':
                return true;
                
            // Cache-Löschung
            case 'CACHE_DELETED':
                return true;
                
            default:
                return false;
        }
    }

    /**
     * Bestimmt ob URLs gelöscht werden sollen
     */
    public function shouldDeleteUrls(): bool
    {
        switch ($this->extensionPointName) {
            case 'YFORM_DATA_DELETED':
                return $this->isRelevantYFormChange();
                
            case 'ART_DELETED':
            case 'CAT_DELETED':
                return $this->isRelevantStructureChange();
                
            default:
                return false;
        }
    }

    /**
     * Prüft ob eine YForm-Änderung relevant für URL2 ist
     */
    private function isRelevantYFormChange(): bool
    {
        $table = $this->params['table'] ?? null;
        
        if (!$table || !method_exists($table, 'getTableName')) {
            return false;
        }
        
        $tableName = $table->getTableName();
        
        // Prüfen ob für diese Tabelle Profile existieren
        $profiles = Profile::getAllRegisteredProfiles();
        foreach ($profiles as $profile) {
            if ($profile->getTableName() === $tableName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Prüft ob eine Struktur-Änderung relevant für URL2 ist
     */
    private function isRelevantStructureChange(): bool
    {
        $articleId = $this->params['id'] ?? $this->params['article_id'] ?? null;
        
        if (!$articleId) {
            return false;
        }
        
        // Prüfen ob dieser Artikel in Profilen verwendet wird
        $profiles = Profile::getAllRegisteredProfiles();
        foreach ($profiles as $profile) {
            if ($profile->getArticleId() == $articleId) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Holt die betroffenen Profile für diese Änderung
     */
    public function getAffectedProfiles(): array
    {
        $affected = [];
        $profiles = Profile::getAllRegisteredProfiles();
        
        switch ($this->extensionPointName) {
            case 'YFORM_DATA_ADDED':
            case 'YFORM_DATA_UPDATED':
            case 'YFORM_DATA_DELETED':
            case 'REX_YFORM_SAVED':
                $table = $this->params['table'] ?? null;
                if ($table && method_exists($table, 'getTableName')) {
                    $tableName = $table->getTableName();
                    foreach ($profiles as $profile) {
                        if ($profile->getTableName() === $tableName) {
                            $affected[] = $profile;
                        }
                    }
                }
                break;
                
            case 'ART_ADDED':
            case 'ART_UPDATED':
            case 'ART_STATUS':
            case 'ART_DELETED':
            case 'CAT_ADDED':
            case 'CAT_UPDATED':
            case 'CAT_STATUS':
            case 'CAT_DELETED':
                $articleId = $this->params['id'] ?? $this->params['article_id'] ?? null;
                if ($articleId) {
                    foreach ($profiles as $profile) {
                        if ($profile->getArticleId() == $articleId) {
                            $affected[] = $profile;
                        }
                    }
                }
                break;
                
            default:
                // Bei anderen Extension Points alle Profile betroffen
                $affected = array_values($profiles);
                break;
        }
        
        return $affected;
    }

    /**
     * Holt den betroffenen Datensatz
     */
    public function getAffectedDataset(): ?\rex_yform_manager_dataset
    {
        return $this->params['dataset'] ?? null;
    }

    /**
     * Holt die betroffene Tabelle
     */
    public function getAffectedTable(): ?object
    {
        return $this->params['table'] ?? null;
    }

    /**
     * Holt die Extension Point Parameter
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Holt den Extension Point Namen
     */
    public function getName(): string
    {
        return $this->extensionPointName;
    }

    /**
     * Holt den ursprünglichen Extension Point
     */
    public function getExtensionPoint(): rex_extension_point
    {
        return $this->extensionPoint;
    }
}
