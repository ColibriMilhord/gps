-- ============================================================
--  GAEC du Point Sublime — « Les Sublimes » (glaces au lait de chèvre)
--  Schéma MySQL / MariaDB.  À importer une seule fois (phpMyAdmin > Importer).
--  Tables : admin, glaces, buches, tarifs, settings_site
-- ============================================================

SET NAMES utf8mb4;

-- Comptes de l'espace d'administration (mots de passe hachés avec password_hash)
CREATE TABLE IF NOT EXISTS admin (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    identifiant   VARCHAR(60)  NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255) NOT NULL,
    cree_le       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tentatives de connexion échouées (limitation anti force brute)
CREATE TABLE IF NOT EXISTS tentatives_connexion (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip        VARCHAR(45) NOT NULL,
    date_essai TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ip_date (ip, date_essai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parfums de glaces.
--   image : photo du parfum (carte de la vitrine)
--   logo  : logo/étiquette propre à ce parfum (ex. « Crème glacée Miel »)
CREATE TABLE IF NOT EXISTS glaces (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    description TEXT NULL,
    image       VARCHAR(255) NULL,
    logo        VARCHAR(255) NULL,
    prix        DECIMAL(6,2) NULL,
    visible     TINYINT(1) NOT NULL DEFAULT 1,
    ordre       INT NOT NULL DEFAULT 0,
    cree_le     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bûches de Noël sur commande (même logique que les glaces)
CREATE TABLE IF NOT EXISTS buches (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    description TEXT NULL,
    parfums     VARCHAR(255) NULL,
    parts       VARCHAR(60)  NULL,
    image       VARCHAR(255) NULL,
    logo        VARCHAR(255) NULL,
    prix        DECIMAL(6,2) NULL,
    visible     TINYINT(1) NOT NULL DEFAULT 1,
    ordre       INT NOT NULL DEFAULT 0,
    cree_le     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grille tarifaire générale (ex. « Pot 100 ml — 3,50 € »)
CREATE TABLE IF NOT EXISTS tarifs (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL,
    prix    DECIMAL(6,2) NOT NULL,
    ordre   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres du site (clé / valeur)
CREATE TABLE IF NOT EXISTS settings_site (
    cle    VARCHAR(60) NOT NULL PRIMARY KEY,
    valeur TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings_site (cle, valeur) VALUES
    ('noel_actif',    '0'),
    ('noel_message',  'Commandez vos bûches glacées au lait de chèvre pour vos repas de fêtes.'),
    ('prix_mode',     'individuel'),   -- individuel | grille | aucun
    ('telephone',     ''),
    ('email',         ''),
    ('adresse',       'Ferme du Tensonnieu, aux portes des Gorges du Tarn'),
    ('marches',       'Marchés d''Aveyron et de Lozère');

-- Exemple de parfum (le logo « Miel » est fourni dans uploads/logos/)
INSERT INTO glaces (nom, description, logo, prix, ordre) VALUES
    ('Crème glacée Miel', 'Une crème glacée onctueuse au lait de nos chèvres, parfumée au miel.', 'logo-miel.png', 3.50, 1);
INSERT INTO tarifs (libelle, prix, ordre) VALUES
    ('Pot 100 ml', 3.50, 1), ('Pot 500 ml', 9.00, 2), ('Pot 1 L', 16.00, 3);
