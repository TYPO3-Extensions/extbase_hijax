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

class Tx_ExtbaseHijax_Service_AutoIDService implements t3lib_Singleton {
	
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $trackingCache;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->trackingCache = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_storage');
	}
	
	/**
	 * Clears cache of pages where an object with the given identifier is shown
	 * 
	 * @param string $objectIdentifier
	 */
	public function getAutoId($classIdentifier) {
		$exclusiveLock = null;
		$objectIdentifier = 'autoid-'.$classIdentifier;
		$exclusiveLockAcquired = $this->acquireLock($exclusiveLock, $objectIdentifier, TRUE);
		
		$autoId = 0;
		
		if ($exclusiveLockAcquired) {
			if ($this->trackingCache->has($objectIdentifier)) {
				$autoId = $this->trackingCache->get($objectIdentifier);
			}
			$autoId++;
			$this->trackingCache->set($objectIdentifier, $autoId);
				
			$this->releaseLock($exclusiveLock);
		}
		
		return $autoId;
	}	
	
	/**
	 * Lock the process
	 *
	 * @param	Tx_ExtbaseHijax_Lock_Lock	Reference to a locking object
	 * @param	string		String to identify the lock in the system
	 * @param	boolean		Exclusive lock (shared if FALSE)
	 * @return	boolean		Returns TRUE if the lock could be obtained, FALSE otherwise 
	 * @see releaseLock()
	 */
	protected function acquireLock(&$lockObj, $key, $exclusive = TRUE)	{
		try {
			if (!is_object($lockObj)) {
					/* @var $lockObj Tx_ExtbaseHijax_Lock_Lock */
				$lockObj = t3lib_div::makeInstance('Tx_ExtbaseHijax_Lock_Lock', $key);
			}
	
			$success = FALSE;
			if (strlen($key)) {
				$success = $lockObj->acquire($exclusive);
				if ($success) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (Exception $e) {
			t3lib_div::sysLog('Locking: Failed to acquire lock: '.$e->getMessage(), 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
			$success = FALSE;	// If locking fails, return with FALSE and continue without locking
		}
	
		return $success;
	}
	
	/**
	 * Release the lock
	 *
	 * @param	Tx_ExtbaseHijax_Lock_Lock	Reference to a locking object
	 * @return	boolean		Returns TRUE on success, FALSE otherwise
	 * @see acquireLock()
	 */
	protected function releaseLock(&$lockObj) {
		$success = FALSE;
			// If lock object is set and was acquired, release it:
		if (is_object($lockObj) && $lockObj instanceof Tx_ExtbaseHijax_Lock_Lock && $lockObj->getLockStatus()) {
			$success = $lockObj->release();
			$lockObj->sysLog('Released lock');
			$lockObj = NULL;
		}
		return $success;
	}	
}