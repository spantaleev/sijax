<?php
/*
 * This is an example of Sijax usage showing how easy it is
 * to make an ajax-enabled file upload form.
 * 
 * The example below uses two different forms living happily in a single page.
 *
 * While handling several different forms with a single callback works,
 * we're handling both with a different response function.
 */

/**
 * We need to load Sijax classes, either manually or using Core_Loader.
 * The code below simply loads the Core_Loader class
 * and registers autoloading for classes prefixed with `Core_`.
 */
$corePath = dirname(dirname(dirname(__FILE__))) . '/';
require $corePath . 'Loader/Core_Loader.php';
Core_Loader::registerPrefix('Core', $corePath);

class FileHandler {
	
	private function _dumpResponse(Core_Sijax_Plugin_Upload_Response $objResponse, array $formValues, $containerId) {
		$html = '<h3>Response for form: `' . $objResponse->getFormId() . '`</h3>';
		$html .= '<pre>';
		$html .= 'Form values: ' . print_r($formValues, true);
		$html .= 'Files: ' . print_r($_FILES, true);
		$html .= '</pre>';
		
		$objResponse->html('#' . $containerId, $html);
	}
	
	public function handleFormOneUpload(Core_Sijax_Plugin_Upload_Response $objResponse, array $formValues) {
		$this->_dumpResponse($objResponse, $formValues, 'formOneResponse');
	}
	
	public function handleFormTwoUpload(Core_Sijax_Plugin_Upload_Response $objResponse, array $formValues) {
		$this->_dumpResponse($objResponse, $formValues, 'formTwoResponse');
	
		$objResponse->resetForm();
		$objResponse->htmlAppend('#formTwoResponse', 'Form elements were reset!<br />Doing some more work (2 seconds)..');
		$objResponse->flush();
		
		sleep(2);
		
		$objResponse->htmlAppend('#formTwoResponse', '<br />Finished!');
	}

}

//Sijax uses HTTP POST data to pass information around
Core_Sijax::setData($_POST);

$handler = new FileHandler();
$registrationScripts = '';

//Register the first form
$upload = new Core_Sijax_Plugin_Upload();
$upload->setFormId('formOne');
$upload->setCallback('handleFormOneUpload', array($handler, 'handleFormOneUpload'));
$registrationScripts .= $upload->getJs();

//Register the second form
$upload = new Core_Sijax_Plugin_Upload();
$upload->setFormId('formTwo');
$upload->setCallback('handleFormTwoUpload', array($handler, 'handleFormTwoUpload'));
$registrationScripts .= $upload->getJs();

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
		<script type="text/javascript" src="url/to/sijax_upload.js"></script>
		 -->
		 
		<script type="text/javascript">
			<?php
			//Including the files using script-src tags is the
			//recommended method, instead of this (see the example above..)
			echo file_get_contents('../js/sijax.js');
			echo file_get_contents('../Plugin/Upload/js/sijax_upload.js');
			?>
			
			if (typeof($) === 'undefined') {
				alert('Failed to load jquery. Sijax relies on Jquery to work!');
			}
			
			if (typeof(Sijax) === 'undefined') {
				alert('Failed to load sijax.js! This example will not work unless you fix it!');
			}

			if (typeof(sjxUpload) === 'undefined') {
				alert('Failed to load sijax_upload.js! This example will not work unless you fix it!');
			}
					
			<?php echo Core_Sijax::getJs(); ?>
			<?php echo $registrationScripts; ?>
		</script>
	</head>

	<body>
		<div style="float: left; width: 450px">
			<form id="formOne" name="formOne" style="width: 400px" method="post" enctype="multipart/form-data">
				Text field:
				<input type="text" name="message" value="Some text" /><br />
				
				Drop-down:
				<select name="dropdown">
					<option>1</option>
					<option selected="selected">2</option>
					<option>3</option>
				</select>
				<br />
				
				Chechbox:
				<input type="checkbox" checked="checked" name="chkbox" /><br />
				
				File:
				<input type="file" name="file" /><br />
				
				<input type="submit" value="Upload" />
			</form>

			<div id="formOneResponse"></div>
		</div>
		
		<div style="float: left; width: 450px">
			<form id="formTwo" name="formTwo" style="width: 400px" method="post" enctype="multipart/form-data">
				Text field:
				<input type="text" name="message" value="Some text" /><br />
				
				Chechbox:
				<input type="checkbox" checked="checked" name="chkbox" /><br />
				
				Chechbox 2:
				<input type="checkbox" name="chkbox2" /><br />
				
				File:
				<input type="file" name="file" /><br />
				
				<input type="submit" value="Upload" />
			</form>

			<div id="formTwoResponse"></div>
		</div>
	</body>
</html>