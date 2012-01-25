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

/**
 * ViewHelper for tracking record display (as a helper for clearing the cache)
 * 
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_ExtbaseHijax_ViewHelpers_TrackRecordViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var Tx_ExtbaseHijax_Tracking_Manager
	 */
	protected $trackingManager;
	
	/**
	 * Injects the tracking manager
	 *
	 * @param Tx_ExtbaseHijax_Tracking_Manager $trackingManager
	 * @return void
	 */
	public function injectTrackingManager(Tx_ExtbaseHijax_Tracking_Manager $trackingManager) {
		$this->trackingManager = $trackingManager;
	}
		
	/**
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object Object to use
	 * @param boolean $clearCacheOnAllHashesForCurrentPage Clear cache on all hashes for current page
	 * @return string the rendered string
	 * @api
	 */
	public function render(Tx_Extbase_DomainObject_AbstractDomainObject $object = NULL, $clearCacheOnAllHashesForCurrentPage = false) {
		if ($clearCacheOnAllHashesForCurrentPage) {
			$this->trackingManager->trackObjectOnPage($object, 'id');
		} else {
			$this->trackingManager->trackObjectOnPage($object, 'hash');
		}
		
		return $this->renderChildren();
	}
}

?>
