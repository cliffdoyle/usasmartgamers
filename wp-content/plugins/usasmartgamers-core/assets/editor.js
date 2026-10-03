/* Generic editor UI for all USA Smart Gamers dynamic blocks (definitions come from PHP: window.usgEditor). */
(function (wp, cfg) {
	'use strict';
	if (!wp || !cfg) { return; }
	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;

	function optionsFor(ctrl) {
		if (ctrl.source && cfg.sources[ctrl.source]) { return cfg.sources[ctrl.source]; }
		return ctrl.options || [];
	}

	function control(ctrl, schema, props) {
		var v = props.attributes[ctrl.key];
		var set = function (val) { var o = {}; o[ctrl.key] = val; props.setAttributes(o); };
		switch (ctrl.type) {
			case 'textarea':
				return el(c.TextareaControl, { key: ctrl.key, label: ctrl.label, value: v || '', rows: 6, onChange: set });
			case 'number':
				return el(c.TextControl, { key: ctrl.key, label: ctrl.label, type: 'number', value: v === undefined ? '' : v, onChange: function (x) { set(x === '' ? 0 : Number(x)); } });
			case 'toggle':
				return el(c.ToggleControl, { key: ctrl.key, label: ctrl.label, checked: !!v, onChange: set });
			case 'select':
			case 'post':
				return el(c.SelectControl, {
					key: ctrl.key, label: ctrl.label, value: v === undefined || v === 0 ? '' : String(v), options: optionsFor(ctrl),
					onChange: function (x) { set(schema.type === 'number' ? (x === '' ? 0 : parseInt(x, 10)) : x); }
				});
			case 'posts':
				var cur = Array.isArray(v) ? v : [];
				return el('div', { key: ctrl.key, className: 'usg-posts-control' },
					el('p', { style: { fontWeight: 600, margin: '8px 0 4px' } }, ctrl.label),
					optionsFor(ctrl).filter(function (o) { return o.value !== ''; }).map(function (o) {
						var id = parseInt(o.value, 10);
						return el(c.CheckboxControl, {
							key: o.value, label: o.label, checked: cur.indexOf(id) !== -1,
							onChange: function (on) { set(on ? cur.concat([id]) : cur.filter(function (x) { return x !== id; })); }
						});
					}));
			default:
				return el(c.TextControl, { key: ctrl.key, label: ctrl.label, value: v || '', onChange: set });
		}
	}

	cfg.blocks.forEach(function (def) {
		wp.blocks.registerBlockType(def.name, {
			apiVersion: 3,
			title: def.title,
			icon: def.icon,
			category: 'usg',
			attributes: def.attributes,
			supports: { html: false },
			edit: function (props) {
				var blockProps = be.useBlockProps();
				var controls = def.controls.map(function (ctrl) { return control(ctrl, def.attributes[ctrl.key] || {}, props); });
				return el('div', blockProps,
					controls.length ? el(be.InspectorControls, null, el(c.PanelBody, { title: def.title + ' settings', initialOpen: true }, controls)) : null,
					el(SSR, {
						block: def.name,
						attributes: props.attributes,
						EmptyResponsePlaceholder: function () {
							return el(c.Placeholder, { icon: def.icon, label: def.title, instructions: 'Configure this block in the sidebar (Settings panel).' });
						}
					})
				);
			},
			save: function () { return null; }
		});
	});
})(window.wp, window.usgEditor);
