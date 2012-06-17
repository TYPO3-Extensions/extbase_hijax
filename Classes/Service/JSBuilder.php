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

class Tx_ExtbaseHijax_Service_JSBuilder implements t3lib_Singleton {
	/**
	 * @var Tx_ExtbaseHijax_MVC_Dispatcher
	 */
	protected $mvcDispatcher;
	
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;
	
	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;
	
	/**
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;
	
	/**
	 * @param t3lib_PageRenderer $pageRenderer
	 */
	public function injectPageRenderer(t3lib_PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}
	
	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}
	
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
	 * @param Tx_Extbase_Service_ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(Tx_Extbase_Service_ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}	
	
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
	 * Returns TRUE if what we are outputting may be cached
	 *
	 * @return boolean
	 */
	protected function isCached() {
		$userObjType = $this->configurationManager->getContentObject()->getUserObjectType();
		return ($userObjType !== tslib_cObj::OBJECTTYPE_USER_INT);
	}	
	
	/**
	 * Returns JS callback for the given action
	 * 
	 * @param string $action
	 * @param array $arguments
	 * @param string $controller
	 * @param string $extensionName
	 * @param string $pluginName
	 * @param string $format
	 * @return string
	 */
	public function getAjaxFunction($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $format = '', $section='footer') {
			// current element needs to have additional logic...
		$this->hijaxEventDispatcher->setIsHijaxElement(true);
		
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
		
		$settings = array(
				'extension' => $extensionName,
				'plugin' => $pluginName,
				'controller' => $controller,
				'format' => $format ? $format : 'html',
				'action' => $action,
				'arguments' => $arguments,
				'settingsHash' => $this->mvcDispatcher->getCurrentListener() ? $this->mvcDispatcher->getCurrentListener()->getId() : '',
				'namespace' => ($extensionName && $pluginName) ? $this->extensionService->getPluginNamespace($extensionName, $pluginName) : '',
			);
		
		$functionName = 'extbaseHijax_'.md5(serialize($settings));
		
		$content = "; $functionName=function(settings, pendingElement, loaders) {";
		foreach ($settings as $k=>$v) {
			$content .= "if (typeof settings.$k == 'undefined') settings.$k=".json_encode($v).";";
		}
		$content .= "return jQuery.hijax(settings, pendingElement, loaders);};";
		
		if ($this->isCached()) {
			if ($section=='footer') {
				$this->pageRenderer->addJsFooterInlineCode(md5($content), $content, FALSE, TRUE);
			} else {
				$this->pageRenderer->addJsInlineCode(md5($content), $content, FALSE, TRUE);
			}
		} else {
			// additionalFooterData not possible in USER_INT
			$GLOBALS['TSFE']->additionalHeaderData[md5($content)] = t3lib_div::wrapJS($content);
		}
		
		return $functionName;
	}
}