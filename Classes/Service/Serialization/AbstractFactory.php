<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic@essentialdots.com>
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

abstract class Tx_ExtbaseHijax_Service_Serialization_AbstractFactory implements t3lib_Singleton {
	
	/**
	 * @var array
	 */
	protected $properties = array();
	
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $storage;	
	
	/**
	 * @var Tx_Extbase_Object_Container_Container
	 */
	protected $objectContainer;
	
	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;	
	
	/**
	 * @var array
	 */
	protected $objectCache;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container');	
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->storage = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_storage');
		$this->objectCache = array();
	}
	
	/**
	 * Serialize an object
	 * 
	 * @param object $object
	 */
	public function serialize($object) {
		$properties = array();
		foreach ($this->properties as $property) {
			if (method_exists($object, 'get'.ucfirst($property))) {
				$properties[$property] = call_user_func(array($object, 'get'.ucfirst($property)));
			} elseif (method_exists($object, $property)) {
				$properties[$property] = call_user_func(array($object, $property));
			}
		}
		
		return serialize(array('properties'=>$properties, 'className'=>get_class($object)));
	}
	
	/**
	 * Unserialize an object
	 *
	 * @param string $str
	 * @return object
	 */
	public function unserialize($str) {
		$o = unserialize($str);
		$className = $o['className'];
		$object = $this->objectContainer->getEmptyObject($className); 
		
		foreach ($o['properties'] as $property => $value) {
			if (method_exists($object, 'set'.ucfirst($property))) {
				call_user_func(array($object, 'set'.ucfirst($property)), $value);
			}
		}
	
		return $object;
	}	
	
	/**
	 * @param unknown_type $object
	 * @return boolean
	 */
	public function persist($object) {
		$id = $this->getIdForObject($object);
		if ($id) {
			if (!$this->storage->has($id)) {
				$this->storage->set($id, $this->serialize($object));
			}
			$result = true;
		} else {
			$result = false;
		}
	}
	
	/**
	 * @param string $id
	 * @return object
	 */
	public function findById($id) {
		$fullId = 'serialized-'.get_class($this).'-'.$id;
		$object = null;
		
		if ($this->objectCache[$fullId]) {
			$object = $this->objectCache[$fullId];
		} elseif ($this->storage->has($fullId)) {
			$object = $this->unserialize($this->storage->get($fullId));
			$this->objectCache[$fullId] = $object;
		}
		
		return $object;
	}
	
	/**
	 * 
	 * @param mixed $object
	 * @return string
	 */
	protected function getIdForObject($object) {
		if (method_exists($object, 'getId')) {
			$result = 'serialized-'.get_class($this).'-'.$object->getId();
		} else {
			$result = false;
		}

		return $result;
	}
}