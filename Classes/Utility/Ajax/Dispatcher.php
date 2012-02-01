<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

/**
 * An AJAX dispatcher.
 */
class Tx_ExtbaseHijax_Utility_Ajax_Dispatcher implements t3lib_Singleton {
	/**
	 * @var int
	 */
	protected static $loopCount = 0;
	
	/**
	 * @var bool
	 */
	protected $isActive = false;
	
	/**
	 * Array of all request Arguments
	 * 
	 * @var array
	*/
	protected $requestArguments = array();
     
	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $cacheInstance;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;

	/**
	 * @var Tx_ExtbaseHijax_Service_Serialization_ListenerFactory
	 */
	protected $listenerFactory;
	
	/**
	 * Constructor
	 *
	 * @api
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
		$this->listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax');
	}
	
	/**
	 * Called by ajax.php / eID.php
	 * Builds an extbase context and returns the response.
	 * 
	 * @return void
	 */
	public function dispatch() {
		$this->setIsActive(true);
		
		$this->initializeDatabase();
		$this->initializeTca();
		$this->initializeTsfe();
		
		$callback = t3lib_div::_GP('callback');
		$requests = t3lib_div::_GP('r');
		$eventsToListen = t3lib_div::_GP('e');
		$responses = array(
						'original' => array(),
						'affected' => array(),
						);

		foreach ($requests as $r) {
			$configuration = array();
			$configuration['extensionName'] = $r['extension'];
			$configuration['pluginName']    = $r['plugin'];
			
				// load settings saved under settingsHash
			if ($r['settingsHash'] && $this->cacheInstance->has($r['settingsHash'])) {
				$tryConfiguration = $this->cacheInstance->get($r['settingsHash']);
				if ($configuration['extensionName']==$tryConfiguration['extensionName'] && $configuration['pluginName']==$tryConfiguration['pluginName']) {
					$configuration = $tryConfiguration;
				}
			}
			
			$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
			$bootstrap->initialize($configuration);

			$request  = $this->buildRequest($r);
			
				/* @var $response Tx_Extbase_MVC_Web_Response */
			$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');

			$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
			$dispatcher->dispatch($request, $response);
			
			$responses['original'][] = array( 'id' => $r['id'], 'response' => $response->getContent() );
		}
		
		while ($this->hijaxEventDispatcher->hasPendingNextPhaseEvents()) {
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();
			$this->parseAndRunEventListeners($responses, $eventsToListen);
				
			if (self::$loopCount++>99) {
				// preventing dead loops
				break;
			}
		}
		
		foreach ($responses['original'] as $i=>$response) {
			$this->hijaxEventDispatcher->replaceXMLCommentsWithDivs($responses['original'][$i]['response']);
		}
		foreach ($responses['affected'] as $i=>$response) {
			$this->hijaxEventDispatcher->replaceXMLCommentsWithDivs($responses['affected'][$i]['response']);
		}
		
		//$response->setHeader('Content-Type', 'application/x-json');
		header('Content-type: text/javascript');
		
		echo $callback.'('.json_encode($responses).')';
		
		$this->cleanShutDown();
		
		$this->setIsActive(false);
	}
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	protected function parseAndRunEventListeners(&$responses, $eventsToListen) {
		foreach ($responses['original'] as $response) {
			$this->hijaxEventDispatcher->parseAndRunEventListeners($response['response']);
		}
		foreach ($eventsToListen as $listenerId => $eventNames) {
			$shouldProcess = FALSE;
			foreach ($eventNames as $eventName) {
				if ($this->hijaxEventDispatcher->hasPendingEventWithName($eventName)) {
					$shouldProcess = TRUE;
					break;
				}
			}

			if ($shouldProcess) {
				/* @var $listener Tx_ExtbaseHijax_Event_Listener */
				$listener = $this->listenerFactory->findById($listenerId);
			
				$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
				$bootstrap->initialize($listener->getConfiguration());
				$request = $listener->getRequest();
			
				/* @var $response Tx_Extbase_MVC_Web_Response */
				$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
			
				$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
				$dispatcher->dispatch($request, $response);
			
				$responses['affected'][] = array( 'id' => $listenerId, 'response' => $response->getContent() );
			}			
		}
	}	
	
	/**
	 * Initializes TYPO3 db.
	 * 
	 * @return void
	 */
	protected function initializeDatabase() {
		tslib_eidtools::connectDB();
	}
	
	/**
	 * Initializes the TCA.
	 * 
	 * @return void
	 */
	protected function initializeTca() {
		tslib_eidtools::initTCA();
	}
	
	/**
	 * Initializes TSFE.
	 * 
	 * @return void
	 */
	protected function initializeTsfe() {
		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], t3lib_div::_GP('id'), t3lib_div::_GP('type'), true);
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->initUserGroups();
		$GLOBALS['TSFE']->checkAlternativeIdMethods();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->newCObj();
	}
	
	/**
	 * Shuts down services and persists objects.
	 * 
	 * @return void
	 */
	protected function cleanShutDown() {
		$this->objectManager->get('Tx_Extbase_Persistence_Manager')->persistAll();
		$this->objectManager->get('Tx_Extbase_Reflection_Service')->shutdown();
	}
	
	/**
	 * Build a request object
	 * 
	 * @param $r array
	 * 
	 * @return Tx_Extbase_MVC_Web_Request $request
	 */
	protected function buildRequest($r) {
		
			/* @var $request Tx_Extbase_MVC_Request */
		$request = $this->objectManager->get('Tx_Extbase_MVC_Web_Request');
		 
		$request->setControllerExtensionName($r['extension']);
		$request->setPluginName($r['plugin']);
		$request->setControllerName($r['controller']);
		$request->setControllerActionName($r['action']);
		$request->setArguments(!is_array($r['arguments']) ? array() : $r['arguments']);
		
		return $request;
	}
	
	/**
	 * @return the $isActive
	 */
	public function getIsActive() {
		return $this->isActive;
	}

	/**
	 * @param boolean $isActive
	 */
	protected function setIsActive($isActive) {
		$this->isActive = $isActive;
	}
}

?>