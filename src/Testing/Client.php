<?php
/**
 * This file is part of the Autarky package.
 *
 * (c) Andreas Lutro <anlutro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Autarky\Testing;

use Symfony\Component\HttpKernel\Client as BaseClient;

/**
 * {@inheritdoc}
 */
class Client extends BaseClient
{
	/**
	 * {@inheritdoc}
	 */
	protected function doRequest($request)
	{
		return $this->kernel->run($request, false);
	}
}