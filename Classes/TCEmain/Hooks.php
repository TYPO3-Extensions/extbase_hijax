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

class Tx_ExtbaseHijax_TCEmain_Hooks implements t3lib_Singleton {
	
	/**
	 * @var Tx_ExtbaseHijax_Tracking_Manager
	 */
	protected $trackingManager;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->trackingManager = t3lib_div::makeInstance('Tx_ExtbaseHijax_Tracking_Manager');
	}

	/**
	 * Clear cache post processor.
	 *
	 * @param object $params parameter array
	 * @param object $pObj parent object
	 * @return void
	 */
	public function clearCachePostProc(&$params, &$pObj) {
		switch ($params['cacheCmd']) {
			case 'all':
				$this->trackingManager->flushTrackingInfo();
				break;
			default:
				break;
		}
	}	
	
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param	array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $id The records id (if any)
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	public function processDatamap_preProcessFieldArray($fieldArray, $table, $id, $pObj) {
		// not used atm
	}	
	
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param	string $status Operation status
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $id The records id (if any)
	 * @param	array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $pObj) {
		// not used atm
	}
	
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param	string $command Action to be performed
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $id The records id (if any)
	 * @param	string $value 
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */	
	public function processCmdmap_preProcess($command, $table, $id, $value, $pObj) {
		// not used atm
	}
	
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param	string $command Action to be performed
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $id The records id (if any)
	 * @param	string $value 
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	public function processCmdmap_postProcess($command, $table, $id, $value, $pObj) {
		$objectIdentifier = $this->trackingManager->getObjectIdentifierForRecord($table, $id);
		$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
	}
	
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param	string $status Operation status
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $rawId The records id (if any)
	 * @param	array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	 public function processDatamap_afterDatabaseOperations($status, $table, $rawId, $fieldArray, $pObj) {
		if (!t3lib_utility_Math::canBeInterpretedAsInteger($rawId)) {
			$rawId = $pObj->substNEWwithIDs[$rawId];
			$objectIdentifier = $this->trackingManager->getObjectIdentifierForRepository($table, $fieldArray['pid']);
			$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
		}
		if (t3lib_utility_Math::canBeInterpretedAsInteger($rawId)) {
			$objectIdentifier = $this->trackingManager->getObjectIdentifierForRecord($table, $rawId);
			$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
		}
	}
}