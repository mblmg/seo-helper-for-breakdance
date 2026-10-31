<?php
/**
 * Modul: Kapitel-Navigation für Unterseiten.
 *
 * Ein Bereich ist eine Elternseite mit mindestens zwei veröffentlichten Unterseiten. Für
 * eingeschaltete Bereiche liefern vier Breakdance-Datenfelder Titel und Link des vorherigen
 * und des nächsten Kapitels. Die Reihenfolge ist die Seitenreihenfolge (menu_order).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Öffentliche, hierarchische Inhaltstypen (Seiten und ähnliche). */
function imm_sh_kapitel_typen()
{
    return array_values(get_post_types(['public' => true, 'hierarchical' => true]));
}

/**
 * Veröffentlichte Unterseiten einer Elternseite in Seitenreihenfolge.
 *
 * @return int[]
 */
function imm_sh_kapitel_von_eltern($eltern_id)
{
    $typ = get_post_type($eltern_id);
    if (!$typ) {
        return [];
    }

    return array_map('intval', get_posts([
        'post_type'      => $typ,
        'post_parent'    => (int) $eltern_id,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]));
}

/**
 * Alle möglichen Bereiche, die größten zuerst.
 *
 * @return array<int, int[]> Elternseiten-ID => Kapitel-IDs
 */
function imm_sh_kapitel_bereiche()
{
    $typen = imm_sh_kapitel_typen();
    if (!$typen) {
        return [];
    }
    global $wpdb;
    $platzhalter = implode(',', array_fill(0, count($typen), '%s'));
    $eltern      = $wpdb->get_col($wpdb->prepare(
        "SELECT post_parent FROM {$wpdb->posts}
         WHERE post_type IN ($platzhalter) AND post_status = 'publish' AND post_parent > 0
         GROUP BY post_parent HAVING COUNT(*) >= 2",
        $typen
    ));

    $bereiche = [];
    foreach ($eltern as $id) {
        if (get_post_status((int) $id) === 'publish') {
            $bereiche[(int) $id] = imm_sh_kapitel_von_eltern($id);
        }
    }
    uasort($bereiche, function ($a, $b) {
        return count($b) <=> count($a);
    });

    return $bereiche;
}

/**
 * Vorheriges (-1) oder nächstes (+1) Kapitel, 0 wenn es keins gibt oder der Bereich aus ist.
 */
function imm_sh_kapitel_nachbar($post_id, $richtung)
{
    if (!$post_id || !imm_sh_an('kapitel')) {
        return 0;
    }
    $eltern = (int) wp_get_post_parent_id($post_id);
    if (!$eltern || !in_array($eltern, imm_sh_optionen()['kapitel_bereiche'], true)) {
        return 0;
    }
    $ids = imm_sh_kapitel_von_eltern($eltern);
    $pos = array_search((int) $post_id, $ids, true);
    if ($pos === false || !isset($ids[$pos + $richtung])) {
        return 0;
    }

    return $ids[$pos + $richtung];
}

add_action('init', function () {
    if (!function_exists('\\Breakdance\\DynamicData\\registerField')
        || !class_exists('\\Breakdance\\DynamicData\\StringField')) {
        return;
    }

    $felder = [
        ['imm_sh_kapitel_zurueck_titel', __('Vorheriges Kapitel: Titel', 'imm-seo-helfer'), -1, 'string'],
        ['imm_sh_kapitel_zurueck_url', __('Vorheriges Kapitel: Link', 'imm-seo-helfer'), -1, 'url'],
        ['imm_sh_kapitel_weiter_titel', __('Nächstes Kapitel: Titel', 'imm-seo-helfer'), 1, 'string'],
        ['imm_sh_kapitel_weiter_url', __('Nächstes Kapitel: Link', 'imm-seo-helfer'), 1, 'url'],
    ];

    foreach ($felder as $feld) {
        \Breakdance\DynamicData\registerField(new class ($feld) extends \Breakdance\DynamicData\StringField {
            private $slug;
            private $label;
            private $richtung;
            private $typ;

            public function __construct($feld)
            {
                [$this->slug, $this->label, $this->richtung, $this->typ] = $feld;
            }

            public function label()
            {
                return $this->label;
            }

            public function category()
            {
                return __('SEO-Helfer', 'imm-seo-helfer');
            }

            public function slug()
            {
                return $this->slug;
            }

            public function returnTypes()
            {
                return [$this->typ];
            }

            public function handler($attributes): \Breakdance\DynamicData\StringData
            {
                $ziel = imm_sh_kapitel_nachbar(get_the_ID(), $this->richtung);
                if (!$ziel) {
                    return \Breakdance\DynamicData\StringData::emptyString();
                }
                $wert = $this->typ === 'url' ? get_permalink($ziel) : get_the_title($ziel);

                return \Breakdance\DynamicData\StringData::fromString(strip_shortcodes((string) $wert));
            }
        });
    }
}, 20);
