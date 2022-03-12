$(function() {
	$('#menu').mmenu({
		offCanvas	: {
			position  : "right"
		},
		extensions	: [ 'effect-slide-menu', 'pageshadow' ],
		searchfield	: false,
		counters	: true,
		navbar 		: {
			title		: 'メニュー'
		},
		navbars		: [
			 {
				position	: 'top',
				content		: [
					'prev',
					'title',
					'close'
				]
			}
		]
	});
});