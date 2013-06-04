<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class Tx_ExtbaseHijax_HTMLConverter_ConverterFactory implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $converterClassNames = array();

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
	}

	/**
	 * @param $format
	 * @return Tx_ExtbaseHijax_HTMLConverter_AbstractConverter
	 */
	public function getConverter($format) {
		$converter = NULL;
		if ($this->converterClassNames[$format]) {
			$converter = $this->objectManager->get($this->converterClassNames[$format]);
		} elseif (class_exists('Tx_ExtbaseHijax_HTMLConverter_'.strtoupper($format).'Converter')) {
			$converter = $this->objectManager->get('Tx_ExtbaseHijax_HTMLConverter_'.strtoupper($format).'Converter');
		} elseif (strpos($format, '.html') !== false) {
			$converter = $this->getConverter(str_replace('.html', '', $format));
		} else {
			$converter = $this->objectManager->get('Tx_ExtbaseHijax_HTMLConverter_NullConverter');
		}

		return $converter;
	}

	/**
	 * @param string $format
	 * @param string $converterClassName
	 */
	public function registerConverter($format, $converterClassName) {
		$this->converterClassNames[$format] = $converterClassName;
	}
}