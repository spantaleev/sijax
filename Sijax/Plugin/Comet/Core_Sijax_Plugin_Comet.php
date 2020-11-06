<?php
/**
 * A helper class to simplify comet usage.
 */
final class Core_Sijax_Plugin_Comet {

	/**
	 * Helper function to simplify registering comet functions with Sijax.
	 *
	 * @param string $functionName
	 * @param callback $callback
	 * @param array $params
	 */
	public static function registerCallback($functionName, $callback, $params = array()) {
		if (! isset($params [Core_Sijax::PARAM_RESPONSE_CLASS])) {
			$params [Core_Sijax::PARAM_RESPONSE_CLASS] = __CLASS__ . '_Response';
		}

		Core_Sijax::registerCallback($functionName, $callback, $params);
	}

}
?>
