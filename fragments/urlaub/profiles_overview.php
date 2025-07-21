<?php

/** @var rex_fragment $this */

use Alexplusde\Urlaub\Profile;
use Alexplusde\Urlaub\Generator;

$profiles = $this->getVar('profiles', []);

if (!function_exists('makeUrlaubLabel')) {
    /**
     * Generates a badge HTML element.
     *
     * @param string $value The text to display inside the badge.
     * @param string $class The CSS class for styling the badge.
     * @param string|null $icon Optional icon class
     * @return string The HTML for the badge.
     */
    function makeUrlaubLabel(string $value, string $class = 'info', ?string $icon = null): string
    {
        $iconHtml = $icon ? '<i class="rex-icon ' . htmlspecialchars($icon) . '"></i> ' : '';
        return '<span class="label label-' . htmlspecialchars($class) . '">' . $iconHtml . htmlspecialchars($value) . '</span>';
    }
}

foreach ($profiles as $profileKey => $profile): 
    // URL-Anzahl für dieses Profil ermitteln
    $generator = new Generator();
    $urls = $generator->getUrlsForProfile($profile);
    $urlCount = count($urls);
    
    // YForm-Tabelle prüfen
    $table = null;
    try {
        $table = rex_yform_manager_table::get($profile->getTableName());
    } catch (Exception $e) {
        // Tabelle existiert nicht
    }
    
    // Artikel prüfen
    $article = null;
    if ($profile->getArticleId()) {
        $article = rex_article::get($profile->getArticleId(), $profile->getClangId());
    }
    ?>
    
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">
            <strong><?= htmlspecialchars($profile->getKey()) ?></strong>
            <span class="text-muted"><?= htmlspecialchars($profile->getNamespace()) ?></span>
            <?php if ($urlCount > 0): ?>
                <span class="badge"><?= $urlCount ?> URLs</span>
            <?php endif; ?>
            
            <!-- Regenerate Button -->
            <a href="<?= rex_url::currentBackendPage(['action' => 'regenerate_profile', 'profile_key' => $profile->getKey()] + rex_csrf_token::factory('urlaub_regenerate')->getUrlParams()) ?>" 
               class="btn btn-primary btn-xs pull-right"
               data-confirm="<?= rex_i18n::msg('urlaub_regenerate_profile_confirm', $profile->getKey()) ?>">
                <i class="rex-icon fa-refresh"></i> <?= rex_i18n::msg('urlaub_regenerate') ?>
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="row">
            <!-- Tabellen-Info -->
            <div class="col-md-6">
                <h5><i class="rex-icon fa-database"></i> <?= rex_i18n::msg('urlaub_table_info') ?></h5>
                <p>
                    <strong><?= rex_i18n::msg('urlaub_table_name') ?>:</strong> 
                    <?= htmlspecialchars($profile->getTableName()) ?>
                    <?php if ($table): ?>
                        <?= makeUrlaubLabel(rex_i18n::msg('urlaub_table_exists'), 'success', 'fa-check') ?>
                        <a href="<?= rex_url::backendPage('yform/manager/data_edit', ['table_name' => $profile->getTableName()]) ?>" 
                           class="btn btn-xs btn-default">
                            <i class="rex-icon fa-list"></i> <?= rex_i18n::msg('urlaub_show_data') ?>
                        </a>
                    <?php else: ?>
                        <?= makeUrlaubLabel(rex_i18n::msg('urlaub_table_not_found'), 'danger', 'fa-times') ?>
                    <?php endif; ?>
                </p>
                
                <!-- Query Info -->
                <?php if ($profile->getQuery()): ?>
                    <p>
                        <strong><?= rex_i18n::msg('urlaub_query') ?>:</strong><br>
                        <small class="text-muted"><?= rex_i18n::msg('urlaub_query_configured') ?></small>
                        <?= makeUrlaubLabel(rex_i18n::msg('urlaub_query_active'), 'success', 'fa-filter') ?>
                    </p>
                <?php else: ?>
                    <p>
                        <strong><?= rex_i18n::msg('urlaub_query') ?>:</strong><br>
                        <?= makeUrlaubLabel(rex_i18n::msg('urlaub_query_none'), 'warning', 'fa-exclamation-triangle') ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Artikel-Info -->
            <div class="col-md-6">
                <h5><i class="rex-icon rex-icon-article"></i> <?= rex_i18n::msg('urlaub_article_info') ?></h5>
                <?php if ($article): ?>
                    <p>
                        <strong><?= htmlspecialchars($article->getName()) ?></strong><br>
                        <small class="text-muted">ID: <?= $article->getId() ?>, <?= rex_clang::get($profile->getClangId())->getName() ?></small>
                    </p>
                    <p>
                        <a href="<?= $article->getUrl() ?>" class="btn btn-xs btn-default" target="_blank">
                            <i class="rex-icon rex-icon-frontend"></i> <?= rex_i18n::msg('urlaub_frontend') ?>
                        </a>
                        <a href="<?= rex_url::backendPage('content/edit', ['article_id' => $article->getId(), 'clang' => $profile->getClangId()]) ?>" 
                           class="btn btn-xs btn-default">
                            <i class="rex-icon rex-icon-backend"></i> <?= rex_i18n::msg('urlaub_backend') ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p>
                        <?= makeUrlaubLabel(rex_i18n::msg('urlaub_article_not_configured'), 'warning', 'fa-exclamation-triangle') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <!-- SEO-Konfiguration -->
            <div class="col-md-12">
                <h5><i class="rex-icon fa-google"></i> <?= rex_i18n::msg('urlaub_seo_config') ?></h5>
                <div class="row">
                    <div class="col-md-4">
                        <strong><?= rex_i18n::msg('urlaub_seo_title') ?>:</strong><br>
                        <?php if ($profile->getSeoTitle()): ?>
                            <code><?= htmlspecialchars($profile->getSeoTitle()) ?></code>
                        <?php else: ?>
                            <?= makeUrlaubLabel(rex_i18n::msg('urlaub_not_configured'), 'default', 'fa-minus') ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong><?= rex_i18n::msg('urlaub_seo_description') ?>:</strong><br>
                        <?php if ($profile->getSeoDescription()): ?>
                            <code><?= htmlspecialchars($profile->getSeoDescription()) ?></code>
                        <?php else: ?>
                            <?= makeUrlaubLabel(rex_i18n::msg('urlaub_not_configured'), 'default', 'fa-minus') ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong><?= rex_i18n::msg('urlaub_seo_image') ?>:</strong><br>
                        <?php if ($profile->getSeoImage()): ?>
                            <code><?= htmlspecialchars($profile->getSeoImage()) ?></code>
                        <?php else: ?>
                            <?= makeUrlaubLabel(rex_i18n::msg('urlaub_not_configured'), 'default', 'fa-minus') ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row" style="margin-top: 10px;">
                    <div class="col-md-6">
                        <strong><?= rex_i18n::msg('urlaub_sitemap') ?>:</strong><br>
                        <?php if (is_bool($profile->getAddToSitemap())): ?>
                            <?= $profile->getAddToSitemap() ? 
                                makeUrlaubLabel(rex_i18n::msg('urlaub_sitemap_always'), 'success', 'fa-check') : 
                                makeUrlaubLabel(rex_i18n::msg('urlaub_sitemap_never'), 'danger', 'fa-times') ?>
                        <?php else: ?>
                            <code><?= htmlspecialchars($profile->getAddToSitemap()) ?></code>
                            <?= makeUrlaubLabel(rex_i18n::msg('urlaub_sitemap_dynamic'), 'info', 'fa-code') ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong><?= rex_i18n::msg('urlaub_modify_date') ?>:</strong><br>
                        <?php if ($profile->getModifyDate()): ?>
                            <code><?= htmlspecialchars($profile->getModifyDate()) ?></code>
                        <?php else: ?>
                            <?= makeUrlaubLabel(rex_i18n::msg('urlaub_not_configured'), 'default', 'fa-minus') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- URL-Beispiele -->
        <?php if ($urlCount > 0): ?>
        <div class="row" style="margin-top: 15px;">
            <div class="col-md-12">
                <h5><i class="rex-icon fa-link"></i> <?= rex_i18n::msg('urlaub_generated_urls') ?></h5>
                <div class="well well-sm">
                    <?php 
                    $displayUrls = array_slice($urls, 0, 3); // Nur die ersten 3 URLs anzeigen
                    foreach ($displayUrls as $urlData): ?>
                        <p style="margin: 0;">
                            <code><?= htmlspecialchars($urlData['url']) ?></code>
                            <small class="text-muted"> → Dataset ID: <?= $urlData['dataset_id'] ?></small>
                        </p>
                    <?php endforeach; ?>
                    
                    <?php if ($urlCount > 3): ?>
                        <p style="margin: 5px 0 0 0;">
                            <small class="text-muted"><?= rex_i18n::msg('urlaub_and_more', $urlCount - 3) ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Code-Beispiele -->
        <div class="row" style="margin-top: 15px;">
            <div class="col-md-12">
                <h5><i class="rex-icon fa-code"></i> <?= rex_i18n::msg('urlaub_usage_examples') ?></h5>
                <div class="panel panel-default">
                    <div class="panel-body" style="padding: 10px;">
                        <small>
                            <strong><?= rex_i18n::msg('urlaub_url_creation') ?>:</strong><br>
                            <code>UrlResolver::getUrlForDataset('<?= htmlspecialchars($profile->getTableName()) ?>', $datasetId)</code><br><br>
                            
                            <strong><?= rex_i18n::msg('urlaub_url_resolution') ?>:</strong><br>
                            <code>UrlResolver::resolve($url)</code><br><br>
                            
                            <strong><?= rex_i18n::msg('urlaub_seo_data') ?>:</strong><br>
                            <code>UrlResolver::getSeoData($url)</code>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endforeach; ?>
