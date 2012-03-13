<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class Tx_ExtbaseHijax_Service_Serialization_RequestFactory extends Tx_ExtbaseHijax_Service_Serialization_AbstractFactory {
	
	/**
	 * @var array
	 */
	protected $properties = array('format', 'method', 'isCached', 'baseUri', 'controllerObjectName', 'pluginName', 'controllerExtensionName', 'controllerExtensionKey', 'controllerSubpackageKey', 'controllerName', 'controllerActionName');//, 'arguments', 'internalArguments');
	
	/**
	 * Unserialize an object
	 *
	 * @param string $str
	 * @return object
	 */
	public function unserialize($str) {
		$object = parent::unserialize($str);
	
		$object->setRequestUri(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		
		return $object;
	}	
}