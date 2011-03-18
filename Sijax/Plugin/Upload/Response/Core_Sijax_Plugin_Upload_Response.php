<?php
/**
 * This is the Sijax response class for upload callbacks.
 * It supports everything that the base response class provides,
 * while adding some additional functionality to help dealing with forms.
 * 
 * It also adds `flush()` support, so you can do some comet stuff from within your upload callback.
 */
final class Core_Sijax_Plugin_Upload_Response extends Core_Sijax_Response {

	private $_formId = '';
	private $_flushesCount = 0;

	public function __construct(array $requestArgs) {
		parent::__construct($requestArgs);

		//The formId that we're dealing with is the only expected argument.	
		if (isset($requestArgs[0])) {
			$this->_formId = (string) $requestArgs[0];
		}
	}

	/**
	 * Used internally to overwrite the arguments passed to the response function.
	 * @return array
	 */
	public function getRequestArgs() {
		$postData = Core_Sijax::getData();
		unset($postData [Core_Sijax::PARAM_REQUEST]);
		unset($postData [Core_Sijax::PARAM_ARGS]);

		Core_Sijax::setRequestArgs(array($postData));

		return array($postData);
	}

	/**
	 * Returns the form id that we're dealing with.
	 * @return string
	 */
	public function getFormId() {
		return $this->_formId;
	}

	/**
	 * Resets the form to its state at the time the page was initially loaded.
	 */
	public function resetForm() {
		return parent::call('sjxUpload.resetForm', array($this->getFormId()));
	}

	/**
	 * Sends the commands accumulated so far to the browser.
	 * You can continue pushing new commands which will be
	 * sent either at the next flush() or when the response functions exits.
	 */
	public function flush() {
		echo $this->getJson();

		if ($this->_flushesCount === 0) {
			//The first flush is ignored in Chrome and IE for some reason
			//Echoing this additional data, seems to fix it
			echo "\n<script type='text/javascript'></script>\n\n";
		}

		ob_flush();
		flush();

		++ $this->_flushesCount;

		return parent::clearCommands();
	}

	public function getJson() {
		return "
		<script type='text/javascript'>
		window.parent.sjxUpload.processResponse(" . json_encode($this->_formId) .  ", " . parent::getJson() . ");
		</script>";
	}

}
?>