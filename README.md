# GAEC du Point Sublime — « Les Sublimes »

Site vitrine + espace d'administration pour les glaces au lait de chèvre de la Ferme du Tensonnieu.
Stack : HTML/CSS/JS vanilla, PHP (PDO), MySQL. Prêt pour un hébergement mutualisé.

## Déploiement
1. Copier `config.example.php` en `config.local.php` (hors git) et renseigner MySQL + `SITE_URL`.
2. Ouvrir `/admin/install.php` : importe `sql/schema.sql` si besoin et crée le compte administrateur
   (ou importer `sql/schema.sql` via phpMyAdmin). Supprimer ensuite `admin/install.php`.
3. Se connecter sur `/admin/`.

## Administration
- **Glaces** / **Bûches de Noël** : CRUD (nom, description, prix, photo, **logo propre au produit**, ordre, visibilité).
  Le logo générique du site est `assets/img/logo.png` ; le logo de chaque parfum se téléverse dans l'admin
  (exemple fourni : « Crème glacée Miel »).
- **Prix** : par produit, grille tarifaire générale (`tarifs`) ou aucun.
- **Module Noël** : interrupteur On/Off → flocons, touches festives, section « Nos bûches de Noël sur commande ».
- API JSON publique en lecture : `api/catalogue.php`.

Incrémenter `version.php` à chaque modification visible.
