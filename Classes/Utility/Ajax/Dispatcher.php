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
	 * Constructor
	 *
	 * @api
	 */
	public function __construct() {
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
		
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax');
		
		$callback = t3lib_div::_GP('callback');
		$requests = t3lib_div::_GP('r');
		$responses = array();

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
			//$response->setHeader('Content-Type', 'application/x-json');
			
			$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
			$dispatcher->dispatch($request, $response);
			
			//$response->sendHeaders();
			
			$responses[] = array( 'id' => $r['id'], 'response' => $response->getContent() );
		}
		
		header('Content-type: text/javascript');
		
		echo $callback.'('.json_encode($responses).')';
		
		$this->cleanShutDown();
		
		$this->setIsActive(false);
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