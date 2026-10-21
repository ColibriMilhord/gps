<?php
// Version du site : à incrémenter dans le même commit que toute modification visible.
// MAJEURE.MINEURE.CORRECTIF
function version_site(): string { return '1.1.3'; }
function version_site_date(): string { return '2026-10-24'; }
function version_site_affichee(): string {
    return 'v' . version_site() . ' · ' . date('d/m/Y', strtotime(version_site_date()));
}
