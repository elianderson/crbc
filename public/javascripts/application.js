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
	var $slideshow = $("#slideshow"),
		$tweetlist = $(".tweets");

/*
** INITIALIZE
---------------------------------------------------------------*/	
	function init() {
		// Check for slideshow
		if ( $slideshow.length ) {
			slideShow();
		}
		
		// Check for twitter list
		if ( $tweetlist.length ) {
			twitterFeed();
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
	
	function twitterFeed() {
		var screen_name = "ClackamasRiver",
			twitter_url = "http://search.twitter.com/search.json";
			
		$.ajax({
			type: "GET",
			url: twitter_url,
			data: "q=user&3A" + screen_name,
			error: function (jqXHR, textStatus, errorThrown) {
				console.log("errorThrown: ", errorThrown);
				console.log("textStatus: ", textStatus);
				console.log("jqXHR: ", jqXHR);
			},
			success: function (data, textStatus, jqXHR) {
				console.log("Log: ", data);
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