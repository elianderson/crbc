/*!
 * Piwik - Web Analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

function piwikHelper()
{
}

/*
 * Displays a Modal window popover. Text will be taken from the DOM node domSelector.
 * When user clicks Yes in Modal,onValidate() will be executed.
 * 
 * On clicking No, or esc key, the modal will fade out.
 */
piwikHelper.windowModal = function( domSelector, onValidate )
{
	var question = $(domSelector);
	$(document).keydown( function( e ) { 
		if( e.which == 27)  $.unblockUI(); }
	);
	$('#no', question).unbind('click').click($.unblockUI);
	$('#yes', question).unbind('click').click(function() {
		onValidate();
		$.unblockUI();
	});
	$.blockUI({
		message: question, 
		css: { width: 650, border:0, background:"none", top:90 }
	});
	
	$.unblockUI
}

piwikHelper.getCurrentQueryStringWithParametersModified = function(newparams)
{
	var parameters = String(window.location.search);
	if(newparams) {
		if(parameters != '') {
			var r, i, keyvalue, keysvalues = newparams.split('&');
			for(i in keysvalues) {
				keyvalue = keysvalues[i].split('=');
				r = new RegExp('(^|[?&])'+keyvalue[0]+'=[^&]*');
				parameters = parameters.replace(r, '');
			}
			parameters += '&' + newparams;
			if(parameters[0] == '&') {
				parameters = '?' + parameters.substring(1);
			}
		} else {
			parameters = '?' + newparams;
		}
	}
	return String(window.location.pathname) + parameters;
}

/*
 *  Returns query string for an object of key,values
 *  Note: we don't use $.param from jquery as it doesn't return array values the PHP way (returns a=v1&a=v2 instead of a[]=v1&a[]=v2)
 *  Example:
 *  	piwikHelper.getQueryStringFromParameters({"a":"va","b":["vb","vc"],"c":1})
 *  Returns:
 *  	a=va&b[]=vb&b[]=vc&c=1
 */
piwikHelper.getQueryStringFromParameters = function(parameters)
{
	var queryString = '';
	if(!parameters || parameters.length==0) {
		return queryString;
	}
	for(var name in parameters) {
		value = parameters[name];
		if(typeof value == 'object') {
			for(var i in value) {
				queryString += name + '[]=' + value[i] + '&';
			}
		} else {
			queryString += name + '=' + value + '&';
		}
	}
	return queryString.substring(0, queryString.length-1);
}

piwikHelper.findSWFGraph = function(name) {
	if(document.getElementById)
		return document.getElementById(name);
	if(document.layers)
		return document[id];
	if(document.all)
		return document.all[id];
	return null;
}

piwikHelper.showAjaxError = function( string, errorDivID )
{
	errorDivID = errorDivID || 'ajaxError';
	$('#'+errorDivID).html(string).show();
}

piwikHelper.hideAjaxError = function(errorDivID)
{
	errorDivID = errorDivID || 'ajaxError';
	$('#'+errorDivID).hide();
}

piwikHelper.showAjaxLoading = function(loadingDivID)
{
	loadingDivID = loadingDivID || 'ajaxLoading';
	$('#'+loadingDivID).show();
}
piwikHelper.hideAjaxLoading = function(loadingDivID)
{
	loadingDivID = loadingDivID || 'ajaxLoading';
	$('#'+loadingDivID).hide();
}

piwikHelper.getStandardAjaxConf = function(loadingDivID, errorDivID, params)
{
	piwikHelper.showAjaxLoading(loadingDivID);
	piwikHelper.hideAjaxError(errorDivID);
	var ajaxRequest = {};
	ajaxRequest.type = 'GET';
	ajaxRequest.url = 'index.php';
	ajaxRequest.dataType = 'json';
	ajaxRequest.error = piwikHelper.ajaxHandleError;
	ajaxRequest.success = function(response) { piwikHelper.ajaxHandleResponse(response, loadingDivID, errorDivID, params); };
	return ajaxRequest;
}

piwikHelper.redirectToUrl = function(url) {
	window.location = url;
}

piwikHelper.ajaxHandleError = function()
{
	$('#loadingError').show();
	setTimeout( function(){ 
		$('#loadingError').fadeOut('slow');
		}, 2000);
}

piwikHelper.ajaxHandleResponse = function(response, loadingDivID, errorDivID, params)
{
	if(response.result == "error") 
	{
		piwikHelper.hideAjaxLoading(loadingDivID);
		piwikHelper.showAjaxError(response.message, errorDivID);
	}
	else
	{
		// add updated=1 to the URL so that a "Your changes have been saved" message is displayed
		var urlToRedirect = piwikHelper.getCurrentQueryStringWithParametersModified(params);
		var updatedUrl = new RegExp('&updated=([0-9]+)');
		var updatedCounter = updatedUrl.exec(urlToRedirect);
		if(!updatedCounter)
		{
			urlToRedirect += '&updated=1';
		}
		else
		{
			updatedCounter = 1 + parseInt(updatedCounter[1]);
			urlToRedirect = urlToRedirect.replace(new RegExp('(&updated=[0-9]+)'), '&updated=' + updatedCounter);
		}
		var currentHashStr = window.location.hash;
		if(currentHashStr.length > 0) {
			urlToRedirect += currentHashStr;
		}
		piwikHelper.redirectToUrl(urlToRedirect);
	}
}

/**
 * Scrolls the window to the jquery element 'elem' 
 * if the top of the element is not currently visible on screen
 * @param elem Selector for the DOM node to scroll to, eg. '#myDiv'  
 * @param time Specifies the duration of the animation in ms
 */ 
piwikHelper.lazyScrollTo = function(elem, time)
{
	var elemTop = $(elem).offset().top;
	// only scroll the page if the graph is not visible 
	if(elemTop < $(window).scrollTop()
	|| elemTop > $(window).scrollTop()+$(window).height())
	{
		// scroll the page smoothly to the graph
		$.scrollTo(elem, time);
	}
}

piwikHelper.getApiFormatTextarea = function (textareaContent)
{
	return textareaContent.trim().split("\n").join(',');
}

piwikHelper.OFC = (function () {
	var _data = {};
	return {
		get: function (id) {
			return typeof _data[id] == 'undefined' ? '' : _data[id]; },
		set: function (id, data) { _data[id] = data; },
		jquery: {
			name: 'jQuery',
			rasterize: function (src, dst) { $('#'+dst).replaceWith(piwikHelper.OFC.jquery.image(src)); },
			image: function (src) { return '<img title="Piwik Graph" src="data:image/png;base64,' + $('#'+src)[0].get_img_binary() + '" />'; },
			popup: function (src) {
				var img_win = window.open('', 'ExportChartAsImage');
				img_win.document.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" /><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>' + _pk_translate('General_ExportAsImage_js') + '</title></head><body>' + piwikHelper.OFC.jquery.image(src) + '<br /><br /><p>' + _pk_translate('General_SaveImageOnYourComputer_js') + '</p></body></html>');
				img_win.document.close();
			},
			load: function (dst, data) { $('#'+dst)[0].load(data || piwikHelper.OFC.get(dst)); }
		}
	};
})();

// Open Flash Charts 2 - callback when chart is being initialized
function open_flash_chart_data(chartId) {
	if (typeof chartId != 'undefined') {
		return piwikHelper.OFC.get(chartId);
	}
	return '';
}

// Open Flash Charts 2 - callback when user selects "Save Image Locally" (right click on Flash chart for pop-up menu)
function save_image(chartId) {
	if (typeof chartId != 'undefined') {
		piwikHelper.OFC.jquery.popup(chartId);
	}
}

String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}

//Helper function :
//returns true if the event keypress passed in parameter is the ENTER key
function isEnterKey(e)
{
	return (window.event?window.event.keyCode:e.which)==13; 
}
