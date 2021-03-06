<?php
namespace Autarky\Tests\Config;

use PHPUnit_Framework_TestCase;
use Mockery as m;

use Autarky\Config\FileStore;
use Autarky\Config\LoaderFactory;

class FileStoreTest extends PHPUnit_Framework_TestCase
{
	protected function getConfigPath()
	{
		return TESTS_RSC_DIR.'/config';
	}

	protected function makeConfig()
	{
		$loaderFactory = new LoaderFactory(new \Autarky\Container\Container);
		$loaderFactory->addLoader('php', 'Autarky\Config\Loaders\PhpFileLoader');
		return new FileStore($loaderFactory, $this->getConfigPath());
	}

	/** @test */
	public function canGet()
	{
		$config = $this->makeConfig();
		$this->assertEquals('bar', $config->get('testfile.foo'));
	}

	/** @test */
	public function canSet()
	{
		$config = $this->makeConfig();
		$config->set('testfile.foo', 'baz');
		$this->assertEquals('baz', $config->get('testfile.foo'));
	}

	/** @test */
	public function canGetAndSet()
	{
		$config = $this->makeConfig();
		$this->assertEquals('bar', $config->get('testfile.foo'));
		$config->set('testfile.foo', 'baz');
		$this->assertEquals('baz', $config->get('testfile.foo'));
	}

	/** @test */
	public function getNonexistantKeys()
	{
		$config = $this->makeConfig();
		$this->assertEquals(null, $config->get('testfile.bar'));
		$this->assertEquals(null, $config->get('testfile.bar.baz'));
	}

	/** @test */
	public function getDefault()
	{
		$config = $this->makeConfig();
		$this->assertEquals('bar', $config->get('testfile.bar', 'bar'));
	}

	/** @test */
	public function environmentOverrides()
	{
		$config = $this->makeConfig();
		$config->setEnvironment('dummyenv');
		$this->assertEquals('baz', $config->get('testfile.foo'));
	}

	/** @test */
	public function addNamespace()
	{
		$config = $this->makeConfig();
		$config->addNamespace('namespace', $this->getConfigPath().'/vendor/namespace');
		$this->assertEquals('three', $config->get('namespace:testfile.three'));
	}

	/** @test */
	public function overrideNamespace()
	{
		$config = $this->makeConfig();
		$config->addNamespace('namespace', $this->getConfigPath().'/vendor/namespace');
		$this->assertEquals('ONE', $config->get('namespace:testfile.one'));
	}

	/** @test */
	public function namespaceInEnvironment()
	{
		$config = $this->makeConfig();
		$config->setEnvironment('dummyenv');
		$config->addNamespace('namespace', $this->getConfigPath().'/vendor/namespace');
		$this->assertEquals('ONE', $config->get('namespace:testfile.one'));
	}

	/** @test */
	public function notArrayThrowsException()
	{
		$this->setExpectedException('InvalidArgumentException');
		$config = $this->makeConfig();
		$config->get('notarray.foo');
	}

	/** @test */
	public function customLoaderIsCalled()
	{
		$config = $this->makeConfig();
		$config->getLoaderFactory()->addLoader('mock', m::mock(['load' => ['foo' => 'bar']]));
		$this->assertEquals('bar', $config->get('mockedfile.foo'));
		$this->assertEquals(null, $config->get('mockedfile.bar'));
	}
}
