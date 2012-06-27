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

class Tx_ExtbaseHijax_ViewHelpers_AjaxLinkViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper {

	/**
	 * @var Tx_ExtbaseHijax_MVC_Dispatcher
	 */
	protected $mvcDispatcher;
	
	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;
	
	/**
	 * Injects the MVC dispatcher
	 *
	 * @param Tx_ExtbaseHijax_MVC_Dispatcher $mvcDispatcher
	 * @return void
	 */
	public function injectMVCDispatcher(Tx_ExtbaseHijax_MVC_Dispatcher $mvcDispatcher) {
		$this->mvcDispatcher = $mvcDispatcher;
	}
	
	/**
	 * @param Tx_Extbase_Service_ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(Tx_Extbase_Service_ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}
		
	/**
	 * @var Tx_ExtbaseHijax_Service_JSBuilder
	 */
	protected $jsBuilder;
	
	/**
	 * injectJSBuilder
	 *
	 * @param Tx_ExtbaseHijax_Service_JSBuilder $jsBuilder
	 * @return void
	 */
	public function injectJSBuilder(Tx_ExtbaseHijax_Service_JSBuilder $jsBuilder) {
		$this->jsBuilder = $jsBuilder;
	}
			
	/**
	 * @param string $action
	 * @param array $arguments
	 * @param string $controller
	 * @param string $extensionName
	 * @param string $pluginName
	 * @param string $format
	 * @param int $pageUid
	 * 
	 * @return string
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $format = '', $pageUid = 0) {
		$request = $this->mvcDispatcher->getCurrentRequest();
		
		if ($request) {
			if ($action === NULL) {
				$action = $request->getControllerActionName();
			}
				
			if ($controller === NULL) {
				$controller = $request->getControllerName();
			}
				
			if ($extensionName === NULL) {
				$extensionName = $request->getControllerExtensionName();
			}
				
			if ($pluginName === NULL && TYPO3_MODE === 'FE') {
				$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
			}
			if ($pluginName === NULL) {
				$pluginName = $request->getPluginName();
			}
		}
		
		$additionalArguments = array();
		$this->hA('r[0][arguments]', $arguments, $additionalArguments);
		
		$language = intval($GLOBALS['TSFE'] ? $GLOBALS['TSFE']->sys_language_content : 0);
		$additionalParams = "&r[0][extension]={$extensionName}&r[0][plugin]={$pluginName}&r[0][controller]={$controller}&r[0][action]={$action}&r[0][format]={$format}&eID=extbase_hijax_dispatcher&L={$language}";

		if ($additionalArguments) {
			$additionalParams .= '&'.implode('&', $additionalArguments);
		}
		
		/* @var $cObj tslib_cObj */
		$cObj = t3lib_div::makeInstance('tslib_cObj'); 
		
		return $cObj->typoLink('', array(
				'returnLast' => 'url',
				'additionalParams' => $additionalParams,
				'parameter' => $pageUid ? $pageUid : ($GLOBALS['TSFE'] ? $GLOBALS['TSFE']->id : 0)
			));
	}
	
	/**
	 * @param string $namespace
	 * @param array $arguments
	 * @param array $additionalArguments
	 */
	protected function hA($namespace, $arguments, &$additionalArguments) {
		if ($arguments) {
			foreach ($arguments as $i => $v) {
				if (is_array($v)) {
					$this->hA($namespace."[$i]", $v, $additionalArguments);
				} else {
					$additionalArguments[] = $namespace."[$i]=".rawurlencode($v);
				}
			}
		}
	}
}

?>