<?php
namespace Blocks;

/**
 * App functions
 */
class AppVariable
{
	/**
	 * Returns the current Blocks build
	 * @return string
	 */
	public function build()
	{
		return Blocks::getBuild();
	}

	/**
	 * Returns the current Blocks version
	 * @return string
	 */
	public function version()
	{
		return Blocks::getVersion();
	}

	/**
	 * Returns the current Blocks edition
	 * @return string
	 */
	public function edition()
	{
		return Blocks::getEdition();
	}
}
