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

class Tx_ExtbaseHijax_ViewHelpers_AjaxFunctionViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	
	/**
	 * @var Tx_ExtbaseHijax_Service_JSBuilder
	 */
	protected $jsBuilder;
	
	/**
	 * injectJSBuilder
	 *
	 * @param Tx_ExtbaseHijax_Service_JSBuilder $jsBuilder
	 * @return void
	 */
	public function injectJSBuilder(Tx_ExtbaseHijax_Service_JSBuilder $jsBuilder) {
		$this->jsBuilder = $jsBuilder;
	}
			
	/**
	 * @param string $action
	 * @param array $arguments
	 * @param string $controller
	 * @param string $extensionName
	 * @param string $pluginName
	 * @param string $format
	 * @param string $section
	 * 
	 * @return string
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extension = NULL, $plugin = NULL, $format = '', $section='footer') {
        return $this->jsBuilder->getAjaxFunction($action, $arguments, $controller, $extension, $plugin, $format, $section);
	}
}

?>