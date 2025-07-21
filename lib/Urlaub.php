<?php

namespace Url;

class Urlaub extends \rex_addon
{
    public const ADDON_NAME = 'urlaub';

    public function __construct()
    {
        parent::get(self::ADDON_NAME);
    }

    public function setProfileAsProperty(Profile $profile)
    {
        $this->setProperty('profile', $profile);
    }

    public function getProfile(): ?Profile
    {
        return $this->getProperty('profile') ?? null;
    }
    
    public function getProfiles(): array
    {
        $profiles = [];
        $profile = $this->getProfile();
        if ($profile) {
            $profiles[$profile->getId()] = $profile;
        }

        // Add external profiles from extension point
        $externalProfiles = \rex_extension::registerPoint(new \rex_extension_point('URL2_PROFILES', []));
        if (is_array($externalProfiles)) {
            foreach ($externalProfiles as $profileConfig) {
                $profiles[$profileConfig['key']] = new Profile($profileConfig);
            }
        }

        return $profiles;
    }
}
