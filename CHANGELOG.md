# Changelog

## 1.2.0 – 2026-10-31

- Neuer Helfer „Firmenprofil für Google“: ergänzt Yoasts Organisation im Schema um Unternehmenstyp, Adresse, Einzugsgebiet, Öffnungszeiten, Profile mit Google-Unternehmensprofil als Karte, Inhaber, Fachgebiete, Sprachen und Angebote mit Preisen. Standardmäßig aus; Eingabe auf der Einstellungsseite.
- Einstellungsseite weist darauf hin, wenn Yoast die Website nicht als Organisation führt, und verlinkt den Rich-Results-Test von Google.

## 1.1.0 – 2026-10-31

- Updates über GitHub: Neue Versionen kommen aus den Releases dieses Repositorys und erscheinen in WordPress wie normale Plugin-Updates (Plugin Update Checker 5.7).
- Automatische Updates sind beim Aktivieren eingeschaltet und lassen sich in der Plugin-Liste abschalten.
- Einstellungsseite zeigt im Fuß, ob Updates automatisch installiert werden.

## 1.0.1 – 2026-10-31

- Einstellungsseite: Statuszeilen mit Akzentlinie statt getönter Fläche, damit sie auch mit Dunkelmodus-Erweiterungen im Browser lesbar bleiben.

## 1.0.0 – 2026-10-31

Erste seitenunabhängige Version, hervorgegangen aus dem „Japanisch-Lehrer SEO-Helfer“ (1.3.0).

- Bilder-Sitemap erkennt Bilder jetzt in allen Breakdance-Elementen, die ein Bild als Mediathek-Eintrag speichern, auch in eigenen Elementen. Hintergrundbilder werden ausgelassen.
- Bilder-Sitemap und Hauptbild gelten für alle öffentlichen Inhaltstypen statt nur für Seiten.
- Kapitel-Navigation: Bereiche sind standardmäßig aus und werden einzeln eingeschaltet. Unterstützt alle hierarchischen Inhaltstypen.
- Erkennung von Breakdance und Yoast SEO. Fehlt ein Plugin, ruht der betroffene Helfer und die Einstellungsseite zeigt den Grund.
- Einstellungsseite im Farbschema des WordPress-Backends.
- Seiten-Cache wird nach dem Speichern geleert (WP-Optimize, WP Super Cache, W3 Total Cache, LiteSpeed).
- Neue Slugs für die Datenfelder: `imm_sh_*` statt `jlde_*`.
