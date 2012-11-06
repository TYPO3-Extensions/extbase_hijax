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

class Tx_ExtbaseHijax_ViewHelpers_Animation_SceneViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'div';

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param Tx_Extbase_Service_ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(Tx_Extbase_Service_ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * Render the animation element.
	 *
	 * @param string $id
	 * @param string $duration
	 * @param string $nextSceneId
	 * @param string $resultTarget target where the results will be loaded
	 * @param string $loaders target where the loader will be shown
	 * @return string rendered element
	 */
	public function render($id = '', $duration = '', $nextSceneId = '', $resultTarget = NULL, $loaders = NULL) {

		$this->tag->addAttribute('data-hijax-scene-id', $id);
		$this->tag->addAttribute('data-hijax-scene-duration', $duration);
		$this->tag->addAttribute('data-hijax-scene-next-scene-id', $nextSceneId);
		$this->tag->addAttribute('data-hijax-element-type', 'scene');
		$this->tag->addAttribute('class', trim($this->arguments['class'].' hijax-element'));

		if ($resultTarget) {
			$this->tag->addAttribute('data-hijax-result-target', $resultTarget);
		} else {
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
			$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();				
			$this->tag->addAttribute('data-hijax-result-target', "jQuery(this).parents('.hijax-element[data-hijax-listener-id=".$listener->getId()."]')");
			$this->tag->addAttribute('data-hijax-result-wrap', 'false');
		}
		if ($loaders) {
			$this->tag->addAttribute('data-hijax-loaders', $loaders);
		}

		$this->tag->setContent($this->renderChildren());

		$this->tag->setContent('<div class="hijax-content">'.$this->tag->getContent().'</div><div class="hijax-loading"></div>');
		
		return $this->tag->render();
	}
}

?>