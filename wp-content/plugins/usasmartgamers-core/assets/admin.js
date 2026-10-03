/* USA Smart Gamers admin field UI */
(function ($) {
	'use strict';

	$(document).on('click', '.usg-rep-add', function () {
		var $rep = $(this).closest('.usg-repeater');
		var html = $rep.find('template.usg-rep-template').html().replace(/__i__/g, String(Date.now()));
		$rep.find('tbody').append(html);
	});
	$(document).on('click', '.usg-rep-remove', function () {
		$(this).closest('tr').remove();
	});
	$(document).on('click', '.usg-rep-up', function () {
		var $tr = $(this).closest('tr');
		$tr.prev().before($tr);
	});
	$(document).on('click', '.usg-rep-down', function () {
		var $tr = $(this).closest('tr');
		$tr.next().after($tr);
	});

	$(document).on('click', '.usg-check-all, .usg-check-none', function () {
		var on = $(this).hasClass('usg-check-all');
		$(this).closest('.usg-field, td').find('.usg-checkgrid input[type=checkbox]').prop('checked', on);
	});

	$(document).on('click', '.usg-image-select', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.usg-image-field');
		var frame = wp.media({ title: 'Select image', multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			$wrap.find('input[type=hidden]').val(a.id);
			var src = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
			$wrap.find('.usg-image-preview').html('<img src="' + src + '" alt="">');
		});
		frame.open();
	});
	$(document).on('click', '.usg-image-remove', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.usg-image-field');
		$wrap.find('input[type=hidden]').val('');
		$wrap.find('.usg-image-preview').empty();
	});
})(jQuery);
