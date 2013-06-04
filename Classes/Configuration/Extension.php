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

class Tx_ExtbaseHijax_Configuration_Extension implements Tx_ExtbaseHijax_Configuration_ExtensionInterface, t3lib_Singleton {
	
	/**
	 * @var array
	 */
	protected $configuration;
	
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;	
	
	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;
		
	/**
	 * constructor
	 */
	public function __construct() {
		$this->configuration = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extbase_hijax'] ? unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extbase_hijax']) : array();
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->configurationManager = $this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface');
	}

	/**
	 * returns configurationvalue for the given key
	 *
	 * @param string $key
	 * @return string
	 */
	public function get($key) {
		return $this->configuration[$key];
	}
	
	/**
	 * @return boolean
	 */
	public function hasIncludedCSSJS() {
		return (boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['includedCSSJS'];
	}

	/**
	 * @param boolean $includedCSSJS
	 * @return void
	 */
	public function setIncludedCSSJS($includedCSSJS) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedCSSJS'] = $includedCSSJS;
	}
	
	/**
	 * @return boolean
	 */
	public function shouldIncludeEofe() {
		return ((boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['includeEofe'] || (boolean) $GLOBALS['TSFE']->tmpl->setup['config.']['extbase_hijax.']['includeEofe']);
	}

	/**
	 * @param boolean $includeEofe
	 * @return void
	 */
	public function setIncludeEofe($includeEofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includeEofe'] = $includeEofe;
	}	
	
	/**
	 * @return boolean
	 */
	public function hasIncludedEofe() {
		return (boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['includedEofe'];
	}

	/**
	 * @param boolean $includedEofe
	 * @return void
	 */
	public function setIncludedEofe($includedEofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedEofe'] = $includedEofe;
	}
	
	/**
	 * @return boolean
	 */
	public function shouldIncludeSofe() {
		return ((boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['includeSofe'] || (boolean) $GLOBALS['TSFE']->tmpl->setup['config.']['extbase_hijax.']['includeSofe']);
	}

	/**
	 * @param boolean $includeSofe
	 * @return void
	 */
	public function setIncludeSofe($includeSofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includeSofe'] = $includeSofe;
	}	
	
	/**
	 * @return boolean
	 */
	public function hasIncludedSofe() {
		return (boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['includedSofe'];
	}

	/**
	 * @param boolean $includedSofe
	 * @return void
	 */
	public function setIncludedSofe($includedSofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedSofe'] = $includedSofe;
	}	
	
	/**
	 * @return boolean
	 */
	public function hasAddedBodyClass() {
		return (boolean) $GLOBALS['TSFE']->config['extbase_hijax.']['addedBodyClass'];
	}

	/**
	 * @param boolean $includedSofe
	 * @return void
	 */
	public function setAddedBodyClass($addedBodyClass) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['addedBodyClass'] = $addedBodyClass;
	}
		
	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return $GLOBALS['TSFE']->baseUrl ? $GLOBALS['TSFE']->baseUrl : ( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . $GLOBALS['TSFE']->absRefPrefix ) ;
	}
	
	/**
	 * @return string
	 */
	public function getCacheInvalidationLevel() {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		
		return (string) ($frameworkConfiguration['settings']['cacheInvalidationLevel'] ? $frameworkConfiguration['settings']['cacheInvalidationLevel'] : 'noinvalidation');
	}

	/**
	 * @return integer
	 */
	public function getNextElementId() {
		return intval($GLOBALS['TSFE']->config['extbase_hijax.']['nextElementId']);
	}
	
	/**
	 * @param integer $nextElementId
	 * @return void
	 */
	public function setNextElementId($nextElementId) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['nextElementId'] = $nextElementId;
	}
}