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
*  the Free Software Foundation; either version 2 of the License, or
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

class Tx_ExtbaseHijax_Event_Listener {
	
	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;
	
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_ExtbaseHijax_Service_AutoIDService
	 */
	protected $autoIDService;
	
	/**
	 * @var Tx_Extbase_MVC_RequestInterface
	 */
	protected $request;
	
	/**
	 * @var tslib_cObj
	 */
	protected $cObj;
	
	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var string
	 */
	protected $id;
	
	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}
	
	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}
	
	/**
	 * @param Tx_ExtbaseHijax_Service_AutoIDService $autoIDService
	 */
	public function injectAutoIDService(Tx_ExtbaseHijax_Service_AutoIDService $autoIDService) {
		$this->autoIDService = $autoIDService;
	}
	
	/**
	 * Constructs a new Tx_ExtbaseHijax_Event_Listener.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface		$request		The request
	 * @param array 								$configuration 	Framework configuraiton
	 * @param tslib_cObj	 						$cObj	 	An array of parameters
	 */
	public function __construct(Tx_Extbase_MVC_RequestInterface $request, $configuration = null, $cObj = null) {
		$this->injectObjectManager(t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager'));
		$this->injectConfigurationManager($this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface'));
		$this->injectAutoIDService($this->objectManager->get('Tx_ExtbaseHijax_Service_AutoIDService'));
		
		$this->request = $request;
		$this->request->setMethod('GET');
		
		if ($configuration) {
			$this->configuration = $configuration;
		} else {
			$this->configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		}
		
		if ($cObj) {
			$this->cObj = $cObj;
		} else {
			$this->cObj = $this->configurationManager->getContentObject();
		}
		
			/* @var $listenerFactory Tx_ExtbaseHijax_Service_Serialization_ListenerFactory */
		$listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
			// old logic - using autoincrement
		//$this->id = $this->autoIDService->getAutoId(get_class($this));
			// new logic - determine the id based on md5 hash
		$this->id = ''; // resetting the id so it doesn't affect the hash
		$serialized = $listenerFactory->serialize($this);
		list($table, $uid) = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
		if ($table=='tt_content' && $uid) {
			$this->id = str_replace(':', '-', $this->cObj->currentRecord).'-'.md5($serialized);
		} else {
				// test if this is ExtbaseHijax Pi1
			if ($this->request->getControllerExtensionName()=='ExtbaseHijax' && $this->request->getPluginName()=='Pi1') {
				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['loadContentFromTypoScript']);
				$settingsHash = t3lib_div::hmac($encodedSettings);
				if ($this->configuration['switchableControllerActions']['ContentElement'][0]=='user') {
					$this->id = 'h-'.$settingsHash.'-'.$encodedSettings;
				} else {
					$this->id = 'hInt-'.$settingsHash.'-'.$encodedSettings;
				}
			} elseif ($this->configuration['settings']['fallbackTypoScriptConfiguration']) {
				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['fallbackTypoScriptConfiguration']);
				$settingsHash = t3lib_div::hmac($encodedSettings);
				$this->id = 'f-'.$settingsHash.'-'.$encodedSettings;
			} else {
				$this->id = md5($serialized);
			}
		}
	}

	/**
	 * @return Tx_Extbase_MVC_RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * @return the $request
	 */
	public function getSerializedRequest() {
		return $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_RequestFactory')->serialize($this->request);
	}
	
	/**
	 * @return the $request
	 */
	public function getSerializedCObj() {
		return $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_CObjFactory')->serialize(t3lib_div::makeInstance('Tx_ExtbaseHijax_Event_CObj', $this->cObj));
	}	

	/**
	 * @return tslib_cObj $cObj
	 */
	public function getCObj() {
		return $this->cObj;
	}

	/**
	 * @return the $configuration
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * @return the $id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param Tx_Extbase_MVC_RequestInterface $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}
	
	/**
	 * @param string $request
	 */
	public function setSerializedRequest($request) {
		$this->request = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_RequestFactory')->unserialize($request);
	}
	
	/**
	 * @param string $cObj
	 */
	public function setSerializedCObj($cObj) {
			/* @var $eventCObj Tx_ExtbaseHijax_Event_CObj */
		$eventCObj = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_CObjFactory')->unserialize($cObj);
		$eventCObj->reconstitute();
		$this->cObj = $eventCObj->getCObj();
	}	
	
	/**
	 * @param tslib_cObj $cObj
	 */
	public function setCObj($cObj) {
		$this->cObj = $cObj;
	}

	/**
	 * @param multitype: $configuration
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @param number $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
}