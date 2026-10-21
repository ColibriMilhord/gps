<?php
// Partenaires qui vendent nos glaces.
require_once __DIR__ . '/_crud.php';
page_crud([
    'table' => 'partenaires', 'page' => 'partenaires.php', 'actif' => 'partenaires', 'titre' => 'Partenaires revendeurs', 'singulier' => 'un partenaire',
    'tri' => 'ordre, nom',
    'aide' => 'Les boutiques et restaurants qui vendent nos glaces. Ils apparaissent dans « Où nous retrouver ».',
    'champs' => [
        ['nom', 'Nom', 'text', ['requis' => 1, 'max' => 140]],
        ['categorie', 'Catégorie', 'select', ['choix' => CATEGORIES_PARTENAIRE, 'defaut' => 'boutique']],
        ['ville', 'Ville', 'text', ['max' => 100]],
        ['adresse', 'Adresse', 'text', []],
        ['telephone', 'Téléphone', 'text', ['max' => 40]],
        ['site_web', 'Site web', 'url', ['placeholder' => 'https://…']],
        ['ordre', 'Ordre', 'number', []],
    ],
    'colonnes' => [
        ['Nom', fn($l) => $l['nom']],
        ['Catégorie', fn($l) => CATEGORIES_PARTENAIRE[$l['categorie']] ?? $l['categorie']],
        ['Ville', fn($l) => (string)$l['ville']],
    ],
]);
