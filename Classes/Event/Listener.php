<?php
namespace EssentialDots\ExtbaseHijax\Event;

/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class Listener {
	
	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\AutoIDService
	 */
	protected $autoIDService;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\RequestInterface
	 */
	protected $request;
	
	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
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
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}
	
	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}
	
	/**
	 * @param \EssentialDots\ExtbaseHijax\Service\AutoIDService $autoIDService
	 */
	public function injectAutoIDService(\EssentialDots\ExtbaseHijax\Service\AutoIDService $autoIDService) {
		$this->autoIDService = $autoIDService;
	}
	
	/**
	 * Constructs a new \EssentialDots\ExtbaseHijax\Event\Listener.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface		$request		The request
	 * @param array 								$configuration 	Framework configuraiton
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer	 						$cObj	 	An array of parameters
	 */
	public function __construct(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, $configuration = null, $cObj = null) {
		$this->injectObjectManager(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager'));
		$this->injectConfigurationManager($this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface'));
		$this->injectAutoIDService($this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\AutoIDService'));
		
		$this->request = $request;
		if (method_exists($this->request, 'setMethod')) {
			$this->request->setMethod('GET');
		}

		if ($configuration) {
			$this->configuration = $configuration;
		} else {
			$this->configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		}
		
		if ($cObj) {
			$this->cObj = $cObj;
		} else {
			$this->cObj = $this->configurationManager->getContentObject();
		}
		
			/* @var $listenerFactory \EssentialDots\ExtbaseHijax\Service\Serialization\ListenerFactory */
		$listenerFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\ListenerFactory');
			// old logic - using autoincrement
		//$this->id = $this->autoIDService->getAutoId(get_class($this));
			// new logic - determine the id based on md5 hash
		$this->id = ''; // resetting the id so it doesn't affect the hash
		$serialized = $listenerFactory->serialize($this);
		list($table, $uid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->cObj->currentRecord);
		if ($table=='tt_content' && $uid) {
			$this->id = str_replace(':', '-', $this->cObj->currentRecord).'-'.md5($serialized);
		} else {
				// test if this is ExtbaseHijax Pi1
			if (method_exists($this->request, 'getControllerExtensionName') && method_exists($this->request, 'getPluginName') && $this->request->getControllerExtensionName()=='ExtbaseHijax' && $this->request->getPluginName()=='Pi1') {
				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['loadContentFromTypoScript']);
				$settingsHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings);
				if ($this->configuration['switchableControllerActions']['ContentElement'][0]=='user') {
					$this->id = 'h-'.$settingsHash.'-'.$encodedSettings;
				} else {
					$this->id = 'hInt-'.$settingsHash.'-'.$encodedSettings;
				}
			} elseif ($this->configuration['settings']['fallbackTypoScriptConfiguration']) {
				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['fallbackTypoScriptConfiguration']);
				$settingsHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings);
				$this->id = 'f-'.$settingsHash.'-'.$encodedSettings;
			} else {
				$this->id = md5($serialized);
			}
		}
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * @return string
	 */
	public function getSerializedRequest() {
		return $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\RequestFactory')->serialize($this->request);
	}
	
	/**
	 * @return string
	 */
	public function getSerializedCObj() {
		return $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\CObjFactory')->serialize(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Event\\CObj', $this->cObj));
	}	

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
	 */
	public function getCObj() {
		return $this->cObj;
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}
	
	/**
	 * @param string $request
	 */
	public function setSerializedRequest($request) {
		$this->request = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\RequestFactory')->unserialize($request);
	}
	
	/**
	 * @param string $cObj
	 */
	public function setSerializedCObj($cObj) {
			/* @var $eventCObj \EssentialDots\ExtbaseHijax\Event\CObj */
		$eventCObj = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\CObjFactory')->unserialize($cObj);
		$eventCObj->reconstitute();
		$this->cObj = $eventCObj->getCObj();
	}	
	
	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
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