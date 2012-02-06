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

class Tx_ExtbaseHijax_Utility_HTTP implements t3lib_Singleton {
	/**
	 * @var int
	 */
	protected static $loopCount = 0;
	
	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;
		
	/**
	 * ajaxDispatcher
	 *
	 * @var Tx_ExtbaseHijax_Utility_Ajax_Dispatcher
	 */
	protected $ajaxDispatcher;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initializeObjectManager();
	}
	
	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 */
	protected function initializeObjectManager() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->ajaxDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Utility_Ajax_Dispatcher');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
	}

	/**
	 * Sends a redirect header response and exits. Additionaly the URL is
	 * checked and if needed corrected to match the format required for a
	 * Location redirect header. By default the HTTP status code sent is
	 * a 'HTTP/1.1 303 See Other'.
	 *
	 * @param	string	The target URL to redirect to
	 * @param	string	An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
	 */
	public static function redirect($url, $httpStatus = t3lib_utility_Http::HTTP_STATUS_303) {
			/* @var $httpServiceInstance Tx_ExtbaseHijax_Utility_HTTP */
		$httpServiceInstance = t3lib_div::makeInstance('Tx_ExtbaseHijax_Utility_HTTP');
		$httpServiceInstance->redirectInstance($url, $httpStatus);
	}

	/**
	 * Sends a redirect header response and exits. Additionaly the URL is
	 * checked and if needed corrected to match the format required for a
	 * Location redirect header. By default the HTTP status code sent is
	 * a 'HTTP/1.1 303 See Other'.
	 *
	 * @param	string	The target URL to redirect to
	 * @param	string	An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
	 */	
	protected function redirectInstance($url, $httpStatus = t3lib_utility_Http::HTTP_STATUS_303) {
		if ($this->ajaxDispatcher->getIsActive()) {
				/* @var $redirectException Tx_ExtbaseHijax_MVC_Exception_RedirectAction */
			$redirectException = t3lib_div::makeInstance('Tx_ExtbaseHijax_MVC_Exception_RedirectAction');
			$redirectException->setUrl(t3lib_div::locationHeaderUrl($url));
			$redirectException->setHttpStatus($httpStatus);
			throw $redirectException;
		} else {
			header($httpStatus);
			header('Location: ' . t3lib_div::locationHeaderUrl($url));
			
			exit;
		}
	}
}

?>