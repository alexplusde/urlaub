<?php

namespace Alexplusde\Urlaub;

use rex_extension;
use rex_extension_point;

class ProfileManager
{
    /**
     * Initialisiert den Profile Manager und registriert den Extension Point
     */
    public static function init(): void
    {
        // Extension Point für die Registrierung von URL-Profilen
        rex_extension::register('URL_PROFILES', [self::class, 'registerProfiles']);
        
        // Profile aus der Datenbank laden und registrieren
        self::loadDatabaseProfiles();
        
        // Extension Point auslösen, damit andere Addons ihre Profile registrieren können
        rex_extension::registerPoint(new rex_extension_point('URL_PROFILES', []));
    }

    /**
     * Callback-Methode für den Extension Point
     * Andere Addons können hier ihre Profile registrieren
     */
    public static function registerProfiles(rex_extension_point $ep): mixed
    {
        $profiles = $ep->getSubject();
        
        if (!is_array($profiles)) {
            $profiles = [];
        }
        
        foreach ($profiles as $profileConfig) {
            if (self::validateProfileConfig($profileConfig)) {
                $profile = new Profile($profileConfig);
                Profile::registerProfile($profile);
            }
        }
        
        return $profiles;
    }

    /**
     * Lädt Profile aus der Datenbank und registriert sie
     */
    private static function loadDatabaseProfiles(): void
    {
        $sql = \rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . Profile::TABLE_NAME);
        
        while ($sql->hasNext()) {
            $row = $sql->getRow();
            
            // Query aus JSON dekodieren
            if (!empty($row['query'])) {
                $row['query'] = json_decode($row['query'], true);
            }
            
            $profile = new Profile();
            $profile->setFromConfig($row);
            $profile->setId((int)$row['id']);
            
            Profile::registerProfile($profile);
            
            $sql->next();
        }
    }

    /**
     * Validiert die Konfiguration eines Profils
     */
    private static function validateProfileConfig(array $config): bool
    {
        $requiredFields = ['key', 'tablename'];
        
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Registriert ein einzelnes Profil programmatisch
     */
    public static function addProfile(array $config): ?Profile
    {
        if (!self::validateProfileConfig($config)) {
            return null;
        }
        
        $profile = new Profile($config);
        Profile::registerProfile($profile);
        
        return $profile;
    }

    /**
     * Entfernt ein registriertes Profil
     */
    public static function removeProfile(string $key): bool
    {
        $profiles = Profile::getAllRegisteredProfiles();
        
        if (isset($profiles[$key])) {
            unset($profiles[$key]);
            Profile::clearRegisteredProfiles();
            
            // Alle anderen Profile wieder registrieren
            foreach ($profiles as $profile) {
                Profile::registerProfile($profile);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Erstellt Profile für alle registrierten Profile
     */
    public static function generateAllUrls(): void
    {
        $generator = new Generator();
        $generator->generateAllUrls();
    }

    /**
     * Hilfsmethode für andere Addons zum einfachen Registrieren von Profilen
     *
     * Beispiel-Aufruf:
     * \Alexplusde\Urlaub\ProfileManager::registerUrlProfile([
     *     'key' => 'events',
     *     'tablename' => 'events',
     *     'query' => \rex_yform_manager_table::get('events')->query()->where('status', 1),
     *     'seo_title' => 'getValue("title")',
     *     'seo_description' => 'getValue("description")',
     *     'seo_image' => 'getValue("image")',
     *     'addToSitemap' => true,
     *     'modifyDate' => 'getValue("updatedate")'
     * ]);
     */
    public static function registerUrlProfile(array $config): ?Profile
    {
        return self::addProfile($config);
    }
}
