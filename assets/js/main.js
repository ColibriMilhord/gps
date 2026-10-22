// Les Sublimes — interactions du site public (JavaScript vanilla)
(function () {
    'use strict';
    var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // En-tête : fond opaque après le hero
    var entete = document.getElementById('site-header');
    function surDefilement() {
        entete.classList.toggle('scrolled', window.scrollY > 40);
        // Parallaxe de la photo de Noisette (hors mobile / mouvement réduit)
        if (!reduit && window.innerWidth > 800) {
            document.querySelectorAll('[data-parallax]').forEach(function (el) {
                var r = el.parentElement.getBoundingClientRect();
                if (r.bottom < 0 || r.top > window.innerHeight) return;
                el.style.backgroundPosition = 'center ' + (30 + (r.top / window.innerHeight) * -12) + '%';
            });
        }
    }
    window.addEventListener('scroll', surDefilement, { passive: true });
    surDefilement();

    // Échap ferme le menu
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav && nav.classList.contains('ouvert')) { nav.classList.remove('ouvert'); bouton.setAttribute('aria-expanded', 'false'); entete.classList.remove('menu-ouvert'); document.body.classList.remove('menu-ouvert'); bouton.focus(); } });
    // Menu mobile
    var bouton = document.querySelector('.nav-toggle');
    var nav = document.getElementById('nav-principal');
    if (bouton && nav) {
        bouton.addEventListener('click', function () {
            var ouvert = nav.classList.toggle('ouvert');
            bouton.setAttribute('aria-expanded', ouvert);
            entete.classList.toggle('menu-ouvert', ouvert); document.body.classList.toggle('menu-ouvert', ouvert);
        });
        nav.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') { nav.classList.remove('ouvert'); bouton.setAttribute('aria-expanded', 'false'); entete.classList.remove('menu-ouvert'); document.body.classList.remove('menu-ouvert'); }
        });
    }

    // Apparition au défilement
    var elements = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('visible'); obs.unobserve(en.target); } });
        }, { threshold: 0.12 });
        elements.forEach(function (el) { obs.observe(el); });
    } else {
        elements.forEach(function (el) { el.classList.add('visible'); });
    }

    // Lightbox du logo propre à chaque glace / bûche
    var dlg = document.getElementById('dialogue-logo');
    document.addEventListener('click', function (e) {
        var b = e.target.closest('.carte-logo');
        if (!b || !dlg || !dlg.showModal) return;   // sans <dialog> : le lien ouvre l'image
        e.preventDefault();
        dlg.querySelector('img').src = b.dataset.logo;
        dlg.querySelector('img').alt = 'Logo ' + b.dataset.titre;
        dlg.querySelector('p').textContent = b.dataset.titre;
        dlg.showModal();
    });
    if (dlg) dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

    // Vidéo d'ambiance : bouton pause (accessibilité) ; arrêtée si l'utilisateur réduit les animations
    var video = document.querySelector('.hero video');
    var pause = document.querySelector('.video-pause');
    if (video && pause) {
        if (reduit) { video.pause(); pause.hidden = true; }
        pause.addEventListener('click', function () {
            var enPause = video.paused ? (video.play(), false) : (video.pause(), true);
            pause.setAttribute('aria-pressed', enPause);
            pause.textContent = enPause ? '▶' : '⏸';
            pause.setAttribute('aria-label', enPause ? 'Relancer la vidéo' : 'Mettre la vidéo en pause');
        });
    }

    // Portrait de Noisette : léger décalage au défilement
    var portrait = document.querySelector('[data-portrait] img');
    if (portrait && !reduit) {
        window.addEventListener('scroll', function () {
            var r = portrait.parentElement.getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight) return;
            portrait.style.transform = 'translateY(' + ((r.top / window.innerHeight) * -24) + 'px) scale(1.08)';
        }, { passive: true });
    }

    // La chèvre trotte le long des 4 étapes au fil du défilement
    var liste = document.querySelector('[data-chapitres]');
    var chevre = liste && liste.querySelector('.chevre-marcheuse');
    if (chevre && !reduit) {
        var placer = function () {
            var r = liste.getBoundingClientRect();
            var p = (window.innerHeight * 0.55 - r.top) / r.height;
            p = Math.max(0, Math.min(1, p));
            chevre.style.top = (p * (r.height - 32)) + 'px';
        };
        window.addEventListener('scroll', placer, { passive: true });
        window.addEventListener('resize', placer);
        placer();
    }

    // Formulaire de contact : champs adaptés au profil choisi
    var form = document.querySelector('.form-contact');
    if (form) {
        var profil = form.querySelector('[name=profil]');
        var adapter = function () {
            form.querySelectorAll('[data-pour]').forEach(function (el) {
                var visible = !profil.value || el.dataset.pour.split(' ').indexOf(profil.value) !== -1;
                el.hidden = !visible;
            });
        };
        profil.addEventListener('change', adapter);
        adapter();
    }
    // Liens « Organiser une visite » / « Proposer nos glaces » : pas de rechargement,
    // on présélectionne le profil dans le formulaire et on y fait défiler la page.
    function allerAuFormulaire(valeur, produit) {
        var cible = document.getElementById('contact');
        if (!form || !cible) return false;
        var profil = form.querySelector('[name=profil]');
        if (valeur && profil.querySelector('option[value="' + valeur + '"]')) {
            profil.value = valeur;
            profil.dispatchEvent(new Event('change'));
        }
        var choix = produit && form.querySelector('[name=produit]');
        if (choix && choix.tagName === 'SELECT') {
            for (var i = 0; i < choix.options.length; i++) if (choix.options[i].value === produit) choix.selectedIndex = i;
        }
        cible.scrollIntoView({ behavior: reduit ? 'auto' : 'smooth', block: 'start' });
        window.setTimeout(function () { profil.focus({ preventScroll: true }); }, reduit ? 0 : 700);
        return true;
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href*="profil="]');
        if (!a) return;
        var href = a.getAttribute('href');
        var m = href.match(/profil=([a-z]+)/), pr = href.match(/produit=([^&#]+)/);
        if (m && allerAuFormulaire(m[1], pr && decodeURIComponent(pr[1]))) e.preventDefault();
    });
    // Arrivée directe avec ?profil=… : le défilement initial est faussé par les images qui se chargent, on le refait une fois la page prête
    if (/[?&]profil=/.test(location.search) && form) {
        window.addEventListener('load', function () {
            var m = location.search.match(/profil=([a-z]+)/), pr = location.search.match(/produit=([^&#]+)/);
            allerAuFormulaire(m && m[1], pr && decodeURIComponent(pr[1]));
            // Les images « lazy » qui se chargent au-dessus décalent la page : on recale deux fois
            [900, 2200].forEach(function (d) {
                window.setTimeout(function () {
                    var cible = document.getElementById('contact');
                    if (Math.abs(cible.getBoundingClientRect().top - 80) > 20) window.scrollTo(0, cible.getBoundingClientRect().top + window.scrollY - 80);
                }, d);
            });
        });
    }

    var alerte = document.getElementById('alerte-contact');
    if (alerte) alerte.focus({ preventScroll: false });

    // Flocons de Noël (uniquement si le module Noël est actif)
    if (document.body.classList.contains('noel') && !reduit) {
        var conteneur = document.createElement('div');
        conteneur.className = 'flocons';
        conteneur.setAttribute('aria-hidden', 'true');
        var n = window.innerWidth < 600 ? 14 : 28;
        for (var i = 0; i < n; i++) {
            var f = document.createElement('span');
            f.className = 'flocon';
            f.textContent = i % 3 ? '❄' : '❅';
            f.style.left = Math.random() * 100 + '%';
            f.style.fontSize = (10 + Math.random() * 14) + 'px';
            f.style.animationDuration = (9 + Math.random() * 10) + 's';
            f.style.animationDelay = (-Math.random() * 15) + 's';
            f.style.setProperty('--derive', (Math.random() * 80 - 40) + 'px');
            conteneur.appendChild(f);
        }
        document.body.appendChild(conteneur);
    }
})();
