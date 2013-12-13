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
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;
	
	/**
	 * @var boolean
	 */
	protected $preventMarkupUpdateOnAjaxLoad;

	/**
	 * @var Tx_EdCache_Domain_Repository_CacheRepository
	 */
	protected $cacheRepository;

	/**
	 * @var boolean
	 */
	protected $initializedTSFE = FALSE;

	/**
	 * @var boolean
	 */
	protected $errorWhileConverting = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
		$this->serviceContent = $this->objectManager->get('Tx_ExtbaseHijax_Service_Content');
		$this->listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
		$this->extensionService = $this->objectManager->get('Tx_Extbase_Service_ExtensionService');
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_storage');
		$this->preventMarkupUpdateOnAjaxLoad = false;
		if (t3lib_extMgm::isLoaded('ed_cache')) {
			$this->cacheRepository = t3lib_div::makeInstance('Tx_EdCache_Domain_Repository_CacheRepository');
		}
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
		$eventsToListen = t3lib_div::_GP('evts') ? t3lib_div::_GP('evts') : t3lib_div::_GP('e');
		$preventDirectOutput = false;
		try {
			$this->initializeDatabase();
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();
			
			$responses = array(
					'original' => array(),
					'affected' => array(),
			);
			
			foreach ($requests as $r) {

				if ($r['secureLocalStorage']) {
					echo file_get_contents(t3lib_extMgm::extPath('extbase_hijax', 'Resources/Private/Templates/SecureLocalStorage/IFrame.html'));
					exit;
				}

				$skipProcessing = FALSE;
				$configuration = array();

				$allowCaching = FALSE;
				if ($r['chash']) {
					/* @var $cacheHash t3lib_cacheHash */
					$cacheHash = t3lib_div::makeInstance('t3lib_cacheHash');
					$allowCaching = $r['chash'] == $cacheHash->calculateCacheHash(array(
						'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
						'action' => $r['action'],
						'controller' => $r['controller'],
						'extension' => $r['extension'],
						'plugin' => $r['plugin'],
						'arguments' => $r['arguments'],
						'settingsHash' => $r['settingsHash']
					));
				}

				if ($r['tsSource']) {
					$this->initialize();
					if ($this->serviceContent->isAllowedTypoScriptPath($r['tsSource'])) {
						/* @var $listener Tx_ExtbaseHijax_Event_Listener */
						$encodedSettings = str_replace('.', '---', $r['tsSource']);
						$settingsHash = t3lib_div::hmac($encodedSettings);
						$listener = $this->listenerFactory->findById('h-'.$settingsHash.'-'.$encodedSettings);
						$configuration = $listener->getConfiguration();
						$r['extension'] = $configuration['extensionName'];
						$r['plugin'] = $configuration['pluginName'];
						$r['controller'] = $configuration['controller'];
						$r['action'] = $configuration['action'];
					} else {
						throw new Exception('Path not allowed.', 503);
					}
				} elseif ($r['settingsHash']) {
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
					$allowCaching = FALSE; // we do not want to cache this request
					$configuration['extensionName'] = $r['extension'];
					$configuration['pluginName']    = $r['plugin'];
					$configuration['controller']    = $r['controller'];
					$configuration['action']        = $r['action'];
				} else {
					$skipProcessing = TRUE;
				}
				
				if (!$skipProcessing) {
					if ($allowCaching && $this->cacheRepository) {
						$cacheConf = array(
							'contentFunc' => array($this, 'handleFrontendRequest'),
							'contentFuncParams' => array(
								$bootstrap,
								$configuration,
								$r,
								$request,
								$listener,
								TRUE
							)
						);
						if ($configuration['settings']['extbaseHijaxDefaultCacheExpiryPeriod']) {
							$cacheConf['expire_on_datetime'] = $GLOBALS['EXEC_TIME'] + $configuration['settings']['extbaseHijaxDefaultCacheExpiryPeriod'];
						}
						$responses['original'][] = $this->cacheRepository->getByKey('hijax_'.$r['chash'], $cacheConf, $bootstrap->cObj);
					} else {
						$responses['original'][] = $this->handleFrontendRequest($bootstrap, $configuration, $r, $request, $listener, FALSE);
					}
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
			$preventDirectOutput = true;
		} catch (Exception $e) {
			header('HTTP/1.1 503 Service Unavailable');
			header('Status: 503 Service Unavailable');
			
			error_log($e->getMessage());
			$responses = array('success'=>false, 'code'=>$e->getCode());
		}

		if (!$preventDirectOutput && $responses['original'][0]['format']!='html' && is_string($responses['original'][0]['response'])) {
			foreach ($responses['original'][0]['headers'] as $header) {
				header(trim($header));
			}
			header ( 'Cache-control: private' );
			header ( 'Connection: Keep-Alive' );
			header ( 'Content-Length: ' . strlen ( $responses['original'][0]['response'] ) );
			echo $responses['original'][0]['response'];
		} elseif ($callback) {
			header('Content-type: text/javascript');
			echo $callback.'('.json_encode($responses).')';
		} else {
			header('Content-type: application/x-json');
			echo json_encode($responses);
		}

		$this->setIsActive(false);
	}

	/**
	 * @param $bootstrap
	 * @param $configuration
	 * @param $r
	 * @param $request
	 * @param $listener
	 * @return array
	 */
	public function handleFrontendRequest($bootstrap, $configuration, $r, $request, $listener, $isCacheCallback = FALSE) {
		$this->initialize();

		$bootstrap->initialize($configuration);
		$this->setPreventMarkupUpdateOnAjaxLoad(false);
		/* @var $request Tx_Extbase_MVC_Web_Request */
		$request = $this->buildRequest($r, $request);
		$request->setDispatched(false);

		$namespace = $this->extensionService->getPluginNamespace($request->getControllerExtensionName(), $request->getPluginName());
		$_POST[$namespace] = $request->getArguments();

		/* @var $response Tx_Extbase_MVC_Web_Response */
		$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');

		/* @var $dispatcher Tx_ExtbaseHijax_MVC_Dispatcher */
		$dispatcher = $this->objectManager->get('Tx_ExtbaseHijax_MVC_Dispatcher');
		$dispatcher->dispatch($request, $response, $listener);

		$_POST[$namespace] = array();

		$content = $response->getContent();
		$this->serviceContent->processIntScripts($content);
		$this->serviceContent->processAbsRefPrefix($content, $configuration['settings']['absRefPrefix']);
		$response->setContent($content);

		// convert HTML to specified format
		$htmlConverter = $this->objectManager->get('Tx_ExtbaseHijax_HTMLConverter_ConverterFactory'); /* @var $htmlConverter Tx_ExtbaseHijax_HTMLConverter_ConverterFactory */
		$converter = $htmlConverter->getConverter($request->getFormat());

		try {
			$response = $converter->convert($response);
		} catch (Tx_ExtbaseHijax_HTMLConverter_FailedConversionException $e) {
			$this->errorWhileConverting = TRUE;
		}

		$result = array( 'id' => $r['id'], 'format' => $request->getFormat(), 'response' => $response->getContent(), 'preventMarkupUpdate' => $this->getPreventMarkupUpdateOnAjaxLoad(), 'headers' => $response->getHeaders());

		if (!$this->errorWhileConverting && $isCacheCallback && !$request->isCached() && $this->cacheRepository) {
			error_log('Throwing Tx_EdCache_Exception_PreventActionCaching, did you missconfigure cacheable actions in Extbase?');
			/* @var $preventActionCaching Tx_EdCache_Exception_PreventActionCaching */
			$preventActionCaching = t3lib_div::makeInstance('Tx_EdCache_Exception_PreventActionCaching');
			$preventActionCaching->setResult($result);
			throw $preventActionCaching;
		}

		return $result;
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
						$configuration = $listener->getConfiguration();
						$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
						$bootstrap->cObj = $listener->getCObj();
						$bootstrap->initialize($configuration);

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
	 * initialize TSFE and TCA
	 */
	protected function initialize() {
		if (!$this->initializedTSFE) {
			$this->initializedTSFE = TRUE;
			$this->initializeTca();
			$this->initializeTsfe();
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
		/* @var $tsfe tslib_fe */
		$tsfe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], t3lib_div::_GP('id'), t3lib_div::_GP('type'), true);
		$GLOBALS['TSFE'] = &$tsfe;

		$tsfe->initFEuser();
		$tsfe->initUserGroups();
		$tsfe->checkAlternativeIdMethods();
		$tsfe->determineId();
		$tsfe->getCompressedTCarray();
		$tsfe->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$tsfe->initTemplate();
		$tsfe->getConfigArray();
		$tsfe->settingLanguage();
		$tsfe->settingLocale();
		$tsfe->calculateLinkVars();
		$tsfe->newCObj();
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
		if (is_array($arr)) {
			foreach ($arr as $k => $v) {
                if (!is_array($v) && !is_object($v) && !is_null($v)) {
	                $arr[$k] = (string) $v;
                } else {
	                $this->stringify($arr[$k]);
                }
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