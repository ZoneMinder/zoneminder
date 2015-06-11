<?php

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
abstract class CrudTestCase extends CakeTestCase {

/**
 * List of Reflection properties made public
 *
 * @var array
 */
	protected $_reflectionPropertyCache = array();

/**
 * List of Reflection methods made public
 *
 * @var array
 */
	protected $_reflectionMethodCache = array();

/**
 * List of class name <=> instance used for invocation
 *
 * @var array
 */
	protected $_reflectionInstanceCache = array();

	public function setUp() {
		parent::setUp();
		$this->resetReflectionCache();
	}

/**
 * Reset the internal reflection caches
 *
 * @return void
 */
	public function resetReflectionCache() {
		$this->_reflectionPropertyCache = array();
		$this->_reflectionMethodCache = array();
		$this->_reflectionInstanceCache = array();
	}

/**
 * Map a instance of a object to its class name
 *
 * @param Object $instance
 * @return void
 */
	public function setReflectionClassInstance($instance, $class = null) {
		$class = $class ?: get_class($instance);
		$this->_reflectionInstanceCache[$class] = $instance;
	}

/**
 * Get working instance of "$class"
 *
 * @param string $class
 * @return Object
 * @throws Exception When the reflection instance cannot be found
 */
	public function getReflectionInstance($class) {
		$class = $this->_getReflectionTargetClass($class);
		if (empty($this->_reflectionInstanceCache[$class])) {
			throw new Exception(sprintf('Unable to find instance of %s in the reflection cache. Have you added it using "setReflectionClassInstance"?', $class));
		}

		return $this->_reflectionInstanceCache[$class];
	}

/**
 * Helper method to call a protected method
 *
 * @param string $method
 * @param array $args Argument list to call $method with (call_user_func_array style)
 * @param string $class Target reflection class
 * @return mixed
 */
	public function callProtectedMethod($method, $args = array(), $class = null) {
		$class = $this->_getReflectionTargetClass($class);
		$cacheKey = $class . '_' . $method;

		if (!in_array($cacheKey, $this->_reflectionMethodCache)) {
			$this->_reflectionMethodCache[$cacheKey] = new ReflectionMethod($class, $method);
			$this->_reflectionMethodCache[$cacheKey]->setAccessible(true);
		}

		return $this->_reflectionMethodCache[$cacheKey]->invokeArgs($this->getReflectionInstance($class), $args);
	}

/**
 * Helper method to get the value of a protected property
 *
 * @param string $property
 * @param string $class Target reflection class
 * @return mixed
 */
	public function getProtectedProperty($property, $class = null) {
		$Instance = $this->_getReflectionPropertyInstance($property, $class);
		return $Instance->getValue($this->getReflectionInstance($class));
	}

/**
 * Helper method to set the value of a protected property
 *
 * @param string $property
 * @param mixed $value
 * @param string $class Target reflection class
 * @return mixed
 */
	public function setProtectedProperty($property, $value, $class = null) {
		$Instance = $this->_getReflectionPropertyInstance($property, $class);
		return $Instance->setValue($this->getReflectionInstance($class), $value);
	}

/**
 * Get a reflection property object
 *
 * @param string $property
 * @param string $class
 * @return ReflectionProperty
 */
	protected function _getReflectionPropertyInstance($property, $class) {
		$class = $this->_getReflectionTargetClass($class);
		$cacheKey = $class . '_' . $property;

		if (!in_array($cacheKey, $this->_reflectionPropertyCache)) {
			$this->_reflectionPropertyCache[$cacheKey] = new ReflectionProperty($class, $property);
			$this->_reflectionPropertyCache[$cacheKey]->setAccessible(true);
		}

		return $this->_reflectionPropertyCache[$cacheKey];
	}

/**
 * Get the reflection class name
 *
 * @param string $class
 * @return string
 * @throws Exception When the reflection target cannot be found
 */
	protected function _getReflectionTargetClass($class) {
		if (is_object($class)) {
			$class = get_class($class);
		}

		if (!empty($class)) {
			return $class;
		}

		if (isset($this->defaultRelfectionTarget)) {
			$class = $this->defaultRelfectionTarget;
			if (is_object($class)) {
				$class = get_class($class);
			}
		}

		if (empty($class)) {
			throw new Exception(sprintf('Unable to find reflection target; have you set $defaultRelfectionTarget or passed in class name?', $class));
		}

		return $class;
	}

}
