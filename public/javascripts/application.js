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
		$map = $("#map");
		$mapInfo = $("#map-info");

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
		
		//Check for map page info
		if ( $map.length ) {
			mapInfo();
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
		var twURL = "http://search.twitter.com/search.json?q=ClackamasRiver&rpp=3&page=1&show_user=true&callback=?";
		
		$.getJSON(twURL, function( data ) {
			$.each(data.results, function( index, item ) {
				tweet = "";
				tweet += '<li><p><img src="' + item.profile_image_url + '" alt="' + item.from_user + '" />';
				tweet += item.text.linkify();
				tweet += '<span class="time"> ' + relativeTime(item.created_at) + '</span>';
				tweet += '<span class="author"> by: <a href="http://twitter.com/' + item.from_user + '" target="_blank">';
				tweet += '@' + item.from_user + '</a>' + '</span>';
				tweet += '</p></li>';
				$tweetlist.append(tweet);
			});
		});
	}
	
	function mapInfo() {
		//initialize map functionality in javascript
		$mapInfo.children("div").css('display', 'none');
		$mapInfo.children(":first").css('display', 'block');
		$map.children(":first").children('a').addClass('map_selected');
		
		//implement clicking on map labels and displaying descriptions
		$map.children('li').click(function() {
			var mapId = $(this).attr('id');
			$('a.map_selected').removeClass('map_selected');
			$('li#'+mapId).children('a').addClass('map_selected');
			$mapInfo.children("div").css('display', 'none');
			$infoDiv = $mapInfo.children('div.'+mapId);
			$mapInfo.prepend($infoDiv);
			$infoDiv.css('display','block');
		});
	}

/*
** PUBLIC METHODS
---------------------------------------------------------------*/	
	return {
		init: init
	};

}();

/*
|***************************************************************
| EVENTS / FUNCTIONS
|***************************************************************
*/
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

String.prototype.linkify = function() {
	return this.replace(/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+/, function(m) {
		return m.link(m);
	});
};

function relativeTime(time_value) {
	var parsed_date = Date.parse(time_value);
	var relative_to = (arguments.length > 1) ? arguments[1] : new Date();
	var delta = parseInt((relative_to.getTime() - parsed_date) / 1000);
	if(delta < 60) {
		return "less than a minute ago";
	} else if(delta < 120) {
		return "about a minute ago";
	} else if(delta < (45*60)) {
		return (parseInt(delta / 60)).toString() + " minutes ago";
	} else if(delta < (90*60)) {
		return "about an hour ago";
	} else if(delta < (24*60*60)) {
		return "about " + (parseInt(delta / 3600)).toString() + " hours ago";
	} else if(delta < (48*60*60)) {
		return "1 day ago";
	} else {
		return (parseInt(delta / 86400)).toString() + " days ago";
	}
}