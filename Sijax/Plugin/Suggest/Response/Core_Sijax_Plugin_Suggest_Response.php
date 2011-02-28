<?php
/**
 * This is the Sijax response class for suggest callbacks.
 * It supports everything that the base response class provides,
 * while adding some additional functionality to help dealing with the suggest plugin.
 */
final class Core_Sijax_Plugin_Suggest_Response extends Core_Sijax_Response {

	private $_response = array();

	public function __construct(array $requestArgs) {
		parent::__construct($requestArgs);
		
		/**
		 * The sijax response function expects only one parameter.
		 * It should be an array containing different parameters.
		 * We MUST send the `fieldId` parameter back.
		 * We'll send the whole incoming array back though.
		 */
		if (isset($requestArgs[0])) {
			$this->_response = $requestArgs[0];
		}
		
		$this->addSuggestions(array());
	}
	
	public function addSuggestions(array $suggestions) {
		$this->_response ['suggestions'] = $suggestions;

		return $this;
	}

	public function getJson() {
		parent::call('sjxSuggest.processResponse', array($this->_response));
		
		return parent::getJson();
	}

}
?>