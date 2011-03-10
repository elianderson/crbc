/*
|***************************************************************
| CLASS: CRBC
|***************************************************************
*/
var crbc = function () {
	
/*
** VARIABLES
---------------------------------------------------------------*/
	// Global
	var $slideshow = $("#slideshow");

/*
** INITIALIZE
---------------------------------------------------------------*/	
	function init() {
		// Check for slideshow
		if ( $slideshow.length ) {
			slideShow();
		}
	}
	
	function slideShow() {
		$("#slideshow").cycle({
			speed:				3000, 
			timeout:			12000, 
			pager:				'.slide-nav-full', 
			prev:				'.prev a',
			next:				'.next a',
			activePagerClass:	'on',
			pagerAnchorBuilder: function ( i, slide ) {
				return '<li><a href="#">' + ( i + 1) + '</a></li>';
			}
		});
	}

/*
** PUBLIC METHODS
---------------------------------------------------------------*/	
	return {
		init: init
	};

}();

$(window).load(function () {
	
	// Init
	crbc.init();
	
	// IE6 sfhover class for dropdown menus
	$(".ie6").find("#nav").find("li").hover(function (e) {
		$(this).addClass("sfhover");
	}, function (e) {
		$(this).removeClass("sfhover");
	});
	
});