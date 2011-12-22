<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2011 AOE media GmbH <dev@aoemedia.de>, AOE media GmbH
*
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
 * Based on a script by Daniel Lienert (http://daniel.lienert.cc/blog/blog-post/2011/04/23/extbase-und-ajax/).
 * 
 * @access     public
 * @package    Mcep
 * @subpackage Utility\Ajax
 * @author     Timo Fuchs <timo.fuchs@aoemedia.de>
 */
class Tx_ExtbaseHijax_Utility_Ajax_Dispatcher {
	
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
	 * @var string
	 */
	//protected $extensionName;
     
	/**
	 * @var string
	 */
	//protected $pluginName;
     
	/**
	 * @var string
	 */
	//protected $controllerName; 
     
	/**
	 * @var string
	 */
	//protected $actionName; 
     
	/**
	 * @var array
	 */
	//protected $arguments;
	
	/**
	 * Called by ajax.php / eID.php
	 * Builds an extbase context and returns the response.
	 * 
	 * @return void
	 */
	public function dispatch() {
		
		$this->initializeDatabase();
		$this->initializeTca();
		$this->initializeTsfe();

		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		
		$callback = t3lib_div::_GP('callback');
		$requests = t3lib_div::_GP('r');
		$responses = array();

		foreach ($requests as $r) {
			$configuration = array();
			$configuration['extensionName'] = $r['extension'];
			$configuration['pluginName']    = $r['plugin'];
						
			// TODO: load settings saved under settingsHash
			
			$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
			$bootstrap->initialize($configuration);
			
			$request  = $this->buildRequest($r);
			
				/* @var $response Tx_Extbase_MVC_Web_Response */
			$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
			//$response->setHeader('Content-Type', 'application/x-json');
			
			$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
			$dispatcher->dispatch($request, $response);
			
			//$response->sendHeaders();
			
			//$responses[] = array( 'id' => $r['id'], 'response' => ( $r['extension'] == 'Disqus' ? '<script type="text/javascript">alert("teest");</script>' : $response->getContent() ) );
			$responses[] = array( 'id' => $r['id'], 'response' => $response->getContent() );
		}
		
		header('Content-type: text/javascript');
		
		echo $callback.'('.json_encode($responses).')';
		
		$this->cleanShutDown();
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
	 * 
	 * 
	 * @return Tx_LogMe_Logger
	 */
	protected function getLogger() {
		return $this->objectManager->get('Tx_LogMe_Logger');
	}
}

?>