/* USA Smart Gamers – front-end interactivity (no dependencies). */
(function () {
	'use strict';
	var D = window.usgData || {};
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

	function api(path, opts) {
		opts = opts || {};
		var headers = { 'Content-Type': 'application/json' };
		if (D.nonce) { headers['X-WP-Nonce'] = D.nonce; }
		return fetch(D.rest + path, { method: opts.method || 'GET', headers: headers, credentials: 'same-origin', body: opts.body ? JSON.stringify(opts.body) : undefined })
			.then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw j; } return j; }); });
	}
	function cookie(name, value, days) {
		if (value === undefined) {
			var m = document.cookie.match('(?:^|; )' + name + '=([^;]*)');
			return m ? decodeURIComponent(m[1]) : '';
		}
		document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (days * 86400) + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
	}
	function setBalance(n) { $$('[data-usg-balance]').forEach(function (el) { el.textContent = Number(n).toLocaleString(); }); }

	/* ---- Copy promo codes ---- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-usg-copy]');
		if (!b) { return; }
		var text = b.getAttribute('data-usg-copy');
		var done = function () { b.classList.add('is-copied'); setTimeout(function () { b.classList.remove('is-copied'); }, 1800); };
		if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, done); } else { done(); }
	});

	/* ---- Geo-targeted toplists ---- */
	function refreshGeo() {
		$$('[data-usg-geo]').forEach(function (box) {
			var cfg;
			try { cfg = JSON.parse(box.getAttribute('data-usg-geo')); } catch (err) { return; }
			box.classList.add('is-loading');
			api('geo-block?_=' + Date.now(), { method: 'POST', body: cfg }).then(function (res) {
				if (res && typeof res.html === 'string') { box.innerHTML = res.html; }
				syncPickers(res.state);
			}).catch(function () {}).then(function () { box.classList.remove('is-loading'); });
		});
	}
	function syncPickers(state) {
		$$('[data-usg-state-picker]').forEach(function (s) { if (state) { s.value = state; } });
	}
	document.addEventListener('change', function (e) {
		if (!e.target.matches('[data-usg-state-picker]')) { return; }
		cookie('usg_state', e.target.value, e.target.value ? 365 : -1);
		syncPickers(e.target.value);
		refreshGeo();
	});
	document.addEventListener('click', function (e) {
		if (!e.target.closest('[data-usg-open-picker]')) { return; }
		var p = $('[data-usg-state-picker]');
		if (p) { p.focus(); if (p.showPicker) { try { p.showPicker(); } catch (err) {} } p.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
	});
	syncPickers(cookie('usg_state'));
	if ($('[data-usg-geo]')) { refreshGeo(); }

	/* ---- Tabs ---- */
	document.addEventListener('click', function (e) {
		var tab = e.target.closest('.usg-tabs__tab');
		if (!tab) { return; }
		var nav = tab.parentNode;
		$$('.usg-tabs__tab', nav).forEach(function (t) {
			var on = t === tab;
			t.setAttribute('aria-selected', on ? 'true' : 'false');
			var panel = document.getElementById(t.getAttribute('aria-controls'));
			if (panel) { panel.hidden = !on; }
		});
	});

	/* ---- Show more lists ---- */
	$$('[data-usg-showmore]').forEach(function (list) {
		var n = parseInt(list.getAttribute('data-usg-showmore'), 10) || 5;
		var items = $$(':scope > li', list);
		if (items.length <= n) { return; }
		items.slice(n).forEach(function (li) { li.hidden = true; });
		var btn = document.createElement('button');
		btn.type = 'button'; btn.className = 'usg-btn usg-btn--ghost usg-btn--block'; btn.textContent = 'Show more';
		btn.addEventListener('click', function () { items.forEach(function (li) { li.hidden = false; }); btn.remove(); });
		list.after(btn);
	});

	/* ---- Player review form ---- */
	document.addEventListener('submit', function (e) {
		var f = e.target.closest('[data-usg-review]');
		if (!f) { return; }
		e.preventDefault();
		var msg = $('.usg-form-msg', f);
		var fd = new FormData(f);
		var body = { post_id: parseInt(f.getAttribute('data-usg-review'), 10), rating: fd.get('rating'), title: fd.get('title'), content: fd.get('content'), sub: {} };
		fd.forEach(function (v, k) { var m = k.match(/^sub\[(\w+)\]$/); if (m) { body.sub[m[1]] = v; } });
		f.querySelector('button[type=submit]').disabled = true;
		api('reviews', { method: 'POST', body: body }).then(function (r) { msg.textContent = r.message; f.reset(); })
			.catch(function (err) { msg.textContent = (err && err.message) || 'Something went wrong.'; f.querySelector('button[type=submit]').disabled = false; });
	});

	/* ---- Slot demos ---- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-usg-demo]');
		if (!b) { return; }
		var wrap = b.closest('.usg-demo');
		var url = b.getAttribute('data-usg-demo');
		if (!url || !wrap) { return; }
		var frame = document.createElement('iframe');
		frame.src = url; frame.title = 'Slot demo'; frame.loading = 'lazy'; frame.allow = 'fullscreen';
		frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups');
		$('.usg-demo__stage', wrap).innerHTML = '';
		$('.usg-demo__stage', wrap).appendChild(frame);
		wrap.classList.add('is-playing');
		if (D.nonce) {
			api('task/slot-demo', { method: 'POST', body: { slot: parseInt(b.getAttribute('data-slot'), 10) } }).then(function (r) { if (r.coins) { setBalance(r.balance); } }).catch(function () {});
		}
	});

	/* ---- Rewards ---- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-usg-redeem]');
		if (!b) { return; }
		var msg = $('.usg-reward__msg', b.parentNode);
		b.disabled = true;
		api('redeem', { method: 'POST', body: { reward: parseInt(b.getAttribute('data-usg-redeem'), 10) } })
			.then(function (r) { msg.textContent = r.message; setBalance(r.balance); })
			.catch(function (err) { msg.textContent = (err && err.message) || 'Something went wrong.'; b.disabled = false; });
	});

	/* ---- Notifications bell ---- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-usg-bell]');
		var openPanel = $('.usg-bell__panel:not([hidden])');
		if (!b) { if (openPanel && !e.target.closest('.usg-bell__panel')) { openPanel.hidden = true; } return; }
		var panel = b.nextElementSibling;
		panel.hidden = !panel.hidden;
		b.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
		var badge = $('.usg-bell__badge', b);
		if (!panel.hidden && badge) { badge.remove(); api('me/seen', { method: 'POST', body: {} }).catch(function () {}); }
	});

	/* ---- Sticky footer CTA ---- */
	var sticky = $('[data-usg-sticky]');
	if (sticky && !sessionStorage.getItem('usgStickyClosed')) {
		var onScroll = function () { sticky.hidden = window.scrollY < 700; };
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
		$('.usg-sticky-cta__close', sticky).addEventListener('click', function () {
			sessionStorage.setItem('usgStickyClosed', '1');
			window.removeEventListener('scroll', onScroll);
			sticky.hidden = true;
		});
	}

	/* ---- Calculators ---- */
	function americanToDecimal(a) { a = parseFloat(String(a).replace('+', '')); if (!a || Math.abs(a) < 100) { return NaN; } return a > 0 ? 1 + a / 100 : 1 + 100 / Math.abs(a); }
	function decimalToAmerican(d) { if (!(d > 1)) { return NaN; } return d >= 2 ? Math.round((d - 1) * 100) : Math.round(-100 / (d - 1)); }
	function gcd(a, b) { return b ? gcd(b, a % b) : a; }
	function decimalToFraction(d) { var n = Math.round((d - 1) * 100), g = gcd(n, 100); return (n / g) + '/' + (100 / g); }
	function money(n) { return isFinite(n) ? (n < 0 ? '-$' : '$') + Math.abs(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'; }
	function pct(n) { return isFinite(n) ? (n * 100).toFixed(2) + '%' : '—'; }
	function fmtAm(a) { return isFinite(a) ? (a > 0 ? '+' + a : String(a)) : '—'; }

	var calcs = {
		bonus: function (v) {
			var bonus = Math.min(v.deposit * v.match / 100, v.max || Infinity);
			var base = v.applies === 'both' ? v.deposit + bonus : bonus;
			var play = base * v.wager / (Math.max(v.contrib, 1) / 100);
			var loss = play * (1 - v.rtp / 100);
			return { bonus: money(bonus), playthrough: money(play), loss: money(loss), ev: money(bonus - loss) };
		},
		odds: function (v) {
			var d;
			var raw = String(v.odds).trim();
			if (v.format === 'decimal') { d = parseFloat(raw); }
			else if (v.format === 'fractional') { var p = raw.split('/'); d = 1 + parseFloat(p[0]) / parseFloat(p[1] || 1); }
			else { d = americanToDecimal(raw); }
			if (!(d > 1)) { return {}; }
			return { american: fmtAm(decimalToAmerican(d)), decimal: d.toFixed(2), fractional: decimalToFraction(d), implied: pct(1 / d), payout: money(v.stake * d), profit: money(v.stake * (d - 1)) };
		},
		implied: function (v) {
			var pa = 1 / americanToDecimal(v.a), pb = 1 / americanToDecimal(v.b), t = pa + pb;
			return { pa: pct(pa), pb: pct(pb), vig: pct(1 - 1 / t), fa: pct(pa / t), fb: pct(pb / t) };
		},
		hedge: function (v) {
			var d1 = americanToDecimal(v.odds1), d2 = americanToDecimal(v.odds2);
			var h = v.stake * d1 / d2;
			return { hedge: money(h), profit: money(v.stake * d1 - v.stake - h), ifwin1: money(v.stake * (d1 - 1)) };
		},
		martingale: function (v, box) {
			var rows = '<thead><tr><th>Loss #</th><th>Bet</th><th>Total risked</th></tr></thead><tbody>', total = 0, bet = v.base, survive = 0;
			for (var i = 1; i <= Math.min(v.losses, 20); i++) {
				total += bet;
				if (total <= v.bankroll) { survive = i; }
				rows += '<tr><td>' + i + '</td><td>' + money(bet) + '</td><td>' + money(total) + '</td></tr>';
				if (i < v.losses) { bet *= v.mult; }
			}
			var t = $('[data-out=table]', box); if (t) { t.innerHTML = rows + '</tbody>'; }
			return { needed: money(total), lastbet: money(bet), survive: String(survive) };
		}
	};
	$$('[data-usg-calc]').forEach(function (box) {
		var type = box.getAttribute('data-usg-calc');
		var run = function () {
			var v = {};
			$$('input,select', box).forEach(function (i) { v[i.name] = (i.type === 'number') ? (parseFloat(i.value) || 0) : i.value; });
			var out = calcs[type] ? calcs[type](v, box) : {};
			$$('[data-out]', box).forEach(function (el) { var k = el.getAttribute('data-out'); if (k !== 'table') { el.textContent = out[k] !== undefined ? out[k] : '—'; } });
		};
		box.addEventListener('input', run);
		run();
	});

	/* ---- Casino finder ---- */
	var finder = $('[data-usg-finder]');
	if (finder) {
		var data = [];
		var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
		var render = function () {
			if (!data.length) {
				finder.innerHTML = '<div class="usg-coming-soon"><span>Coming soon</span></div>';
				return;
			}
			var f = {}; $$('input,select', finder).forEach(function (i) { f[i.name] = i.value; });
			var q = (f.q || '').toLowerCase();
			var list = data.filter(function (o) {
				if (q && o.name.toLowerCase().indexOf(q) === -1) { return false; }
				if (f.vertical && o.vertical !== f.vertical) { return false; }
				if (f.payment && o.payments.indexOf(f.payment) === -1) { return false; }
				if (f.state) {
					if (o.mode === 'international') { return false; }
					var listed = o.states.indexOf(f.state) !== -1;
					if (o.mode === 'all_except' ? listed : !listed) { return false; }
				}
				return true;
			}).sort(function (a, b) { return f.sort === 'name' ? a.name.localeCompare(b.name) : b.rating - a.rating; });
			$('.usg-finder__count', finder).textContent = list.length + ' result' + (list.length === 1 ? '' : 's');
			$('.usg-finder__results', finder).innerHTML = list.length ? list.map(function (o) {
				return '<article class="usg-finder__item">' + o.logo + '<div><h3>' + (o.review ? '<a href="' + esc(o.review) + '">' + esc(o.name) + '</a>' : esc(o.name)) + '</h3><p>' + esc(o.headline) + '</p><p class="usg-small">★ ' + Number(o.rating).toFixed(1) + '/5' + (o.payout ? ' · Payouts: ' + esc(o.payout) : '') + (o.minDep ? ' · Min: ' + esc(o.minDep) : '') + '</p></div><a class="usg-btn usg-btn--cta usg-btn--sm" href="' + esc(o.claim) + '" rel="nofollow sponsored noopener" target="_blank">Claim</a></article>';
			}).join('') : '<p class="usg-notice">No operators match those filters.</p>';
		};
		finder.addEventListener('input', render);
		var st = cookie('usg_state'); if (st && st !== 'INTL') { var sel = $('select[name=state]', finder); if (sel) { sel.value = st; } }
		fetch(D.rest + 'finder').then(function (r) { return r.json(); }).then(function (j) { data = j || []; render(); });
	}

	/* ---- Consent + Google Tag Manager ---- */
	function loadGtm() {
		if (!D.gtm || window.usgGtmLoaded) { return; }
		window.usgGtmLoaded = true;
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
		var s = document.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(D.gtm);
		document.head.appendChild(s);
	}
	var consent = cookie('usg_consent');
	var banner = $('[data-usg-consent]');
	if (consent === 'yes') { loadGtm(); }
	else if (!consent && banner) { banner.hidden = false; }
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-consent]');
		if (!b) { return; }
		var v = b.getAttribute('data-consent');
		cookie('usg_consent', v, 180);
		if (banner) { banner.hidden = true; }
		if (v === 'yes') { loadGtm(); }
	});
})();
