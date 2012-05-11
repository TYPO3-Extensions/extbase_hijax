<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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
 * @author Nikola Stojiljkovic <nikola.stojiljkovic@essentialdots.com>
 */
class Tx_ExtbaseHijax_Controller_ContentElementController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;
	
	/**
	 * @var Tx_Extbase_Service_TypoScriptService
	 */
	protected $typoScriptService;
	
	/**
	 * Injects the event dispatcher
	 *
	 * @param Tx_ExtbaseHijax_Event_Dispatcher $eventDispatcher
	 * @return void
	 */
	public function injectEventDispatcher(Tx_ExtbaseHijax_Event_Dispatcher $eventDispatcher) {
		$this->hijaxEventDispatcher = $eventDispatcher;
	}	
	
	/**
	 * Injects the TS service
	 *
	 * @param Tx_Extbase_Service_TypoScriptService $typoScriptService
	 * @return void
	 */
	public function injectTypoScriptService(Tx_Extbase_Service_TypoScriptService $typoScriptService) {
		$this->typoScriptService = $typoScriptService;
	}	
	
	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @api
	 */
	protected function initializeAction() {
		if ($this->settings['listenOnEvents']) {
			$eventNames = t3lib_div::trimExplode(',', $this->settings['listenOnEvents']);
			foreach ($eventNames as $eventName) {
				$this->hijaxEventDispatcher->connect($eventName);
			}
		}
	}	
	
	/**
	 * Renders content element (cacheable)
	 */
	public function userAction() {

	}

	/**
	 * Renders content element (non-cacheable)
	 */
	public function userIntAction() {

	}
}
