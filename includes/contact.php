<?php
// Formulaire de contact : traitement (POST) et affichage.
// Anti-spam sans cookie : champ piège, délai minimal de remplissage, 5 messages/heure/IP.

/** Traite l'envoi. Retourne ['erreurs' => [...], 'valeurs' => [...]] ou redirige en cas de succès. */
function contact_traiter(bool $noel): array {
    $profils = profils_contact($noel);
    $v = [];
    foreach (['profil', 'nom', 'email', 'telephone', 'etablissement', 'produit', 'date_souhaitee', 'message'] as $c) {
        $v[$c] = trim((string)($_POST[$c] ?? ''));
    }
    if ($v['profil'] === 'confiture') $v['produit'] = trim((string)($_POST['produit_confiture'] ?? ''));   // deux listes de produits selon le profil
    elseif ($v['profil'] !== 'buche') $v['produit'] = '';
    $err = [];
    if (!isset($profils[$v['profil']])) $err['profil'] = 'Choisissez ce qui vous décrit le mieux.';
    if ($v['nom'] === '') $err['nom'] = 'Indiquez votre nom.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Indiquez une adresse e-mail valide.';
    if (mb_strlen($v['message']) < 5) $err['message'] = 'Écrivez-nous quelques mots.';
    if (empty($_POST['consentement'])) $err['consentement'] = 'Veuillez accepter que vos informations servent à vous répondre.';
    $limites = ['nom' => 120, 'email' => 190, 'telephone' => 40, 'etablissement' => 140, 'produit' => 190, 'date_souhaitee' => 60, 'message' => 4000];
    foreach ($limites as $c => $max) $v[$c] = mb_substr($v[$c], 0, $max);

    // Anti-spam : on répond « envoyé » aux robots sans rien enregistrer
    $robot = ($_POST['site_web'] ?? '') !== '' || (time() - (int)($_POST['t'] ?? 0)) < 3;
    if ($err) return ['erreurs' => $err, 'valeurs' => $v];

    if (!$robot && ($pdo = db())) {
        try {
            $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
            $st = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE ip = ? AND cree_le > ?');
            $st->execute([$ip, date('Y-m-d H:i:s', time() - 3600)]);
            if ((int)$st->fetchColumn() >= 5) {
                return ['erreurs' => ['general' => 'Trop de messages envoyés. Réessayez plus tard ou appelez-nous.'], 'valeurs' => $v];
            }
            $pdo->prepare('INSERT INTO messages (profil, nom, email, telephone, etablissement, produit, date_souhaitee, message, ip, cree_le)
                           VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$v['profil'], $v['nom'], $v['email'], $v['telephone'], $v['etablissement'], $v['produit'], $v['date_souhaitee'], $v['message'], $ip, date('Y-m-d H:i:s')]);
            contact_notifier($v, $profils[$v['profil']]);
        } catch (Throwable $e) {
            error_log('contact: ' . $e->getMessage());
            return ['erreurs' => ['general' => "Votre message n'a pas pu être enregistré. Merci de nous appeler ou de réessayer plus tard."], 'valeurs' => $v];
        }
    }
    header('Location: ./?envoye=1#contact');
    exit;
}

/** Notification par e-mail (si une adresse est renseignée dans l'admin). Échec silencieux : le message est déjà en base. */
function contact_notifier(array $v, string $profilLibelle): void {
    $dest = reglages()['email'] ?? '';
    if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) return;
    $clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
    $corps = "Nouveau message depuis le site Les Sublimes\n\nProfil : $profilLibelle\nNom : {$v['nom']}\nE-mail : {$v['email']}\nTéléphone : {$v['telephone']}\n"
           . "Établissement : {$v['etablissement']}\nDemande : {$v['produit']}\nDate souhaitée : {$v['date_souhaitee']}\n\n{$v['message']}\n";
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
    @mail($dest, '=?UTF-8?B?' . base64_encode('Les Sublimes — ' . $clean($profilLibelle)) . '?=', $corps,
        "From: Les Sublimes <no-reply@$host>\r\nReply-To: " . $clean($v['email']) . "\r\nContent-Type: text/plain; charset=UTF-8");
}

/** Affiche le formulaire. */
function contact_afficher(bool $noel, array $buches, array $res = [], array $confitures = []): void {
    $err = $res['erreurs'] ?? [];
    $v = $res['valeurs'] ?? [];
    $val = fn($c) => h($v[$c] ?? '');
    $profils = profils_contact($noel);
    $e = fn($c) => isset($err[$c]) ? '<span class="erreur" id="err-' . $c . '">' . h($err[$c]) . '</span>' : '';
    $d = fn($c) => isset($err[$c]) ? ' aria-invalid="true" aria-describedby="err-' . $c . '"' : '';
    ?>
    <form class="form-contact" method="post" action="./#contact" novalidate data-noel="<?= $noel ? '1' : '0' ?>">
        <input type="hidden" name="contact" value="1">
        <input type="hidden" name="t" value="<?= time() ?>">
        <div class="piege" aria-hidden="true"><label>Ne pas remplir <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label></div>

        <?php if (!empty($err)): ?>
            <div class="alerte alerte--err" role="alert" tabindex="-1" id="alerte-contact">
                <?= isset($err['general']) ? h($err['general']) : 'Merci de corriger les champs signalés.' ?>
            </div>
        <?php endif; ?>

        <div class="champ">
            <label for="c-profil">Vous êtes… *</label>
            <select id="c-profil" name="profil" required<?= $d('profil') ?>>
                <option value="">Choisissez dans la liste</option>
                <?php foreach ($profils as $k => $l): ?>
                    <option value="<?= h($k) ?>" <?= ($v['profil'] ?? '') === $k ? 'selected' : '' ?>><?= h($l) ?></option>
                <?php endforeach; ?>
            </select><?= $e('profil') ?>
        </div>

        <div class="champ champ--2">
            <div><label for="c-nom">Nom et prénom *</label>
                <input id="c-nom" name="nom" required autocomplete="name" value="<?= $val('nom') ?>"<?= $d('nom') ?>><?= $e('nom') ?></div>
            <div><label for="c-email">E-mail *</label>
                <input id="c-email" type="email" name="email" required autocomplete="email" value="<?= $val('email') ?>"<?= $d('email') ?>><?= $e('email') ?></div>
        </div>

        <div class="champ champ--2">
            <div><label for="c-tel">Téléphone</label>
                <input id="c-tel" type="tel" name="telephone" autocomplete="tel" value="<?= $val('telephone') ?>"></div>
            <div data-pour="restaurant boutique partenaire"><label for="c-etab">Nom de votre établissement</label>
                <input id="c-etab" name="etablissement" autocomplete="organization" value="<?= $val('etablissement') ?>"></div>
        </div>

        <?php if ($noel): ?>
        <div class="champ" data-pour="buche">
            <label for="c-buche">Quelle bûche ?</label>
            <select id="c-buche" name="produit">
                <option value="">Je précise dans mon message</option>
                <?php foreach ($buches as $b): ?>
                    <option value="<?= h($b['nom']) ?>" <?= ($v['produit'] ?? '') === $b['nom'] ? 'selected' : '' ?>><?= h($b['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($confitures): ?>
        <div class="champ" data-pour="confiture">
            <label for="c-confiture">Quelle confiture de lait ?</label>
            <select id="c-confiture" name="produit_confiture">
                <option value="">Je précise dans mon message</option>
                <?php foreach ($confitures as $k): ?>
                    <option value="<?= h($k['nom']) ?>" <?= ($v['produit'] ?? '') === $k['nom'] ? 'selected' : '' ?>><?= h($k['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="champ" data-pour="visite buche glace confiture">
            <label for="c-date">Date souhaitée</label>
            <input id="c-date" name="date_souhaitee" value="<?= $val('date_souhaitee') ?>" placeholder="ex. samedi 20 décembre, ou « courant juillet »">
        </div>

        <div class="champ">
            <label for="c-msg">Votre message *</label>
            <textarea id="c-msg" name="message" rows="5" required<?= $d('message') ?>><?= $val('message') ?></textarea><?= $e('message') ?>
            <p class="aide" data-pour="restaurant boutique partenaire">Précisez vos volumes, vos parfums préférés, votre zone de livraison…</p>
            <p class="aide" data-pour="visite">Indiquez le nombre de personnes et vos disponibilités.</p>
            <p class="aide" data-pour="glace buche confiture">Indiquez parfums, quantités et le jour de retrait souhaité.</p>
        </div>

        <div class="champ champ--case">
            <label class="case"><input type="checkbox" name="consentement" value="1" <?= !empty($_POST['consentement']) ? 'checked' : '' ?><?= $d('consentement') ?>>
                <span>J'accepte que ces informations soient utilisées pour répondre à ma demande. *</span></label><?= $e('consentement') ?>
        </div>
        <button class="btn" type="submit">Envoyer mon message</button>
        <p class="aide">* champs obligatoires</p>
    </form>
    <?php
}
