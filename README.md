# SEO-Helfer für Breakdance

WordPress-Plugin von i-make.media. Es schließt Lücken zwischen dem Pagebuilder Breakdance und Yoast SEO, die auf allen Breakdance-Websites gleich auftreten.

## Was es tut

| Helfer | Braucht | Wirkung |
|---|---|---|
| Bilder für die Bildersuche | Breakdance, Yoast SEO | Trägt Bilder aus Breakdance-Layouts in die Yoast-Bilder-Sitemap ein. Yoast liest nur den klassischen Beitragsinhalt und übersieht sie sonst. Gilt für alle öffentlichen Inhaltstypen. Hintergrundbilder bleiben draußen. |
| Vorschaubild als Hauptbild | Yoast SEO | Hat eine Seite kein Beitragsbild, aber ein eigenes Yoast-Social-Bild, wird dieses im Schema als `primaryImageOfPage` gemeldet. |
| Kapitel-Navigation | Breakdance | Datenfelder für vorheriges und nächstes Kapitel unter einer Elternseite. Reihenfolge = Seitenreihenfolge (`menu_order`). Bereiche werden einzeln eingeschaltet. |
| Autoren-Bio in Absätzen | Breakdance | Datenfeld mit den biografischen Angaben des Autors, Leerzeilen werden zu Absätzen. |
| Firmenprofil für Google | Yoast SEO | Ergänzt Yoasts Organisation im Schema um Unternehmenstyp (z. B. `ProfessionalService`), Adresse, Einzugsgebiet, Öffnungszeiten, Profile (`sameAs`, Google-Profil als `hasMap`), Inhaber, Fachgebiete, Sprachen und Angebote. Das kann Yoast nur mit dem kostenpflichtigen Local-SEO-Add-on. Standardmäßig aus, weil die Angaben je Website anders sind. Voraussetzung: In Yoast ist die Website als Organisation eingetragen. |

Fehlt ein benötigtes Plugin, ruht der Helfer und die Einstellungsseite sagt, warum. Einstellungen unter **Einstellungen → SEO-Helfer**.

## Breakdance-Datenfelder

Kategorie „SEO-Helfer“ in der Auswahl der dynamischen Daten:

| Slug | Inhalt |
|---|---|
| `imm_sh_kapitel_zurueck_titel` | Titel des vorherigen Kapitels |
| `imm_sh_kapitel_zurueck_url` | Link des vorherigen Kapitels |
| `imm_sh_kapitel_weiter_titel` | Titel des nächsten Kapitels |
| `imm_sh_kapitel_weiter_url` | Link des nächsten Kapitels |
| `imm_sh_autor_bio` | Autoren-Bio als HTML-Absätze (Text-Element mit Tag `div`) |

Auf dem ersten bzw. letzten Kapitel und in ausgeschalteten Bereichen sind die Kapitelfelder leer. Elemente dafür mit der Bedingung „ist nicht leer“ ausblenden.

## Firmenprofil: Eingabeformate

| Feld | Format |
|---|---|
| Öffnungszeiten | eine Zeile pro Zeitraum, z. B. `Mo, Di, Do–Sa 09:00–19:00` oder `Mo–Fr 9–12, 14–18`; geschlossene Tage weglassen |
| Angebote | eine Zeile pro Angebot: `Name \| Preis \| Einheit \| Beschreibung`; mit Einheit (z. B. `Monat`) wird ein Abo-Preis daraus, ohne ein Einmalpreis |
| Profile, Einzugsgebiet, Fachgebiete | eine Angabe pro Zeile; `Deutschland`, `Österreich`, `Schweiz` werden als Land gemeldet, alles andere als Ort |

Name, Logo, Telefon und E-Mail kommen weiter aus den Yoast-Einstellungen.

## Installation und Updates

Einmalig von Hand installieren: unter [Releases](https://github.com/mblmg/seo-helper-for-breakdance/releases) die Datei `imm-seo-helfer.zip` herunterladen und in WordPress unter Plugins → Installieren → Plugin hochladen einspielen.

Danach prüft das Plugin alle zwölf Stunden, ob es auf GitHub ein neueres Release gibt, und meldet es WordPress als Update. Automatische Updates sind beim Aktivieren eingeschaltet. WordPress installiert neue Versionen dann von selbst, meist innerhalb eines Tages. In der Plugin-Liste lässt sich das pro Website abschalten, und „Nach Updates suchen“ prüft sofort.

## Neue Version veröffentlichen

1. Versionsnummer an zwei Stellen in `imm-seo-helfer.php` erhöhen: im Kopf (`Version:`) und in `IMM_SH_VERSION`.
2. Im `CHANGELOG.md` einen Abschnitt `## X.Y.Z – Datum` ergänzen.
3. Committen und auf `main` pushen.

Die GitHub-Action unter `.github/workflows/release.yml` merkt, dass es für diese Versionsnummer noch kein Release gibt. Sie prüft die Angaben, baut `imm-seo-helfer.zip` und legt das Release `vX.Y.Z` mit den Notizen aus dem Changelog an. Ab dann verteilen sich die Updates von selbst. Commits ohne neue Versionsnummer lösen kein Release aus. Ein fehlgeschlagener Lauf lässt sich unter Actions → Release → „Run workflow“ wiederholen.

## Aufbau

```
imm-seo-helfer.php            Plugin-Kopf, Konstanten, lädt die Module
includes/optionen.php         Einstellungen, Erkennung von Breakdance und Yoast
includes/breakdance-bilder.php  Bilder im Breakdance-Elementbaum finden
includes/modul-*.php          je ein Helfer (sitemap-bilder, hauptbild, kapitel, autor, firma)
includes/admin.php            Einstellungsseite
includes/updates.php          Updates über GitHub-Releases
lib/plugin-update-checker/    Bibliothek für die Updates (YahnisElsts, MIT)
.github/workflows/release.yml baut bei neuer Versionsnummer das Release
assets/                       CSS und JS der Einstellungsseite
uninstall.php                 räumt beim Löschen die Einstellungen auf
```

Option: `imm_seo_helfer`. Präfix für Funktionen und Slugs: `imm_sh_`.

## Voraussetzungen

WordPress 6.5, PHP 7.4. Getestet mit WordPress 7.1, Breakdance 3.0 und Yoast SEO.
