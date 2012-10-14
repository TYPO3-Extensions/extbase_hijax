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
	 * @var Tx_ExtbaseHijax_Service_Content
	 */
	protected $serviceContent;
	
	/**
	 * @var boolean
	 */
	protected $preventMarkupUpdateOnAjaxLoad;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
		$this->serviceContent = $this->objectManager->get('Tx_ExtbaseHijax_Service_Content');
		$this->listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_storage');
		$this->preventMarkupUpdateOnAjaxLoad = false;
	}
	
	/**
	 * Called by ajax.php / eID.php
	 * Builds an extbase context and returns the response.
	 * 
	 * @return void
	 */
	public function dispatch() {
		$this->setIsActive(true);
		$callback = t3lib_div::_GP('callback');
		$requests = t3lib_div::_GP('r');
		$eventsToListen = t3lib_div::_GP('e');
		
		try {
			$this->initializeDatabase();
			$this->initializeTca();
			$this->initializeTsfe();
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();
			
			$responses = array(
					'original' => array(),
					'affected' => array(),
			);
			
			foreach ($requests as $r) {
				$skipProcessing = FALSE;
				$configuration = array();
				
				if ($r['settingsHash']) {
					/* @var $listener Tx_ExtbaseHijax_Event_Listener */
					$listener = $this->listenerFactory->findById($r['settingsHash']);
				}
				
				$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
				
					// load settings saved under settingsHash
				if ($listener) {
					$configuration = $listener->getConfiguration();
					$request = $listener->getRequest();	
					$bootstrap->cObj = $listener->getCObj();
				} elseif (Tx_ExtbaseHijax_Utility_Extension::isAllowedHijaxAction($r['extension'], $r['controller'], $r['action'])) {
					$configuration['extensionName'] = $r['extension'];
					$configuration['pluginName']    = $r['plugin'];
					$configuration['controller']    = $r['controller'];
					$configuration['action']        = $r['action'];
				} else {
					$skipProcessing = TRUE;
				}
				
				if (!$skipProcessing) {
					$bootstrap->initialize($configuration);
					$this->setPreventMarkupUpdateOnAjaxLoad(false);
					
					$request = $this->buildRequest($r, $request);
					$request->setDispatched(false);
										
					/* @var $response Tx_Extbase_MVC_Web_Response */
					$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
				
					/* @var $dispatcher Tx_ExtbaseHijax_MVC_Dispatcher */
					$dispatcher = $this->objectManager->get('Tx_ExtbaseHijax_MVC_Dispatcher');
					$dispatcher->dispatch($request, $response, $listener);
										
					$content = $response->getContent();
					$this->serviceContent->processIntScripts($content);
					$this->serviceContent->processAbsRefPrefix($content, $configuration['settings']['absRefPrefix']);
					$responses['original'][] = array( 'id' => $r['id'], 'format' => $request->getFormat(), 'response' => $content, 'preventMarkupUpdate' => $this->getPreventMarkupUpdateOnAjaxLoad() );
				}
			}
			
				// see if there are affected elements on the page as well
				// and run their code generation again
			$this->parseAndRunEventListeners($responses, $eventsToListen, FALSE);
				
			while ($this->hijaxEventDispatcher->hasPendingNextPhaseEvents()) {
				$this->hijaxEventDispatcher->promoteNextPhaseEvents();
				$this->parseAndRunEventListeners($responses, $eventsToListen);
			
				if (self::$loopCount++>99) {
					// preventing dead loops
					break;
				}
			}
			
			foreach ($responses['original'] as $i=>$response) {
				$this->hijaxEventDispatcher->replaceXMLCommentsWithDivs($responses['original'][$i]['response'], $responses['original'][$i]['format']);
				if ($responses['original'][$i]['format']=='json') {
						// yes, non-optimal, but no time for now to change the extbase core...
					$responses['original'][$i]['response'] = json_decode($responses['original'][$i]['response']);
				}
			}
			foreach ($responses['affected'] as $i=>$response) {
				$this->hijaxEventDispatcher->replaceXMLCommentsWithDivs($responses['affected'][$i]['response'], $responses['affected'][$i]['format']);
				if ($responses['affected'][$i]['format']=='json') {
						// yes, non-optimal, but no time for now to change the extbase core...
					$responses['affected'][$i]['response'] = json_decode($responses['affected'][$i]['response']);
				}
			}
			
			$this->cleanShutDown();
		} catch (Tx_ExtbaseHijax_MVC_Exception_RedirectAction $redirectException) {
			$responses = array(
				'redirect' => array(
					'url' => $redirectException->getUrl(),
					'code' => $redirectException->getHttpStatus()	
				)
			);
		} catch (Exception $e) {
			header('HTTP/1.1 503 Service Unavailable');
			header('Status: 503 Service Unavailable');
			
			error_log($e->getMessage());
			$responses = array('success'=>false, 'code'=>$e->getCode());
		}
		
		if ($callback) {
			header('Content-type: text/javascript');
			echo $callback.'('.json_encode($responses).')';
		} else {
			header('Content-type: application/x-json');
			echo json_encode($responses);
		}
		
		$this->setIsActive(false);
	}
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	protected function parseAndRunEventListeners(&$responses, $eventsToListen, $processOriginal=TRUE) {
		if ($processOriginal) {
			foreach ($responses['original'] as $response) {
				$this->hijaxEventDispatcher->parseAndRunEventListeners($response['response']);
			}
		}
		if ($eventsToListen && is_array($eventsToListen)) {
			foreach ($eventsToListen as $listenerId => $eventNames) {
				$shouldProcess = FALSE;
				foreach ($eventNames as $eventName) {
					if ($this->hijaxEventDispatcher->hasPendingEventWithName($eventName, $listenerId)) {
						$shouldProcess = TRUE;
						break;
					}
				}

				if ($shouldProcess) {
						/* @var $listener Tx_ExtbaseHijax_Event_Listener */
					$listener = $this->listenerFactory->findById($listenerId);

					if ($listener) {
						$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
						$bootstrap->cObj = $listener->getCObj();
						$bootstrap->initialize($listener->getConfiguration());

						/* @var $request Tx_Extbase_MVC_Web_Request */
						$request = $listener->getRequest();
						$request->setDispatched(false);
						$this->setPreventMarkupUpdateOnAjaxLoad(false);

						/* @var $response Tx_Extbase_MVC_Web_Response */
						$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');

						/* @var $dispatcher Tx_ExtbaseHijax_MVC_Dispatcher */
						$dispatcher = $this->objectManager->get('Tx_ExtbaseHijax_MVC_Dispatcher');
						$dispatcher->dispatch($request, $response, $listener);

						$content = $response->getContent();
						$this->serviceContent->processIntScripts($content);
						$this->serviceContent->processAbsRefPrefix($content, $configuration['settings']['absRefPrefix']);
						$responses['affected'][] = array( 'id' => $listenerId, 'format' => $request->getFormat(), 'response' => $content, 'preventMarkupUpdate' => $this->getPreventMarkupUpdateOnAjaxLoad() );
					} else {
						// TODO: log error message
					}
				}
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
		$GLOBALS['TSFE']->sys_language_uid = intval(t3lib_div::_GP('L'));
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
	 * @param array $r
	 * @param Tx_Extbase_MVC_Web_Request $request
	 * @return Tx_Extbase_MVC_Request
	 */
	protected function buildRequest($r, &$request = null) {
		
		if (!$request) {
			$request = $this->objectManager->get('Tx_Extbase_MVC_Web_Request');
		}
		
		$request->setControllerExtensionName($r['extension']);
		$request->setPluginName($r['plugin']);
		$request->setFormat($r['format'] ? $r['format'] : 'html');
		$request->setControllerName($r['controller']);
		$request->setControllerActionName($r['action']);
		if ($r['arguments'] && !is_array($r['arguments'])) {
			$r['arguments'] = unserialize($r['arguments']);
			$this->stringify($r['arguments']);
		}

		$request->setArguments(t3lib_div::array_merge_recursive_overrule($request->getArguments(), !is_array($r['arguments']) ? array() : $r['arguments']));
				
		return $request;
	}

	/**
	 * @param $arr
	 */
	protected function stringify(&$arr) {
		foreach ($arr as $k => $v) {
			if (!is_array($v)) {
				$arr[$k] = (string) $v;
			} else {
				$this->stringify($arr[$k]);
			}
		}
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
	
	/**
	 * @return the $preventMarkupUpdateOnAjaxLoad
	 */
	public function getPreventMarkupUpdateOnAjaxLoad() {
		return $this->preventMarkupUpdateOnAjaxLoad;
	}

	/**
	 * @param boolean $preventMarkupUpdateOnAjaxLoad
	 */
	public function setPreventMarkupUpdateOnAjaxLoad($preventMarkupUpdateOnAjaxLoad) {
		$this->preventMarkupUpdateOnAjaxLoad = $preventMarkupUpdateOnAjaxLoad;
	}
}

?>