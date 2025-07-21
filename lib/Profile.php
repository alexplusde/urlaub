<?php

namespace Alexplusde\Urlaub;

use rex_yform_manager_dataset;
use rex_yform_manager_query;
use rex_extension_point;

class Profile
{
    const TABLE_NAME = 'urlaub_profile';

    private static array $registeredProfiles = [];

    private int $id = 0;
    private string $key = '';
    private string $namespace = '';
    private string $table_name = '';
    private ?rex_yform_manager_query $query = null;

    private int $article_id = 0;
    private int $clang_id = 1;
    private bool $sitemap_enabled = true;

    private string $seo_title = '';
    private string $seo_description = '';
    private string $seo_image = '';
    private string $seo_modifydate = '';
    private bool|string $addToSitemap = true;
    private string $modifyDate = '';

    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->setFromConfig($config);
        }
    }

    /**
     * Erstellt ein Profil aus einer Konfiguration
     */
    public function setFromConfig(array $config): self
    {
        $this->key = $config['key'] ?? '';
        $this->namespace = $config['key'] ?? '';
        $this->table_name = $config['tablename'] ?? '';
        $this->query = $config['query'] ?? null;
        $this->seo_title = $config['seo_title'] ?? '';
        $this->seo_description = $config['seo_description'] ?? '';
        $this->seo_image = $config['seo_image'] ?? '';
        $this->addToSitemap = $config['addToSitemap'] ?? true;
        $this->modifyDate = $config['modifyDate'] ?? '';

        return $this;
    }

    // Save in Database
    public function save(): self
    {
        $sql = \rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setValue('key', $this->key);
        $sql->setValue('namespace', $this->namespace);
        $sql->setValue('table_name', $this->table_name);
        $sql->setValue('query', $this->query ? json_encode($this->query) : '');
        $sql->setValue('seo_title', $this->seo_title);
        $sql->setValue('seo_description', $this->seo_description);
        $sql->setValue('seo_image', $this->seo_image);
        $sql->setValue('addToSitemap', is_bool($this->addToSitemap) ? (int)$this->addToSitemap : $this->addToSitemap);
        $sql->setValue('modifyDate', $this->modifyDate);
        if ($this->id > 0) {
            $sql->setWhere(['id' => $this->id]);
        }
        $sql->insertOrUpdate();
        return $this;
    }

    public function delete() : void
    {
        $sql = \rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setWhere(['id' => $this->id]);
        $sql->delete();
    }

    public static function load(int $id): self
    {
        $sql = \rex_sql::factory();
        $sql->setTable(self::TABLE_NAME);
        $sql->setWhere(['id' => $id]);
        $sql->select();

        if ($sql->getRows() === 0) {
            throw new \Exception("Profile with ID $id not found.");
        }
        
        $profileData = $sql->getRow();
        if (!is_array($profileData)) {
            throw new \Exception("Invalid profile data for ID $id.");
        }
        
        $profile = new self();
        $profile->setFromConfig($profileData);
        $profile->id = $id;
        return $profile;
    }

    // Getter-Methoden
    public function getKey(): string
    {
        return $this->key;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getTableName(): string
    {
        return $this->table_name;
    }

    public function getQuery(): ?rex_yform_manager_query
    {
        return $this->query;
    }

    public function getSeoTitle(): string
    {
        return $this->seo_title;
    }

    public function getSeoDescription(): string
    {
        return $this->seo_description;
    }

    public function getSeoImage(): string
    {
        return $this->seo_image;
    }

    public function getAddToSitemap(): bool|string
    {
        return $this->addToSitemap;
    }

    public function getModifyDate(): string
    {
        return $this->modifyDate;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setArticleId(int $article_id): void
    {
        $this->article_id = $article_id;
    }

    public function getArticleId(): int
    {
        return $this->article_id;
    }

    public function setClangId(int $clang_id): void
    {
        $this->clang_id = $clang_id;
    }

    public function getClangId(): int
    {
        return $this->clang_id;
    }

    public function setSitemapEnabled(bool|string $enabled): void
    {
        $this->sitemap_enabled = $enabled;
    }

    public function isSitemapEnabled(): bool|string
    {
        return $this->sitemap_enabled;
    }

    public function setSeoTitle(string $title): void
    {
        $this->seo_title = $title;
    }

    public function setSeoDescription(string $description): void
    {
        $this->seo_description = $description;
    }

    public function setSeoImage(string $image): void
    {
        $this->seo_image = $image;
    }

    public function setAddToSitemap(bool|string $addToSitemap): void
    {
        $this->addToSitemap = $addToSitemap;
    }

    public function setModifyDate(string $modifyDate): void
    {
        $this->modifyDate = $modifyDate;
    }

    

    /**
     * Führt die SEO-Methode für einen Datensatz aus
     */
    public function executeSeoMethod(rex_yform_manager_dataset $dataset, string $methodName): string
    {
        if (empty($methodName)) {
            return '';
        }

        try {
            // Einfache Methoden-Aufrufe wie getValue('field') oder getImage()
            if (strpos($methodName, '(') !== false) {
                // Einfacher Parser für Methoden mit Parametern
                if (preg_match('/(\w+)\(([^)]*)\)/', $methodName, $matches) === 1 && count($matches) === 3) {
                    $method = $matches[1];
                    $param = trim($matches[2], '\'"');
                    
                    if (method_exists($dataset, $method)) {
                        $result = $param ? $dataset->$method($param) : $dataset->$method();
                        return (string) $result;
                    }
                }
            } else {
                // Einfache Methoden ohne Parameter
                if (method_exists($dataset, $methodName)) {
                    $result = $dataset->$methodName();
                    return (string) $result;
                }
            }
        } catch (\Throwable $e) {
            // Log error but don't break execution
            error_log('Urlaub: Error executing SEO method: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Prüft ob ein Datensatz zur Sitemap hinzugefügt werden soll
     */
    public function shouldAddToSitemap(rex_yform_manager_dataset $dataset): bool
    {
        if (is_bool($this->addToSitemap)) {
            return $this->addToSitemap;
        }

        if (is_string($this->addToSitemap)) {
            $result = $this->executeSeoMethod($dataset, $this->addToSitemap);
            return (bool) $result;
        }

        return true;
    }

    /**
     * Ermittelt das Änderungsdatum für einen Datensatz
     */
    public function getDatasetModifyDate(rex_yform_manager_dataset $dataset): string
    {
        if (empty($this->modifyDate)) {
            return '';
        }

        // Wenn es ein Feld-Name ist, Wert aus Dataset holen
        if (method_exists($dataset, 'getValue') && $dataset->hasValue($this->modifyDate)) {
            return $dataset->getValue($this->modifyDate);
        }

        // Wenn es eine Methode ist
        $result = $this->executeSeoMethod($dataset, $this->modifyDate);
        if ($result) {
            return (string) $result;
        }

        // Falls direkt ein Datum übergeben wurde
        return $this->modifyDate;
    }

    /**
     * Registriert ein Profil im statischen Array
     */
    public static function registerProfile(self $profile): void
    {
        self::$registeredProfiles[$profile->getKey()] = $profile;
    }

    /**
     * Gibt alle registrierten Profile zurück
     */
    public static function getAllRegisteredProfiles(): array
    {
        return self::$registeredProfiles;
    }

    /**
     * Holt ein registriertes Profil anhand des Schlüssels
     */
    public static function getRegisteredProfile(string $key): ?self
    {
        return self::$registeredProfiles[$key] ?? null;
    }

    /**
     * Löscht alle registrierten Profile (nützlich für Tests)
     */
    public static function clearRegisteredProfiles(): void
    {
        self::$registeredProfiles = [];
    }
}
