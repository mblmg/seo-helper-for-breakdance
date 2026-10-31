<?php
/**
 * Einstellungen und Voraussetzungen.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gespeicherte Einstellungen mit Standardwerten.
 *
 * kapitel_bereiche: IDs der Elternseiten, deren Unterseiten eine Kapitel-Navigation bekommen.
 * Neue Bereiche sind aus, bis sie jemand einschaltet.
 *
 * @return array{sitemap_bilder: bool, hauptbild: bool, kapitel: bool, kapitel_bereiche: int[]}
 */
function imm_sh_optionen()
{
    $standard = [
        'sitemap_bilder'   => true,
        'hauptbild'        => true,
        'kapitel'          => false,
        'kapitel_bereiche' => [],
    ];
    $gespeichert = get_option(IMM_SH_OPTION, []);
    $o           = array_merge($standard, is_array($gespeichert) ? $gespeichert : []);

    $o['kapitel_bereiche'] = array_values(array_filter(array_map('intval', (array) $o['kapitel_bereiche'])));

    return $o;
}

/**
 * Ist ein Modul eingeschaltet und auf dieser Website lauffähig?
 */
function imm_sh_an($modul)
{
    $o = imm_sh_optionen();
    if (empty($o[$modul])) {
        return false;
    }

    return imm_sh_fehlt($modul) === [];
}

/** Yoast SEO aktiv? */
function imm_sh_hat_yoast()
{
    return defined('WPSEO_VERSION');
}

/** Breakdance aktiv? */
function imm_sh_hat_breakdance()
{
    return defined('__BREAKDANCE_VERSION') || function_exists('\\Breakdance\\Data\\get_meta');
}

/**
 * Welche Plugins fehlen einem Modul auf dieser Website?
 *
 * @return string[] Namen der fehlenden Plugins, leer wenn alles da ist.
 */
function imm_sh_fehlt($modul)
{
    $braucht = [
        'sitemap_bilder' => ['breakdance', 'yoast'],
        'hauptbild'      => ['yoast'],
        'kapitel'        => ['breakdance'],
        'autor'          => ['breakdance'],
    ];
    $fehlt = [];
    foreach ($braucht[$modul] ?? [] as $plugin) {
        if ($plugin === 'yoast' && !imm_sh_hat_yoast()) {
            $fehlt[] = 'Yoast SEO';
        }
        if ($plugin === 'breakdance' && !imm_sh_hat_breakdance()) {
            $fehlt[] = 'Breakdance';
        }
    }

    return $fehlt;
}

/** Beim Aktivieren die Standardwerte anlegen, ohne Bestehendes zu überschreiben. */
function imm_sh_aktivieren()
{
    if (get_option(IMM_SH_OPTION) === false) {
        add_option(IMM_SH_OPTION, imm_sh_optionen());
    }
}
