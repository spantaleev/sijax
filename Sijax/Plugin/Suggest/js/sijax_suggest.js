var sjxSuggest = {};

sjxSuggest.registeredFields = [];	//contains all the registered fields' objects
sjxSuggest.selectedField = -1; //stores the last list id that the user was working on
sjxSuggest.curField = null; //store the current fieldItem for faster global access
sjxSuggest.registeredFieldsLog = []; //keep the IDs of all the fields that are currently registered.. used for a quick registration check
sjxSuggest.checkInterval = 100;
sjxSuggest.enableLogger = false;

(function () {
	var appVersion = window.navigator.appVersion,
		iln;
	
	for (iln = 0; iln < appVersion.length; iln = iln + 1) {
		if (appVersion.charAt(iln) === "(") {
			break;
		}
	}
	
	sjxSuggest.isNetscape = (appVersion.charAt(iln + 1).toUpperCase() !== "C");
})();


sjxSuggest.getKeyCode = function (event) {
	return (sjxSuggest.isNetscape) ? event.which : window.event.keyCode;
};

sjxSuggest.suggestKeyDownHandler = function (keyCode, fieldItem) {
	if (keyCode === 9) {
		//Tab
		sjxSuggest.selectItem(fieldItem);
		return false;
	}
	
	if (keyCode === 38) {
		sjxSuggest.switchPosition(fieldItem.position - 1);
		return false;
	} else if (keyCode === 40) {
		if (fieldItem.listIsOpen === 0) {
			//If the list is closed and the down arrow is pressed, show it..
			sjxSuggest.showList(fieldItem.lastOffset);
		} else {
			//do the default action otherwise.. move down through the list
			sjxSuggest.switchPosition(fieldItem.position + 1);
		}
		
		return false;
	} else if (keyCode === 27) {
		//Escape
		sjxSuggest.hideList(fieldItem);
		return false;
	}
	
	return true;
};

sjxSuggest.suggestKeyPressHandler = function (keyCode, fieldItem) {
	if (keyCode === 13) {
		// Enter.. select item and prevent the form from being submitted
		sjxSuggest.selectItem(fieldItem);
		return false;
	}
	
	return true;
};

sjxSuggest.suggestMouseUp = function (events) {
	//todo -> implement ignoring of non-left clicks
	if (sjxSuggest.selectedField === -1) {
		return;
	}
	
	var fieldItem = sjxSuggest.registeredFields[sjxSuggest.selectedField];
	
	if (fieldItem.listIsOpen === 1) {
		window.setTimeout(function () {
			sjxSuggest.deselectField();
			sjxSuggest.hideList();
		}, 200);
	}
};

document.onmouseup = sjxSuggest.suggestMouseUp;

sjxSuggest.switchPosition = function (moveWhere) {
	var fieldItem = sjxSuggest.registeredFields[sjxSuggest.selectedField];
	jQuery('#' + fieldItem.containerId + 'Item' + fieldItem.position).attr('class', fieldItem.itemClass);
	fieldItem.position = moveWhere;
	
	if (moveWhere >= fieldItem.listItemsCount) {
		fieldItem.position = 0;
	} else if (fieldItem.position < 0) {
		fieldItem.position = fieldItem.listItemsCount - 1;
	}
	
	jQuery('#' + fieldItem.containerId + 'Item' + fieldItem.position).attr('class', fieldItem.itemSelectedClass);
};

sjxSuggest.selectItem = function () {
	var fieldItem = sjxSuggest.registeredFields[sjxSuggest.selectedField];
	eval(fieldItem.listItems[fieldItem.position].selectResponse);
	window.setTimeout(function () {
		sjxSuggest.hideList();
	}, 100);
};

sjxSuggest.deselectField = function () {
	sjxSuggest.selectedField = -1;
};

sjxSuggest.trim = function (str) {
	return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
};

sjxSuggest.showList = function (listOffset) {
	if (sjxSuggest.curField.html === '') {
		//nothing to show
		return;
	}

	sjxSuggest.curField.fieldContainer.innerHTML = sjxSuggest.curField.html;
	sjxSuggest.selectedField = sjxSuggest.curField.listId;
	sjxSuggest.curField.position = 0;	//reset the marker position
	
	sjxSuggest.curField.clickClosingFlag = 0;
	
	var showAt = listOffset;
	sjxSuggest.curField.lastOffset = listOffset;
	
	sjxSuggest.curField.fieldContainer.style.marginLeft = showAt + 'px';
	sjxSuggest.addToLogger('positioned at: ' + showAt + 'px');

	if (sjxSuggest.curField.listIsOpen === 0) {
		jQuery('#' + sjxSuggest.curField.containerId).show('fast');
	}
	
	sjxSuggest.curField.listIsOpen = 1;
};

sjxSuggest.hideList = function () {
	try {
		if (sjxSuggest.curField.listIsOpen === 0) {
			//list is already closed
			return;
		}
		
		sjxSuggest.curField.listIsOpen = 0; //mark as closed
		sjxSuggest.curField.position = 0;	//reset position counter
		jQuery('#' + sjxSuggest.curField.containerId).hide('fast');
		sjxSuggest.deselectField();
	} catch (e) {
		sjxSuggest.addToLogger(e);
	}
};

sjxSuggest.getString = function () {
	var myField = sjxSuggest.curField.fieldBox,
		delimiter = sjxSuggest.curField.delimiter,
		delimiterLength = delimiter.length;

	try {
		if (document.selection) {
			// IE Support
			//IE users can currently edit only the last element
			var tagsArray = jQuery('#' + sjxSuggest.curField.fieldId).attr('value').split(delimiter);
			sjxSuggest.curField.totalLength = jQuery('#' + sjxSuggest.curField.fieldId).attr('value').length;
			sjxSuggest.curField.tagPosition = tagsArray.length - delimiterLength;
			return tagsArray[tagsArray.length - 1];
		}
		
		if (myField.selectionStart || myField.selectionStart == '0') { //Real Browsers Support
			var startPos = myField.selectionStart;
			var tagsArray = jQuery('#' + sjxSuggest.curField.fieldId).attr('value').split(delimiter);

			sjxSuggest.curField.totalLength = 0;
			for (var i = 0; i < tagsArray.length; i++) {
				sjxSuggest.curField.totalLength = sjxSuggest.curField.totalLength + tagsArray[i].length + delimiterLength;
				if (sjxSuggest.curField.totalLength > startPos) {
					sjxSuggest.curField.tagPosition = i;	//mark the place for the tag to be inserted
					return tagsArray[i];
				}
			}

			return '';
		}
	} catch (e) {
		sjxSuggest.addToLogger(e);
		return '';
	}
};

sjxSuggest.prepareContainer = function (containerId, fieldNum) {
	try {
		sjxSuggest.registeredFields[fieldNum].fieldContainer = document.getElementById(containerId);
		if (sjxSuggest.registeredFields[fieldNum].fieldContainer === undefined) {
			return;
		}
		
		sjxSuggest.registeredFields[fieldNum].fieldContainer.style.cursor = 'pointer';
		sjxSuggest.registeredFields[fieldNum].fieldContainer.style.position = 'absolute';
		sjxSuggest.registeredFields[fieldNum].fieldContainer.style.zIndex = '100';
	} catch (e) {
		sjxSuggest.addToLogger(e);
	}
};

sjxSuggest.prepareBox = function (boxId, fieldNum) {
	try {
		sjxSuggest.registeredFields[fieldNum].fieldBox = document.getElementById(boxId);
		if (sjxSuggest.registeredFields[fieldNum].fieldBox === undefined) {
			return;
		}
		sjxSuggest.registeredFields[fieldNum].fieldBox.setAttribute("autocomplete", "off");
		
		var activityCallback = function () {
			sjxSuggest.selectedField = fieldNum;
		};
		
		var keyDownCallback = function (event) {
			sjxSuggest.selectedField = fieldNum;
			var keyCode = sjxSuggest.getKeyCode(event);
			
			return sjxSuggest.suggestKeyDownHandler(keyCode, sjxSuggest.registeredFields[fieldNum]);
		};
		
		var keyPressCallback = function (event) {
			sjxSuggest.selectedField = fieldNum;
			var keyCode = sjxSuggest.getKeyCode(event);
			
			return sjxSuggest.suggestKeyPressHandler(keyCode, sjxSuggest.registeredFields[fieldNum]);
		};
		
		//Make sure that field activity (focus, keypresses, mouseclicks) result in making it the selectedField.
		sjxSuggest.registeredFields[fieldNum].fieldBox.onfocus = activityCallback;
		sjxSuggest.registeredFields[fieldNum].fieldBox.onclick = activityCallback;
		sjxSuggest.registeredFields[fieldNum].fieldBox.onchange = activityCallback;
		
		sjxSuggest.registeredFields[fieldNum].fieldBox.onkeydown = keyDownCallback;
		sjxSuggest.registeredFields[fieldNum].fieldBox.onkeypress = keyPressCallback;
	} catch (e) {
		sjxSuggest.addToLogger(e);
	}
};

sjxSuggest.registerSuggestField = function (params) {
	if (params.fieldId === undefined) {
		return sjxSuggest.throwCriticalError('Registering a field without a fieldId is not possible!');
	}
	
	if (params.containerId === undefined) {
		return sjxSuggest.throwCriticalError('Registering a field without a suggestions container is not possible!');
	}
	
	if (params.callback === undefined) {
		return sjxSuggest.throwCriticalError('Registering a field without a sijax response function is not possible!');
	}

	if (params.delimiter === undefined) {
		params.delimiter = ' ';
	}
	
	var fieldNum;
	
	if (sjxSuggest.registeredFieldsLog[params.fieldId] !== undefined) {
		fieldNum = sjxSuggest.registeredFieldsLog[params.fieldId];
		sjxSuggest.addToLogger('field is registered at ' + fieldNum + '! update only..');
		sjxSuggest.prepareContainer(params.containerId, fieldNum);
		sjxSuggest.prepareBox(params.fieldId, fieldNum);
		return;
	}

	fieldNum = sjxSuggest.registeredFields.length;
	
	sjxSuggest.registeredFieldsLog[params.fieldId] = fieldNum;
	sjxSuggest.registeredFields[fieldNum] = params;
	sjxSuggest.registeredFields[fieldNum].listId = fieldNum;
	
	sjxSuggest.prepareContainer(params.containerId, fieldNum);
	sjxSuggest.prepareBox(params.fieldId, fieldNum);

	sjxSuggest.addToLogger(params.fieldId + ' initially registered at: ' + fieldNum);
};

sjxSuggest.processResponse = function (args) {
	sjxSuggest.curField = sjxSuggest.getFieldItem(args.fieldId);
	sjxSuggest.curField.listItems = args.suggestions;
	sjxSuggest.curField.listItemsCount = args.suggestions.length;
	
	sjxSuggest.curField.position = 0;
	
	if (sjxSuggest.curField.listItemsCount === 0 && sjxSuggest.curField.listIsOpen === 1 && sjxSuggest.curField.emptySetMessage === undefined) {
		sjxSuggest.hideList();
	}
	
	if (sjxSuggest.curField.listItemsCount === 0 && sjxSuggest.curField.emptySetMessage !== undefined) {
		//prepare a fake list
		sjxSuggest.curField.listItemsCount = 1;
		sjxSuggest.curField.listItems[0] = {
			"display": sjxSuggest.curField.emptySetMessage,
			"selectResponse": sjxSuggest.curField.emptySetSelectResponse
		};
	}

	var html = '';
	//generate the html constructing the list
	for (var i = 0; i < sjxSuggest.curField.listItemsCount; ++ i) {
		var listItem = sjxSuggest.curField.listItems[i],
			divClass;
			
		if (sjxSuggest.curField.position == i) {
			divClass = sjxSuggest.curField.itemSelectedClass;
		} else {
			divClass = sjxSuggest.curField.itemClass;
		}
	
		html += '<div id="' + sjxSuggest.curField.containerId + 'Item' + i + '" class="' + divClass + '" nowrap="nowrap" onmouseover="sjxSuggest.switchPosition(' + i + ');" onclick="sjxSuggest.selectItem();">' + listItem.display + '</div>';
	}

	sjxSuggest.curField.html = html;
	sjxSuggest.selectedField = sjxSuggest.curField.listId;
	sjxSuggest.showList(args.offset);
};

sjxSuggest.getFieldItem = function (fieldId) {
	for (var id in sjxSuggest.registeredFields) {
		if (sjxSuggest.registeredFields[id].fieldId == fieldId) {
			return sjxSuggest.registeredFields[id];
		}
	}
			
	return undefined;
};

sjxSuggest.suggestionsGetter = function () {
	if (sjxSuggest.selectedField === -1) {
		//the selectedField var gets updated when a registered textbox gets clicked
		sjxSuggest.addToLogger('no field selected');
		return;
	}
	
	sjxSuggest.curField = sjxSuggest.registeredFields[sjxSuggest.selectedField];

	sjxSuggest.addToLogger('suggestion iterration');
	if (sjxSuggest.curField.fieldBox.value.length === 0) {
		sjxSuggest.curField.lastKey = '';
		sjxSuggest.curField.lastKeySubmitted = '';
		sjxSuggest.hideList();	//make sure that the list is closed, because no text is entered
		return;
	}
	
	var text = sjxSuggest.getString();	//get the search string from the whole comma seperated list

	if (text.length < 2) {
		sjxSuggest.hideList();
		return;
	}
	
	//check if the previous key was the same as this one and that we haven't already submited it
	if (sjxSuggest.curField.lastKey == text && sjxSuggest.curField.lastKey != sjxSuggest.curField.lastKeySubmitted) {	
		sjxSuggest.getSuggestions(text);
		sjxSuggest.curField.lastKeySubmitted = text;
	}
	
	sjxSuggest.curField.lastKey = text;
};

sjxSuggest.getSuggestions = function (text) {
	sjxSuggest.curField.lastKeySubmitted = text;
	
	if (sjxSuggest.curField.fieldBox.value == sjxSuggest.curField.closedOn) {
		return;
	}

	sjxSuggest.addToLogger('should have requested suggestions for ' + text + ' by `' + sjxSuggest.curField.callback + '`');
	
	var dataArray = {};
	dataArray.fieldId = sjxSuggest.curField.fieldId;
	dataArray.suggestionsLimit = sjxSuggest.curField.suggestionsLimit;
	dataArray.fieldValue = text;

	if (sjxSuggest.curField.additional !== undefined) {
		dataArray.additional = sjxSuggest.getAdditionalData(sjxSuggest.curField.additional);
	}

	Sijax.request(sjxSuggest.curField.callback, [dataArray]);
};

sjxSuggest.getAdditionalData = function (additional) {
	var paramsData = {};
	
	jQuery.each(additional, function (key, value) {
		paramsData[key] = sjxSuggest.getValueOrCmdResult(value);
	});
	
	return paramsData;
};

sjxSuggest.getValueOrCmdResult = function (string) {
	try {
		return eval(string);
	} catch (e) {
		return string;
	}
};

sjxSuggest.throwCriticalError = function (errorMsg) {
	window.alert(errorMsg);
	return false;
};

sjxSuggest.addToLogger = function (text) {
	if (! sjxSuggest.enableLogger) {
		return;
	}
	
	try {
		jQuery('#logger').append('<br />' + text);
	} catch (e) {
	}
};

sjxSuggest.reInitSuggestFields = function () {
	jQuery.each(sjxSuggest.registeredFields, function (index, fieldItem) {
		sjxSuggest.prepareContainer(fieldItem.containerId, index);
		sjxSuggest.prepareBox(fieldItem.fieldId, index);
	});
};

window.setInterval(function () {
	sjxSuggest.suggestionsGetter();
}, sjxSuggest.checkInterval);
