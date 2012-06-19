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

class Tx_ExtbaseHijax_ViewHelpers_ScriptViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper {

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
	}

	/**
	 * @var Tx_ExtbaseHijax_Utility_Ajax_Dispatcher
	 */
	protected $ajaxDispatcher;
	
	/**
	 * Injects the event dispatcher
	 *
	 * @param Tx_ExtbaseHijax_Utility_Ajax_Dispatcher $ajaxDispatcher
	 * @return void
	 */
	public function injectAjaxDispatcher(Tx_ExtbaseHijax_Utility_Ajax_Dispatcher $ajaxDispatcher) {
		$this->ajaxDispatcher = $ajaxDispatcher;
	}	
	
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
	 * Returns TRUE if what we are outputting may be cached
	 *
	 * @return boolean
	 */
	protected function isCached() {
		$userObjType = $this->configurationManager->getContentObject()->getUserObjectType();
		return ($userObjType !== tslib_cObj::OBJECTTYPE_USER_INT);
	}
	
	/**
	 * @param string $src
	 * @param string $type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @param string $section
	 * @param boolean $preventMarkupUpdateOnAjaxLoad
	 * @param boolean $moveToExternalFile
	 * 
     * @return string
	 */
	public function render($src="", $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE, $section = 'footer', $preventMarkupUpdateOnAjaxLoad = false, $moveToExternalFile = false) {
        $content = $this->renderChildren();
        
        if ($this->ajaxDispatcher->getIsActive()) {
        	if ($preventMarkupUpdateOnAjaxLoad) {
        		$this->ajaxDispatcher->setPreventMarkupUpdateOnAjaxLoad(true);
        	}
        		// need to just echo the code in ajax call
        	if (!$src) {
        		return t3lib_div::wrapJS($content);
        	} else {
        		return '<script type="'.htmlspecialchars($type).'" src="'.htmlspecialchars($src).'"></script>';
        	}
        } else {
	        if ($this->isCached()) {
	        	
	        	if (!$src && $moveToExternalFile) {
	        		$src = 'typo3temp/extbase_hijax/'.md5($content).'.js';
	        		t3lib_div::writeFileToTypo3tempDir(PATH_site.$src, $content);
	        	}
	        	
	        	if (!$src) {
	        		if ($section=='footer') {
	        			$this->pageRenderer->addJsFooterInlineCode(md5($content), $content, $compress, $forceOnTop);
	        		} else {
	        			$this->pageRenderer->addJsInlineCode(md5($content), $content, $compress, $forceOnTop);
	        		}
	        	} else {
	        		if ($section=='footer') {
		        		$this->pageRenderer->addJsFooterFile($src, $type, $compress, $forceOnTop, $allWrap, $excludeFromConcatenation);
	        		} else {
	        			$this->pageRenderer->addJsFile($src, $type, $compress, $forceOnTop, $allWrap, $excludeFromConcatenation);
	        		}
	        	}
	        } else {
	        		// additionalFooterData not possible in USER_INT
	        	if (!$src) {
	        		$GLOBALS['TSFE']->additionalHeaderData[md5($content)] = t3lib_div::wrapJS($content);
	        	} else {
	        		$GLOBALS['TSFE']->additionalHeaderData[md5($src)] = '<script type="'.htmlspecialchars($type).'" src="'.htmlspecialchars($src).'"></script>';
	        	}
	        }
        }
	}
}
?>
