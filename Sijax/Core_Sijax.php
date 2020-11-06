<?php
/**
 * This is the main Sijax class that takes care of,
 * registering callable functions, processing incoming data and dispatching calls.
 */
final class Core_Sijax {

	const PARAM_REQUEST = 'sijax_rq';
	const PARAM_ARGS = 'sijax_args';

	const EVENT_BEFORE_PROCESSING = 'beforeProcessing';
	const EVENT_AFTER_PROCESSING = 'afterProcessing';
	const EVENT_INVALID_REQUEST = 'invalidRequest';

	const PARAM_CALLBACK = 'callback';
	const PARAM_RESPONSE_CLASS = 'responseClass';

	private static $_requestUri = null;
	private static $_jsonUri = null;
	private static $_registeredMethods = array();
	private static $_events = array();

	//Would contain the request data (usually $_POST)
	private static $_data = array();

	//Would store a cached version of the arguments to pass to the requested function
	private static $_requestArgs = null;

	/**
	 * Sets the incoming data array.
	 * This is usually $_POST or whatever the framework uses.
	 *
	 * @param array $data
	 */
	public static function setData(array $data) {
		self::$_data = $data;
	}

	/**
	 * Returns the incoming data array.
	 */
	public static function getData() {
		return self::$_data;
	}

	/**
	 * Tells Sijax the URI to submit ajax requests to.
	 * If you don't pass a request URI, the current URI would be
	 * detected and set automatically.
	 *
	 * @param string $uri
	 */
	public static function setRequestUri($uri) {
		self::$_requestUri = $uri;
	}

	/**
	 * Sets the URI to an external JSON library,
	 * for browsers that do not support native JSON (such as IE<=7).
	 *
	 * The specified script will only be loaded if such a browser is detected.
	 * If this is not specified, Sijax will not work at all in IE<=7.
	 *
	 * @param $uri
	 */
	public static function setJsonUri($uri) {
		self::$_jsonUri = $uri;
	}

	/**
	 * Returns the name of the requested function
	 * or NULL if no function is requested.
	 */
	public static function getRequestFunction() {
		if (! isset(self::$_data[self::PARAM_REQUEST])) {
			return null;
		}

		return (string) self::$_data[self::PARAM_REQUEST];
	}

	/**
	 * Returns an array of arguments to pass to the requested function.
	 */
	public static function getRequestArgs() {
		if (self::$_requestArgs === null) {
			if (isset(self::$_data[self::PARAM_ARGS])) {
				self::$_requestArgs = (array) json_decode(self::$_data[self::PARAM_ARGS], true);
			} else {
				self::$_requestArgs = array();
			}
		}

		return self::$_requestArgs;
	}

	/**
	 * Sets the request arguments, possibly overriding the autodetected arguments array.
	 * This is useful for plugins that would like to "rewrite" the arguments array.
	 *
	 * @param array $args
	 */
	public static function setRequestArgs(array $args) {
		self::$_requestArgs = $args;
	}

	/**
	 * Tells whether the current request is a Sijax request.
	 */
	public static function isSijaxRequest() {
		if (! isset(self::$_data[self::PARAM_REQUEST])) {
			return false;
		}

		if (! isset(self::$_data[self::PARAM_ARGS])) {
			return false;
		}

		return true;
	}

	/**
	 * Registers all methods of the specified object instance (or class).
	 * These methods will be callable from the browser.
	 *
	 * The optional $params array allows the response class
	 * to be changed from the default one (Core_Sijax_Response).
	 *
	 * @param object/class $object
	 * @param array $params
	 * @throws Exception
	 */
	public static function registerObject($object, $params = array()) {
		if ($object === null) {
			throw new Exception ( 'Object is NULL!' );
		}

		foreach ( get_class_methods ( $object ) as $methodName ) {
			if (isset(self::$_registeredMethods[$methodName])) {
				//Don't register methods on top of another methods..
				continue;
			}

			$params [self::PARAM_CALLBACK] = array ( $object, $methodName );

			self::$_registeredMethods [$methodName] = $params;
		}
	}

	/**
	 * Registers the specified callback function (closure, class method, function name),
	 * to be callable from the browser.
	 *
	 * The optional $params array allows the response class
	 * to be changed from the default one (Core_Sijax_Response).
	 *
	 * @param $functionName
	 * @param $callback
	 * @param $params
	 */
	public static function registerCallback($functionName, $callback, $params = array()) {
		$params [self::PARAM_CALLBACK] = $callback;

		self::$_registeredMethods [$functionName] = $params;
	}

	/**
	 * Executes the specified callback (closure, class method, function name),
	 * passing the specified arguments to it.
	 *
	 * The optional $params array allows the response class
	 * to be changed from the default one (Core_Sijax_Response).
	 *
	 * @param callback $callback
	 * @param array $args
	 * @param array $params
	 */
	public static function executeCallback($callback = null, $args = array(), $params = array()) {
		if (isset($params [self::PARAM_RESPONSE_CLASS])) {
			$responseClass = $params [self::PARAM_RESPONSE_CLASS];
		} else {
			$responseClass = __CLASS__ . '_Response';
		}

		$objResponse = new $responseClass($args);

		self::fireEvent($objResponse, self::EVENT_BEFORE_PROCESSING, array());

		self::_callFunction($callback, $objResponse);

		self::fireEvent($objResponse, self::EVENT_AFTER_PROCESSING, array());

		die($objResponse->getJson());
	}

	/**
	 * Inspects the data array (as specified by setData()) to determine
	 * if the current server request is to be handled by sijax.
	 *
	 * If this is a normal page request, this simply returns without doing anything.
	 *
	 * If this is a VALID sijax request (for a registered function), it gets called.
	 *
	 * If this is an INVALID sijax request, the EVENT_INVALID_REQUEST event gets fired.
	 * In case no custom event handler is specified, the default one is triggered (_invalidRequestCallback).
	 */
	public static function processRequest() {
		if (! self::isSijaxRequest()) {
			return;
		}

		$requestFunction = self::getRequestFunction();
		$callback = null;
		$args = array();
		$params = array();

		if (isset(self::$_registeredMethods[$requestFunction])) {
			$params = self::$_registeredMethods[$requestFunction];
			$callback = $params [self::PARAM_CALLBACK];
			$args = self::getRequestArgs();
		} else {
			if (self::hasEvent(self::EVENT_INVALID_REQUEST)) {
				$callback = self::$_events[self::EVENT_INVALID_REQUEST];
			} else {
				$callback = array(__CLASS__, '_invalidRequestCallback');
			}

			$args = array($requestFunction);
		}

		self::executeCallback($callback, $args, $params);
	}

	private static function _invalidRequestCallback(Core_Sijax_Response $objResponse, $functionName) {
		$objResponse->alert('The action you performed is currently unavailable! (Sijax error)');
	}

	/**
	 * Prepares the callback function arguments and calls it.
	 *
	 * The optional $requestArgs array allows the detected request args
	 * which may have been altered by the response object to be overriden.
	 * It's not used for normal requests.
	 * Events and manually executed callbacks however override the request args.
	 *
	 * @param callback $callback
	 * @param Core_Sijax_Response $objResponse
	 * @param array $requestArgs
	 */
	private static function _callFunction($callback, Core_Sijax_Response $objResponse, $requestArgs = null) {
		if ($requestArgs === null) {
			/**
			 * Normal functions are called like this.
			 * The object response class was given the args before,
			 * but may have changed them
			 */
			$requestArgs = $objResponse->getRequestArgs();
		}

		$args = array_merge(array($objResponse), $requestArgs);

		try {
			call_user_func_array($callback, $args);
		} catch (\TypeError $e) {
			$objResponse->call('console.log', ['Sijax error: function called with an invalid number of arguments or wrong types']);
		}
	}

	/**
	 * Sets a callback function to be called when the specified event occurs.
	 * Only one callback can be executed per event.
	 *
	 * The provided EVENT_* constants should be used for handling system events.
	 * Additionally, you can use any string to define your own events and callbacks.
	 *
	 * If more are needed, they may be chained manually.
	 * Certain functionality to allow this (getEvent()) is missing though.
	 *
	 * @param string $eventName
	 * @param callback $callback
	 */
	public static function registerEvent($eventName, $callback) {
		self::$_events[$eventName] = $callback;
	}

	/**
	 * Tells whether there's a callback function to be called
	 * when the specified event occurs.
	 *
	 * @param string $eventName
	 */
	public static function hasEvent($eventName) {
		return isset(self::$_events[$eventName]);
	}

	/**
	 * Fires the specified event.
	 *
	 * @param Core_Sijax_Response $objResponse
	 * @param string $eventName
	 * @param array $args
	 */
	public static function fireEvent(Core_Sijax_Response $objResponse, $eventName, array $args) {
		if (! self::hasEvent($eventName)) {
			return;
		}

		return self::_callFunction(self::$_events[$eventName], $objResponse, $args);
	}

	/**
	 * Tries to detect the current request URI.
	 * Sijax requests would be send to the same URI.
	 * If you want to avoid autodetection, or override this, use setRequestUri().
	 */
	private static function _detectRequestUri() {
		$requestUri = strip_tags ( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
		self::setRequestUri($requestUri);
	}

	/**
	 * Returns the javascript needed to prepare sijax for running on a page.
	 */
	public static function getJs() {
		if (self::$_requestUri === null) {
			self::_detectRequestUri();
		}

		$script = "";

		$script .= "Sijax.setRequestUri(" . json_encode(self::$_requestUri) . ");";
		$script .= "Sijax.setJsonUri(" . json_encode(self::$_jsonUri) . ");";

		return $script;
	}

}
?>
