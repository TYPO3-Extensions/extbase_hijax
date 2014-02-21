<?php
namespace EssentialDots\ExtbaseHijax\Lock;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Nikola Stojiljkovic <nikola.stojiljkovic@essentialdots.com>
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

class LockManager implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $existingLocksCount = array();

	/**
	 * @var array
	 */
	protected $lockKeyType = array();

	/**
	 * @var array
	 */
	protected $lockObjectsKeys = array();

	/**
	 * Lock the process
	 *
	 * @param \EssentialDots\ExtbaseHijax\Lock\Lock $lockObj
	 * @param $key                  String to identify the lock in the system
	 * @param bool $exclusive       Exclusive lock (shared if FALSE)
	 * @return bool                 Returns TRUE if the lock could be obtained, FALSE otherwise
	 */
	public function acquireLock(&$lockObj, $key, $exclusive = TRUE)	{
		try {
			if (!is_object($lockObj)) {
				/* @var $lockObj \EssentialDots\ExtbaseHijax\Lock\Lock */
				$lockObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Lock\\Lock', $key);
			}

			if (array_key_exists($key, $this->lockKeyType) && $this->lockKeyType[$key] !== $exclusive) {
				error_log('The same key cannot be used for shared and exclusive locks atm. Key: '.$key);
				return FALSE;
			}

			$this->lockKeyType[$key] = $exclusive;
			$this->lockObjectsKeys[spl_object_hash($lockObj)] = $key;

			if (array_key_exists($key, $this->existingLocksCount) && $this->existingLocksCount[$key] > 0) {
				$this->existingLocksCount[$key]++;
				return true;
			} else {
				$this->existingLocksCount[$key] = 1;
			}

			$success = FALSE;
			if (strlen($key)) {
				$success = $lockObj->acquire($exclusive);
				if ($success) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (\Exception $e) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking: Failed to acquire lock: '.$e->getMessage(), 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			$success = FALSE;	// If locking fails, return with FALSE and continue without locking
		}

		return $success;
	}

	/**
	 * Release the lock
	 *
	 * @param	\EssentialDots\ExtbaseHijax\Lock\Lock	Reference to a locking object
	 * @return	boolean		Returns TRUE on success, FALSE otherwise
	 * @see acquireLock()
	 */
	public function releaseLock(&$lockObj) {
		$success = FALSE;
		// If lock object is set and was acquired, release it:
		if (is_object($lockObj) && $lockObj instanceof \EssentialDots\ExtbaseHijax\Lock\Lock) {
			if (!array_key_exists(spl_object_hash($lockObj), $this->lockObjectsKeys)) {
				return FALSE;
			} else {
				$key = $this->lockObjectsKeys[spl_object_hash($lockObj)];
				$leftoverLocks = --$this->existingLocksCount[$key];
			}
			unset($this->lockObjectsKeys[spl_object_hash($lockObj)]);

			if ($leftoverLocks == 0 && $lockObj->getLockStatus()) {
				unset($this->lockKeyType[$key]);
				unset($this->existingLocksCount[$key]);

				$success = $lockObj->release();
				$lockObj->sysLog('Released lock');
				$lockObj = NULL;
			} elseif ($leftoverLocks == 0) {
				error_log('The lock created using LockManager was unlocked directly. Please avoid this!. Lock key: '.$key);
			}
		}
		return $success;
	}
}