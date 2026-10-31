/* SEO-Helfer für Breakdance: Einstellungsseite */
(function () {
    'use strict';

    // Bereiche abdunkeln, solange die Kapitel-Navigation aus ist
    document.querySelectorAll('.imm-sh [data-steuert]').forEach(function (schalter) {
        var ziel = document.getElementById(schalter.getAttribute('data-steuert'));
        if (!ziel) {
            return;
        }
        var aktualisieren = function () {
            ziel.classList.toggle('ist-aus', !schalter.checked);
        };
        schalter.addEventListener('change', aktualisieren);
        aktualisieren();
    });
})();
