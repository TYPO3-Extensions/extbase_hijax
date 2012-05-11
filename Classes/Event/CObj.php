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
*  the Free Software Foundation; either version 2 of the License, or
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

class Tx_ExtbaseHijax_Event_CObj {
	
	/**
	 * @var tslib_cObj
	 */
	protected $cObj;
	
	/**
	 * @var array
	 */
	protected $data;
	
	/**
	 * @var string
	 */
	protected $table;
	
	/**
	 * Constructs a new Tx_ExtbaseHijax_Event_Listener.
	 *
	 * @param tslib_cObj		$cObj	 	An array of parameters
	 */
	public function __construct($cObj = null) {
		$this->cObj = $cObj;

		$reset = true;
		
		if ($this->cObj && $this->cObj->currentRecord) {
			list($table, $uid) = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
			if ($table=='tt_content' && $uid) {
				$this->data = $this->cObj->data;
				list($this->table) = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
				$reset = false;
			}
		} 
		
		if ($reset) {
			$this->data = array();
			$this->table = '';
			$this->cObj = t3lib_div::makeInstance('tslib_cObj');
			$this->cObj->start($this->data, $this->table);
		}
	}
	
	/**
	 * @return void
	 */
	public function reconstitute() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->start($this->data, $this->table);
	}
	
	/**
	 * @return the $cObj
	 */
	public function getCObj() {
		return $this->cObj;
	}

	/**
	 * @return the $data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @return the $table
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @param tslib_cObj $cObj
	 */
	public function setCObj($cObj) {
		$this->cObj = $cObj;
	}

	/**
	 * @param multitype: $data
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}
}