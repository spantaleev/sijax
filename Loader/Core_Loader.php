<?php
/**
 * Autoloads certainly formatted class names.
 * 
 * Every class is "namespaced" by adding a `Prefix_` to it.
 * Once that prefix is registered for autoloading, using:
 * `Core_Loader::registerPrefix('Prefix_', '{base path to Prefix}`)`
 * the loader would try to load `Prefix_` classes automatically.
 * 
 * The naming convetion looks similar to that of Zend Framework,
 * with a slight change - the filename is different.
 * 
 * `Prefix_My_ClassName` maps to the file:
 * `{base path to Prefix}/My/ClassName/Prefix_My_ClassName.php`
 * While Zend Framework would have mapped it to:
 * `{base path to Prefix}/My/ClassName.php`
 *  
 * Regular namespaces (available since php 5.3) are currently not supported.
 */
final class Core_Loader {

	private static $_prefixData = array ();
	private static $_inittedPackages = array();
	private static $_requiredOnce = array ();
	
	private static $_eventsMap = array();
	private static $_events = array();
	
	private static $_isRegistered = false;
	
	const EVENT_BEFORE_PACKAGE_LOAD = 0;
	const EVENT_AFTER_PACKAGE_LOAD = 1;
	const EVENT_BEFORE_CLASS_LOAD = 2;
	const EVENT_AFTER_CLASS_LOAD = 3;
	const EVENT_EXCEPTION = 4;

	public static function registerPrefix($prefixKey, $basePath) {
		if (! self::$_isRegistered) {
			spl_autoload_register ( array (__CLASS__, 'autoload' ) );
			self::$_isRegistered = true;
		}
		
		self::$_prefixData [$prefixKey] = $basePath;
	}
	
	public static function registerEvent($type, $callback) {
		self::$_eventsMap[$type] = true;
		
		if (! isset(self::$_events[$type])) {
			self::$_events[$type] = array($callback);
		} else {
			self::$_events[$type][] = $callback;
		}
	}
	
	public static function fireEvent($type, array $args) {
		$callbacksArray = self::$_events[$type];
		
		foreach ($callbacksArray as $callback) {
			call_user_func_array($callback, $args);
		}
	}

	private static function getPathByPrefix($prefixKey) {
		if (isset ( self::$_prefixData [$prefixKey] )) {
			return self::$_prefixData [$prefixKey];
		}

		return null;
	}

	public static function autoload($className) {
		self::loadComponent($className, true);
	}

	public static function loadComponent($className, $isAutoload = false) {
		$start = microtime ( true );

		$partsOriginal = explode ( '_', $className );

		if (! isset($partsOriginal [1]) ) {
			//This is an unrecognized class by this autoloader.
			//It's probably destined to be handled by another one.
			//This only recognizes classes named like that: `{Prefix}_{Anything}`
			return;
		}

		$partsNoPrefix = $partsOriginal;
		$prefixKey = array_shift ( $partsNoPrefix );
		
		if (! isset(self::$_prefixData[$prefixKey])) {
			//Unknown prefix key..
			//Class loading is probably desitined to be handled by another autoloader
			return;
		}
		
		$appPath = self::$_prefixData[$prefixKey];
		$packageKey = (isset($partsNoPrefix[0]) ? $partsNoPrefix[0] : '');
		$classPath = $appPath . implode ( '/', $partsNoPrefix ) . '/' . implode ( '_', $partsOriginal ) . '.php';

		if (! file_exists ( $classPath )) {
			self::throwException ( 'No class file found for class: ' . $className . ' in ' . $classPath, $isAutoload );
		}

		if (isset(self::$_eventsMap[self::EVENT_BEFORE_PACKAGE_LOAD])) {
			self::fireEvent(self::EVENT_BEFORE_PACKAGE_LOAD, array($prefixKey, $packageKey, $className));
		}
		
		if (isset(self::$_eventsMap[self::EVENT_BEFORE_CLASS_LOAD])) {
			self::fireEvent(self::EVENT_BEFORE_CLASS_LOAD, array($className));
		}
		
		require $classPath;
		
		if (isset(self::$_eventsMap[self::EVENT_AFTER_CLASS_LOAD])) {
			self::fireEvent(self::EVENT_AFTER_CLASS_LOAD, array($className, microtime ( true ) - $start));
		}

		if (isset(self::$_eventsMap[self::EVENT_AFTER_PACKAGE_LOAD])) {
			self::fireEvent(self::EVENT_AFTER_PACKAGE_LOAD, array($prefixKey, $packageKey, $className));
		}
	}

	/**
	 * This "throws" exceptions in a smart way, depending on whether we're in an autoload function.
	 * Autoload exceptions cannot be caught, so we're sending them using an event callback.
	 * If there's no event callback registered for that, we just die().
	 *
	 * @param string $message
	 */
	public static function throwException($message, $inAutoload = false) {
		if (! $inAutoload) {
			throw new Core_Loader_Exception($message);
		}

		if (isset(self::$_eventsMap[self::EVENT_EXCEPTION])) {
			if (class_exists('Core_Exception', false)) {
				$exceptionObject = new Core_Exception($message);
			} else {
				$exceptionObject = new Exception($message);
			}
			
			self::fireEvent(self::EVENT_EXCEPTION, array($exceptionObject));
		} else {
			die ( __CLASS__ . ': an error occured during autoloading, but we there\'s no callback to handle it.' );
		}
	}

	/**
	 * require_once alternative which throws exceptions when
	 * loading the specified file fails.
	 * It's also a little bit faster than require_once.
	 * 
	 * @param unknown_type $path
	 * @throws Core_Exception
	 */
	public static function requireOnce($path) {
		if (isset ( self::$_requiredOnce [$path] )) {
			return;
		}

		self::$_requiredOnce [$path] = true;
		if (! file_exists ( $path )) {
			throw new Core_Loader_Exception ( 'File `' . $path . '` does not exist!' );
		}

		require $path;
	}
	
	/**
	 * App_Something_Else maps to {path_to_App}/Something/Else/
	 * 
	 * @param string $ident
	 * @throws Core_Exception
	 */
	public static function getBaseLocationByIdentifier($ident) {
		$partsOriginal = explode ( '_', $ident );

		if (! isset($partsOriginal[1])) {
			throw new Core_Exception ( 'Invalid identifier: ' . $ident );
		}

		$partsNoPrefix = $partsOriginal;
		$prefixKey = array_shift ( $partsNoPrefix );

		$basePath = self::getPathByPrefix ( $prefixKey );
		if ($basePath === null) {
			throw new Core_Exception ( 'Unknown prefix for identifier: ' . $ident );
		}

		return $basePath . implode ( '/', $partsNoPrefix ) . '/';
	}
	
}
?>