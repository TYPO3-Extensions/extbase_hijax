<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class ScriptViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @var \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher
	 */
	protected $ajaxDispatcher;
	
	/**
	 * Injects the event dispatcher
	 *
	 * @param \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher $ajaxDispatcher
	 * @return void
	 */
	public function injectAjaxDispatcher(\EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher $ajaxDispatcher) {
		$this->ajaxDispatcher = $ajaxDispatcher;
	}	
	
	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;
	
	/**
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
	 */
	public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}	
	
	/**
	 * Returns TRUE if what we are outputting may be cached
	 *
	 * @return boolean
	 */
	protected function isCached() {
		$userObjType = $this->configurationManager->getContentObject()->getUserObjectType();
		return ($userObjType !== \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER_INT);
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
	 * @param boolean $noCache
	 * 
     * @return string
	 */
	public function render($src="", $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE, $section = 'footer', $preventMarkupUpdateOnAjaxLoad = false, $moveToExternalFile = false, $noCache = false) {
        $content = $this->renderChildren();
        
        if ($this->ajaxDispatcher->getIsActive()) {
        	if ($preventMarkupUpdateOnAjaxLoad) {
        		$this->ajaxDispatcher->setPreventMarkupUpdateOnAjaxLoad(true);
        	}
        		// need to just echo the code in ajax call
        	if (!$src) {
        		return \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
        	} else {
        		return '<script type="'.htmlspecialchars($type).'" src="'.htmlspecialchars($src).'"></script>';
        	}
        } else {
	        if (!$noCache && $this->isCached()) {
	        	
	        	if (!$src && $moveToExternalFile) {
	        		$src = 'typo3temp'.DIRECTORY_SEPARATOR.'extbase_hijax'.DIRECTORY_SEPARATOR.md5($content).'.js';
	        		\TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir(PATH_site.$src, $content);
	        		
	        		if ($GLOBALS['TSFE']) {
	        			if ($GLOBALS['TSFE']->baseUrl) {
	        				$src = $GLOBALS['TSFE']->baseUrl . $src;
	        			} elseif ($GLOBALS['TSFE']->absRefPrefix) {
	        				$src = $GLOBALS['TSFE']->absRefPrefix . $src;
	        			}
	        		}
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
	        		$GLOBALS['TSFE']->additionalHeaderData[md5($content)] = \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
	        	} else {
	        		$GLOBALS['TSFE']->additionalHeaderData[md5($src)] = '<script type="'.htmlspecialchars($type).'" src="'.htmlspecialchars($src).'"></script>';
	        	}
	        }
        }

		return '';
	}
}
?>
