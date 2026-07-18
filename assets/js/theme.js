/**
 * ZoomBlog — core front-end (no dependencies).
 * Theme toggle, sticky header, mobile drawer, header search, progress bar,
 * copy-to-clipboard sharing.
 */
(function () {
	'use strict';

	var root = document.documentElement;

	/* ---- Light/Dark toggle (cycles light↔dark; sepia set via reader panel) */
	function currentTheme() {
		return root.getAttribute('data-theme') || 'light';
	}
	function setTheme(t) {
		root.setAttribute('data-theme', t);
		try { localStorage.setItem('zb-theme', t); } catch (e) {}
		syncThemeIcon();
		document.dispatchEvent(new CustomEvent('zb:themechange', { detail: t }));
	}
	function syncThemeIcon() {
		var dark = currentTheme() === 'dark';
		document.querySelectorAll('.zb-theme-toggle').forEach(function (btn) {
			var sun = btn.querySelector('.zb-icon-sun');
			var moon = btn.querySelector('.zb-icon-moon');
			if (sun) sun.hidden = dark;
			if (moon) moon.hidden = !dark;
		});
	}
	document.addEventListener('click', function (e) {
		var toggle = e.target.closest('.zb-theme-toggle');
		if (toggle) {
			setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
		}
	});
	syncThemeIcon();

	/* ---- Mobile drawer --------------------------------------------------- */
	var burger = document.querySelector('.zb-burger');
	var drawer = document.getElementById('zb-drawer');
	if (burger && drawer) {
		burger.addEventListener('click', function () {
			var open = drawer.hasAttribute('hidden');
			if (open) { drawer.removeAttribute('hidden'); } else { drawer.setAttribute('hidden', ''); }
			burger.setAttribute('aria-expanded', String(open));
			document.body.style.overflow = open ? 'hidden' : '';
		});
	}

	/* ---- Header search toggle ------------------------------------------- */
	var searchToggle = document.querySelector('.zb-search-toggle');
	var headerSearch = document.querySelector('.zb-header__search');
	if (searchToggle && headerSearch) {
		searchToggle.addEventListener('click', function () {
			var open = headerSearch.hasAttribute('hidden');
			if (open) { headerSearch.removeAttribute('hidden'); } else { headerSearch.setAttribute('hidden', ''); }
			searchToggle.setAttribute('aria-expanded', String(open));
			if (open) {
				var input = headerSearch.querySelector('input[type="search"]');
				if (input) input.focus();
			}
		});
	}

	/* ---- Reading progress bar (rAF-throttled) --------------------------- */
	var fill = document.querySelector('.zb-progress__fill');
	if (fill) {
		var ticking = false;
		function update() {
			var h = document.documentElement.scrollHeight - window.innerHeight;
			var pct = h > 0 ? (window.scrollY / h) * 100 : 0;
			fill.style.width = pct + '%';
			ticking = false;
		}
		window.addEventListener('scroll', function () {
			if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
		}, { passive: true });
		update();
	}

	/* ---- Copy-to-clipboard share ---------------------------------------- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.zb-share__copy');
		if (!btn) return;
		var url = btn.getAttribute('data-url') || location.href;
		var done = function () {
			var old = btn.textContent;
			btn.textContent = '✓';
			setTimeout(function () { btn.textContent = old; }, 1500);
		};
		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(done).catch(done);
		} else {
			var t = document.createElement('textarea');
			t.value = url; document.body.appendChild(t); t.select();
			try { document.execCommand('copy'); } catch (err) {}
			document.body.removeChild(t); done();
		}
	});

	/* ---- Sticky header shadow on scroll --------------------------------- */
	var header = document.querySelector('.zb-sticky-header .zb-header');
	if (header) {
		window.addEventListener('scroll', function () {
			header.classList.toggle('is-scrolled', window.scrollY > 10);
		}, { passive: true });
	}
})();
