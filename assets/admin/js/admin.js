/* global jQuery */

jQuery(function($) {
	$('.flowread-color-field').wpColorPicker();

	var $settingsWrap = $('.flowread-progressbar-settings');
	if (!$settingsWrap.length) {
		return;
	}

	var toggleSecondary = function() {
		var isGradient = $('#flowread_style').val() === 'gradient';
		$settingsWrap.toggleClass('flowread-secondary-visible', isGradient);
	};

	toggleSecondary();
	$('#flowread_style').on('change', toggleSecondary);
});
