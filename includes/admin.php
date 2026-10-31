<?php
/**
 * Einstellungsseite unter Einstellungen → SEO-Helfer.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_options_page(
        __('SEO-Helfer', 'imm-seo-helfer'),
        __('SEO-Helfer', 'imm-seo-helfer'),
        'manage_options',
        'imm-seo-helfer',
        'imm_sh_seite'
    );
});

add_filter('plugin_action_links_' . plugin_basename(IMM_SH_DATEI), function ($links) {
    array_unshift($links, sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=imm-seo-helfer')),
        esc_html__('Einstellungen', 'imm-seo-helfer')
    ));

    return $links;
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_imm-seo-helfer') {
        return;
    }
    wp_enqueue_style('imm-sh-admin', IMM_SH_URL . 'assets/admin.css', [], IMM_SH_VERSION);
    wp_enqueue_script('imm-sh-admin', IMM_SH_URL . 'assets/admin.js', [], IMM_SH_VERSION, true);
});

/** Gängige Seiten-Caches leeren, damit Änderungen sofort sichtbar sind. */
function imm_sh_seitencache_leeren()
{
    if (function_exists('WP_Optimize') && method_exists(WP_Optimize(), 'get_page_cache')) {
        WP_Optimize()->get_page_cache()->purge();
    }
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }
    do_action('litespeed_purge_all');
}

add_action('admin_post_imm_sh_speichern', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Dafür fehlen dir die Rechte.', 'imm-seo-helfer'));
    }
    check_admin_referer('imm_sh_speichern');

    $moeglich = array_keys(imm_sh_kapitel_bereiche());
    $bereiche = array_values(array_intersect(array_map('intval', (array) ($_POST['kapitel_bereiche'] ?? [])), $moeglich));
    $o        = imm_sh_optionen();

    // Gesperrte Schalter (fehlendes Plugin) behalten ihren Wert für später.
    foreach (['sitemap_bilder', 'hauptbild', 'kapitel', 'firma'] as $modul) {
        if (imm_sh_fehlt($modul) === []) {
            $o[$modul] = !empty($_POST[$modul]);
        }
    }
    if (imm_sh_fehlt('kapitel') === []) {
        $o['kapitel_bereiche'] = $bereiche;
    }

    // Firmendaten auch speichern, wenn das Modul gerade aus ist.
    $eingabe = wp_unslash((array) ($_POST['firma_daten'] ?? []));
    $daten   = [];
    foreach (imm_sh_firma_standard() as $feld => $standard) {
        $wert = (string) ($eingabe[$feld] ?? $standard);
        $daten[$feld] = in_array($feld, ['einzugsgebiet', 'oeffnungszeiten', 'profile', 'themen', 'sprachen', 'angebote'], true)
            ? sanitize_textarea_field($wert)
            : sanitize_text_field($wert);
    }
    if (!isset(imm_sh_firma_typen()[$daten['typ']])) {
        $daten['typ'] = 'LocalBusiness';
    }
    $daten['land']     = strtoupper(substr($daten['land'], 0, 2));
    $o['firma_daten']  = $daten;
    update_option(IMM_SH_OPTION, $o);

    delete_transient('imm_sh_sitemap_kennzahlen');
    imm_sh_seitencache_leeren();

    wp_safe_redirect(add_query_arg(['page' => 'imm-seo-helfer', 'gespeichert' => 1], admin_url('options-general.php')));
    exit;
});

/** Hinweis, wenn einem Modul ein Plugin fehlt. Gibt true zurück, wenn etwas fehlt. */
function imm_sh_fehlt_hinweis($modul)
{
    $fehlt = imm_sh_fehlt($modul);
    if (!$fehlt) {
        return false;
    }
    printf(
        '<p class="imm-sh__fehlt">%s</p>',
        esc_html(sprintf(
            /* translators: %s: Plugin-Namen */
            __('Braucht %s. Auf dieser Website ist das nicht aktiv, deshalb ruht dieser Helfer.', 'imm-seo-helfer'),
            implode(' und ', $fehlt)
        ))
    );

    return true;
}

/** Ein Schalter mit Titel. */
function imm_sh_schalter($name, $titel, $an, $gesperrt, $steuert = '')
{
    ?>
    <label class="imm-sh__schalter<?php echo $gesperrt ? ' ist-gesperrt' : ''; ?>">
        <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1"
            <?php checked($an && !$gesperrt); ?>
            <?php disabled($gesperrt); ?>
            <?php echo $steuert ? 'data-steuert="' . esc_attr($steuert) . '"' : ''; ?>>
        <span class="imm-sh__regler" aria-hidden="true"></span>
        <span class="imm-sh__name"><?php echo esc_html($titel); ?></span>
    </label>
    <?php
}

/** Darstellung der Einstellungsseite */
function imm_sh_seite()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $o        = imm_sh_optionen();
    $bereiche = imm_sh_hat_breakdance() ? imm_sh_kapitel_bereiche() : [];
    ?>
    <div class="wrap imm-sh">
        <h1 class="imm-sh__titel"><?php esc_html_e('SEO-Helfer', 'imm-seo-helfer'); ?></h1>
        <p class="imm-sh__intro"><?php esc_html_e('Kleine Helfer, die Lücken zwischen Breakdance und Yoast SEO schließen, damit Google und KI-Suchen die Website besser verstehen. Jeder lässt sich einzeln abschalten.', 'imm-seo-helfer'); ?></p>

        <ul class="imm-sh__umgebung" aria-label="<?php esc_attr_e('Gefundene Plugins', 'imm-seo-helfer'); ?>">
            <li class="<?php echo imm_sh_hat_breakdance() ? 'ist-da' : 'ist-weg'; ?>">
                Breakdance <span><?php echo imm_sh_hat_breakdance() ? esc_html__('aktiv', 'imm-seo-helfer') : esc_html__('nicht aktiv', 'imm-seo-helfer'); ?></span>
            </li>
            <li class="<?php echo imm_sh_hat_yoast() ? 'ist-da' : 'ist-weg'; ?>">
                Yoast SEO <span><?php echo imm_sh_hat_yoast() ? esc_html__('aktiv', 'imm-seo-helfer') : esc_html__('nicht aktiv', 'imm-seo-helfer'); ?></span>
            </li>
        </ul>

        <?php if (!empty($_GET['gespeichert'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Einstellungen gespeichert. Der Seiten-Cache wurde geleert.', 'imm-seo-helfer'); ?></p></div>
        <?php endif; ?>

        <form class="imm-sh__karte" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="imm_sh_speichern">
            <?php wp_nonce_field('imm_sh_speichern'); ?>

            <h2 class="imm-sh__abschnitt"><?php esc_html_e('Was der Helfer tut', 'imm-seo-helfer'); ?></h2>

            <?php /* Bilder in der Sitemap */ ?>
            <div class="imm-sh__zeile">
                <?php
                $gesperrt = imm_sh_fehlt('sitemap_bilder') !== [];
                imm_sh_schalter('sitemap_bilder', __('Bilder für die Bildersuche', 'imm-seo-helfer'), $o['sitemap_bilder'], $gesperrt);
                ?>
                <p class="imm-sh__text"><?php esc_html_e('Yoast übersieht Bilder in Breakdance-Layouts. Der Helfer trägt sie in die Sitemap ein, damit sie in der Google-Bildersuche auftauchen können. Hintergrundbilder bleiben draußen.', 'imm-seo-helfer'); ?></p>
                <?php if (!imm_sh_fehlt_hinweis('sitemap_bilder')) : $z = imm_sh_sitemap_kennzahlen(); ?>
                    <p class="imm-sh__stand">
                        <?php
                        printf(
                            /* translators: 1: Anzahl Bilder, 2: Anzahl Beiträge */
                            esc_html__('Gerade ergänzt: %1$s Bilder auf %2$s Seiten und Beiträgen.', 'imm-seo-helfer'),
                            '<strong>' . (int) $z['bilder'] . '</strong>',
                            (int) $z['beitraege']
                        );
                        ?>
                        <a href="<?php echo esc_url(home_url('/sitemap_index.xml')); ?>" target="_blank" rel="noopener"><?php esc_html_e('Sitemap ansehen', 'imm-seo-helfer'); ?></a>
                    </p>
                <?php endif; ?>
            </div>

            <?php /* Hauptbild */ ?>
            <div class="imm-sh__zeile">
                <?php
                $gesperrt = imm_sh_fehlt('hauptbild') !== [];
                imm_sh_schalter('hauptbild', __('Vorschaubild als Hauptbild für Google', 'imm-seo-helfer'), $o['hauptbild'], $gesperrt);
                ?>
                <p class="imm-sh__text"><?php esc_html_e('Hat eine Seite kein Beitragsbild, aber in Yoast ein eigenes Social-Media-Bild, nennt der Helfer dieses Bild Google als Hauptbild der Seite.', 'imm-seo-helfer'); ?></p>
                <?php if (!imm_sh_fehlt_hinweis('hauptbild')) : $betroffen = imm_sh_hauptbild_betroffen(); ?>
                    <p class="imm-sh__stand">
                        <?php if ($betroffen) : ?>
                            <?php esc_html_e('Betrifft gerade:', 'imm-seo-helfer'); ?>
                            <?php
                            $links = [];
                            foreach ($betroffen as $id => $titel) {
                                $links[] = '<a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html($titel) . '</a>';
                            }
                            echo implode(', ', $links); // phpcs:ignore WordPress.Security.EscapeOutput -- oben escaped
                            ?>
                        <?php else : ?>
                            <?php esc_html_e('Betrifft gerade keine Seite. Dafür in der Yoast-Box unter „Social“ ein eigenes Bild festlegen.', 'imm-seo-helfer'); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php /* Kapitel-Navigation */ ?>
            <div class="imm-sh__zeile">
                <?php
                $gesperrt = imm_sh_fehlt('kapitel') !== [];
                imm_sh_schalter('kapitel', __('Kapitel-Navigation', 'imm-seo-helfer'), $o['kapitel'], $gesperrt, 'imm-sh-bereiche');
                ?>
                <p class="imm-sh__text"><?php esc_html_e('Unterseiten bekommen Links zum vorherigen und zum nächsten Kapitel desselben Bereichs. Das hilft Lesern weiter und zeigt Suchmaschinen, wie die Seiten zusammenhängen. Die Links platzierst du selbst in einer Breakdance-Vorlage, mit den Datenfeldern unten.', 'imm-seo-helfer'); ?></p>
                <?php if (!imm_sh_fehlt_hinweis('kapitel')) : ?>
                    <?php if ($bereiche) : ?>
                        <fieldset class="imm-sh__bereiche" id="imm-sh-bereiche">
                            <legend><?php esc_html_e('Für diese Bereiche', 'imm-seo-helfer'); ?></legend>
                            <?php foreach ($bereiche as $eltern => $kapitel) : ?>
                                <label class="imm-sh__bereich">
                                    <input type="checkbox" name="kapitel_bereiche[]" value="<?php echo (int) $eltern; ?>" <?php checked(in_array($eltern, $o['kapitel_bereiche'], true)); ?>>
                                    <?php echo esc_html(get_the_title($eltern)); ?>
                                    <span class="imm-sh__anzahl">
                                        <?php
                                        /* translators: %d: Anzahl Kapitel */
                                        echo esc_html(sprintf(_n('%d Kapitel', '%d Kapitel', count($kapitel), 'imm-seo-helfer'), count($kapitel)));
                                        ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="imm-sh__stand">
                            <?php esc_html_e('Die Reihenfolge der Kapitel kommt aus der Seitenliste.', 'imm-seo-helfer'); ?>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=page&orderby=menu_order%20title&order=asc')); ?>"><?php esc_html_e('Zur Seitenliste', 'imm-seo-helfer'); ?></a>
                        </p>
                    <?php else : ?>
                        <p class="imm-sh__stand"><?php esc_html_e('Noch kein Bereich auf dieser Website. Sobald eine Seite mindestens zwei veröffentlichte Unterseiten hat, erscheint sie hier zum Einschalten.', 'imm-seo-helfer'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php /* Firmenprofil */ ?>
            <div class="imm-sh__zeile">
                <?php
                $gesperrt = imm_sh_fehlt('firma') !== [];
                imm_sh_schalter('firma', __('Firmenprofil für Google', 'imm-seo-helfer'), $o['firma'], $gesperrt, 'imm-sh-firma');
                $f = imm_sh_firma_daten();
                ?>
                <p class="imm-sh__text"><?php esc_html_e('Yoast meldet Google nur eine allgemeine Organisation. Der Helfer ergänzt, was die lokale Suche und KI-Suchen brauchen: Art des Unternehmens, Adresse, Einzugsgebiet, Öffnungszeiten, Profile wie das Google-Unternehmensprofil und Angebote. Name, Logo, Beschreibung, Telefon und E-Mail kommen aus den Yoast-Einstellungen. Besucher sehen davon nichts.', 'imm-seo-helfer'); ?></p>
                <?php if (!imm_sh_fehlt_hinweis('firma')) : ?>
                    <?php if (!imm_sh_firma_yoast_organisation()) : ?>
                        <p class="imm-sh__fehlt">
                            <?php esc_html_e('In Yoast ist die Website noch nicht als Organisation mit Namen eingetragen. Ohne das gibt es kein Firmenprofil, das der Helfer ergänzen kann.', 'imm-seo-helfer'); ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wpseo_page_settings#/site-representation')); ?>"><?php esc_html_e('Zur Yoast-Einstellung', 'imm-seo-helfer'); ?></a>
                        </p>
                    <?php elseif ($o['firma']) : ?>
                        <p class="imm-sh__stand">
                            <?php esc_html_e('Aktiv auf jeder Seite der Website.', 'imm-seo-helfer'); ?>
                            <a href="<?php echo esc_url('https://search.google.com/test/rich-results?url=' . rawurlencode(home_url('/'))); ?>" target="_blank" rel="noopener"><?php esc_html_e('Bei Google prüfen', 'imm-seo-helfer'); ?></a>
                        </p>
                    <?php endif; ?>

                    <fieldset class="imm-sh__firma" id="imm-sh-firma">
                        <legend><?php esc_html_e('Angaben zum Unternehmen', 'imm-seo-helfer'); ?></legend>

                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-typ"><?php esc_html_e('Art des Unternehmens', 'imm-seo-helfer'); ?></label>
                            <select id="imm-sh-typ" name="firma_daten[typ]">
                                <?php foreach (imm_sh_firma_typen() as $typ => $titel) : ?>
                                    <option value="<?php echo esc_attr($typ); ?>" <?php selected($f['typ'], $typ); ?>><?php echo esc_html($titel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-strasse"><?php esc_html_e('Straße und Hausnummer', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-strasse" name="firma_daten[strasse]" value="<?php echo esc_attr($f['strasse']); ?>">
                            <span class="imm-sh__hilfe"><?php esc_html_e('Nur eintragen, wenn sie auch im Google-Unternehmensprofil sichtbar ist.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-plz"><?php esc_html_e('PLZ', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-plz" name="firma_daten[plz]" value="<?php echo esc_attr($f['plz']); ?>" inputmode="numeric">
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-ort"><?php esc_html_e('Ort', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-ort" name="firma_daten[ort]" value="<?php echo esc_attr($f['ort']); ?>">
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-region"><?php esc_html_e('Bundesland', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-region" name="firma_daten[region]" value="<?php echo esc_attr($f['region']); ?>">
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-land"><?php esc_html_e('Land (Kürzel)', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-land" name="firma_daten[land]" value="<?php echo esc_attr($f['land']); ?>" maxlength="2">
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-inhaber"><?php esc_html_e('Inhaber oder Inhaberin', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-inhaber" name="firma_daten[inhaber]" value="<?php echo esc_attr($f['inhaber']); ?>">
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-preisrahmen"><?php esc_html_e('Preisrahmen', 'imm-seo-helfer'); ?></label>
                            <select id="imm-sh-preisrahmen" name="firma_daten[preisrahmen]">
                                <?php foreach (['' => __('keine Angabe', 'imm-seo-helfer'), '€' => '€', '€€' => '€€', '€€€' => '€€€', '€€€€' => '€€€€'] as $wert => $titel) : ?>
                                    <option value="<?php echo esc_attr($wert); ?>" <?php selected($f['preisrahmen'], $wert); ?>><?php echo esc_html($titel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-profile"><?php esc_html_e('Profile, eine Adresse pro Zeile', 'imm-seo-helfer'); ?></label>
                            <textarea id="imm-sh-profile" name="firma_daten[profile]" rows="3" placeholder="https://maps.app.goo.gl/…"><?php echo esc_textarea($f['profile']); ?></textarea>
                            <span class="imm-sh__hilfe"><?php esc_html_e('Google-Unternehmensprofil, LinkedIn, Branchenverzeichnisse. Der Link zum Google-Profil wird zusätzlich als Karte gemeldet.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-einzugsgebiet"><?php esc_html_e('Einzugsgebiet, ein Ort pro Zeile', 'imm-seo-helfer'); ?></label>
                            <textarea id="imm-sh-einzugsgebiet" name="firma_daten[einzugsgebiet]" rows="4"><?php echo esc_textarea($f['einzugsgebiet']); ?></textarea>
                            <span class="imm-sh__hilfe"><?php esc_html_e('Städte, Kreise oder „Deutschland“.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-oeffnungszeiten"><?php esc_html_e('Öffnungszeiten, eine Zeile pro Zeitraum', 'imm-seo-helfer'); ?></label>
                            <textarea id="imm-sh-oeffnungszeiten" name="firma_daten[oeffnungszeiten]" rows="3" placeholder="Mo–Fr 09:00–18:00&#10;Sa 10:00–14:00"><?php echo esc_textarea($f['oeffnungszeiten']); ?></textarea>
                            <span class="imm-sh__hilfe"><?php esc_html_e('Tage mit Komma oder Bindestrich, mehrere Zeitspannen mit Komma: „Mo, Di, Do–Sa 09:00–19:00“ oder „Mo–Fr 9–12, 14–18“. Geschlossene Tage einfach weglassen.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-angebote"><?php esc_html_e('Angebote, eines pro Zeile', 'imm-seo-helfer'); ?></label>
                            <textarea id="imm-sh-angebote" name="firma_daten[angebote]" rows="5" placeholder="Name | Preis | Einheit | Beschreibung"><?php echo esc_textarea($f['angebote']); ?></textarea>
                            <span class="imm-sh__hilfe"><?php esc_html_e('Aufbau: Name | Preis in Euro | Einheit | Beschreibung. Nur der Name ist Pflicht. Einheit z. B. „Monat“ für Abos, leer für Einmalpreise.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld imm-sh__feld--breit">
                            <label for="imm-sh-themen"><?php esc_html_e('Fachgebiete, eines pro Zeile', 'imm-seo-helfer'); ?></label>
                            <textarea id="imm-sh-themen" name="firma_daten[themen]" rows="3"><?php echo esc_textarea($f['themen']); ?></textarea>
                            <span class="imm-sh__hilfe"><?php esc_html_e('Worin das Unternehmen sich auskennt. KI-Suchen nutzen das, um es passenden Fragen zuzuordnen.', 'imm-seo-helfer'); ?></span>
                        </div>
                        <div class="imm-sh__feld">
                            <label for="imm-sh-sprachen"><?php esc_html_e('Sprachen', 'imm-seo-helfer'); ?></label>
                            <input type="text" id="imm-sh-sprachen" name="firma_daten[sprachen]" value="<?php echo esc_attr($f['sprachen']); ?>" placeholder="de, en">
                        </div>
                    </fieldset>
                <?php endif; ?>
            </div>

            <p class="imm-sh__aktion"><button type="submit" class="button button-primary"><?php esc_html_e('Einstellungen speichern', 'imm-seo-helfer'); ?></button></p>
        </form>

        <?php if (imm_sh_hat_breakdance()) : ?>
            <section class="imm-sh__karte imm-sh__felder" aria-labelledby="imm-sh-felder-titel">
                <h2 class="imm-sh__abschnitt" id="imm-sh-felder-titel"><?php esc_html_e('Datenfelder für Breakdance', 'imm-seo-helfer'); ?></h2>
                <p class="imm-sh__text"><?php esc_html_e('Im Builder in der Auswahl der dynamischen Daten unter „SEO-Helfer“. Elemente für ein leeres Feld blendest du mit einer Bedingung „ist nicht leer“ aus, etwa den Zurück-Link auf dem ersten Kapitel.', 'imm-seo-helfer'); ?></p>
                <dl class="imm-sh__liste">
                    <dt><?php esc_html_e('Vorheriges Kapitel: Titel / Link', 'imm-seo-helfer'); ?></dt>
                    <dd><?php esc_html_e('Titel und Adresse des Kapitels davor. Leer auf dem ersten Kapitel.', 'imm-seo-helfer'); ?></dd>
                    <dt><?php esc_html_e('Nächstes Kapitel: Titel / Link', 'imm-seo-helfer'); ?></dt>
                    <dd><?php esc_html_e('Titel und Adresse des Kapitels danach. Leer auf dem letzten Kapitel.', 'imm-seo-helfer'); ?></dd>
                    <dt><?php esc_html_e('Autoren-Bio in Absätzen', 'imm-seo-helfer'); ?></dt>
                    <dd><?php esc_html_e('Die biografischen Angaben aus dem Benutzerprofil, Leerzeilen werden zu Absätzen. Im Text-Element als Tag „div“ wählen.', 'imm-seo-helfer'); ?></dd>
                </dl>
            </section>
        <?php endif; ?>

        <p class="imm-sh__fuss">
            <?php
            /* translators: %s: Versionsnummer */
            echo esc_html(sprintf(__('SEO-Helfer für Breakdance, Version %s, von i-make.media.', 'imm-seo-helfer'), IMM_SH_VERSION));
            echo ' ';
            if (wp_is_auto_update_enabled_for_type('plugin') && in_array(plugin_basename(IMM_SH_DATEI), (array) get_site_option('auto_update_plugins', []), true)) {
                esc_html_e('Neue Versionen installieren sich automatisch.', 'imm-seo-helfer');
            } else {
                esc_html_e('Neue Versionen erscheinen unter Plugins als Update.', 'imm-seo-helfer');
            }
            ?>
            <a href="<?php echo esc_url(IMM_SH_REPO); ?>" target="_blank" rel="noopener"><?php esc_html_e('Versionen auf GitHub', 'imm-seo-helfer'); ?></a>
        </p>
    </div>
    <?php
}
