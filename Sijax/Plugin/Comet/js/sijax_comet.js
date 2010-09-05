"use strict";
var sjxComet = {};

sjxComet.request = function (functionName, callArgs) {
	var iframe = document.createElement('iframe'),
		frameId = 'frame4_' + functionName + '_' + (new Date().getTime());
	
	iframe.setAttribute('id', frameId);
	iframe.setAttribute('name', frameId);
	iframe.setAttribute('style', 'display: none;');
	
	var form = document.createElement('form'),
		formId = 'form4_' + functionName;
	
	form.setAttribute('id', formId);
	form.setAttribute('name', formId);
	form.setAttribute('method', 'post');
	form.setAttribute('action', Sijax.getRequestUri());
	form.setAttribute('target', frameId);
	
	$('body').append(iframe);
	$('body').append(form);
	
	$('#' + frameId).bind('load', function () {
		//We need to remove the iframe, after leaving this callback
		//Or Google Chrome reports "Failed to load resource"
		window.setTimeout(function () {
			$('#' + frameId).remove();
		});
	});
	
	var formObject = $('#' + formId);
	
	var element = document.createElement('input');
	element.setAttribute('type', 'hidden');
	element.setAttribute('name', Sijax.PARAM_REQUEST);
	element.setAttribute('value', functionName);
	formObject.append(element);
	
	var element = document.createElement('input');
	element.setAttribute('type', 'hidden');
	element.setAttribute('name', Sijax.PARAM_ARGS);
	element.setAttribute('value', JSON.stringify(callArgs));
	formObject.append(element);
		
	formObject.trigger('submit');
};