<?php
/**
 * This is an example of Sijax usage, showing a Comet streaming example.
 */

/**
 * We need to load Sijax classes, either manually or using Core_Loader.
 * The code below simply loads the Core_Loader class
 * and registers autoloading for classes prefixed with `Core_`.
 */
$corePath = dirname(dirname(dirname(__FILE__))) . '/';
require $corePath . 'Loader/Core_Loader.php';
Core_Loader::registerPrefix('Core', $corePath);

class CometHandler {
	public function doWork(Core_Sijax_Plugin_Comet_Response $objResponse, $sleepTime) {
		for ($i = 1; $i <= 5; ++ $i) {
			$width = ($i * 80) . 'px';
			$objResponse->script("$('#progress').css('width', '$width');");
			$objResponse->html('#progress', $width);
			
			//Send the data to the browser
			$objResponse->flush();
			
			if ($i !== 5) {
				usleep($sleepTime);
			}
		}
	}
}

//Sijax uses HTTP POST data to pass information around
Core_Sijax::setData($_POST);

Core_Sijax_Plugin_Comet::registerCallback('doWork', array(new CometHandler(), 'doWork'));

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
			echo file_get_contents('../Plugin/Comet/js/sijax_comet.js');
			?>
				
			if (typeof($) === 'undefined') {
				alert('Failed to load jquery. Sijax relies on Jquery to work!');
			}
			
			if (typeof(Sijax) === 'undefined') {
				alert('Failed to load sijax.js! This example will not work unless you fix it!');
			}

			if (typeof(sjxComet) === 'undefined') {
				alert('Failed to load sijax_comet.js! This example will not work unless you fix it!');
			}
					
			<?php echo Core_Sijax::getJs(); ?>
		</script>
	</head>

	<body>
		<div id="progressWrapper" style="height: 22px; width: 400px; border: 1px solid #e0e0e0; margin-bottom: 10px;">
			<div id="progress" style="width: 0px; height: 100%; background-color: #72cd52; display: block;">
				&nbsp;
			</div>
		</div>
		
		
		<button id="btnStart">Start</button>
		
		<script type="text/javascript">
		$('#btnStart').bind('click', function () {
			$('#progress').css('width', 0).html('&nbsp;');

			//sleep time is 500ms
			sjxComet.request('doWork', [500000]);
		});
		</script>
	</body>
</html>