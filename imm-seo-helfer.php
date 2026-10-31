<?php
/**
 * Plugin Name:       SEO-Helfer für Breakdance
 * Plugin URI:        https://i-make.media
 * Description:       Schließt SEO-Lücken zwischen Breakdance und Yoast: Breakdance-Bilder in der Bilder-Sitemap, das Yoast-Vorschaubild als Hauptbild für Google, eine Kapitel-Navigation für Unterseiten und die Autoren-Bio als Breakdance-Datenfeld. Einstellungen unter Einstellungen → SEO-Helfer.
 * Version:           1.0.1
 * Author:            i-make.media
 * Author URI:        https://i-make.media
 * License:           GPL-2.0-or-later
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Text Domain:       imm-seo-helfer
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IMM_SH_VERSION', '1.0.1');
define('IMM_SH_OPTION', 'imm_seo_helfer');
define('IMM_SH_DATEI', __FILE__);
define('IMM_SH_PFAD', plugin_dir_path(__FILE__));
define('IMM_SH_URL', plugin_dir_url(__FILE__));

require_once IMM_SH_PFAD . 'includes/optionen.php';
require_once IMM_SH_PFAD . 'includes/breakdance-bilder.php';
require_once IMM_SH_PFAD . 'includes/modul-sitemap-bilder.php';
require_once IMM_SH_PFAD . 'includes/modul-hauptbild.php';
require_once IMM_SH_PFAD . 'includes/modul-kapitel.php';
require_once IMM_SH_PFAD . 'includes/modul-autor.php';

if (is_admin()) {
    require_once IMM_SH_PFAD . 'includes/admin.php';
}

register_activation_hook(__FILE__, 'imm_sh_aktivieren');
