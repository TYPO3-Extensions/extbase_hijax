<?php
namespace EssentialDots\ExtbaseHijax\HTMLConverter;

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

abstract class AbstractConverter implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var \EssentialDots\ExtbaseHijax\Configuration\ExtensionInterface
	 */
	protected $extensionConfiguration;

	/**
	 * Injects the extension configuration
	 *
	 * @param \EssentialDots\ExtbaseHijax\Configuration\ExtensionInterface $extensionConfiguration
	 * @return void
	 */
	public function injectEventDispatcher(\EssentialDots\ExtbaseHijax\Configuration\ExtensionInterface $extensionConfiguration) {
		$this->extensionConfiguration = $extensionConfiguration;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Response $response
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Response
	 */
	abstract public function convert($response);
}