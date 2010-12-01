<?php
/**
 * This is the base Sijax response class.
 * A response class object is passed as the first argument to every Sijax response callback.
 *
 * The response object is the way to talk back to the client (browser).
 * Calling the different methods of it queues commands to be passed to the client
 * when the response callback exits.
 */
class Core_Sijax_Response {

	const COMMAND_ALERT = 'alert';
	const COMMAND_HTML = 'html';
	const COMMAND_SCRIPT = 'script';
	const COMMAND_ATTR = 'attr';
	const COMMAND_CSS = 'css';
	const COMMAND_REMOVE = 'remove';
	const COMMAND_CALL = 'call';

	//Contains the commands queue
	private $_commands = array();

	//Contains the arguments to pass to the function
	protected $_requestArgs = array();

	public function __construct(array $requestArgs) {
		$this->_requestArgs = $requestArgs;
	}

	/**
	 * Returns the final request arguments to pass to the callback function.
	 *
	 * The initial request arguments are passed to this class' constructor.
	 * They, however, may not be final. Certain response classes need to modify them.
	 */
	public function getRequestArgs() {
		return $this->_requestArgs;
	}

	/**
	 * Adds a command to the queue.
	 * You shouldn't need to call this unless you're developing a plugin.
	 *
	 * @param string $type
	 * @param array $params
	 */
	public function addCommand($type, array $params) {
		$params ['type'] = (string) $type;

		$this->_commands [] = $params;

		return $this;
	}

	/**
	 * Removes every command added to the queue.
	 */
	public function clearCommands() {
		$this->_commands = array();

		return $this;
	}

	/**
	 * Shows a window alert message.
	 * Same as `window.alert()` in a browser.
	 *
	 * @param string $string
	 */
	public function alert($string) {
		return $this->addCommand(self::COMMAND_ALERT, array(self::COMMAND_ALERT => $string));
	}

	private function _html($selector, $html, $setType) {
		return $this->addCommand(self::COMMAND_HTML, array('selector' => $selector, 'html' => $html, 'setType' => $setType));
	}

	/**
	 * Adds the given html to the element specified by the selector,
	 * replacing any html content inside it.
	 * Scripts inside the html block are also executed.
	 *
	 * @param string $selector
	 * @param string $html
	 */
	public function html($selector, $html) {
		return $this->_html($selector, $html, 'replace');
	}

	/**
	 * Appends the given html to the element specified by the selector.
	 * Scripts inside the html block are also executed.
	 *
	 * @param string $selector
	 * @param string $html
	 */
	public function htmlAppend($selector, $html) {
		return $this->_html($selector, $html, 'append');
	}

	/**
	 * Prepends the given html to the element specified by the selector.
	 * Scripts inside the html block are also executed.
	 *
	 * @param string $selector
	 * @param string $html
	 */
	public function htmlPrepend($selector, $html) {
		return $this->_html($selector, $html, 'prepend');
	}


	/**
	 * Executes the given javascript code.
	 *
	 * @param string $script
	 */
	public function script($script) {
		return $this->addCommand(self::COMMAND_SCRIPT, array(self::COMMAND_SCRIPT => $script));
	}

	/**
	 * Finds an element by the specified selector and changes
	 * the specified css (style) property to the given value.
	 * Same as jquery's $(selector).css('property', 'value');
	 *
	 * @param string $selector
	 * @param string $property
	 * @param mixed $value
	 */
	public function css($selector, $property, $value) {
		return $this->addCommand(self::COMMAND_CSS, array('selector' => $selector, 'key' => $property, 'value' => $value));
	}

	private function _attr($selector, $property, $value, $setType) {
		$params = array();
		$params ['selector'] = $selector;
		$params ['key'] = $property;
		$params ['value'] = $value;
		$params ['setType'] = $setType;

		return $this->addCommand(self::COMMAND_ATTR, $params);
	}

	/**
	 * Finds an element by the specified selector and changes
	 * the specified property to the given value.
	 * Same as jquery's $(selector).attr('property', 'value');
	 *
	 * @param string $selector
	 * @param string $property
	 * @param mixed $value
	 */
	public function attr($selector, $property, $value) {
		return $this->_attr($selector, $property, $value, 'replace');
	}

	/**
	 * Same as attr(), but this appends the given value,
	 * instead of setting it.
	 *
	 * @param string $selector
	 * @param string $property
	 * @param mixed $value
	 */
	public function attrAppend($selector, $property, $value) {
		return $this->_attr($selector, $property, $value, 'append');
	}

	/**
	 * Same as attr(), but this prepends the given value,
	 * instead of setting it.
	 *
	 * @param string $selector
	 * @param string $property
	 * @param mixed $value
	 */
	public function attrPrepend($selector, $property, $value) {
		return $this->_attr($selector, $property, $value, 'prepend');
	}

	/**
	 * Removes the element specified by the selector.
	 *
	 * @param string $selector
	 */
	public function remove($selector) {
		return $this->addCommand(self::COMMAND_REMOVE, array(self::COMMAND_REMOVE => $selector));
	}

	/**
	 * Redirects to the given URL.
	 *
	 * @param string $url
	 */
	public function redirect($url) {
		return $this->script("window.location = " . json_encode($url) . ";");
	}

	/**
	 * Calls the specified javascript function,
	 * passing the params array to it.
	 *
	 * @param string $jsFunctionName
	 * @param array $params
	 */
	public function call($jsFunctionName, $params = array()) {
		return $this->addCommand(self::COMMAND_CALL, array(self::COMMAND_CALL => $jsFunctionName, 'params' => $params));
	}

	public function getJson() {
		return json_encode($this->_commands);
	}

}
?>