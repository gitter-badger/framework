<?php
/**
 * This file is part of the Autarky package.
 *
 * (c) Andreas Lutro <anlutro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Autarky\Container;

use Closure;
use ReflectionClass;
use ReflectionException;

/**
 * Default implementation of the IoC container.
 */
class Container implements ContainerInterface
{
	protected $instances = [];
	protected $factories = [];
	protected $aliases = [];
	protected $aware = [];

	/**
	 * {@inheritdoc}
	 */
	public function bind($abstract, $concrete = null)
	{
		if ($concrete === null) {
			$concrete = $abstract;
		}

		$this->factories[$abstract] = $this->getFactory($concrete);
	}

	protected function getFactory($concrete)
	{
		if ($concrete instanceof Closure) {
			return $concrete;
		}

		return function($container) use($concrete) {
			return $this->build($concrete);
		};
	}

	/**
	 * {@inheritdoc}
	 */
	public function share($abstract, $concrete = null)
	{
		if ($concrete === null) {
			$concrete = $abstract;
		}

		if (is_string($concrete)) {
			$concrete = $this->getFactory($concrete);
		}

		if ($concrete instanceof Closure) {
			$this->factories[$abstract] = function($container) use($abstract, $concrete) {
				$result = $concrete($container);
				$this->instances[$abstract] = $result;
				return $result;
			};
		} else {
			$this->checkAwareInterfaces($concrete);
			$this->instances[$abstract] = $concrete;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function resolve($abstract)
	{
		if (isset($this->aliases[$abstract])) {
			$abstract = $this->aliases[$abstract];
		}

		if (isset($this->instances[$abstract])) {
			return $this->instances[$abstract];
		}

		if (isset($this->factories[$abstract])) {
			$object = $this->factories[$abstract]($this);
		} else {
			$object = $this->build($abstract);
		}

		$this->checkAwareInterfaces($object);

		return $object;
	}

	protected function checkAwareInterfaces($object)
	{
		if ($object instanceof ContainerAwareInterface) {
			$object->setContainer($this);
		}

		foreach ($this->aware as $aware) {
			if ($object instanceof $aware[0]) {
				$params = array_map(function($param) {
					return $this->resolve($param);
				}, $aware[2]);
				call_user_func_array([$object, $aware[1]], $params);
			}
		}
	}

	protected function build($class)
	{
		$reflClass = new ReflectionClass($class);

		if (!$reflClass->isInstantiable()) {
			throw new NotInstantiableException("Class $class is not instantiable");
		}

		if (!$reflClass->hasMethod('__construct')) {
			return $reflClass->newInstance();
		}

		$args = [];
		$reflMethod = $reflClass->getMethod('__construct');

		foreach ($reflMethod->getParameters() as $reflParam) {
			if (!$paramClass = $reflParam->getClass()) {
				break;
			}

			if ($reflParam->isOptional()) {
				try {
					$args[] = $this->resolve($paramClass->getName());
				} catch (ReflectionException $e) {
					$args[] = null;
				}
			} else {
				$args[] = $this->resolve($paramClass->getName());
			}
		}

		return $reflClass->newInstanceArgs($args);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alias($alias, $target)
	{
		$this->aliases[$alias] = $target;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isBound($abstract)
	{
		if (isset($this->aliases[$abstract])) {
			$abstract = $this->aliases[$abstract];
		}

		return isset($this->instances[$abstract])
			|| isset($this->factories[$abstract]);
	}

	public function aware($interface, $method, $parameters)
	{
		$this->aware[] = [$interface, $method, (array) $parameters];
	}
}
