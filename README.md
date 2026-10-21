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

## v1.1.0
- **Où nous retrouver** (admin > Où nous retrouver) : ferme, marchés, événements. Dates passées masquées automatiquement ; le prochain rendez-vous est mis en avant sous l'accueil.
- **Partenaires** (admin > Partenaires) : boutiques/restaurants qui vendent les glaces.
- **Formulaire de contact** avec profils (restaurant, boutique, partenaire, visite, commande de glaces, bûche si Noël actif) ; messages dans admin > Messages + e-mail de notification si un courriel est renseigné.
- Mise à jour d'une base 1.0 : automatique à la première connexion à l'admin (ou importer `sql/migration-1.1.0.sql`).
