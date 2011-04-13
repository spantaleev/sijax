<?php
/**
 * This is an interface allowing us to take sijax methods out of the controllers
 * yet have them interact with the controller data natively.
 *
 */
abstract class Core_Sijax_Handler {

	private $_handlerObject = null;

	public function __construct($handlerObject = null) {
		if ($handlerObject === null) {
			throw new Core_Exception('A context object MUST be passed!');
		}

		$this->_handlerObject = $handlerObject;
	}

	public function __get($var) {
		return $this->_handlerObject->$var;
	}

	public function __call($func, $args) {
		return call_user_func_array(array($this->_handlerObject, $func), $args);
	}

	public function __set($var, $value) {
		$this->_handlerObject->$var = $value;
	}

}
?>
