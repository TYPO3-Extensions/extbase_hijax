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

class AjaxFunctionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {
	
	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\JSBuilder
	 */
	protected $jsBuilder;
	
	/**
	 * injectJSBuilder
	 *
	 * @param \EssentialDots\ExtbaseHijax\Service\JSBuilder $jsBuilder
	 * @return void
	 */
	public function injectJSBuilder(\EssentialDots\ExtbaseHijax\Service\JSBuilder $jsBuilder) {
		$this->jsBuilder = $jsBuilder;
	}

	/**
	 * @param null $action
	 * @param array $arguments
	 * @param null $controller
	 * @param null $extension
	 * @param null $plugin
	 * @param string $format
	 * @param string $section
	 * @return string
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extension = NULL, $plugin = NULL, $format = '', $section='footer') {
        return $this->jsBuilder->getAjaxFunction($action, $arguments, $controller, $extension, $plugin, $format, $section);
	}
}

?>