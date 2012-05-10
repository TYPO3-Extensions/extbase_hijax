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

class Tx_ExtbaseHijax_Utility_Extension extends Tx_Extbase_Utility_Extension {
	/**
	 * Register an Extbase Hijax actions
	 * FOR USE IN ext_localconf.php FILES
	 *
	 * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param string $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
	 * @param string $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
	 * @return void
	 */
	static public function registerHijaxPlugin($extensionKey, array $controllerActions, array $nonCacheableControllerActions = array()) {
		if (empty($extensionKey)) {
			throw new InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionName = t3lib_div::underscoredToUpperCamelCase($extensionKey);
		foreach ($controllerActions as $controllerName => $actionsList) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName] = array('actions' => t3lib_div::trimExplode(',', $actionsList));
			if (!empty($nonCacheableControllerActions[$controllerName])) {
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName]['nonCacheableActions'] = t3lib_div::trimExplode(',', $nonCacheableControllerActions[$controllerName]);
			}
		}		
	}

	/**
	 * @param string $extensionName
	 * @param string $controllerName
	 * @param string $actionName
	 * @return boolean
	 */
	static public function isAllowedHijaxAction($extensionName, $controllerName, $actionName) {
		if (
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax'] && 
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName] &&
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName] &&
				in_array($actionName, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName]['actions'])
		) {
			return true;
		} else {
			return false;
		}
	}
}

?>