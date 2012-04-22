<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_ExtbaseHijax_ViewHelpers_Widget_LinkViewHelper extends Tx_Fluid_ViewHelpers_Widget_LinkViewHelper {
	
	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;
	
	/**
	 * @param Tx_Extbase_Service_ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(Tx_Extbase_Service_ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
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
	 * Render the link.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param string $section The anchor to be added to the URI
	 * @param string $format The requested format, e.g. ".html"
	 * @param boolean $ajax TRUE if the URI should be to an AJAX widget, FALSE otherwise.
	 * @return string The rendered link
	 * @api
	 */
	public function render($action = NULL, $arguments = array(), $section = '', $format = '', $ajax = TRUE) {
		if ($ajax === TRUE) {
			$this->renderHijaxDataAttributes($action, $arguments, $controller = null, $extensionName = null, $pluginName = null);
		}
		$uri = $this->getWidgetUri();
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());

		return $this->tag->render();
	}
	
	/**
	 * Renders hijax-related data attributes
	 *
	 * @return void
	 */
	protected function renderHijaxDataAttributes($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL) {
		$this->hijaxEventDispatcher->setIsHijaxElement(true);		
		
		$request = $this->controllerContext->getRequest();
			/* @var $widgetContext Tx_ExtbaseHijax_Core_Widget_WidgetContext */
		$widgetContext = $request->getWidgetContext();
	
		$this->tag->addAttribute('data-hijax-element-type', 'link');
		$this->tag->addAttribute('class', trim($this->arguments['class'].' hijax-element'));
	
	
		if ($action === NULL) {
			$action = $widgetContext->getParentControllerContext()->getRequest()->getControllerActionName();
		}
		$this->tag->addAttribute('data-hijax-action', $action);
	
	
		if ($controller === NULL) {
			$controller = $widgetContext->getParentControllerContext()->getRequest()->getControllerName();
		}
		$this->tag->addAttribute('data-hijax-controller', $controller);
	
	
		if ($extensionName === NULL) {
			$extensionName = $widgetContext->getParentControllerContext()->getRequest()->getControllerExtensionName();
		}
		$this->tag->addAttribute('data-hijax-extension', $extensionName);
	
		if ($pluginName === NULL && TYPO3_MODE === 'FE') {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
		}
		if ($pluginName === NULL) {
			$pluginName = $request->getPluginName();
		}
		$this->tag->addAttribute('data-hijax-plugin', $pluginName);
	
		$requestArguments = $widgetContext->getParentControllerContext()->getRequest()->getArguments();
		$requestArguments[$widgetContext->getWidgetIdentifier()] = ($arguments && is_array($arguments)) ? $arguments : array();				
		$this->tag->addAttribute('data-hijax-arguments', serialize($requestArguments));
		
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
		$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
		$this->tag->addAttribute('data-hijax-settings', $listener->getId());
	
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		$this->tag->addAttribute('data-hijax-namespace', $pluginNamespace);
	}
}

?>