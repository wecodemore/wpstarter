<?php
/**
 * This file is part of the "" package.
 *
 * © 2016 Franz Josef Kaiser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use ArrayAccess;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\FileBuilder;

/**
 * Steps that generates wp-cli.yml in root folder.
 *
 * @author  Franz Josef Kaiser <wecodemore@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class WPCliStep implements FileStepInterface, BlockingStepInterface
{
	/**
	 * @var \WCM\WPStarter\Setup\FileBuilder
	 */
	private $builder;

	/**
	 * @var array
	 */
	private $vars;

	/**
	 * @var string
	 */
	private $error = '';

	/**
	 * @param \WCM\WPStarter\Setup\FileBuilder $builder
	 */
	public function __construct( FileBuilder $builder )
	{
		$this->builder = $builder;
	}

	/**
	 * Returns the target path of the file the step will create.
	 *
	 * @param  \ArrayAccess $paths
	 * @return string
	 */
	public function targetPath( ArrayAccess $paths )
	{
		return rtrim( "{$paths['root']}/{$paths['wp']}", "/" )."/wp-cli.yml";
	}

	/**
	 * Return true if the step is allowed, i.e. the run method have to be called or not
	 *
	 * @param  \WCM\WPStarter\Setup\Config $config
	 * @param  \ArrayAccess                $paths
	 * @return bool
	 */
	public function allowed( Config $config, ArrayAccess $paths )
	{
		return true;
	}

	/**
	 * Process the step.
	 *
	 * @param  \ArrayAccess $paths Have to return one of the step constants.
	 * @return int
	 */
	public function run( ArrayAccess $paths )
	{
		$root        = rtrim( $paths['root'], '/' );
		$parent      = rtrim( ltrim( $paths['wp-parent'], '/' ), '/' );

		$this->vars  = array(
			'WP_INSTALL_PATH' => "{$root}/{$parent}",
		);
		$build       = $this->builder->build(
			$paths,
			'wp-cli.yml.example',
			$this->vars
		);

		if ( ! $this->builder->save(
			$build,
			dirname( $this->targetPath( $paths ) ),
			'wp-cli.yml'
		) ) {
			$this->error = 'Error while creating wp-cli.yml';

			return self::ERROR;
		}

		return self::SUCCESS;
	}

	/**
	 * Return error message if any.
	 *
	 * @return string
	 */
	public function error()
	{
		return $this->error;
	}

	/**
	 * Return success message if any.
	 *
	 * @return string
	 */
	public function success()
	{
		return '<comment>wp-cli.yml</comment> saved successfully.';
	}
}