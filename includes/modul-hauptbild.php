<?php
/**
 * Modul: Eigenes Yoast-Vorschaubild als Hauptbild im Schema.
 *
 * Yoast nennt Google als Hauptbild (primaryImageOfPage) nur das Beitragsbild. Seiten ohne
 * Beitragsbild, aber mit eigenem Social-Media-Bild, gehen dabei leer aus.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Eigenes Yoast-Vorschaubild eines Beitrags, wenn er kein Beitragsbild hat. */
function imm_sh_hauptbild_id_fuer($post_id)
{
    if (has_post_thumbnail($post_id)) {
        return 0;
    }
    $id = (int) get_post_meta($post_id, '_yoast_wpseo_opengraph-image-id', true);

    return $id && wp_attachment_is_image($id) ? $id : 0;
}

/** Hauptbild der aktuell aufgerufenen Seite, 0 wenn das Modul nichts tun soll. */
function imm_sh_schema_hauptbild_id()
{
    if (!imm_sh_an('hauptbild') || !is_singular()) {
        return 0;
    }

    return imm_sh_hauptbild_id_fuer(get_queried_object_id());
}

add_filter('wpseo_schema_webpage', function ($data) {
    $bild_id = imm_sh_schema_hauptbild_id();
    if (!$bild_id || !empty($data['primaryImageOfPage'])) {
        return $data;
    }
    $bild_ref                   = ['@id' => get_permalink(get_queried_object_id()) . '#primaryimage'];
    $data['primaryImageOfPage'] = $bild_ref;
    $data['image']              = $bild_ref;
    $data['thumbnailUrl']       = wp_get_attachment_url($bild_id);

    return $data;
});

add_filter('wpseo_schema_graph', function ($graph) {
    $bild_id = imm_sh_schema_hauptbild_id();
    if (!$bild_id || !is_array($graph)) {
        return $graph;
    }
    $bild_at = get_permalink(get_queried_object_id()) . '#primaryimage';
    foreach ($graph as $teil) {
        if (($teil['@id'] ?? '') === $bild_at) {
            return $graph;
        }
    }
    $meta    = wp_get_attachment_metadata($bild_id);
    $graph[] = array_filter([
        '@type'      => 'ImageObject',
        '@id'        => $bild_at,
        'inLanguage' => get_bloginfo('language'),
        'url'        => wp_get_attachment_url($bild_id),
        'contentUrl' => wp_get_attachment_url($bild_id),
        'width'      => $meta['width'] ?? null,
        'height'     => $meta['height'] ?? null,
        'caption'    => (string) get_post_meta($bild_id, '_wp_attachment_image_alt', true),
    ]);

    return $graph;
});

/**
 * Beiträge, bei denen das Modul gerade greift (für die Einstellungsseite).
 *
 * @return array<int, string> ID => Titel
 */
function imm_sh_hauptbild_betroffen()
{
    $ids = get_posts([
        'post_type'      => array_values(array_diff(get_post_types(['public' => true]), ['attachment'])),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => '_yoast_wpseo_opengraph-image-id',
    ]);
    $treffer = [];
    foreach ($ids as $id) {
        if (imm_sh_hauptbild_id_fuer($id)) {
            $treffer[$id] = get_the_title($id);
        }
    }

    return $treffer;
}
