/* USA Smart Gamers theme UI: mobile mega-nav, search toggle, sticky section menu, back-to-top. */
(function () {
	'use strict';
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var body = document.body;

	/* Mobile menu */
	var toggle = $('[data-nav-toggle]');
	if (toggle) {
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') !== 'true';
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			body.classList.toggle('nav-open', open);
		});
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('.mega__toggle');
		if (!b) { return; }
		var item = b.closest('.mega__item');
		var open = b.getAttribute('aria-expanded') !== 'true';
		$$('.mega__item--d0.is-open').forEach(function (i) { if (i !== item) { i.classList.remove('is-open'); $('.mega__toggle', i).setAttribute('aria-expanded', 'false'); } });
		b.setAttribute('aria-expanded', open ? 'true' : 'false');
		item.classList.toggle('is-open', open);
	});
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') { return; }
		$$('.mega__item--d0.is-open').forEach(function (i) { i.classList.remove('is-open'); });
		if (body.classList.contains('nav-open') && toggle) { toggle.click(); }
	});

	/* Header search */
	var st = $('[data-search-toggle]');
	var sb = $('#header-search');
	if (st && sb) {
		st.addEventListener('click', function () {
			sb.hidden = !sb.hidden;
			st.setAttribute('aria-expanded', sb.hidden ? 'false' : 'true');
			if (!sb.hidden) { var i = $('input[type=search]', sb); if (i) { i.focus(); } }
		});
	}

	/* Sticky section menu (table of contents) built from H2s */
	var toc = $('[data-toc]');
	var content = $('.entry-content');
	if (toc && content) {
		var heads = $$('h2', content).filter(function (h) { return !h.closest('.usg-offer, .usg-faq__item, .author-box, .usg-calc, .usg-account, .usg-ureviews__formwrap') && h.textContent.trim(); });
		if (heads.length >= 3) {
			var list = $('.toc-bar__list', toc);
			var used = {};
			heads.forEach(function (h) {
				if (!h.id) {
					var id = h.textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) || 'section';
					while (used[id] || document.getElementById(id)) { id += '-x'; }
					h.id = id;
				}
				used[h.id] = true;
				var li = document.createElement('li');
				var a = document.createElement('a');
				a.href = '#' + h.id;
				a.textContent = h.textContent.replace(/\s+/g, ' ').trim().slice(0, 48);
				li.appendChild(a);
				list.appendChild(li);
			});
			toc.hidden = false;
			if ('IntersectionObserver' in window) {
				var links = $$('a', list);
				var io = new IntersectionObserver(function (entries) {
					entries.forEach(function (en) {
						if (!en.isIntersecting) { return; }
						links.forEach(function (l) {
							var on = l.getAttribute('href') === '#' + en.target.id;
							l.classList.toggle('is-active', on);
							if (on && l.scrollIntoView && list.scrollWidth > list.clientWidth) { list.scrollTo({ left: l.offsetLeft - 20, behavior: 'smooth' }); }
						});
					});
				}, { rootMargin: '-120px 0px -70% 0px' });
				heads.forEach(function (h) { io.observe(h); });
			}
		}
	}

	/* Back to top */
	var top = $('[data-to-top]');
	if (top) {
		window.addEventListener('scroll', function () { top.hidden = window.scrollY < 900; }, { passive: true });
		top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
	}
})();
