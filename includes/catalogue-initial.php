<?php
// Catalogue initial des parfums (ingrédients issus des étiquettes du GAEC, logos dans uploads/logos/).
// Injecté une seule fois en base par migrer_base() ; ensuite tout se modifie dans l'administration.
const STABILISANT = 'stabilisant bio (farine de lin et fibres végétales)';
const POIDS_POT = '100 g / 140 ml';

return [
    ['nom' => 'Vanille',  'ordre' => 1,  'logo' => null,
     'ingredients' => 'lait entier bio de nos chèvres, sucre, crème bio de nos chèvres, vanille Tahitensis, ' . STABILISANT],
    ['nom' => 'Chocolat', 'ordre' => 2,  'logo' => null,
     'ingredients' => 'lait entier bio de nos chèvres, chocolat, sucre, miel, ' . STABILISANT],
    ['nom' => 'Miel',     'ordre' => 3,  'logo' => 'logo-miel.png', 'recherche' => '%Miel%',
     'ingredients' => 'lait entier et crème bio de nos chèvres, miel, sucre, ' . STABILISANT],
    ['nom' => 'Yaourt',   'ordre' => 4,  'logo' => null,
     'ingredients' => 'lait entier et yaourt bio de nos chèvres, sucre, crème bio de nos chèvres, ' . STABILISANT],
    ['nom' => 'Fraise',   'ordre' => 5,  'logo' => 'logo-fraise.jpg',
     'ingredients' => 'pulpe de fraises, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Cerise',   'ordre' => 6,  'logo' => 'logo-cerise.jpg',
     'ingredients' => 'pulpe de cerises, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Abricot',  'ordre' => 7,  'logo' => 'logo-abricot.jpg',
     'ingredients' => 'pulpe d’abricots, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Pêche',    'ordre' => 8,  'logo' => 'logo-peche.jpg',
     'ingredients' => 'pulpe de pêches, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Mirabelle','ordre' => 9,  'logo' => 'logo-mirabelle.jpg',
     'ingredients' => 'pulpe de mirabelles, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Melon',    'ordre' => 10, 'logo' => 'logo-melon.jpg',
     'ingredients' => 'pulpe de melon, lait entier bio de nos chèvres, sucre, sirop de glucose maison, ' . STABILISANT],
    ['nom' => 'Pomme',    'ordre' => 11, 'logo' => null,
     'ingredients' => 'pulpe de pomme, lait entier bio de nos chèvres, sucre, sirop de glucose maison, stabilisant (farine de lin et fibres végétales)'],
    ['nom' => 'Fouace',   'ordre' => 12, 'logo' => 'logo-fouace.jpg',
     'ingredients' => 'lait bio de nos chèvres, sucre, fouace, ' . STABILISANT],
    // Pas d'étiquette d'ingrédients fournie pour ce parfum : à compléter dans l'administration.
    ['nom' => "Pain d'épices", 'ordre' => 13, 'logo' => 'logo-pain-epices.jpg', 'ingredients' => null],
];
