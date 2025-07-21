<?php

/**
 * Urlaub Addon - URLs Übersicht
 */

use Alexplusde\Urlaub\Generator;
use Alexplusde\Urlaub\Profile;

/** @var rex_addon $this */

// Seiten-Header
echo rex_view::title($this->i18n('urlaub_urls'));

// Actions verarbeiten
$action = rex_request('action', 'string');
$urlId = rex_request('url_id', 'int');

if ($action === 'delete_url' && $urlId > 0) {
    if (rex_csrf_token::factory('urlaub_url_delete')->isValid()) {
        try {
            $generator = new Generator();
            $generator->deleteUrl($urlId);
            echo rex_view::success($this->i18n('urlaub_url_deleted'));
        } catch (Exception $e) {
            echo rex_view::error($this->i18n('urlaub_url_delete_error') . ': ' . $e->getMessage());
        }
    } else {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    }
}

// URL-Liste aufbauen
$query = 'SELECT * FROM ' . Generator::TABLE_NAME . ' ORDER BY profile_name, url';
$list = rex_list::factory($query);

$list->addTableAttribute('class', 'table-striped');
$list->setNoRowsMessage($this->i18n('urlaub_urls_empty'));

// Spalten definieren
$list->removeColumn('id');
$list->removeColumn('url_hash');
$list->removeColumn('profile_id');

// Profile Name Spalte
$list->setColumnLabel('profile_name', $this->i18n('urlaub_profile'));
$list->setColumnFormat('profile_name', 'custom', function($params) {
    $profileName = $params['list']->getValue('profile_name');
    return '<span class="label label-primary">' . htmlspecialchars($profileName) . '</span>';
});

// URL Spalte mit Link
$list->setColumnLabel('url', $this->i18n('urlaub_url'));
$list->setColumnFormat('url', 'custom', function($params) {
    $url = $params['list']->getValue('url');
    $fullUrl = rex_yrewrite::getFullPath($url);
    return '<a href="' . htmlspecialchars($fullUrl) . '" target="_blank">' . 
           '<code>' . htmlspecialchars($url) . '</code></a>';
});

// Tabelle und Dataset ID
$list->setColumnLabel('tablename', $this->i18n('urlaub_table'));
$list->setColumnLabel('dataset_id', $this->i18n('urlaub_dataset_id'));
$list->setColumnFormat('dataset_id', 'custom', function($params) {
    $tablename = $params['list']->getValue('tablename');
    $datasetId = $params['list']->getValue('dataset_id');
    
    // Link zur YForm-Datenbearbeitung
    $editUrl = rex_url::backendPage('yform/manager/data_edit', [
        'table_name' => $tablename,
        'func' => 'edit',
        'data_id' => $datasetId
    ]);
    
    return '<a href="' . $editUrl . '">' . $datasetId . '</a>';
});

// SEO-Daten
$list->setColumnLabel('seo_title', $this->i18n('urlaub_seo_title'));
$list->setColumnFormat('seo_title', 'custom', function($params) {
    $title = $params['list']->getValue('seo_title');
    if (empty($title)) {
        return '<span class="text-muted">-</span>';
    }
    return '<span title="' . htmlspecialchars($title) . '">' . 
           htmlspecialchars(mb_substr($title, 0, 30) . (mb_strlen($title) > 30 ? '...' : '')) . '</span>';
});

$list->setColumnLabel('seo_description', $this->i18n('urlaub_seo_description'));
$list->setColumnFormat('seo_description', 'custom', function($params) {
    $description = $params['list']->getValue('seo_description');
    if (empty($description)) {
        return '<span class="text-muted">-</span>';
    }
    return '<span title="' . htmlspecialchars($description) . '">' . 
           htmlspecialchars(mb_substr($description, 0, 40) . (mb_strlen($description) > 40 ? '...' : '')) . '</span>';
});

// Sitemap
$list->setColumnLabel('in_sitemap', $this->i18n('urlaub_in_sitemap'));
$list->setColumnFormat('in_sitemap', 'custom', function($params) {
    $inSitemap = (bool) $params['list']->getValue('in_sitemap');
    if ($inSitemap) {
        return '<span class="label label-success"><i class="rex-icon fa-check"></i> ' . 
               rex_i18n::msg('urlaub_yes') . '</span>';
    } else {
        return '<span class="label label-default"><i class="rex-icon fa-times"></i> ' . 
               rex_i18n::msg('urlaub_no') . '</span>';
    }
});

// Artikel-Info
$list->setColumnLabel('article_id', $this->i18n('urlaub_article'));
$list->setColumnFormat('article_id', 'custom', function($params) {
    $articleId = $params['list']->getValue('article_id');
    $clangId = $params['list']->getValue('clang_id');
    
    if ($articleId > 0) {
        $article = rex_article::get($articleId, $clangId);
        if ($article) {
            $editUrl = rex_url::backendPage('content/edit', [
                'article_id' => $articleId,
                'clang' => $clangId
            ]);
            return '<a href="' . $editUrl . '">' . 
                   htmlspecialchars($article->getName()) . ' (ID: ' . $articleId . ')</a>';
        }
    }
    return '<span class="text-muted">-</span>';
});

// Actions Spalte
$list->addColumn('actions', $this->i18n('urlaub_actions'), -1);
$list->setColumnFormat('actions', 'custom', function($params) {
    $urlId = $params['list']->getValue('id');
    $url = $params['list']->getValue('url');
    $fullUrl = rex_yrewrite::getFullPath($url);
    
    $actions = '';
    
    // Frontend-Link
    $actions .= '<a href="' . htmlspecialchars($fullUrl) . '" target="_blank" class="btn btn-xs btn-default" title="' . rex_i18n::msg('urlaub_view_frontend') . '">';
    $actions .= '<i class="rex-icon rex-icon-frontend"></i></a> ';
    
    // Löschen-Button
    $deleteUrl = rex_url::currentBackendPage([
        'action' => 'delete_url', 
        'url_id' => $urlId
    ] + rex_csrf_token::factory('urlaub_url_delete')->getUrlParams());
    
    $actions .= '<a href="' . $deleteUrl . '" class="btn btn-xs btn-danger" ';
    $actions .= 'data-confirm="' . rex_i18n::msg('urlaub_url_delete_confirm') . '" ';
    $actions .= 'title="' . rex_i18n::msg('urlaub_delete') . '">';
    $actions .= '<i class="rex-icon rex-icon-delete"></i></a>';
    
    return $actions;
});

// Filter nach Profil
$profiles = Profile::getAllRegisteredProfiles();
if (!empty($profiles)) {
    $profileOptions = ['' => rex_i18n::msg('urlaub_all_profiles')];
    foreach ($profiles as $profile) {
        $profileOptions[$profile->getKey()] = $profile->getKey();
    }
    
    $profileFilter = rex_request('profile_filter', 'string');
    if ($profileFilter) {
        $query .= ' WHERE profile_name = ' . rex_sql::factory()->escape($profileFilter);
        $list = rex_list::factory($query);
        // Spalten neu definieren da Liste neu erstellt wurde
        $list->addTableAttribute('class', 'table-striped');
        $list->setNoRowsMessage($this->i18n('urlaub_urls_empty'));
        $list->removeColumn('id');
        $list->removeColumn('url_hash');
        $list->removeColumn('profile_id');
    }
    
    // Filter-Formular oberhalb der Liste
    $filterForm = '<div class="panel panel-default">';
    $filterForm .= '<div class="panel-body">';
    $filterForm .= '<form method="get">';
    $filterForm .= '<input type="hidden" name="page" value="urlaub">';
    $filterForm .= '<input type="hidden" name="subpage" value="urls">';
    $filterForm .= '<div class="form-group">';
    $filterForm .= '<label for="profile_filter">' . rex_i18n::msg('urlaub_filter_by_profile') . '</label>';
    $filterForm .= '<select name="profile_filter" id="profile_filter" class="form-control" onchange="this.form.submit()">';
    
    foreach ($profileOptions as $value => $label) {
        $selected = ($value === $profileFilter) ? ' selected="selected"' : '';
        $filterForm .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    
    $filterForm .= '</select>';
    $filterForm .= '</div>';
    $filterForm .= '</form>';
    $filterForm .= '</div>';
    $filterForm .= '</div>';
    
    echo $filterForm;
}

// Statistiken
$totalUrls = rex_sql::factory()->getArray('SELECT COUNT(*) as total FROM ' . Generator::TABLE_NAME)[0]['total'] ?? 0;
$sitemapUrls = rex_sql::factory()->getArray('SELECT COUNT(*) as total FROM ' . Generator::TABLE_NAME . ' WHERE in_sitemap = 1')[0]['total'] ?? 0;

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<div class="panel panel-info">';
echo '<div class="panel-body">';
echo '<h4><i class="rex-icon fa-bar-chart"></i> ' . rex_i18n::msg('urlaub_statistics') . '</h4>';
echo '<p><strong>' . rex_i18n::msg('urlaub_total_urls') . ':</strong> ' . $totalUrls . '</p>';
echo '<p><strong>' . rex_i18n::msg('urlaub_sitemap_urls') . ':</strong> ' . $sitemapUrls . '</p>';
echo '<p><strong>' . rex_i18n::msg('urlaub_profiles_count') . ':</strong> ' . count($profiles) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Liste ausgeben
echo $list->get();
