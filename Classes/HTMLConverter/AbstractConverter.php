<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

abstract class Tx_ExtbaseHijax_HTMLConverter_AbstractConverter implements t3lib_Singleton {
	/**
	 * @var Tx_ExtbaseHijax_Configuration_ExtensionInterface
	 */
	protected $extensionConfiguration;

	/**
	 * Injects the extension configuration
	 *
	 * @param Tx_ExtbaseHijax_Configuration_ExtensionInterface $extensionConfiguration
	 * @return void
	 */
	public function injectEventDispatcher(Tx_ExtbaseHijax_Configuration_ExtensionInterface $extensionConfiguration) {
		$this->extensionConfiguration = $extensionConfiguration;
	}

	/**
	 * @param Tx_Extbase_MVC_Web_Response $response
	 * @return Tx_Extbase_MVC_Web_Response
	 */
	abstract public function convert($response);
}