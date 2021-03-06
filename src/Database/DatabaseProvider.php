<?php
/**
 * This file is part of the Autarky package.
 *
 * (c) Andreas Lutro <anlutro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Autarky\Database;

use Autarky\Container\ContainerInterface;
use Autarky\Kernel\ServiceProvider;

/**
 * Simple service provider that binds a shared PDO instance onto the container,
 * using settings found in the database config file.
 */
class DatabaseProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->getContainer()->share('Autarky\Database\MultiPdoContainer', function() {
			return new MultiPdoContainer($this->app->getConfig());
		});

		$this->app->getContainer()->share('PDO', function(ContainerInterface $container) {
			return $container->resolve('Autarky\Database\MultiPdoContainer')->getPdo();
		});
	}
}
