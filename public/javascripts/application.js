$(window).load(function() {
	
	// IE6 sfhover class for dropdown menus
	$(".ie6").find("#nav").find("li").hover(function(e) {
		$(this).addClass("sfhover");
	}, function(e) {
		$(this).removeClass("sfhover");
	});
	
});