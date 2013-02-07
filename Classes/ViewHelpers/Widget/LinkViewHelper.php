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
	 * @var	tslib_cObj
	 */
	protected $contentObject;
	
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->contentObject = $this->configurationManager->getContentObject();
	}
	
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
	 * @param array $contextArguments Context arguments
	 * @param string $section The anchor to be added to the URI
	 * @param string $format The requested format, e.g. ".html"
	 * @param boolean $ajax TRUE if the URI should be to an AJAX widget, FALSE otherwise.
	 * @param boolean $cachedAjaxIfPossible TRUE if the URI should be cached (with respect to non-cacheable actions)
	 * @return string The rendered link
	 * @api
	 */
	public function render($action = NULL, $arguments = array(), $contextArguments = array(), $section = '', $format = '', $ajax = TRUE, $cachedAjaxIfPossible = TRUE) {
		$uri = $this->getWidgetUri($action, $arguments, $contextArguments, $ajax, $cachedAjaxIfPossible);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());

		return $this->tag->render();
	}
	
	/**
	 * Renders hijax-related data attributes
	 *
	 * @return void
	 */
	protected function getWidgetUri($action = NULL, array $arguments = array(), array $contextArguments = array(), $ajax = TRUE, $cachedAjaxIfPossible = TRUE) {
		$this->hijaxEventDispatcher->setIsHijaxElement(true);		
		
		$request = $this->controllerContext->getRequest();
			/* @var $widgetContext Tx_ExtbaseHijax_Core_Widget_WidgetContext */
		$widgetContext = $request->getWidgetContext();
		$tagAttributes = array();

		if ($ajax) {
			$tagAttributes['data-hijax-element-type'] = 'link';
			$this->tag->addAttribute('class', trim($this->arguments['class'].' hijax-element'));
		}
	
		if ($action === NULL) {
			$action = $widgetContext->getParentControllerContext()->getRequest()->getControllerActionName();
		}
		if ($ajax) {
			$tagAttributes['data-hijax-action'] = $action;
		}
	
		$controller = $widgetContext->getParentControllerContext()->getRequest()->getControllerName();
		if ($ajax) {
			$tagAttributes['data-hijax-controller'] = $controller;
		}
	
		$extensionName = $widgetContext->getParentControllerContext()->getRequest()->getControllerExtensionName();
		if ($ajax) {
			$tagAttributes['data-hijax-extension'] = $extensionName;
		}
		
		if (TYPO3_MODE === 'FE') {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
		} 
		if (!$pluginName) {
			$pluginName = $request->getPluginName();
		}
		if (!$pluginName) {
			$pluginName = $widgetContext->getParentPluginName();
		}
		if ($ajax) {
			$tagAttributes['data-hijax-plugin'] = $pluginName;
		}

		$requestArguments = $widgetContext->getParentControllerContext()->getRequest()->getArguments();
		$requestArguments = array_merge($requestArguments, $this->hijaxEventDispatcher->getContextArguments());
		$requestArguments = array_merge($requestArguments, $contextArguments);
		$requestArguments[$widgetContext->getWidgetIdentifier()] = ($arguments && is_array($arguments)) ? $arguments : array();
		if ($ajax) {
			$tagAttributes['data-hijax-arguments'] = serialize($requestArguments);
		}
			
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
		$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
		if ($ajax) {
			$tagAttributes['data-hijax-settings'] = $listener->getId();
		}
		
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		if ($ajax) {
			$tagAttributes['data-hijax-namespace'] = $pluginNamespace;
		}

		if ($cachedAjaxIfPossible) {
			/* @var $cacheHash t3lib_cacheHash */
			$cacheHash = t3lib_div::makeInstance('t3lib_cacheHash');
			$tagAttributes['data-hijax-chash'] = $cacheHash->calculateCacheHash(array(
				'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
				'action' => $tagAttributes['data-hijax-action'],
				'controller' => $tagAttributes['data-hijax-controller'],
				'extension' => $tagAttributes['data-hijax-extension'],
				'plugin' => $tagAttributes['data-hijax-plugin'],
				'arguments' => $tagAttributes['data-hijax-arguments'],
				'settingsHash' => $tagAttributes['data-hijax-settings']
			));
		}

		foreach ($tagAttributes as $tagAttribute => $attributeValue) {
			$this->tag->addAttribute($tagAttribute, $attributeValue);
		}

		$uriBuilder = $this->controllerContext->getUriBuilder();

		$argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();
		
		if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
			$requestArguments['format'] = $this->arguments['format'];
		}
			
		return $uriBuilder
			->reset()
			//->setUseCacheHash($this->contentObject->getUserObjectType() === tslib_cObj::OBJECTTYPE_USER)
			->setArguments(array($pluginNamespace => $requestArguments))
			->setSection($this->arguments['section'])
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array($argumentPrefix, 'cHash'))
			->setFormat($this->arguments['format'])
			->build();
	}	
}

?>