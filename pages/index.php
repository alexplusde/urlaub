<?php

/**
 * Urlaub Addon - Hauptseite
 */

/** @var rex_addon $this */

// Weiterleitung zur Profile-Seite
rex_response::sendRedirect(rex_url::backendPage('urlaub/profiles'));
