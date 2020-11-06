<?php
/**
 * A helper class to prepare the suggest plugin.
 */
final class Core_Sijax_Plugin_Suggest {

	const PARAM_FIELD_ID = 'fieldId';
	const PARAM_FIELD_VALUE = 'fieldValue';
	const PARAM_CONTAINER_ID = 'containerId';
	const PARAM_DELIMITER = 'delimiter';
	const PARAM_CALLBACK = 'callback';
	const PARAM_CSS_CLASS = 'itemClass';
	const PARAM_CSS_CLASS_SELECTED = 'itemSelectedClass';
	const PARAM_ADDITIONAL = 'additional';

	private $_params = array();

	public function __construct() {
		$this->setItemCssClass('sjxSuggest-item');
		$this->setItemSelectedCssClass('sjxSuggest-item-selected');
	}

	/**
	 * Sets the text field, typing text in which will show suggestions.
	 *
	 * @param string $id
	 */
	public function setFieldId($id) {
		$this->_params[self::PARAM_FIELD_ID] = $id;

		return $this;
	}

	/**
	 * Sets the container in which the suggestions html will be rendered.
	 *
	 * @param string $id
	 */
	public function setContainerId($id) {
		$this->_params[self::PARAM_CONTAINER_ID] = $id;

		return $this;
	}

	/**
	 * Sets the sijax function to call when requesting suggestions.
	 *
	 * @param string $functionName
	 */
	public function setCallback($functionName, $callback) {
		$this->_params[self::PARAM_CALLBACK] = $functionName;

		$params = array(Core_Sijax::PARAM_RESPONSE_CLASS => __CLASS__ . '_Response');

		Core_Sijax::registerCallback($functionName, $callback, $params);

		return $this;
	}

	/**
	 * Sets the CSS class of a single suggestion's container.
	 *
	 * @param string $class
	 */
	public function setItemCssClass($class) {
		$this->_params[self::PARAM_CSS_CLASS] = $class;

		return $this;
	}

	/**
	 * Sets the CSS class of a single suggestion's container,
	 * when the suggestion container is selected (has "focus").
	 *
	 * @param string $class
	 */
	public function setItemSelectedCssClass($class) {
		$this->_params[self::PARAM_CSS_CLASS_SELECTED] = $class;

		return $this;
	}

	/**
	 * Sets the string/char to be used to delimit words in the textbox.
	 * The default is space.
	 *
	 * @param $string
	 */
	public function setDelimiter($string) {
		$this->_params[self::PARAM_DELIMITER] = $string;

		return $this;
	}

	/**
	 * Sets a key-value array of additional parameters to be passed,
	 * when suggestions are requested.
	 *
	 * Example: array('someOtherField' => "$('#fieldId').attr('value')")
	 * When requesting suggestions the `someOtherField` field
	 * will be populated with the result of eval()-ing its script.
	 *
	 * @param array $map
	 */
	public function setAdditionalData(array $map) {
		$this->_params[self::PARAM_ADDITIONAL] = $map;

		return $this;
	}

	/**
	 * Generates and returns the javascript code needed to register this suggest field.
	 */
	public function getJs() {
		return "$(function () { sjxSuggest.registerSuggestField(" . json_encode($this->_params) . "); });";
	}

}
?>
