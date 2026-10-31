<?php
/**
 * Updates über GitHub.
 *
 * Neue Versionen kommen aus den Releases von github.com/mblmg/seo-helper-for-breakdance.
 * Die Bibliothek „Plugin Update Checker“ fragt sie alle zwölf Stunden ab und meldet sie
 * WordPress wie ein Update aus dem Plugin-Verzeichnis. Automatische Updates sind beim
 * Aktivieren eingeschaltet und lassen sich in der Plugin-Liste wie gewohnt abschalten.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IMM_SH_REPO', 'https://github.com/mblmg/seo-helper-for-breakdance/');

require_once IMM_SH_PFAD . 'lib/plugin-update-checker/plugin-update-checker.php';

$imm_sh_updates = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    IMM_SH_REPO,
    IMM_SH_DATEI,
    'imm-seo-helfer'
);

// Das Release-ZIP enthält nur die Plugin-Dateien. Fehlt es, nimmt WordPress den Quellcode des Tags.
$imm_sh_updates->getVcsApi()->enableReleaseAssets('/imm-seo-helfer\.zip($|[?&#])/i');

/** Automatische Updates für dieses Plugin einschalten (nur wenn noch nicht gesetzt). */
function imm_sh_auto_updates_einschalten()
{
    $auto = (array) get_site_option('auto_update_plugins', []);
    $name = plugin_basename(IMM_SH_DATEI);
    if (!in_array($name, $auto, true)) {
        $auto[] = $name;
        update_site_option('auto_update_plugins', $auto);
    }
}
