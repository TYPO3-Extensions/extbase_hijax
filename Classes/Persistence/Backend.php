<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_ExtbaseHijax_Persistence_Backend extends Tx_Extbase_Persistence_Backend {

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $_addedObjects = NULL;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $_removedObjects = NULL;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $_changedObjects = NULL;

	/**
	 * @param Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend $storageBackend
	 * @return void
	 */
	public function injectStorageBackend(Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * @var Tx_Extbase_SignalSlot_Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @param Tx_Extbase_SignalSlot_Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(Tx_Extbase_SignalSlot_Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * Inserts an object in the storage backend
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @return void
	 */
	protected function insertObject(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeInsertObjectHijax', array('object' => $object));

		parent::insertObject($object);

		if ($object->getUid() >= 1) {
			/*
			 * Check if update operation will be called for this object
			 * (depending on the properties)
			 * @see Tx_Extbase_Persistence_Backend::persistObject
			 */
			$dataMap = $this->dataMapper->getDataMap(get_class($object));
			$properties = $object->_getProperties();
			$row = array();
			foreach ($properties as $propertyName => $propertyValue) {
				if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) continue;
				$columnMap = $dataMap->getColumnMap($propertyName);
				if ($propertyValue instanceof Tx_Extbase_Persistence_ObjectStorage) {
					if ($object->_isNew() || $propertyValue->_isDirty()) {
						$row[$columnMap->getColumnName()] = true;
					}
				} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
					if ($object->_isDirty($propertyName)) {
						$row[$columnMap->getColumnName()] = true;
					}
					$queue[] = $propertyValue;
				} elseif ($object->_isNew() || $object->_isDirty($propertyName)) {
					$row[$columnMap->getColumnName()] = true;
				}
			}

			if (count($row)>0) {
				$objectHash = spl_object_hash($object);
				$this->pendingIsertObjects[$objectHash] = $object;
			} else {
				$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterInsertObjectHijax', array('object' => $object));
				$this->_addedObjects->attach($object);
			}
		}
	}

	/**
	 * Updates a given object in the storage
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be updated
	 * @param array $row Row to be stored
	 * @return bool
	 */
	protected function updateObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $row) {
		$objectHash = spl_object_hash($object);

		if (!$this->pendingIsertObjects[$objectHash]) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeUpdateObjectHijax', array('object' => $object));
		}

		$result = parent::updateObject($object, $row);

		if ($result === TRUE) {
			if (!$this->pendingIsertObjects[$objectHash]) {
				$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterUpdateObjectHijax', array('object' => $object));
				$this->_changedObjects->attach($object);
			} else {
				unset($this->pendingIsertObjects[$objectHash]);
				$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterInsertObjectHijax', array('object' => $object));
				$this->_addedObjects->attach($object);
			}
		}

		return $result;
	}

	/**
	 * Deletes an object
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be removed from the storage
	 * @param bool $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 */
	protected function removeObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeRemoveObjectHijax', array('object' => $object));

		// TODO: check if object is not already deleted
		parent::removeObject($object, $markAsDeleted);

		// TODO: check if object is removed indeed
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterRemoveObjectHijax', array('object' => $object));
		$this->_removedObjects->attach($object);
	}

	/**
	 * Deletes an object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be removed from the storage
	 * @param boolean $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 */
	protected function removeEntity(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeRemoveObjectHijax', array('object' => $object));

		// TODO: check if object is not already deleted
		parent::removeEntity($object, $markAsDeleted);

		// TODO: check if object is removed indeed
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterRemoveObjectHijax', array('object' => $object));
		$this->_removedObjects->attach($object);
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 */
	public function commit() {
		$this->_addedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->_removedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->_changedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');

		parent::commit();

		foreach ($this->_addedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterInsertCommitObjectHijax', array('object' => $object));
		}
		foreach ($this->_removedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterRemoveCommitObjectHijax', array('object' => $object));
		}
		foreach ($this->_changedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterUpdateCommitObjectHijax', array('object' => $object));
		}
		$this->_addedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->_removedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->_changedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
	}
}

?>