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

class Tx_ExtbaseHijax_Service_Serialization_ListenerFactory extends Tx_ExtbaseHijax_Service_Serialization_AbstractFactory {
	
	/**
	 * @var Tx_ExtbaseHijax_Service_Content
	 */
	protected $serviceContent;

	/**
	 * @var array
	 */
	protected $properties = array('configuration', 'id', 'serializedRequest', 'serializedCObj');

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->serviceContent = $this->objectManager->get('Tx_ExtbaseHijax_Service_Content');
	}
	
	/**
	 * @param string $listenerId
	 * @return object
	 */
	public function findById($listenerId) {
		if ($listenerId) {
			$object = parent::findById($listenerId);
			
			if (!$object) {
				list($table, $uid, $rawListenerId) = t3lib_div::trimExplode('-', $listenerId);
				
					// try to generate the listener cache
				$object = $this->serviceContent->generateListenerCache($table, $uid);	
			}
		
			return $object;
		} else {
			return null;
		}
	}	
	
}