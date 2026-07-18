/**
 * ZoomBlog — live search. Debounced REST calls, image + category + type in
 * results, recent searches persisted locally. No dependencies.
 */
(function () {
	'use strict';

	var S = window.zoomblogSearch || {};
	var minChars = S.minChars || 2;
	var i18n = S.i18n || {};

	function store() {
		try { return JSON.parse(localStorage.getItem('zb-recent-searches')) || []; } catch (e) { return []; }
	}
	function pushRecent(q) {
		if (!q) return;
		var list = store().filter(function (x) { return x !== q; });
		list.unshift(q);
		if (list.length > 6) list = list.slice(0, 6);
		try { localStorage.setItem('zb-recent-searches', JSON.stringify(list)); } catch (e) {}
	}

	function debounce(fn, wait) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, wait); }; }

	function renderResults(box, results) {
		if (!results.length) { box.innerHTML = '<div class="zb-live-empty">' + (i18n.noResults || 'نتیجه‌ای نیست') + '</div>'; box.hidden = false; return; }
		var html = '<ul class="zb-live-list">';
		results.forEach(function (r) {
			html += '<li class="zb-live-item"><a href="' + r.url + '">'
				+ (r.thumb ? '<img src="' + r.thumb + '" alt="" loading="lazy" width="56" height="56">' : '<span class="zb-live-thumb"></span>')
				+ '<span class="zb-live-text"><b>' + escapeHtml(r.title) + '</b>'
				+ '<small>' + escapeHtml(r.category || '') + (r.type ? ' · ' + escapeHtml(r.type) : '') + '</small></span></a></li>';
		});
		html += '</ul>';
		box.innerHTML = html;
		box.hidden = false;
	}
	function escapeHtml(s) { return (s || '').replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }

	function wire(form) {
		var input = form.querySelector('input[type="search"]');
		var box = form.querySelector('.zb-live-results');
		if (!input || !box) return;

		var run = debounce(function () {
			var q = input.value.trim();
			if (q.length < minChars) { box.hidden = true; box.innerHTML = ''; return; }
			box.innerHTML = '<div class="zb-live-loading">' + (i18n.searching || '…') + '</div>';
			box.hidden = false;
			fetch(S.restUrl + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (data) { renderResults(box, (data && data.results) || []); })
				.catch(function () { box.hidden = true; });
		}, 220);

		input.addEventListener('input', run);
		form.addEventListener('submit', function () { pushRecent(input.value.trim()); });
		document.addEventListener('click', function (e) {
			if (!form.contains(e.target)) { box.hidden = true; }
		});
	}

	document.querySelectorAll('form.zb-searchform[data-live-search="1"]').forEach(wire);

	/* ---- Recent searches chips on the search page ----------------------- */
	var recentWrap = document.querySelector('[data-recent-searches]');
	if (recentWrap) {
		var list = store();
		if (list.length) {
			var holder = recentWrap.querySelector('.zb-recent-searches__list');
			list.forEach(function (q) {
				var a = document.createElement('a');
				a.className = 'zb-tab';
				a.href = '?s=' + encodeURIComponent(q);
				a.textContent = q;
				holder.appendChild(a);
			});
			recentWrap.hidden = false;
		}
	}
})();
