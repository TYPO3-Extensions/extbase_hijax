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

interface Tx_ExtbaseHijax_Configuration_ExtensionInterface {
	
	/**
	 * @return boolean
	 */
	public function hasIncludedCSSJS();

	/**
	 * @param boolean $includedCSSJS
	 * @return void
	 */
	public function setIncludedCSSJS($includedCSSJS);
	
	/**
	 * @return boolean
	 */
	public function shouldIncludeEofe();

	/**
	 * @param boolean $includeEofe
	 * @return void
	 */
	public function setIncludeEofe($includeEofe);
	
	/**
	 * @return boolean
	 */
	public function hasIncludedEofe();

	/**
	 * @param boolean $includedEofe
	 * @return void
	 */
	public function setIncludedEofe($includedEofe);	
	
	/**
	 * @return boolean
	 */
	public function shouldIncludeSofe();

	/**
	 * @param boolean $includeSofe
	 * @return void
	 */
	public function setIncludeSofe($includeSofe);
	
	/**
	 * @return boolean
	 */
	public function hasIncludedSofe();

	/**
	 * @param boolean $includedSofe
	 * @return void
	 */
	public function setIncludedSofe($includedSofe);		
	
	/**
	 * @return boolean
	 */
	public function hasAddedBodyClass();

	/**
	 * @param boolean $addedBodyClass
	 * @return void
	 */
	public function setAddedBodyClass($addedBodyClass);		
	
	/**
	 * @return string
	 */
	public function getBaseUrl();
}