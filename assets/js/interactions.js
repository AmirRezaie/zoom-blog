/**
 * ZoomBlog — reader interactions: like, bookmark, follow, recommend.
 * Bookmarks & history stored in localStorage; likes/recommend/follow POST to
 * admin-ajax with the nonce. No dependencies.
 */
(function () {
	'use strict';

	var D = window.zoomblogData || {};
	var i18n = D.i18n || {};
	var feats = D.features || {};

	var store = {
		getJSON: function (k, d) { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (e) { return d; } },
		setJSON: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
	};

	function post(action, data) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', D.nonce || '');
		Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
		return fetch(D.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
			.then(function (r) { return r.json(); });
	}

	/* ---- Likes ----------------------------------------------------------- */
	if (feats.like) {
		var liked = store.getJSON('zb-liked', []);
		document.querySelectorAll('.zb-like').forEach(function (btn) {
			var id = btn.getAttribute('data-post-id');
			if (liked.indexOf(id) !== -1) { btn.classList.add('is-active'); btn.setAttribute('aria-pressed', 'true'); }
		});
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.zb-like');
			if (!btn) return;
			var id = btn.getAttribute('data-post-id');
			var list = store.getJSON('zb-liked', []);
			var on = list.indexOf(id) !== -1;
			var dir = on ? 'down' : 'up';
			if (on) { list = list.filter(function (x) { return x !== id; }); } else { list.push(id); }
			store.setJSON('zb-liked', list);
			btn.classList.toggle('is-active', !on);
			btn.setAttribute('aria-pressed', String(!on));
			post('zoomblog_like', { post_id: id, dir: dir }).then(function (res) {
				if (res && res.success) {
					var c = btn.querySelector('.zb-like__count');
					if (c) c.textContent = res.data.formatted;
				}
			});
		});
	}

	/* ---- Bookmarks (client-side) ---------------------------------------- */
	if (feats.bookmark) {
		var refreshBookmarkBtns = function () {
			var marks = store.getJSON('zb-bookmarks', []);
			document.querySelectorAll('.zb-bookmark').forEach(function (btn) {
				var id = btn.getAttribute('data-post-id');
				var on = marks.indexOf(id) !== -1;
				btn.classList.toggle('is-active', on);
				btn.setAttribute('aria-pressed', String(on));
			});
		};
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.zb-bookmark');
			if (!btn) return;
			var id = btn.getAttribute('data-post-id');
			var marks = store.getJSON('zb-bookmarks', []);
			var on = marks.indexOf(id) !== -1;
			if (on) { marks = marks.filter(function (x) { return x !== id; }); } else { marks.unshift(id); }
			store.setJSON('zb-bookmarks', marks);
			refreshBookmarkBtns();
		});
		refreshBookmarkBtns();
	}

	/* ---- Follow ---------------------------------------------------------- */
	if (feats.follow) {
		var following = store.getJSON('zb-following', []);
		document.querySelectorAll('.zb-follow').forEach(function (btn) {
			var id = btn.getAttribute('data-author-id');
			if (following.indexOf(id) !== -1) { setFollow(btn, true); }
		});
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.zb-follow');
			if (!btn) return;
			var id = btn.getAttribute('data-author-id');
			var list = store.getJSON('zb-following', []);
			var on = list.indexOf(id) !== -1;
			if (on) { list = list.filter(function (x) { return x !== id; }); } else { list.push(id); }
			store.setJSON('zb-following', list);
			setFollow(btn, !on);
			post('zoomblog_follow', { author_id: id });
		});
	}
	function setFollow(btn, on) {
		btn.classList.toggle('is-active', on);
		btn.setAttribute('aria-pressed', String(on));
		btn.textContent = on ? (i18n.following || 'دنبال می‌کنید') : (i18n.follow || 'دنبال‌کردن');
	}

	/* ---- Recommend ------------------------------------------------------- */
	if (feats.recommend) {
		document.querySelectorAll('.zb-recommend').forEach(function (box) {
			var id = box.getAttribute('data-post-id');
			var voted = store.getJSON('zb-recommended', []);
			if (voted.indexOf(id) !== -1) { disableRecommend(box); }
			box.addEventListener('click', function (e) {
				var btn = e.target.closest('.zb-recommend__btn');
				if (!btn) return;
				var vote = btn.getAttribute('data-vote');
				var list = store.getJSON('zb-recommended', []);
				if (list.indexOf(id) !== -1) return;
				list.push(id); store.setJSON('zb-recommended', list);
				post('zoomblog_recommend', { post_id: id, vote: vote }).then(function (res) {
					if (res && res.success) {
						var pct = box.querySelector('.zb-recommend__percent');
						var bar = box.querySelector('.zb-recommend__bar span');
						if (pct) pct.textContent = res.data.percent + '٪';
						if (bar) bar.style.width = res.data.percent + '%';
					}
					disableRecommend(box);
				});
			});
		});
	}
	function disableRecommend(box) {
		box.querySelectorAll('.zb-recommend__btn').forEach(function (b) { b.disabled = true; });
		box.classList.add('is-voted');
	}

	/* ---- Render bookmark / history panels on demand --------------------- */
	// Any element with [data-zb-list="bookmarks"|"history"] gets filled.
	document.querySelectorAll('[data-zb-list]').forEach(function (el) {
		var type = el.getAttribute('data-zb-list');
		var key = type === 'history' ? 'zb-history' : 'zb-bookmarks';
		var ids = store.getJSON(key, []);
		if (!ids.length) { el.innerHTML = '<p class="zb-empty">' + (i18n.empty || 'موردی نیست.') + '</p>'; return; }
		post('zoomblog_render_list', { ids: JSON.stringify(ids) }).then(function (res) {
			if (res && res.success) { el.innerHTML = res.data.html; }
		});
		el.addEventListener('click', function (e) {
			var rm = e.target.closest('.zb-minilist__remove');
			if (!rm) return;
			var id = rm.getAttribute('data-post-id');
			var list = store.getJSON(key, []).filter(function (x) { return x !== id; });
			store.setJSON(key, list);
			rm.closest('.zb-minilist__item').remove();
		});
	});
})();
