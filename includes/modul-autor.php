<?php
/**
 * Modul: Autoren-Bio als Breakdance-Datenfeld mit echten Absätzen.
 *
 * Breakdances eigenes Feld „Author Bio“ gibt den Text ohne Absätze aus. Dieses Feld setzt
 * Leerzeilen in Absätze um. Im Text-Element als Tag „div“ wählen, sonst entstehen <p> in <p>.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    if (!function_exists('\\Breakdance\\DynamicData\\registerField')
        || !class_exists('\\Breakdance\\DynamicData\\StringField')) {
        return;
    }

    \Breakdance\DynamicData\registerField(new class extends \Breakdance\DynamicData\StringField {
        public function label()
        {
            return __('Autoren-Bio in Absätzen', 'imm-seo-helfer');
        }

        public function category()
        {
            return __('SEO-Helfer', 'imm-seo-helfer');
        }

        public function slug()
        {
            return 'imm_sh_autor_bio';
        }

        public function handler($attributes): \Breakdance\DynamicData\StringData
        {
            $beitrag = get_post();
            if (!$beitrag) {
                return \Breakdance\DynamicData\StringData::emptyString();
            }
            $bio = trim((string) get_the_author_meta('description', $beitrag->post_author));
            if ($bio === '') {
                return \Breakdance\DynamicData\StringData::emptyString();
            }

            return \Breakdance\DynamicData\StringData::fromString(wpautop(wp_kses_post($bio), false));
        }
    });
}, 20);
