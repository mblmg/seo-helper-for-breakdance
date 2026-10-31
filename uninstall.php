<?php
/**
 * Beim Löschen des Plugins die eigenen Einstellungen entfernen.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('imm_seo_helfer');
delete_transient('imm_sh_sitemap_kennzahlen');
