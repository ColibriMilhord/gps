<?php
// « Où nous retrouver » : ferme, marchés, événements.
require_once __DIR__ . '/_crud.php';
page_crud([
    'table' => 'lieux', 'page' => 'lieux.php', 'actif' => 'lieux', 'titre' => 'Où nous retrouver', 'singulier' => 'un rendez-vous',
    'tri' => '(date_debut IS NULL), date_debut, ordre, id',
    'aide' => 'Saisissez une <strong>date</strong> (ou une période) pour un rendez-vous ponctuel : il s\'affiche sur le site jusqu\'à sa fin, puis disparaît tout seul, et le plus proche est mis en avant sous l\'accueil. Sans date, utilisez « Récurrence » (ex. « Tous les samedis matin »).',
    'champs' => [
        ['type', 'Type', 'select', ['choix' => TYPES_LIEU, 'defaut' => 'marche']],
        ['titre', 'Nom du lieu / de l\'événement', 'text', ['requis' => 1, 'max' => 140, 'placeholder' => 'ex. Marché de Millau']],
        ['ville', 'Ville', 'text', ['max' => 100]],
        ['adresse', 'Adresse', 'text', ['placeholder' => 'ex. Place du Mandarous']],
        ['date_debut', 'Date (ou début)', 'date', []],
        ['date_fin', 'Date de fin (si plusieurs jours)', 'date', []],
        ['recurrence', 'Récurrence (sans date)', 'text', ['max' => 140, 'placeholder' => 'ex. Tous les samedis']],
        ['horaires', 'Horaires', 'text', ['max' => 140, 'placeholder' => 'ex. 8 h – 13 h']],
        ['note', 'Précisions', 'textarea', ['large' => 1]],
        ['ordre', 'Ordre (rendez-vous sans date)', 'number', []],
    ],
    'colonnes' => [
        ['Type', fn($l) => TYPES_LIEU[$l['type']] ?? $l['type']],
        ['Nom', fn($l) => $l['titre'] . ($l['ville'] ? ' — ' . $l['ville'] : '')],
        ['Quand', fn($l) => lieu_quand($l) ?: '—'],
    ],
]);
