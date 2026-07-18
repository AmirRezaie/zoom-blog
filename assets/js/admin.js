/**
 * ZoomBlog — admin settings panel: group navigation, live search filtering,
 * and color pickers. Depends on wp-color-picker (jQuery) which WP loads in
 * admin; the rest is vanilla.
 */
(function () {
	'use strict';

	var wrap = document.getElementById('zb-settings');
	if (!wrap) return;

	var navItems = wrap.querySelectorAll('.zb-navitem');
	var panels = wrap.querySelectorAll('.zb-panel');
	var search = document.getElementById('zb-search');
	var noResults = wrap.querySelector('.zb-admin__noresults');

	/* ---- Group navigation ------------------------------------------------ */
	navItems.forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (search && search.value) { search.value = ''; runSearch(); }
			var group = btn.getAttribute('data-group');
			navItems.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
			panels.forEach(function (p) {
				p.classList.toggle('is-active', p.getAttribute('data-group') === group);
				p.classList.remove('zb-forced-visible');
			});
			wrap.querySelectorAll('.zb-field').forEach(function (f) { f.style.display = ''; });
		});
	});

	/* ---- Live search filtering ------------------------------------------ */
	function runSearch() {
		var q = (search.value || '').trim().toLowerCase();
		if (!q) {
			// Restore: show active panel only.
			panels.forEach(function (p) { p.classList.remove('zb-forced-visible'); });
			wrap.querySelectorAll('.zb-field').forEach(function (f) { f.style.display = ''; });
			if (noResults) noResults.hidden = true;
			return;
		}
		var anyMatch = false;
		panels.forEach(function (panel) {
			var fields = panel.querySelectorAll('.zb-field');
			var panelHas = false;
			fields.forEach(function (f) {
				var hay = f.getAttribute('data-search') || '';
				var match = hay.indexOf(q) !== -1;
				f.style.display = match ? '' : 'none';
				if (match) { panelHas = true; anyMatch = true; }
			});
			panel.classList.toggle('zb-forced-visible', panelHas);
			panel.classList.toggle('is-active', false);
		});
		if (noResults) noResults.hidden = anyMatch;
	}
	if (search) {
		search.addEventListener('input', runSearch);
	}

	/* ---- Color pickers --------------------------------------------------- */
	if (window.jQuery && jQuery.fn.wpColorPicker) {
		jQuery('.zb-color').wpColorPicker();
	}
})();
