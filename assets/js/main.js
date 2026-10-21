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

    // Menu mobile
    var bouton = document.querySelector('.nav-toggle');
    var nav = document.getElementById('nav-principal');
    if (bouton && nav) {
        bouton.addEventListener('click', function () {
            var ouvert = nav.classList.toggle('ouvert');
            bouton.setAttribute('aria-expanded', ouvert);
        });
        nav.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') { nav.classList.remove('ouvert'); bouton.setAttribute('aria-expanded', 'false'); }
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
        if (!b || !dlg || !dlg.showModal) return;
        dlg.querySelector('img').src = b.dataset.logo;
        dlg.querySelector('img').alt = 'Logo ' + b.dataset.titre;
        dlg.querySelector('p').textContent = b.dataset.titre;
        dlg.showModal();
    });
    if (dlg) dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

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
