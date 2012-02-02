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

class Tx_ExtbaseHijax_MVC_Dispatcher extends Tx_Extbase_MVC_Dispatcher {
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;

	/**
	 * Extension Configuration
	 *
	 * @var Tx_ExtbaseHijax_Configuration_ExtensionInterface
	 */
	protected $extensionConfiguration;	
	
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $cacheInstance;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Listener
	 */
	protected $currentListener;

	/**
	 * @var int
	 */
	protected static $id = 0;
	
	/**
	 * @var array
	 */
	protected $listenersStack;
	
	/**
	 * Constructs the global dispatcher
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager A reference to the object manager
	 */
	public function __construct(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		parent::__construct($objectManager);
		$this->configurationManager = $this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax');
		$this->extensionConfiguration = $this->objectManager->get('Tx_ExtbaseHijax_Configuration_ExtensionInterface');
		self::$id = $this->extensionConfiguration->getNextElementId();
		$this->listenersStack = array();
	}
		
	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request to dispatch
	 * @param Tx_Extbase_MVC_ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 */
	public function dispatch(Tx_Extbase_MVC_RequestInterface $request, Tx_Extbase_MVC_ResponseInterface $response) {

		if (defined('TYPO3_cliMode') && TYPO3_cliMode === TRUE) {
			parent::dispatch($request, $response);
		} else {
			$this->currentListener = t3lib_div::makeInstance('Tx_ExtbaseHijax_Event_Listener', $request);
			array_push($this->listenersStack, $this->currentListener);
			$this->hijaxEventDispatcher->startContentElement();
			
			parent::dispatch($request, $response);
			
			if ($this->hijaxEventDispatcher->hasListeners('', TRUE)) {
				$currentListeners = $this->hijaxEventDispatcher->getListeners('', TRUE);
					
				$signature = $this->getCurrentListener()->getId().'('.$this->convertArrayToCSV(array_keys($currentListeners)).'); ';
					
				$content = $response->getContent();
			
				$response->setContent('<!-- ###EVENT_LISTENER_'.self::$id.'### START '.$signature.' -->'.$content.'<!-- ###EVENT_LISTENER_'.self::$id.'### END -->');
				$this->extensionConfiguration->setNextElementId(++self::$id);
			}
			
			$this->hijaxEventDispatcher->endContentElement();
			$this->currentListener = array_pop($this->listenersStack);
		}
	}	
	
	/**
	 * @return Tx_ExtbaseHijax_Event_Listener
	 */
	public function getCurrentListener() {
		return $this->currentListener;
	}
	
	/**
	 * @param array $data
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return string
	 */
	protected function convertArrayToCSV($data, $delimiter = ',', $enclosure = '"') {
		$outstream = fopen("php://temp", 'r+');
		fputcsv($outstream, $data, $delimiter, $enclosure);
		rewind($outstream);
		$csv = fgets($outstream);
		fclose($outstream);
		return trim($csv);
	}
	
}