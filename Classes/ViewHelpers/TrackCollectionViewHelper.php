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

class Tx_ExtbaseHijax_ViewHelpers_TrackCollectionViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

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
	 * @param mixed $collection Object to use
	 * @param boolean $clearCacheOnAllHashesForCurrentPage Clear cache on all hashes for current page
	 * @return string the rendered string
	 */
	public function render($collection = NULL, $clearCacheOnAllHashesForCurrentPage = false) {
		foreach ($collection as $object) {
			if ($object && (get_class($object)=='Tx_Extbase_DomainObject_AbstractDomainObject' || is_subclass_of($object, 'Tx_Extbase_DomainObject_AbstractDomainObject'))) {
				if ($clearCacheOnAllHashesForCurrentPage) {
					$this->trackingManager->trackObjectOnPage($object, 'id');
				} else {
					$this->trackingManager->trackObjectOnPage($object, 'hash');
				}
			}
		}
		
		return $this->renderChildren();
	}
}

?>