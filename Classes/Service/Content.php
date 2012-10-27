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

class Tx_ExtbaseHijax_Service_Content implements t3lib_Singleton {
	
	/**
	 * @var string
	 */
	protected $absRefPrefix;
	
	/**
	 * @var boolean
	 */
	protected $executeExtbasePlugins = TRUE;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Listener
	 */
	protected $currentListener;

	/**
	 * @return the $executeExtbasePlugins
	 */
	public function getExecuteExtbasePlugins() {
		return $this->executeExtbasePlugins;
	}

	/**
	 * @param boolean $executeExtbasePlugins
	 */
	public function setExecuteExtbasePlugins($executeExtbasePlugins) {
		$this->executeExtbasePlugins = $executeExtbasePlugins;
	}

	/**
	 * @return the $currentListener
	 */
	public function getCurrentListener() {
		return $this->currentListener;
	}

	/**
	 * @param Tx_ExtbaseHijax_Event_Listener $currentListener
	 */
	public function setCurrentListener($currentListener) {
		$this->currentListener = $currentListener;
	}

	/**
	 * @param string $table
	 * @param int $uid
	 * 
	 * @return Tx_ExtbaseHijax_Event_Listener
	 */
	public function generateListenerCacheForContentElement($table, $uid) {
			/* @var $tslib_cObj tslib_cObj */
		$tslib_cObj = t3lib_div::makeInstance('tslib_cObj');
			// TODO: implement language overlay functions
		$data = t3lib_BEfunc::getRecord($table, $uid);
		if ($data) {
				// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$tslib_cObj->start($data, $table);
			$dummyContent = $tslib_cObj->RECORDS(array(
					'source' => $uid,
					'tables' => $table
			));
			$this->processIntScripts($dummyContent);
				// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = null;
		
		return $listener;
	}
	
	/**
	 * 
	 * @param string $loadContentFromTypoScript
	 * @param string $eventsToListen
	 * @param boolean $cached
	 *
	 * @return Tx_ExtbaseHijax_Event_Listener
	 */
	public function generateListenerCacheForHijaxPi1($loadContentFromTypoScript, $eventsToListen, $cached) {
		/* @var $tslib_cObj tslib_cObj */
		$tslib_cObj = t3lib_div::makeInstance('tslib_cObj');
		
		if ($loadContentFromTypoScript) {
			// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$dummyContent = $tslib_cObj->USER(array(
					'extensionName' => 'ExtbaseHijax',
					'pluginName' => 'Pi1',
					'userFunc' => 'Tx_Extbase_Core_Bootstrap->run',
					'switchableControllerActions.' => array (
						'ContentElement.' => array ('0' => $cached ? 'user' : 'userInt')
					),
					'settings.' => array (
						'listenOnEvents' => implode(',', $eventsToListen),
						'loadContentFromTypoScript' => $loadContentFromTypoScript
					)
			));
			$this->processIntScripts($dummyContent);
			// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = null;
	
		return $listener;
	}

	/**
	 *
	 * @param string $loadContentFromTypoScript
	 * @param string $eventsToListen
	 * @param boolean $cached
	 *
	 * @return Tx_ExtbaseHijax_Event_Listener
	 */
	public function generateListenerCacheForTypoScriptFallback($fallbackTypoScriptConfiguration) {

		if ($fallbackTypoScriptConfiguration) {

			// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$dummyContent = $this->renderTypoScriptPath($fallbackTypoScriptConfiguration);
			$this->processIntScripts($dummyContent);
			// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = null;

		return $listener;
	}

	/**
	 * Processes INT scripts
	 *
	 * @param string $content
	 */
	public function processIntScripts(&$content) {
		$GLOBALS['TSFE']->content = $content;
		if (!$GLOBALS['TSFE']->config['INTincScript']) {
			$GLOBALS['TSFE']->config['INTincScript'] = array();
		}
		$GLOBALS['TSFE']->INTincScript();
		$content = $GLOBALS['TSFE']->content;
	}
	
	/**
	 * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
	 *
	 * @param string $content
	 * @param string $absRefPrefix
	 * @return	void
	 */
	public function processAbsRefPrefix(&$content, $absRefPrefix)	{
		if ($absRefPrefix)	{
			$this->absRefPrefix = $absRefPrefix;
			$this->absRefPrefixCallbackAttribute = "href";
			$content = preg_replace_callback('/\shref="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);
			$this->absRefPrefixCallbackAttribute = "src";
			$content = preg_replace_callback('/\ssrc="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);
			$this->absRefPrefixCallbackAttribute = "action";
			$content = preg_replace_callback('/\saction="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);
		}
	}
	
	/**
	 * @param array $match
	 * @return string
	 */
	protected function processAbsRefPrefixCallback($match) {
	
		$url = $match['url'];
		$urlInfo = parse_url($url);
		if (!$urlInfo['scheme']) {
			if (substr($url, 0, strlen($this->absRefPrefix))==$this->absRefPrefix) {
				// don't change the URL
				// it already starts with absRefPrefix
				return $match[0];
			} else {
				return " {$this->absRefPrefixCallbackAttribute}=\"{$this->absRefPrefix}{$url}\"";
			}
		} else {
			// don't change the URL
			// it has scheme so we assume it's full URL
			return $match[0];
		}
	}

	/**
	 * @param string $typoscriptObjectPath
	 * @throws Exception
	 */
	protected function renderTypoScriptPath($typoscriptObjectPath) {
		/* @var $tslib_cObj tslib_cObj */
		$tslib_cObj = t3lib_div::makeInstance('tslib_cObj');
		$pathSegments = t3lib_div::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				throw new Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist' , 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		return $tslib_cObj->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.']);
	}

	/**
	 * @param string $typoscriptObjectPath
	 * @throws Exception
	 */
	public function isAllowedTypoScriptPath($typoscriptObjectPath) {
		/* @var $tslib_cObj tslib_cObj */
		$tslib_cObj = t3lib_div::makeInstance('tslib_cObj');
		$pathSegments = t3lib_div::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				throw new Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist' , 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		return $setup[$lastSegment . '.']['enableHijax'];
	}
}