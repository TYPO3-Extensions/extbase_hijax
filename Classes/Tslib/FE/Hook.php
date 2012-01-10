<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class Tx_ExtbaseHijax_Tslib_FE_Hook {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;
		
	/**
	 * Extension Configuration
	 *
	 * @var Tx_ExtbaseHijax_Configuration_ExtensionInterface
	 */
	protected $extensionConfiguration;
	
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
		$this->injectExtensionConfiguration($this->objectManager->get('Tx_ExtbaseHijax_Configuration_ExtensionInterface'));
	}
	
	/**
	 * injectExtensionConfiguration
	 *
	 * @param Tx_ExtbaseHijax_Configuration_ExtensionInterface $extensionConfiguration
	 * @return void
	 */
	public function injectExtensionConfiguration(Tx_ExtbaseHijax_Configuration_ExtensionInterface $extensionConfiguration) {
		$this->extensionConfiguration = $extensionConfiguration;
	}
	
	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */
	public function contentPostProc($params, $pObj) {		
		if ($this->extensionConfiguration->shouldIncludeEofe() && !$this->extensionConfiguration->hasIncludedEofe()) {
			$this->extensionConfiguration->setIncludedEofe(true);
			
			$eofe = $pObj->cObj->cObjGetSingle(
				$pObj->tmpl->setup['config.']['extbase_hijax.']['eofe'], 
				$pObj->tmpl->setup['config.']['extbase_hijax.']['eofe.']
			);
			
			$pObj->content = str_ireplace('</body>',  $eofe . '</body>', $pObj->content);
		}
		
		if ($this->extensionConfiguration->shouldIncludeSofe() && !$this->extensionConfiguration->hasIncludedSofe()) {
			$this->extensionConfiguration->setIncludedSofe(true);

			$sofe = $pObj->cObj->cObjGetSingle(
				$pObj->tmpl->setup['config.']['extbase_hijax.']['sofe'], 
				$pObj->tmpl->setup['config.']['extbase_hijax.']['sofe.']
			);
			
			$pObj->content = preg_replace('/<body([^>]*)>/msU',  '<body$1>'.$sofe, $pObj->content);
		}
		
		$bodyClass = $pObj->tmpl->setup['config.']['extbase_hijax.']['bodyClass'];
		if ($bodyClass && !$this->extensionConfiguration->hasAddedBodyClass()) {
			
			$matches = array();
			preg_match('/<body([^>]*)class="([^>]*)">/msU', $pObj->content, $matches);
			$count = 0;
			if (count($matches)) {
				$classes = t3lib_div::trimExplode(" ", $matches[2], true);
				if (!in_array($bodyClass, $classes)) {
					$pObj->content = preg_replace('/<body([^>]*)class="([^>]*)">/msU',  '<body$1class="$2 '.$bodyClass.'">', $pObj->content, -1, $count);
				}
			} else {
				$pObj->content = preg_replace('/<body([^>]*)>/msU',  '<body$1 class="'.$bodyClass.'">', $pObj->content, -1, $count);
			}
			if ($count) {
				$this->extensionConfiguration->setAddedBodyClass(true);
			}
		}
	}
}

?>