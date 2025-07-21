<?php

/**
 * REDAXO URL2 Addon - Settings Page
 * 
 * @package redaxo\urlaub
 * @author  Alexander Walther
 * @since   2.0.0
 */

/** @var rex_addon $this */

use Alexplusde\Urlaub\ProfileManager;

echo rex_view::title($this->i18n('urlaub_settings_title'));

$formContent = '';

// Formular für allgemeine Einstellungen
$formElements = [];

// Cache-Einstellungen
$formElements['fieldset-cache'] = [
    'type'  => 'fieldset',
    'value' => 'legend:' . $this->i18n('urlaub_settings_cache'),
];

$formElements['cache_duration'] = [
    'type'  => 'text',
    'id'    => 'urlaub-cache-duration',
    'name'  => 'cache_duration',
    'label' => $this->i18n('urlaub_settings_cache_duration'),
    'value' => $this->getConfig('cache_duration', 3600),
];

$formElements['enable_cache'] = [
    'type'    => 'checkbox',
    'id'      => 'urlaub-enable-cache', 
    'name'    => 'enable_cache',
    'label'   => $this->i18n('urlaub_settings_enable_cache'),
    'value'   => 1,
    'checked' => $this->getConfig('enable_cache', true),
];

// SEO-Einstellungen
$formElements['fieldset-seo'] = [
    'type'  => 'fieldset', 
    'value' => 'legend:' . $this->i18n('urlaub_settings_seo'),
];

$formElements['default_meta_keywords'] = [
    'type'  => 'textarea',
    'id'    => 'urlaub-default-meta-keywords',
    'name'  => 'default_meta_keywords', 
    'label' => $this->i18n('urlaub_settings_default_meta_keywords'),
    'value' => $this->getConfig('default_meta_keywords', ''),
];

$formElements['default_meta_description'] = [
    'type'  => 'textarea',
    'id'    => 'urlaub-default-meta-description',
    'name'  => 'default_meta_description',
    'label' => $this->i18n('urlaub_settings_default_meta_description'), 
    'value' => $this->getConfig('default_meta_description', ''),
];

// URL-Einstellungen
$formElements['fieldset-urls'] = [
    'type'  => 'fieldset',
    'value' => 'legend:' . $this->i18n('urlaub_settings_urls'),
];

$formElements['url_max_length'] = [
    'type'  => 'text',
    'id'    => 'urlaub-url-max-length',
    'name'  => 'url_max_length',
    'label' => $this->i18n('urlaub_settings_url_max_length'),
    'value' => $this->getConfig('url_max_length', 100),
];

$formElements['url_separator'] = [
    'type'    => 'select',
    'id'      => 'urlaub-url-separator',
    'name'    => 'url_separator', 
    'label'   => $this->i18n('urlaub_settings_url_separator'),
    'options' => [
        '-' => $this->i18n('urlaub_settings_separator_dash'),
        '_' => $this->i18n('urlaub_settings_separator_underscore'),
        '.' => $this->i18n('urlaub_settings_separator_dot'),
    ],
    'selected' => $this->getConfig('url_separator', '-'),
];

// Debug-Einstellungen
$formElements['fieldset-debug'] = [
    'type'  => 'fieldset',
    'value' => 'legend:' . $this->i18n('urlaub_settings_debug'),
];

$formElements['enable_debug'] = [
    'type'    => 'checkbox',
    'id'      => 'urlaub-enable-debug',
    'name'    => 'enable_debug',
    'label'   => $this->i18n('urlaub_settings_enable_debug'),
    'value'   => 1,
    'checked' => $this->getConfig('enable_debug', false),
];

$formElements['log_level'] = [
    'type'    => 'select',
    'id'      => 'urlaub-log-level',
    'name'    => 'log_level',
    'label'   => $this->i18n('urlaub_settings_log_level'),
    'options' => [
        'error'   => $this->i18n('urlaub_settings_log_error'),
        'warning' => $this->i18n('urlaub_settings_log_warning'), 
        'info'    => $this->i18n('urlaub_settings_log_info'),
        'debug'   => $this->i18n('urlaub_settings_log_debug'),
    ],
    'selected' => $this->getConfig('log_level', 'error'),
];

// Submit-Handling
if (rex_post('submit', 'boolean')) {
    $config = [];
    foreach ($formElements as $key => $element) {
        if (isset($element['name'])) {
            $value = rex_post($element['name'], 'string');
            if ($element['type'] === 'checkbox') {
                $value = rex_post($element['name'], 'boolean');
            }
            $config[$element['name']] = $value;
        }
    }
    
    // Konfiguration speichern
    foreach ($config as $key => $value) {
        $this->setConfig($key, $value);
    }
    
    echo rex_view::success($this->i18n('urlaub_settings_saved'));
    
    // Cache leeren nach Konfigurationsänderung
    rex_delete_cache();
    
    // Profile neu laden
    ProfileManager::init();
}

// Formular generieren  
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$formContent = $fragment->parse('core/form/form.php');

$formContent .= '
<fieldset class="form-horizontal">
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button class="btn btn-save rex-form-aligned" type="submit" name="submit" value="1">' . $this->i18n('urlaub_settings_save') . '</button>
        </div>
    </div>
</fieldset>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $this->i18n('urlaub_settings_form_title'), false);
$fragment->setVar('body', $formContent, false);

$content = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$content .= $fragment->parse('core/page/section.php');
$content .= '</form>';

echo $content;

// Cache-Informationen anzeigen
$cacheContent = '<ul>';
$cacheContent .= '<li><strong>' . $this->i18n('urlaub_settings_cache_status') . ':</strong> ';
$cacheContent .= $this->getConfig('enable_cache', true) ? $this->i18n('urlaub_settings_cache_enabled') : $this->i18n('urlaub_settings_cache_disabled');
$cacheContent .= '</li>';
$cacheContent .= '<li><strong>' . $this->i18n('urlaub_settings_cache_duration_current') . ':</strong> ' . $this->getConfig('cache_duration', 3600) . ' ' . $this->i18n('urlaub_settings_seconds') . '</li>';

// Anzahl der geladenen Profile anzeigen
$profiles = \Alexplusde\Urlaub\Profile::getAllRegisteredProfiles();
$cacheContent .= '<li><strong>' . $this->i18n('urlaub_settings_profiles_loaded') . ':</strong> ' . count($profiles) . '</li>';

$cacheContent .= '</ul>';

$cacheContent .= '<p><a class="btn btn-primary" href="' . rex_url::currentBackendPage(['clear_cache' => 1]) . '">' . $this->i18n('urlaub_settings_clear_cache') . '</a></p>';

// Cache leeren wenn angefordert
if (rex_get('clear_cache', 'boolean')) {
    rex_delete_cache();
    ProfileManager::init();
    echo rex_view::success($this->i18n('urlaub_settings_cache_cleared'));
}

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('urlaub_settings_cache_info'), false);
$fragment->setVar('body', $cacheContent, false);
echo $fragment->parse('core/page/section.php');
