<?php
/*
 * This is an example of Sijax usage showing how easy it is
 * to make a tagging system with suggestions support.
 */

/**
 * We need to load Sijax classes, either manually or using Core_Loader.
 * The code below simply loads the Core_Loader class
 * and registers autoloading for classes prefixed with `Core_`.
 */
$corePath = dirname(dirname(dirname(__FILE__))) . '/';
require $corePath . 'Loader/Core_Loader.php';
Core_Loader::registerPrefix('Core', $corePath);

class SuggestHandler {

	private function _getSuggestions($keyword) {
		$suggestionsArray = array();

		for ($i = 0; $i < 10; ++ $i) {
			$suggestion = array();

			$name = $keyword . ' ' . $i;

			$suggestion ['display'] = $name;
			$suggestion ['selectResponse'] = "selectTag(". json_encode($name) . ");";

			$suggestionsArray [] = $suggestion;
		}

		return $suggestionsArray;
	}

	public function suggestTags(Core_Sijax_Plugin_Suggest_Response $objResponse, array $params) {
		//Clear debug information container
		$objResponse->html('#debug', '');

		$fieldValue = $params ['fieldValue'];
		$fieldId = $params ['fieldId'];

		$objResponse->htmlAppend('#debug', 'Provided suggestions for: ' . $fieldValue . '<br />');
		$objResponse->htmlAppend('#debug', 'Text found in field: #' . $fieldId . '<br />');

		$objResponse->addSuggestions($this->_getSuggestions($fieldValue));
	}

}

//Sijax uses HTTP POST data to pass information around
Core_Sijax::setData($_POST);

$handler = new SuggestHandler();
$registrationScripts = '';

//Register suggest support for our first text field
$suggest = new Core_Sijax_Plugin_Suggest();
$suggest->setFieldId('textbox');
$suggest->setContainerId('suggestionsContainer');
$suggest->setCallback('suggestTags', array($handler, 'suggestTags'));
$registrationScripts .= $suggest->getJs();

//Register suggest support for our second text field
//Note that we're using the same callback,
//but a different container id, and a different style
//We're also using a different tag delimiter..
$suggest = new Core_Sijax_Plugin_Suggest();
$suggest->setFieldId('textbox2');
$suggest->setContainerId('suggestionsContainer2');
$suggest->setCallback('suggestTags', array($handler, 'suggestTags'));
$suggest->setItemCssClass('sjxSuggest-item-custom');
$suggest->setItemSelectedCssClass('sjxSuggest-item-custom-selected');
$suggest->setDelimiter(',');
$registrationScripts .= $suggest->getJs();

//Tries to detect if this is a Sijax request,
//and executes the appropriate callback
Core_Sijax::processRequest();
?>
<!DOCTYPE html>
<html>
	<head>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

		<!--
		This is the recommended way of loading the core files..
		<script type="text/javascript" src="url/to/sijax.js"></script>
		<script type="text/javascript" src="url/to/sijax_suggest.js"></script>
		 -->

		<script type="text/javascript">
			<?php
			//Including the files using script-src tags is the
			//recommended method, instead of this (see the example above..)
			echo file_get_contents('../js/sijax.js');
			echo file_get_contents('../Plugin/Suggest/js/sijax_suggest.js');
			?>

			if (typeof($) === 'undefined') {
				alert('Failed to load jquery. Sijax relies on Jquery to work!');
			}

			if (typeof(Sijax) === 'undefined') {
				alert('Failed to load sijax.js! This example will not work unless you fix it!');
			}

			if (typeof(sjxSuggest) === 'undefined') {
				alert('Failed to load sijax_suggest.js! This example will not work unless you fix it!');
			}

			<?php echo Core_Sijax::getJs(); ?>
			<?php echo $registrationScripts; ?>

			//This will be called when a tag is picked
			var selectTag = function(tagName) {
				//We could modify the textbox contents to really auto-complete the word, etc..

				$('#debug').append('Selected tag: `' + tagName + '`');
			};
		</script>

		<style>
		.sjxSuggest-item {
			font-weight: bold;
			height: 18px;
			color: #212222;
			text-align: left;
			-moz-border-radius: 2px; -webkit-border-radius: 2px; border-radius: 2px;
			padding: 1px 6px 0 6px;
			margin-bottom: 1px;
			font-size: 12px;
			border: 1px solid #e7c506;
			box-shadow: 1px 1px 4px #b5b5b5;
			background-color: #fbfbf7;
		}

		.sjxSuggest-item-selected {
			font-weight: bold;
			height: 18px;
			color: #212222;
			text-align: left;
			-moz-border-radius: 2px; -webkit-border-radius: 2px; border-radius: 2px;
			padding: 1px 6px 0 6px;
			margin-bottom: 1px;
			font-size: 12px;
			border: 1px solid #e77006;
			box-shadow: 1px 1px 4px #b5b5b5;
			background-color: #f1f1c7;
		}

		.sjxSuggest-item-custom {
			font-weight: bold;
			height: 18px;
			color: red;
			text-align: left;
			-moz-border-radius: 2px; -webkit-border-radius: 2px; border-radius: 2px;
			padding: 1px 6px 0 6px;
			margin-bottom: 1px;
			font-size: 12px;
			border: 1px solid #e7c506;
			box-shadow: 1px 1px 4px #b5b5b5;
			background-color: #fbfbf7;
		}

		.sjxSuggest-item-custom-selected {
			font-weight: bold;
			height: 18px;
			color: blue;
			text-align: left;
			-moz-border-radius: 2px; -webkit-border-radius: 2px; border-radius: 2px;
			padding: 1px 6px 0 6px;
			margin-bottom: 1px;
			font-size: 12px;
			border: 1px solid #e77006;
			box-shadow: 1px 1px 4px #b5b5b5;
			background-color: #f1f1c7;
		}
		</style>
	</head>

	<body>
		<div style="float: left;">
			Space separated words:<br />
			<input type="text" id="textbox" name="textbox" />
			<div style="clear: both;"></div>
			<div id="suggestionsContainer"></div>

			<br /><br />

			Comma separated words:<br />
			<input type="text" id="textbox2" name="textbox2" />
			<div style="clear: both;"></div>
			<div id="suggestionsContainer2"></div>
		</div>
		<div style="float: left; margin-left: 50px;" id="debug"></div>
	</body>
</html>
