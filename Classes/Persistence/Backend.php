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
	 * @param Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend $storageBackend
	 * @return void
	 */
	public function injectStorageBackend(Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend $storageBackend) {
		$this->storageBackend = $storageBackend;
	}
	
	/**
	 * Inserts an object in the storage backend
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @return void
	 */
	protected function insertObject(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeInsertObject', array('object' => $object));
	
		parent::insertObject($object);
	
		if ($object->getUid() >= 1) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterInsertObject', array('object' => $object));
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
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeUpdateObject', array('object' => $object));
	
		$result = parent::updateObject($object, $row);
	
		if ($result === TRUE) {
			$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterUpdateObject', array('object' => $object));
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
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'beforeRemoveObject', array('object' => $object));
	
		// TODO: check if object is not already deleted
	
		parent::removeObject($object, $markAsDeleted);
	
		// TODO: check if object is removed indeed
	
		$this->signalSlotDispatcher->dispatch('Tx_Extbase_Persistence_Backend', 'afterRemoveObject', array('object' => $object));
	}
}

?>