jQuery(document).ready(function() {
	jQuery('div.ad_unit, li.ad_unit').hover(function() {
		var unit = jQuery(this).find('div.ad_event');
		var hide = unit.find('div.ad_code');
		var show = unit.find('div.ad_info');
		if ( show.size() && hide.size() ) {
			show.css('width', parseInt(hide.width()) - 2).css('height', parseInt(hide.height()) - 2);
			show.show();
			hide.hide();
		}
	}, function() {
		var unit = jQuery(this).find('div.ad_event');
		var show = unit.find('div.ad_code');
		var hide = unit.find('div.ad_info');
		if ( show.size() && hide.size() ) {
			show.show();
			hide.hide();
		}
	});
});