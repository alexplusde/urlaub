<?php

/**
 * REDAXO Urlaub Addon - Database Table Definitions
 * 
 * @package redaxo\urlaub
 * @author  Alexander Walther
 * @since   2.0.0
 */

/* Vorerst keine Profile in der Datenbank über GUI erstellen */
/*
rex_sql_table::get(rex::getTable('urlaub_profile'))
    ->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('profile_name', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('table_parameters', 'text', true))
    ->ensureColumn(new rex_sql_column('namespace', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('url_param_key', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('url_param_value', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('url_pattern', 'varchar(500)', true))
    ->ensureColumn(new rex_sql_column('seo_method', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('seo_config', 'text', true))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, '1'))
    ->ensureColumn(new rex_sql_column('created_at', 'datetime', false))
    ->ensureColumn(new rex_sql_column('updated_at', 'datetime', false))
    ->setPrimaryKey('id')
    ->ensureIndex(new rex_sql_index('profile_name', ['profile_name'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('namespace', ['namespace']))
    ->ensureIndex(new rex_sql_index('table_name', ['table_name']))
    ->ensureIndex(new rex_sql_index('status', ['status']))
    ->ensureIndex(new rex_sql_index('created_at', ['created_at']))
    ->ensure();
*/
// URL2 Generator Tabelle
rex_sql_table::get(rex::getTable('urlaub_generator'))
    ->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('profile_name', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('profile_id', 'int(11)', false))
    ->ensureColumn(new rex_sql_column('data_id', 'int(11)', false))
    ->ensureColumn(new rex_sql_column('clang_id', 'int(11)', false, '1'))
    ->ensureColumn(new rex_sql_column('url', 'varchar(1000)', false))
    ->ensureColumn(new rex_sql_column('url_hash', 'varchar(64)', false))
    ->ensureColumn(new rex_sql_column('redirect_id', 'int(11)', true))
    ->ensureColumn(new rex_sql_column('seo_title', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('seo_description', 'text', true))
    ->ensureColumn(new rex_sql_column('seo_keywords', 'varchar(500)', true))
    ->ensureColumn(new rex_sql_column('canonical_url', 'varchar(1000)', true))
    ->ensureColumn(new rex_sql_column('robots', 'varchar(100)', true, 'index,follow'))
    ->ensureColumn(new rex_sql_column('last_modified', 'datetime', false))
    ->ensureColumn(new rex_sql_column('created_at', 'datetime', false))
    ->ensureColumn(new rex_sql_column('updated_at', 'datetime', false))
    ->setPrimaryKey('id')
    ->ensureIndex(new rex_sql_index('url_hash', ['url_hash'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('profile_data', ['profile_name', 'data_id', 'clang_id'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('profile_name', ['profile_name']))
    ->ensureIndex(new rex_sql_index('profile_id', ['profile_id']))
    ->ensureIndex(new rex_sql_index('data_id', ['data_id']))
    ->ensureIndex(new rex_sql_index('clang_id', ['clang_id']))
    ->ensureIndex(new rex_sql_index('url', ['url']))
    ->ensureIndex(new rex_sql_index('redirect_id', ['redirect_id']))
    ->ensureIndex(new rex_sql_index('last_modified', ['last_modified']))
    ->ensureIndex(new rex_sql_index('created_at', ['created_at']))
    ->ensure();
// Foreign Key Constraints hinzufügen (falls InnoDB Engine)
$sql = rex_sql::factory();
/*
// url2_generator.profile_id -> url2_profile.id
$sql->setQuery('
    ALTER TABLE `' . rex::getTable('url2_generator') . '` 
    ADD CONSTRAINT `fk_url2_generator_profile` 
    FOREIGN KEY (`profile_id`) 
    REFERENCES `' . rex::getTable('url2_profile') . '` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
');

// url2_statistics.url_id -> url2_generator.id  
$sql->setQuery('
    ALTER TABLE `' . rex::getTable('url2_statistics') . '` 
    ADD CONSTRAINT `fk_url2_statistics_url` 
    FOREIGN KEY (`url_id`) 
    REFERENCES `' . rex::getTable('url2_generator') . '` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
');

// Trigger für automatische Zeitstempel erstellen
$sql->setQuery('
    CREATE TRIGGER `tr_url2_profile_updated` 
    BEFORE UPDATE ON `' . rex::getTable('url2_profile') . '`
    FOR EACH ROW 
    SET NEW.updated_at = NOW()
');
;
$sql->setQuery('
    CREATE TRIGGER `tr_url2_generator_updated` 
    BEFORE UPDATE ON `' . rex::getTable('url2_generator') . '`
    FOR EACH ROW 
    SET NEW.updated_at = NOW()
');
*/
