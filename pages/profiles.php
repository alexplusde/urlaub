<?php

/**
 * Urlaub Addon - Profile Übersicht
 */

use Alexplusde\Urlaub\Profile;
use Alexplusde\Urlaub\ProfileManager;

/** @var rex_addon $this */

// Seiten-Header
echo rex_view::title($this->i18n('urlaub_profiles'));

// Prüfen ob Profile existieren
$profiles = Profile::getAllRegisteredProfiles();

if (empty($profiles)) {
    // Keine Profile vorhanden
    $content = '<div class="alert alert-info">';
    $content .= '<h4><i class="rex-icon fa-info-circle"></i> ' . $this->i18n('urlaub_profiles_empty_title') . '</h4>';
    $content .= '<p>' . $this->i18n('urlaub_profiles_empty_text') . '</p>';
    $content .= '<p><strong>' . $this->i18n('urlaub_profiles_help_title') . '</strong></p>';
    $content .= '<ul>';
    $content .= '<li>' . $this->i18n('urlaub_profiles_help_extension_point') . '</li>';
    $content .= '<li>' . $this->i18n('urlaub_profiles_help_profile_manager') . '</li>';
    $content .= '</ul>';
    $content .= '<pre><code>';
    $content .= htmlspecialchars('rex_extension::register(\'URL_PROFILES\', function($ep) {
    $profiles = $ep->getSubject();
    $profiles[] = [
        \'key\' => \'events\',
        \'tablename\' => \'events\',
        \'query\' => rex_yform_manager_table::get(\'events\')->query()->where(\'status\', 1),
        \'seo_title\' => \'getValue("title")\',
        \'seo_description\' => \'getValue("description")\',
        \'seo_image\' => \'getValue("image")\',
        \'addToSitemap\' => true,
        \'modifyDate\' => \'getValue("updatedate")\'
    ];
    return $profiles;
});');
    $content .= '</code></pre>';
    $content .= '</div>';
    
    echo $content;
} else {
    // Profile anzeigen
    $fragment = new rex_fragment();
    $fragment->setVar('profiles', $profiles);
    echo $fragment->parse('urlaub/profiles_overview.php');
}

// Regenerate Button
if (!empty($profiles)) {
    echo '<div class="panel panel-default">';
    echo '<div class="panel-body">';
    echo '<a href="' . rex_url::currentBackendPage(['action' => 'regenerate_all'] + rex_csrf_token::factory('urlaub_regenerate')->getUrlParams()) . '" ';
    echo 'class="btn btn-warning" ';
    echo 'data-confirm="' . $this->i18n('urlaub_regenerate_all_confirm') . '">';
    echo '<i class="rex-icon fa-refresh"></i> ' . $this->i18n('urlaub_regenerate_all');
    echo '</a>';
    echo '</div>';
    echo '</div>';
}

// Actions verarbeiten
$action = rex_request('action', 'string');
$profileKey = rex_request('profile_key', 'string');

if ($action === 'regenerate_all') {
    if (rex_csrf_token::factory('urlaub_regenerate')->isValid()) {
        try {
            ProfileManager::generateAllUrls();
            echo rex_view::success($this->i18n('urlaub_regenerate_all_success'));
        } catch (Exception $e) {
            echo rex_view::error($this->i18n('urlaub_regenerate_all_error') . ': ' . $e->getMessage());
        }
    } else {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    }
}

if ($action === 'regenerate_profile' && $profileKey) {
    if (rex_csrf_token::factory('urlaub_regenerate')->isValid()) {
        try {
            $success = \Alexplusde\Urlaub\UrlResolver::regenerateUrlsForProfile($profileKey);
            if ($success) {
                echo rex_view::success($this->i18n('urlaub_regenerate_profile_success', $profileKey));
            } else {
                echo rex_view::error($this->i18n('urlaub_regenerate_profile_not_found', $profileKey));
            }
        } catch (Exception $e) {
            echo rex_view::error($this->i18n('urlaub_regenerate_profile_error') . ': ' . $e->getMessage());
        }
    } else {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    }
}
