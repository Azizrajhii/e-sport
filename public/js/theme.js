/* e-Sports.tn — public site motion & navigation.
   Works on every page without markup changes: cards and grid items are
   detected automatically and get reveal-on-scroll + hover spotlight. */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var body = document.body;

    /* ---------- Navbar: scrolled state, progress bar, back-to-top ---------- */
    var nav = document.querySelector('.site-nav');
    var progress = document.querySelector('.scroll-progress');
    var toTop = document.querySelector('.to-top');

    function onScroll() {
        var y = window.scrollY;
        var max = document.documentElement.scrollHeight - window.innerHeight;
        if (nav) nav.classList.toggle('is-scrolled', y > 10);
        if (progress) progress.style.setProperty('--progress', max > 0 ? (y / max).toFixed(4) : 0);
        if (toTop) toTop.classList.toggle('show', y > 600);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (toTop) {
        toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }

    /* ---------- Mobile menu & submenus ---------- */
    var toggle = document.querySelector('.nav-toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var open = body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.querySelector('i').className = open ? 'fas fa-times' : 'fas fa-bars';
        });
    }

    document.querySelectorAll('.has-menu > .site-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var item = link.parentElement;
            var wasOpen = item.classList.contains('open');
            document.querySelectorAll('.has-menu.open').forEach(function (m) { m.classList.remove('open'); });
            item.classList.toggle('open', !wasOpen);
            link.setAttribute('aria-expanded', !wasOpen ? 'true' : 'false');
            item.dispatchEvent(new CustomEvent('submenu:open', { bubbles: true }));
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.has-menu')) {
            document.querySelectorAll('.has-menu.open').forEach(function (m) { m.classList.remove('open'); });
        }
    });

    /* ---------- Active nav link ---------- */
    var here = window.location.pathname.replace(/\/$/, '') || '/';
    document.querySelectorAll('.site-menu a[href]').forEach(function (a) {
        var path = a.getAttribute('href').replace(/\/$/, '') || '/';
        if (path !== '/' ? here.indexOf(path) === 0 : here === '/') {
            a.classList.add('active');
            var parent = a.closest('.has-menu');
            if (parent) parent.querySelector('.site-link').classList.add('active');
        }
    });

    /* ---------- Flash messages: auto-dismiss ---------- */
    document.querySelectorAll('.flash-stack .alert').forEach(function (alert, i) {
        setTimeout(function () {
            alert.classList.add('leaving');
            setTimeout(function () { alert.remove(); }, 400);
        }, 5000 + i * 600);
    });

    /* ---------- Cards: spotlight + lift ---------- */
    var main = document.querySelector('main');
    if (!main) return;

    var cardSelector = [
        '.card', '[class*="-card"]:not([class*="-card-"])', '[class*="_card"]',
        '.single_blog_post', '.single_product_item', '.team-item', '.game-item'
    ].join(',');

    var cards = Array.prototype.filter.call(main.querySelectorAll(cardSelector), function (el) {
        // Skip page-wide wrappers and nested cards
        var rect = el.getBoundingClientRect();
        if (rect.width > window.innerWidth * 0.9 || rect.height > 900) return false;
        return !el.parentElement.closest(cardSelector);
    });

    cards.forEach(function (card) {
        if (getComputedStyle(card).position === 'static') card.style.position = 'relative';
        card.classList.add('fx-card');
        card.addEventListener('pointermove', function (e) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });

    /* ---------- Scroll reveal ---------- */
    if (reduceMotion || !('IntersectionObserver' in window)) return;

    var targets = new Set(cards);
    main.querySelectorAll('.page-hero .container > *, main > section, .row > [class*="col"], [data-reveal]').forEach(function (el) {
        if (!el.closest('.fx-card') || el.classList.contains('fx-card')) targets.add(el);
    });

    // Drop targets nested inside another target so groups animate once
    var list = Array.prototype.filter.call(Array.from(targets), function (el) {
        // Carousels and modals position items off-screen: never hide those
        if (el.closest('.owl-carousel, .slick-slider, .swiper, .modal, .site-submenu, [data-no-reveal]')) return false;
        var p = el.parentElement;
        while (p && p !== main) { if (targets.has(p)) return false; p = p.parentElement; }
        return true;
    });

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
            startCounters(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    list.forEach(function (el) {
        // Stagger siblings in the same grid
        var siblings = Array.prototype.filter.call(el.parentElement.children, function (c) { return list.indexOf(c) !== -1; });
        var index = siblings.indexOf(el);
        el.style.setProperty('--delay', Math.min(index, 6) * 0.08 + 's');
        el.classList.add('reveal');
        io.observe(el);
    });

    /* ---------- Count-up numbers ---------- */
    function startCounters(scope) {
        scope.querySelectorAll('[data-count], .number, .stat-number, .stat-value, .count-number').forEach(function (el) {
            if (el.dataset.counted || el.children.length) return;
            var text = el.textContent.trim();
            var match = text.match(/^([^\d-]*)(-?[\d\s.,]+)(.*)$/);
            if (!match) return;
            var raw = match[2].replace(/\s/g, '');
            var decimals = (raw.split(/[.,]/)[1] || '').length;
            var target = parseFloat(raw.replace(/,(?=\d{3}\b)/g, '').replace(',', '.'));
            if (!isFinite(target) || target === 0) return;
            el.dataset.counted = '1';
            var start = performance.now(), duration = 1200;
            (function tick(now) {
                var t = Math.min((now - start) / duration, 1);
                var eased = 1 - Math.pow(1 - t, 3);
                el.textContent = match[1] + (target * eased).toFixed(decimals) + match[3];
                if (t < 1) requestAnimationFrame(tick); else el.textContent = text;
            })(start);
        });
    }

    /* ---------- Button ripple ---------- */
    document.addEventListener('pointerdown', function (e) {
        var btn = e.target.closest('.btn');
        if (!btn) return;
        var r = btn.getBoundingClientRect();
        var size = Math.max(r.width, r.height);
        var span = document.createElement('span');
        span.className = 'ripple';
        span.style.width = span.style.height = size + 'px';
        span.style.left = (e.clientX - r.left - size / 2) + 'px';
        span.style.top = (e.clientY - r.top - size / 2) + 'px';
        btn.appendChild(span);
        setTimeout(function () { span.remove(); }, 600);
    });
})();
