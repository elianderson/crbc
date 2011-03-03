$(document).ready(function () {
	$('#slideshow').cycle({
		speed:       3000, 
    	timeout:     12000, 
    	pager:      'ol.nav', 
    	prev:		'.prev a',
    	next:		'.next a',
    	
    	pagerAnchorBuilder: function(idx, slide) { 
        	return '<li><a href="#slide">'+(idx+1)+'</a></li>'; 
    } 
	});
});
