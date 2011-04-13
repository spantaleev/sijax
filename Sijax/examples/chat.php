<?php
/**
 * This is an example of Sijax usage, showing a simple Shoutbox/Chat implementation.
 */

//Using date() without a timezone set in php.ini may generate errors.
date_default_timezone_set('UTC');

/**
 * We need to load Sijax classes, either manually or using Core_Loader.
 * The code below simply loads the Core_Loader class
 * and registers autoloading for classes prefixed with `Core_`.
 */
$corePath = dirname(dirname(dirname(__FILE__))) . '/';
require $corePath . 'Loader/Core_Loader.php';
Core_Loader::registerPrefix('Core', $corePath);

class ChatHandler {
	public function saveMessage(Core_Sijax_Response $objResponse, $message) {
		//Save $message to database...

		if (trim($message) === '') {
			return $objResponse->alert('Empty messages are not allowed!');
		}

		$timeNow = microtime(true);
		$messageId = md5($message . $timeNow);
		$messageContainerId = 'message_' . $messageId;

		//The message will be invisible at first, and we'll show it using a jquery effect
		$messageFormatted = '
		<div id="' . $messageContainerId . '" style="opacity: 0;">
			[<strong>' . date('H:i:s', (int) $timeNow) . '</strong>] ' . $message . '
		</div>';

		//Append the rendered message at the end
		$objResponse->htmlAppend('#messages', $messageFormatted);

		//Clear the textbox and give it focus in case it has lost it
		$objResponse->attr('#message', 'value', '');
		$objResponse->script("$('#message').focus();");

		//Scroll down the messages area
		$objResponse->script("$('#messages').attr('scrollTop', $('#messages').attr('scrollHeight'));");

		//Make the new message appear in 400ms
		$objResponse->script("$('#$messageContainerId').animate({opacity: 1}, 400);");
	}

	public function clearMessages(Core_Sijax_Response $objResponse) {
		//Delete messages from the database...

		//Clear the messages container
		$objResponse->html('#messages', '');

		//Clear the textbox
		$objResponse->attr('#message', 'value', '');

		//Ensure the textbox has focus
		$objResponse->script("$('#message').focus();");
	}
}

//Sijax uses HTTP POST data to pass information around
Core_Sijax::setData($_POST);

//All the methods of the Handler object will be available for calls
Core_Sijax::registerObject(new ChatHandler());

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
		 -->

		<script type="text/javascript">
			<?php
			//Including the files using script-src tags is the
			//recommended method, instead of this (see the example above..)
			echo file_get_contents('../js/sijax.js');
			?>

			if (typeof($) === 'undefined') {
				alert('Failed to load jquery. Sijax relies on Jquery to work!');
			}

			if (typeof(Sijax) === 'undefined') {
				alert('Failed to load sijax.js! This example will not work unless you fix it!');
			}

			<?php echo Core_Sijax::getJs(); ?>
		</script>
	</head>

	<body>
		<div id="messages" style="border: 1px solid #e0e0e0; margin-bottom: 20px; height: 100px; padding: 5px; overflow-y: scroll;"></div>

		<form id="messageForm" name="messageForm">
			Message: <input type="text" id="message" style="width: 400px" />

			<input type="submit" value="Send" />
			<button id="btnClear">Clear messages</button>
		</form>

		<script type="text/javascript">
		$(function() {
			$('#messageForm').bind('submit', function() {
				Sijax.request('saveMessage', [$('#message').attr('value')]);

				//Prevent the form from being submitted
				return false;
			});

			$('#message').focus();

			$('#btnClear').bind('click', function() {
				Sijax.request('clearMessages');

				return false;
			});
		});
		</script>
	</body>
</html>
