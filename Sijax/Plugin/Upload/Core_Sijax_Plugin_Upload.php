<?php
/**
 * Ajax upload plugin for Sijax.
 *
 * This simple plugin allows you to easily transform any regular file upload form,
 * to an ajax-enabled one.
 *
 */
final class Core_Sijax_Plugin_Upload {

	const PARAM_FORM_ID = 'formId';
	const PARAM_CALLBACK = 'callback';

	private $_params = array();

	public function __construct() {

	}

	/**
	 * Sets the form element, which would be submitted using sijax.
	 * @param string $id
	 */
	public function setFormId($id) {
		$this->_params[self::PARAM_FORM_ID] = $id;

		return $this;
	}

	/**
	 * Sets the sijax function to call when requesting suggestions.
	 * @param string $functionName
	 */
	public function setCallback($functionName, $callback) {
		$this->_params[self::PARAM_CALLBACK] = $functionName;

		$params = array(Core_Sijax::PARAM_RESPONSE_CLASS => __CLASS__ . '_Response');

		Core_Sijax::registerCallback($functionName, $callback, $params);

		return $this;
	}

	/**
	 * Generates and returns the javascript code needed to register this upload form.
	 */
	public function getJs() {
		return "$(function() { sjxUpload.registerForm(" . json_encode($this->_params) . "); });";
	}

}
?>
