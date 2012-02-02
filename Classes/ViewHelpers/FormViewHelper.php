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

class Tx_ExtbaseHijax_ViewHelpers_FormViewHelper extends Tx_Fluid_ViewHelpers_FormViewHelper {

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $cacheInstance;
		
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax');
	}
	
	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}	
	
	/**
	 * Render the form.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param string $controller Target controller
	 * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
	 * @param string $pluginName Target plugin. If empty, the current plugin name is used
	 * @param integer $pageUid Target page uid
	 * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @param integer $pageType Target page type
	 * @param boolean $noCache set this to disable caching for the target page. You should not need this.
	 * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
	 * @param string $section The anchor to be added to the action URI (only active if $actionUri is not set)
	 * @param string $format The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
	 * @param array $additionalParams additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
	 * @param boolean $absolute If set, an absolute action URI is rendered (only active if $actionUri is not set)
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
	 * @param string $fieldNamePrefix Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
	 * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
	 * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
	 * @param string $resultTarget target where the results will be loaded
	 * @return string rendered form
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, $object = NULL, $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $format = '', array $additionalParams = array(), $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $fieldNamePrefix = NULL, $actionUri = NULL, $objectName = NULL, $resultTarget = NULL) {
		$this->renderHijaxDataAttributes($action, $arguments, $controller, $extensionName, $pluginName);
		
		if ($resultTarget) {
			$this->tag->addAttribute('data-hijax-result-target', $resultTarget);
		}	
			
		parent::render($action, $arguments, $controller, $extensionName, $pluginName, $pageUid, $object, $pageType, $noCache, $noCacheHash, $section, $format, $additionalParams, $absolute, $addQueryString, $argumentsToBeExcludedFromQueryString, $fieldNamePrefix, $actionUri, $objectName);
		
		$this->tag->setContent('<div class="hijax-content">'.$this->tag->getContent().'</div><div class="hijax-loading"></div>');
		
		return $this->tag->render();
	}

	/**
	 * Renders hijax-related data attributes
	 *
	 * @return string Hidden fields with referrer information
	 */
	protected function renderHijaxDataAttributes($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL) {
		$request = $this->controllerContext->getRequest();

		
		$this->tag->addAttribute('data-hijax-element-type', 'form');
		$this->tag->addAttribute('class', trim($this->arguments['class'].' hijax-element'));
		
		
		if ($action === NULL) {
			$action = $request->getControllerActionName();
		}
		$this->tag->addAttribute('data-hijax-action', $action);
		
		
		if ($controller === NULL) {
			$controller = $request->getControllerName();
		}
		$this->tag->addAttribute('data-hijax-controller', $controller);
		
		
		if ($extensionName === NULL) {
			$extensionName = $request->getControllerExtensionName();
		}
		$this->tag->addAttribute('data-hijax-extension', $extensionName);
		
		if ($pluginName === NULL && TYPO3_MODE === 'FE') {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $controllerArguments['action']);
		}
		if ($pluginName === NULL) {
			$pluginName = $request->getPluginName();
		}
		$this->tag->addAttribute('data-hijax-plugin', $pluginName);
		
		$this->tag->addAttribute('data-hijax-arguments', htmlspecialchars(serialize($request->getArguments())));
		
		$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$settingsHashKey = 's-'.md5(serialize($frameworkConfiguration));
		if (!$this->cacheInstance->has($settingsHashKey)) {
			$this->cacheInstance->set($settingsHashKey, $frameworkConfiguration);
		}
		$this->tag->addAttribute('data-hijax-settings', $settingsHashKey);
		
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		$this->tag->addAttribute('data-hijax-namespace', $pluginNamespace);
		
		return $result;
	}
}

?>