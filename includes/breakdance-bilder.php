<?php
/**
 * Bilder im Breakdance-Elementbaum finden.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementbaum eines Beitrags, null wenn er nicht mit Breakdance gebaut ist.
 *
 * @return array|null
 */
function imm_sh_breakdance_baum($post_id)
{
    $raw = get_post_meta($post_id, '_breakdance_data', true);
    if (!$raw) {
        return null;
    }
    $data = is_array($raw) ? $raw : json_decode($raw, true);
    $baum = is_array($data) && !empty($data['tree_json_string']) ? json_decode($data['tree_json_string'], true) : null;

    return is_array($baum) && !empty($baum['root']) ? $baum : null;
}

/**
 * Mediathek-IDs aller Inhaltsbilder eines Breakdance-Beitrags.
 *
 * Erkennt jedes Element, das ein Bild als {id, url} speichert (Bild, Galerie, eigene
 * Elemente), und Fundamental-Bilder, die nur eine URL aus der Mediathek kennen.
 * Hintergrundbilder bleiben außen vor, weil sie meist Dekoration sind.
 *
 * @return int[]
 */
function imm_sh_breakdance_bild_ids($post_id)
{
    $post_id = (int) $post_id;
    $cache   = 'bilder_' . $post_id . '_' . get_post_modified_time('U', true, $post_id);
    $treffer = wp_cache_get($cache, 'imm_seo_helfer');
    if (is_array($treffer)) {
        return $treffer;
    }

    $baum = imm_sh_breakdance_baum($post_id);
    if (!$baum) {
        return [];
    }

    $ids  = [];
    $urls = [];
    $walk = function ($wert, $schluessel = '') use (&$walk, &$ids, &$urls) {
        if (!is_array($wert)) {
            return;
        }
        if (stripos((string) $schluessel, 'background') !== false) {
            return;
        }
        if (isset($wert['id'], $wert['url']) && is_numeric($wert['id']) && is_string($wert['url'])) {
            $ids[] = (int) $wert['id'];
        } elseif (isset($wert['url']) && is_string($wert['url']) && !isset($wert['id'])
            && preg_match('#/wp-content/uploads/.+\.(jpe?g|png|gif|webp|avif|svg)$#i', $wert['url'])) {
            $urls[] = $wert['url'];
        }
        foreach ($wert as $k => $kind) {
            $walk($kind, $k);
        }
    };
    $walk($baum['root']);

    foreach (array_unique($urls) as $url) {
        $ids[] = imm_sh_bild_id_aus_url($url);
    }

    $ids = array_values(array_unique(array_filter($ids, function ($id) {
        return $id > 0 && wp_attachment_is_image($id);
    })));

    wp_cache_set($cache, $ids, 'imm_seo_helfer', DAY_IN_SECONDS);

    return $ids;
}

/** Mediathek-ID zu einer Bild-URL, auch für skalierte Varianten (-scaled, -800x600). */
function imm_sh_bild_id_aus_url($url)
{
    $id = attachment_url_to_postid($url);
    if (!$id) {
        $id = attachment_url_to_postid(preg_replace('/-(scaled|\d+x\d+)(\.\w+)$/', '$2', $url));
    }

    return (int) $id;
}
