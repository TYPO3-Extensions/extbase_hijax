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

class Tx_ExtbaseHijax_Tracking_Manager implements t3lib_Singleton {
	/**
	 * the page cache object, use this to save pages to the cache and to
	 * retrieve them again
	 *
	 * @var t3lib_cache_AbstractBackend
	 */
	protected $pageCache;
	
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $trackingCache;
	
	/**
	 * @var tslib_fe
	 */
	protected $fe;

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;
		
	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	protected $dataMapper;
	
	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;	
	
	/**
	 * @var Tx_ExtbaseHijax_Utility_Ajax_Dispatcher
	 */
	protected $ajaxDispatcher;
		
	/**
	 * Extension Configuration
	 *
	 * @var Tx_ExtbaseHijax_Configuration_ExtensionInterface
	 */
	protected $extensionConfiguration;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->fe = $GLOBALS['TSFE'];
		$this->trackingCache = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax');
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->dataMapper = $this->objectManager->get('Tx_Extbase_Persistence_Mapper_DataMapper');
		$this->pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		$this->configurationManager = $this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$this->ajaxDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Utility_Ajax_Dispatcher');
		$this->extensionConfiguration = $this->objectManager->get('Tx_ExtbaseHijax_Configuration_ExtensionInterface');
	}
	
	/**
	 * Clears cache of pages where objects are shown
	 * 
	 * @param array $objects
	 */
	public function clearPageCacheForObjects($objects) {
		if ($objects) {
			foreach ($objects as $object) {
					/* @var $object Tx_Extbase_DomainObject_AbstractDomainObject */
				$objectIdentifier = $this->getObjectIdentifierForObject($object);
				$this->clearPageCacheForObjectIdentifier($objectIdentifier);
			}
		}
		
		return;
	}

	/**
	 * Clears cache of pages where single object is shown
	 *
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object
	 */
	public function clearPageCacheForObject($object) {
		return $this->clearPageCacheForObjects(array($object));
	}
	
	/**
	 * Clears cache of pages where objects are shown
	 *
	 * @param array $objects
	 */
	public function clearPageCacheForObjectIdentifiers($objectIdentifiers) {
		if ($objectIdentifiers) {
			foreach ($objectIdentifiers as $objectIdentifier) {
				$this->clearPageCacheForObjectIdentifier($objectIdentifier);
			}
		}
	
		return;
	}	
	
	/**
	 * Clears cache of pages where an object with the given identifier is shown
	 * 
	 * @param string $objectIdentifier
	 */
	public function clearPageCacheForObjectIdentifier($objectIdentifier) {
			// TODO: Move this to different implementations of the Tracking Manager...
		
		switch ($this->extensionConfiguration->getCacheInvalidationLevel()) {
			case 'consistent':
				$sharedLock = null;
				$sharedLockAcquired = $this->acquireLock($sharedLock, $objectIdentifier, FALSE);
		
				if ($sharedLockAcquired) {
					if ($this->trackingCache->has($objectIdentifier)) {
						$exclusiveLock = null;
						$exclusiveLockAcquired = $this->acquireLock($exclusiveLock, $objectIdentifier.'-e', TRUE);
		
						if ($exclusiveLockAcquired) {
							$pageHashs = $this->trackingCache->get($objectIdentifier);
							if ($pageHashs && count($pageHashs)) {
								foreach ($pageHashs as $pageHash) {
									if (substr($pageHash, 0, 3) == 'id-') {
										$this->pageCache->flushByTag('pageId_' . substr($pageHash, 3));
									} elseif (substr($pageHash, 0, 5) == 'hash-') {
										$this->pageCache->remove(substr($pageHash, 5));
									}
								}
								$this->trackingCache->set($objectIdentifier, array());
							}
								
							$this->releaseLock($exclusiveLock);
						} else {
							$pageHashs = $this->trackingCache->get($objectIdentifier);
							if ($pageHashs && count($pageHashs)) {
								foreach ($pageHashs as $pageHash) {
									$this->pageCache->remove($pageHash);
								}
							}
						}
					}
						
					$this->releaseLock($sharedLock);
				} else {
					// Failed locking
					// should probably throw an exception here
				}
				break;
			default:
			case 'noinvalidation':
				break;
		}
	
		return;
	}	
	/**
	 * Tracks display of an object on a page
	 *
	 * @param mixed $object Repository/Object/table name
	 * @param mixed $hash Hash or page id (depending on the type) for which the object display will be associated
	 * @param string $type 'hash' (for only one hash) or 'id' (for complete page cache of a page, for all hash combinations)
	 * @return void
	 */
	public function trackRepositoryOnPage($object = NULL, $type = 'hash', $hash = false) {

		if ($object && !$this->ajaxDispatcher->getIsActive()) {
			if ($type) {
				switch ($type) {
					case 'id':
						if (!$hash) {
							$hash = intval($this->fe->id);
						}
						$pageHash = 'id-'.$hash;
						break;
					case 'hash':
					default:
						if (!$hash) {
							$hash = $this->fe->getHash();
						}
						$pageHash = 'hash-'.$hash;
						break;
				}		

				if ($object instanceof Tx_Extbase_Persistence_RepositoryInterface) {
					$objectType = preg_replace(array('/_Repository_(?!.*_Repository_)/', '/Repository$/'), array('_Model_', ''), get_class($object));
					$tableName = $this->dataMapper->getDataMap($objectType)->getTableName();
				} elseif ($object instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
					$objectType = get_class($object);
					$tableName = $this->dataMapper->getDataMap($objectType)->getTableName();
				} else {
					$tableName = (string) $object;
				}
				
				$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
				
				if ($tableName && $frameworkConfiguration['persistence']['storagePid']) {
					$storagePids = t3lib_div::intExplode(',', $frameworkConfiguration['persistence']['storagePid'], true);
					
					foreach ($storagePids as $storagePid) {
						$objectIdentifier = $this->getObjectIdentifierForRepository($tableName, $storagePid);
						
						$sharedLock = null;
						$sharedLockAcquired = $this->acquireLock($sharedLock, $objectIdentifier, FALSE);
						
						if ($sharedLockAcquired) {
							if ($this->trackingCache->has($objectIdentifier)) {
								$pageHashs = $this->trackingCache->get($objectIdentifier);
								if (!in_array($pageHash, $pageHashs)) {
									$exclusiveLock = null;
									$exclusiveLockAcquired = $this->acquireLock($exclusiveLock, $objectIdentifier.'-e', TRUE);
									
									if ($exclusiveLockAcquired) {
										$pageHashs = $this->trackingCache->get($objectIdentifier);
										if (!in_array($pageHash, $pageHashs)) {
											$pageHashs[] = $pageHash;
											$this->trackingCache->set($objectIdentifier, array_unique($pageHashs));
										}
										
										$this->releaseLock($exclusiveLock);
									}
								}
							} else {
								$this->trackingCache->set($objectIdentifier, array($pageHash));
							}
							
							$this->releaseLock($sharedLock);
						}	
					}		
				}
		
			}
		}
	}
	
	/**
	 * Tracks display of an object on a page
	 * 
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object Object to use
	 * @param mixed $hash Hash or page id (depending on the type) for which the object display will be associated
	 * @param string $type 'hash' (for only one hash) or 'id' (for complete page cache of a page, for all hash combinations)
	 * @return void
	 */
	public function trackObjectOnPage(Tx_Extbase_DomainObject_AbstractDomainObject $object = NULL, $type = 'hash', $hash = false) {
		if ($object && !$this->ajaxDispatcher->getIsActive()) {
			if ($type) {
				switch ($type) {
					case 'id':
						if (!$hash) {
							$hash = intval($this->fe->id);
						}
						$pageHash = 'id-'.$hash;
						break;
					case 'hash':
					default:
						if (!$hash) {
							$hash = $this->fe->getHash();
						}
						$pageHash = 'hash-'.$hash;
						break;
				}
				
				$objectIdentifier = $this->getObjectIdentifierForObject($object);
				
				$sharedLock = null;
				$sharedLockAcquired = $this->acquireLock($sharedLock, $objectIdentifier, FALSE);
				
				if ($sharedLockAcquired) {
					if ($this->trackingCache->has($objectIdentifier)) {
						$pageHashs = $this->trackingCache->get($objectIdentifier);
						if (!in_array($pageHash, $pageHashs)) {
							$exclusiveLock = null;
							$exclusiveLockAcquired = $this->acquireLock($exclusiveLock, $objectIdentifier.'-e', TRUE);
							
							if ($exclusiveLockAcquired) {
								$pageHashs = $this->trackingCache->get($objectIdentifier);
								if (!in_array($pageHash, $pageHashs)) {
									$pageHashs[] = $pageHash;
									$this->trackingCache->set($objectIdentifier, array_unique($pageHashs));
								}
								
								$this->releaseLock($exclusiveLock);
							}
						}
					} else {
						$this->trackingCache->set($objectIdentifier, array($pageHash));
					}
					
					$this->releaseLock($sharedLock);
				}
			}
		}
	
		return;
	}	
	
	/**
	 * Returns the identifier for an object
	 * 
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object
	 * @return string
	 */
	public function getObjectIdentifierForObject(Tx_Extbase_DomainObject_AbstractDomainObject $object = NULL) {
		$objectIdentifier = false;
		
		if ($object) {
			$dataMap = $this->dataMapper->getDataMap(get_class($object));
			$tableName = $dataMap->getTableName();
			$objectIdentifier = 'r-'.$tableName.'_'.$object->getUid();
		}
		
		return $objectIdentifier;
	}
	
	/**
	 * Returns the identifier for a record
	 *
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object
	 * @param int $id
	 * @return string
	 */
	public function getObjectIdentifierForRecord($table, $id) {
		$objectIdentifier = false;
	
		if ($id) {
			$objectIdentifier = 'r-'.$table.'_'.$id;
		}
	
		return $objectIdentifier;
	}	
	
	/**
	 * Returns the identifier for a record
	 *
	 * @param Tx_Extbase_DomainObject_AbstractDomainObject $object
	 * @param int $pid
	 * @return string
	 */
	public function getObjectIdentifierForRepository($table, $pid) {
		$objectIdentifier = false;
	
		if ($pid) {
			$objectIdentifier = 's-'.$table.'-'.$pid;
		}
	
		return $objectIdentifier;
	}	
	
	/**
	 * Flush the complete tracking info 
	 */
	public function flushTrackingInfo() {
		$this->trackingCache->flush();		
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