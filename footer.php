</main>

<footer class="site-footer">
    <div class="container">
        <img src="assets/img/logo.png" alt="" width="72" height="72" class="footer-logo" loading="lazy">
        <p class="footer-tag">Les Sublimes, les glaces qui font bêler de plaisir</p>
        <div class="footer-links">
            <a href="mentions-legales.php">Mentions légales</a>
            <a href="./#contact">Contact</a>
            <a href="admin/">Administration</a>
        </div>
        <p class="copyright">
            &copy; <?= date('Y') ?> GAEC du Point Sublime — Ferme du Tensonnieu.
            <?php require_once __DIR__ . '/version.php'; ?>
            <span class="version-site" title="Version du site en ligne"><?= h(version_site_affichee()) ?></span>
        </p>
    </div>
</footer>

<script src="assets/js/main.js?v=<?= h(filemtime(__DIR__ . '/assets/js/main.js')) ?>" defer></script>
</body>
</html>
