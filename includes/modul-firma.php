<?php
/**
 * Modul: Firmenprofil für Google (lokales Unternehmen im Schema).
 *
 * Yoast SEO meldet in der Gratisversion nur eine allgemeine Organisation. Typ als lokales
 * Unternehmen, Adresse, Einzugsgebiet, Öffnungszeiten, Profile und Angebote gibt es dort
 * erst mit dem kostenpflichtigen Local-SEO-Add-on. Dieses Modul ergänzt sie an Yoasts
 * Organisation. Name und Logo kommen aus Yoast; Beschreibung, Telefon, E-Mail, Gründungsdatum
 * und USt-ID aus Yoasts Einstellungen trägt das Modul selbst ein, weil Yoast sie nicht ausgibt.
 *
 * Voraussetzung: In Yoast ist die Website als Organisation eingetragen, nicht als Person.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unternehmenstypen nach schema.org, die zur Auswahl stehen.
 *
 * @return array<string, string> Schema-Typ => Bezeichnung
 */
function imm_sh_firma_typen()
{
    return [
        'ProfessionalService'          => __('Dienstleister (Agentur, Büro, Beratung)', 'imm-seo-helfer'),
        'LocalBusiness'                => __('Lokales Unternehmen allgemein', 'imm-seo-helfer'),
        'Store'                        => __('Geschäft, Laden', 'imm-seo-helfer'),
        'FoodEstablishment'            => __('Gastronomie', 'imm-seo-helfer'),
        'HomeAndConstructionBusiness'  => __('Handwerk, Bau, Haus und Garten', 'imm-seo-helfer'),
        'HealthAndBeautyBusiness'      => __('Gesundheit, Kosmetik, Wellness', 'imm-seo-helfer'),
        'MedicalBusiness'              => __('Praxis, Medizin', 'imm-seo-helfer'),
        'LegalService'                 => __('Rechtsberatung', 'imm-seo-helfer'),
        'FinancialService'             => __('Steuern, Finanzen', 'imm-seo-helfer'),
        'EducationalOrganization'      => __('Schule, Unterricht, Bildung', 'imm-seo-helfer'),
        'EntertainmentBusiness'        => __('Kultur, Unterhaltung', 'imm-seo-helfer'),
        'SportsActivityLocation'       => __('Sport, Fitness', 'imm-seo-helfer'),
        'LodgingBusiness'              => __('Unterkunft', 'imm-seo-helfer'),
        'AutomotiveBusiness'           => __('Auto, Werkstatt', 'imm-seo-helfer'),
    ];
}

/**
 * Leere Firmendaten.
 *
 * @return array<string, string>
 */
function imm_sh_firma_standard()
{
    return [
        'typ'              => 'ProfessionalService',
        'strasse'          => '',
        'plz'              => '',
        'ort'              => '',
        'region'           => '',
        'land'             => 'DE',
        'preisrahmen'      => '',
        'inhaber'          => '',
        'einzugsgebiet'    => '',
        'oeffnungszeiten'  => '',
        'profile'          => '',
        'themen'           => '',
        'sprachen'         => '',
        'angebote'         => '',
    ];
}

/** Gespeicherte Firmendaten mit Standardwerten. */
function imm_sh_firma_daten()
{
    $o = imm_sh_optionen();

    return array_merge(imm_sh_firma_standard(), is_array($o['firma_daten'] ?? null) ? $o['firma_daten'] : []);
}

/** Ist in Yoast eine Organisation eingetragen? Nur dann gibt es etwas zu ergänzen. */
function imm_sh_firma_yoast_organisation()
{
    if (!class_exists('WPSEO_Options')) {
        return false;
    }

    return WPSEO_Options::get('company_or_person') === 'company' && WPSEO_Options::get('company_name') !== '';
}

/**
 * Textfeld in nicht-leere Zeilen zerlegen.
 *
 * @return string[]
 */
function imm_sh_firma_zeilen($text)
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text))));
}

/**
 * Öffnungszeiten aus Zeilen wie „Mo, Di, Do–Sa 09:00–19:00“ lesen.
 * Mehrere Zeitspannen pro Zeile sind möglich („Mo–Fr 9–12, 14–18“).
 *
 * @return array<int, array<string, mixed>>
 */
function imm_sh_firma_oeffnungszeiten($text)
{
    $tage = ['mo' => 'Monday', 'di' => 'Tuesday', 'mi' => 'Wednesday', 'do' => 'Thursday', 'fr' => 'Friday', 'sa' => 'Saturday', 'so' => 'Sunday'];
    $reihe = array_keys($tage);
    $zeiten = [];

    foreach (imm_sh_firma_zeilen($text) as $zeile) {
        $zeile = str_replace(['–', '—', ' bis '], '-', mb_strtolower($zeile));
        if (!preg_match_all('/(\d{1,2})(?:[:.](\d{2}))?\s*(?:uhr)?\s*-\s*(\d{1,2})(?:[:.](\d{2}))?/u', $zeile, $spannen, PREG_SET_ORDER)) {
            continue;
        }
        $tagteil = preg_split('/\d/u', $zeile, 2)[0];
        $gefunden = [];
        foreach (preg_split('/[,;\/]+|\s+und\s+/u', $tagteil) as $stueck) {
            $stueck = trim($stueck);
            if (preg_match('/^(mo|di|mi|do|fr|sa|so)\w*\s*-\s*(mo|di|mi|do|fr|sa|so)/u', $stueck, $m)) {
                $von = array_search($m[1], $reihe, true);
                $bis = array_search($m[2], $reihe, true);
                for ($i = $von; $i <= $bis; $i++) {
                    $gefunden[] = $tage[$reihe[$i]];
                }
            } elseif (preg_match('/^(mo|di|mi|do|fr|sa|so)/u', $stueck, $m)) {
                $gefunden[] = $tage[$m[1]];
            }
        }
        if (!$gefunden) {
            continue;
        }
        foreach ($spannen as $s) {
            $zeiten[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => array_values(array_unique($gefunden)),
                'opens'     => sprintf('%02d:%02d', (int) $s[1], (int) ($s[2] ?? 0)),
                'closes'    => sprintf('%02d:%02d', (int) $s[3], (int) ($s[4] ?? 0)),
            ];
        }
    }

    return $zeiten;
}

/** Preis wie „2.600“, „189“ oder „19,90 €“ in eine Zahl für das Schema wandeln. */
function imm_sh_firma_preis($text)
{
    $text = preg_replace('/[^\d.,]/u', '', (string) $text);
    if ($text === '') {
        return '';
    }
    if (strpos($text, ',') !== false) {
        $text = str_replace(',', '.', str_replace('.', '', $text));
    } elseif (preg_match('/\.\d{3}$/', $text)) {
        $text = str_replace('.', '', $text);
    }

    return $text;
}

/**
 * Angebote aus Zeilen „Name | Preis | Einheit | Beschreibung“ lesen.
 * Preis, Einheit und Beschreibung dürfen fehlen.
 *
 * @return array<int, array<string, mixed>>
 */
function imm_sh_firma_angebote($text)
{
    $angebote = [];
    foreach (imm_sh_firma_zeilen($text) as $zeile) {
        $teile = array_map('trim', explode('|', $zeile));
        if ($teile[0] === '') {
            continue;
        }
        $angebot = ['@type' => 'Offer', 'name' => $teile[0]];
        if (!empty($teile[3])) {
            $angebot['description'] = $teile[3];
        }
        $preis = imm_sh_firma_preis($teile[1] ?? '');
        if ($preis !== '') {
            if (!empty($teile[2])) {
                $angebot['priceSpecification'] = [
                    '@type'         => 'UnitPriceSpecification',
                    'price'         => $preis,
                    'priceCurrency' => 'EUR',
                    'unitText'      => $teile[2],
                ];
            } else {
                $angebot['price']         = $preis;
                $angebot['priceCurrency'] = 'EUR';
            }
        }
        $angebote[] = $angebot;
    }

    return $angebote;
}

/**
 * Die Ergänzungen für Yoasts Organisation.
 *
 * @param array<string, string>|null $d Firmendaten, sonst die gespeicherten.
 * @return array<string, mixed>
 */
function imm_sh_firma_schema($d = null)
{
    $d     = $d ?? imm_sh_firma_daten();
    $typen = imm_sh_firma_typen();
    $typ   = isset($typen[$d['typ']]) ? $d['typ'] : 'LocalBusiness';
    $neu   = ['@type' => ['Organization', $typ]];

    $adresse = array_filter([
        'streetAddress'   => $d['strasse'],
        'postalCode'      => $d['plz'],
        'addressLocality' => $d['ort'],
        'addressRegion'   => $d['region'],
        'addressCountry'  => $d['land'],
    ]);
    if (!empty($adresse['addressLocality'])) {
        $neu['address'] = ['@type' => 'PostalAddress'] + $adresse;
    }

    if ($d['preisrahmen'] !== '') {
        $neu['priceRange'] = $d['preisrahmen'];
    }

    $gebiete = imm_sh_firma_zeilen($d['einzugsgebiet']);
    if ($gebiete) {
        $neu['areaServed'] = array_map(function ($name) {
            $land = in_array(mb_strtolower($name), ['deutschland', 'österreich', 'schweiz', 'germany', 'austria', 'switzerland'], true);

            return ['@type' => $land ? 'Country' : 'City', 'name' => $name];
        }, $gebiete);
    }

    $zeiten = imm_sh_firma_oeffnungszeiten($d['oeffnungszeiten']);
    if ($zeiten) {
        $neu['openingHoursSpecification'] = $zeiten;
    }

    $profile = array_values(array_filter(imm_sh_firma_zeilen($d['profile']), function ($url) {
        return (bool) wp_http_validate_url($url);
    }));
    if ($profile) {
        $neu['sameAs'] = $profile;
        foreach ($profile as $url) {
            if (preg_match('#(google\.[a-z.]+/maps|maps\.app\.goo\.gl|g\.page|goo\.gl/maps)#i', $url)) {
                $neu['hasMap'] = $url;
                break;
            }
        }
    }

    if ($d['inhaber'] !== '') {
        $neu['founder'] = ['@type' => 'Person', 'name' => $d['inhaber']];
    }

    $themen = imm_sh_firma_zeilen($d['themen']);
    if ($themen) {
        $neu['knowsAbout'] = $themen;
    }

    $sprachen = imm_sh_firma_zeilen(str_replace(',', "\n", $d['sprachen']));
    if ($sprachen) {
        $neu['knowsLanguage'] = $sprachen;
    }

    $angebote = imm_sh_firma_angebote($d['angebote']);
    if ($angebote) {
        $neu['hasOfferCatalog'] = [
            '@type'           => 'OfferCatalog',
            'name'            => __('Angebote', 'imm-seo-helfer'),
            'itemListElement' => $angebote,
        ];
    }

    return $neu;
}

add_filter('wpseo_schema_organization', function ($data) {
    if (!imm_sh_an('firma') || !is_array($data)) {
        return $data;
    }
    $neu = imm_sh_firma_schema();

    // Profile aus Yoast (Social-Einstellungen) behalten und ergänzen.
    if (!empty($neu['sameAs'])) {
        $neu['sameAs'] = array_values(array_unique(array_merge((array) ($data['sameAs'] ?? []), $neu['sameAs'])));
    }

    // Angaben aus Yoasts Einstellungen, die Yoast selbst nicht ins Schema schreibt.
    if (class_exists('WPSEO_Options')) {
        $aus_yoast = [
            'description'  => 'org-description',
            'telephone'    => 'org-phone',
            'email'        => 'org-email',
            'foundingDate' => 'org-founding-date',
            'vatID'        => 'org-vat-id',
        ];
        foreach ($aus_yoast as $schluessel => $option) {
            $wert = trim((string) WPSEO_Options::get($option, ''));
            if ($wert !== '' && empty($data[$schluessel])) {
                $data[$schluessel] = $wert;
            }
        }
    }

    // Yoast-Angaben wie Name und Logo bleiben, die Ergänzungen kommen dazu.
    return array_merge($data, $neu);
}, 20);
