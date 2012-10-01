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

class Tx_ExtbaseHijax_Tslib_FE_Hook implements t3lib_Singleton {
	/**
	 * @var int
	 */
	protected static $loopCount = 0;
	
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
		$this->extensionConfiguration = $this->objectManager->get('Tx_ExtbaseHijax_Configuration_ExtensionInterface');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
	}

	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */
	public function contentPostProcAll($params, $pObj) {
		$this->contentPostProc($params, $pObj, 'all');
	}

	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */
	public function contentPostProcOutput($params, $pObj) {
		$this->contentPostProc($params, $pObj, 'output');
	}
	
	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 * @param string $hookType
	 */
	protected function contentPostProc($params, $pObj, $hookType) {		
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
		
		while ($this->hijaxEventDispatcher->hasPendingNextPhaseEvents()) {
				// trick to force double rendering of some content elements
			$GLOBALS['TSFE']->recordRegister = array();
				// trick to force loading of full TS template
			if (!$pObj->tmpl->loaded) {
				$pObj->forceTemplateParsing = TRUE;
				$pObj->getConfigArray();
			}
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();
			$this->hijaxEventDispatcher->parseAndRunEventListeners($pObj->content);
			if (!$pObj->config['INTincScript']) {
				$pObj->config['INTincScript'] = array();
			}
			$pObj->INTincScript();
			
			if (self::$loopCount++>99) {
					// preventing dead loops
				break;
			}
		}
		
		if ($hookType=='output' || $pObj->isStaticCacheble()) {
			$this->hijaxEventDispatcher->replaceXMLCommentsWithDivs($pObj->content);
		}
	}

	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */	
	public function initFEuser($params, $pObj) {
			/* @var $fe_user tslib_feUserAuth */ 
		$fe_user = $pObj->fe_user;
		
		if ($fe_user->user && t3lib_div::_GP($fe_user->formfield_status)=='login') {
			$event = new Tx_ExtbaseHijax_Event_Event('user-loggedIn', array('user'=>$fe_user->user));
			$this->hijaxEventDispatcher->notify($event);
		} elseif (!$fe_user->user && t3lib_div::_GP($fe_user->formfield_status)=='logout') {
			$event = new Tx_ExtbaseHijax_Event_Event('user-loggedOut');
			$this->hijaxEventDispatcher->notify($event);
		} elseif (!$fe_user->user && t3lib_div::_GP($fe_user->formfield_status)=='login') {
			$event = new Tx_ExtbaseHijax_Event_Event('user-loginFailure');
			$this->hijaxEventDispatcher->notify($event);
		}
	}
}

?>