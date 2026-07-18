/**
 * ZoomBlog — reader control center, TOC scroll-spy, continue-reading,
 * reading-history recording. No dependencies.
 */
(function () {
	'use strict';

	var root = document.documentElement;
	var store = {
		get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : v; } catch (e) { return d; } },
		set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
		getJSON: function (k, d) { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (e) { return d; } },
		setJSON: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
	};

	/* ===================== Reader Control Center ===================== */
	var fab = document.querySelector('.zb-reader-fab');
	var panel = document.getElementById('zb-reader-panel');

	if (fab && panel) {
		fab.addEventListener('click', function () {
			var open = panel.classList.toggle('is-open');
			fab.setAttribute('aria-expanded', String(open));
		});
		document.addEventListener('click', function (e) {
			if (panel.classList.contains('is-open') && !panel.contains(e.target) && !fab.contains(e.target)) {
				panel.classList.remove('is-open');
				fab.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function applyReaderPrefs() {
		var size = store.get('zb-reader-size', '');
		var lh = store.get('zb-reader-lh', '');
		var width = store.get('zb-reader-width', '');
		if (size) root.style.setProperty('--zb-reader-size', size + 'px');
		if (lh) root.style.setProperty('--zb-reader-lh', lh);
		if (width) root.style.setProperty('--zb-reader-width', width === '100%' ? '100%' : width + 'px');
		document.body.classList.toggle('zb-hide-sidebar', store.get('zb-hide-sidebar', '0') === '1');
		document.body.classList.toggle('zb-focus', store.get('zb-focus', '0') === '1');
		markActive('size', size);
		markActive('lh', lh);
		markActive('width', width);
		markActive('theme', root.getAttribute('data-theme'));
		var hs = panel && panel.querySelector('[data-reader="hide-sidebar"]');
		if (hs) hs.checked = store.get('zb-hide-sidebar', '0') === '1';
		var fc = panel && panel.querySelector('[data-reader="focus"]');
		if (fc) fc.checked = store.get('zb-focus', '0') === '1';
	}

	function markActive(group, val) {
		if (!panel) return;
		var seg = panel.querySelector('[data-reader="' + group + '"]');
		if (!seg) return;
		seg.querySelectorAll('button').forEach(function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-val') === val);
		});
	}

	if (panel) {
		panel.addEventListener('click', function (e) {
			var btn = e.target.closest('button[data-val]');
			if (!btn) return;
			var group = btn.closest('[data-reader]').getAttribute('data-reader');
			var val = btn.getAttribute('data-val');
			if (group === 'size') { store.set('zb-reader-size', val); }
			else if (group === 'lh') { store.set('zb-reader-lh', val); }
			else if (group === 'width') { store.set('zb-reader-width', val); }
			else if (group === 'theme') { root.setAttribute('data-theme', val); store.set('zb-theme', val); }
			applyReaderPrefs();
		});
		panel.addEventListener('change', function (e) {
			var t = e.target;
			if (t.getAttribute('data-reader') === 'hide-sidebar') { store.set('zb-hide-sidebar', t.checked ? '1' : '0'); }
			if (t.getAttribute('data-reader') === 'focus') { store.set('zb-focus', t.checked ? '1' : '0'); }
			applyReaderPrefs();
		});
	}
	applyReaderPrefs();

	/* ===================== TOC toggle + scroll-spy ===================== */
	var toc = document.querySelector('.zb-toc');
	if (toc) {
		var toggle = toc.querySelector('.zb-toc__toggle');
		var list = toc.querySelector('.zb-toc__list');
		if (toggle && list) {
			toggle.addEventListener('click', function () {
				var open = toggle.getAttribute('aria-expanded') !== 'false';
				toggle.setAttribute('aria-expanded', String(!open));
				list.hidden = open;
			});
		}
		var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
		var targets = links.map(function (a) { return document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1))); }).filter(Boolean);
		if ('IntersectionObserver' in window && targets.length) {
			var spy = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting) {
						links.forEach(function (a) { a.parentElement.classList.remove('is-active'); });
						var active = toc.querySelector('a[href="#' + en.target.id + '"]');
						if (active) active.parentElement.classList.add('is-active');
					}
				});
			}, { rootMargin: '-80px 0px -70% 0px' });
			targets.forEach(function (t) { spy.observe(t); });
		}
	}

	/* ===================== Reading history + continue ===================== */
	var body = document.body;
	var m = body.className.match(/postid-(\d+)/);
	if (m && body.classList.contains('single')) {
		var pid = m[1];
		// Record history.
		var hist = store.getJSON('zb-history', []);
		hist = hist.filter(function (id) { return id != pid; });
		hist.unshift(pid);
		if (hist.length > 40) hist = hist.slice(0, 40);
		store.setJSON('zb-history', hist);

		// Continue reading: restore + save scroll position for this post.
		var posKey = 'zb-pos-' + pid;
		var saved = parseInt(store.get(posKey, '0'), 10);
		if (saved > 300 && saved < document.documentElement.scrollHeight - window.innerHeight - 100) {
			showContinue(saved);
		}
		var savePos = debounce(function () { store.set(posKey, String(Math.round(window.scrollY))); }, 400);
		window.addEventListener('scroll', savePos, { passive: true });
		window.addEventListener('beforeunload', function () {
			if (window.scrollY > document.documentElement.scrollHeight - window.innerHeight - 200) {
				store.set(posKey, '0'); // finished — clear.
			}
		});
	}

	function showContinue(pos) {
		var box = document.createElement('div');
		box.className = 'zb-continue';
		box.innerHTML = '<b>ادامهٔ مطالعه از محل قبلی؟</b><div class="zb-continue__actions">'
			+ '<button class="zb-btn zb-btn--primary zb-continue__yes">ادامه</button>'
			+ '<button class="zb-btn zb-btn--ghost zb-continue__no">نه</button></div>';
		document.body.appendChild(box);
		requestAnimationFrame(function () { box.classList.add('is-visible'); });
		box.querySelector('.zb-continue__yes').addEventListener('click', function () {
			window.scrollTo({ top: pos, behavior: 'smooth' });
			box.remove();
		});
		box.querySelector('.zb-continue__no').addEventListener('click', function () { box.remove(); });
		setTimeout(function () { if (box.parentNode) box.remove(); }, 8000);
	}

	function debounce(fn, wait) {
		var t; return function () { clearTimeout(t); t = setTimeout(fn, wait); };
	}
})();
