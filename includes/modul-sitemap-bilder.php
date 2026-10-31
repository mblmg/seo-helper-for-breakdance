<?php
/**
 * Modul: Breakdance-Bilder in der Yoast-Bilder-Sitemap.
 *
 * Yoast liest Bilder nur aus dem klassischen Beitragsinhalt. Breakdance speichert seine
 * Layouts woanders, deshalb fehlen diese Bilder sonst in der Sitemap.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wpseo_sitemap_urlimages', function ($images, $post_id) {
    if (!imm_sh_an('sitemap_bilder')) {
        return $images;
    }
    $images    = (array) $images;
    $vorhanden = wp_list_pluck($images, 'src');

    foreach (imm_sh_breakdance_bild_ids($post_id) as $id) {
        $src = wp_get_attachment_url($id);
        if (!$src || in_array($src, $vorhanden, true)) {
            continue;
        }
        $images[]    = [
            'src'   => $src,
            'title' => get_the_title($id),
            'alt'   => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
        ];
        $vorhanden[] = $src;
    }

    return $images;
}, 10, 2);

/**
 * Kennzahlen für die Einstellungsseite: Bilder und Beiträge, die das Modul ergänzt.
 * Wird zwölf Stunden zwischengespeichert, weil dafür jeder Elementbaum gelesen wird.
 *
 * @return array{bilder: int, beitraege: int, typen: string[]}
 */
function imm_sh_sitemap_kennzahlen($neu = false)
{
    $zahlen = $neu ? false : get_transient('imm_sh_sitemap_kennzahlen');
    if (is_array($zahlen)) {
        return $zahlen;
    }

    $typen  = array_values(array_diff(get_post_types(['public' => true]), ['attachment']));
    $zahlen = ['bilder' => 0, 'beitraege' => 0, 'typen' => []];
    $ids    = get_posts([
        'post_type'      => $typen,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => '_breakdance_data',
    ]);
    foreach ($ids as $id) {
        $n = count(imm_sh_breakdance_bild_ids($id));
        if ($n) {
            $zahlen['bilder'] += $n;
            $zahlen['beitraege']++;
            $zahlen['typen'][get_post_type($id)] = true;
        }
    }
    $zahlen['typen'] = array_keys($zahlen['typen']);
    set_transient('imm_sh_sitemap_kennzahlen', $zahlen, 12 * HOUR_IN_SECONDS);

    return $zahlen;
}

/** Sitemap-Kennzahlen neu berechnen, sobald sich ein Beitrag ändert. */
add_action('save_post', function () {
    delete_transient('imm_sh_sitemap_kennzahlen');
});
